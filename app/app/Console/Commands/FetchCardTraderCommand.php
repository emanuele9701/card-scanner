<?php

namespace App\Console\Commands;

use App\Models\TCGCard;
use App\Models\TCGCardPrice;
use App\Models\TCGCardPriceHistory;
use App\Models\TCGSet;
use App\Models\TcgExternalProvidersGame;
use App\Models\TcgExternalProvidersMapping;
use App\Models\TcgExternalProvidersSet;
use App\Services\CardTrader\CardTraderClient;
use App\Services\CardTrader\CardTraderParser;
use Illuminate\Console\Command;

class FetchCardTraderCommand extends Command
{
    protected $signature = 'app:fetch-cardtrader';
    protected $description = 'Sincronizza e mappa i prezzi delle carte usando le API di CardTrader';

    public function handle(CardTraderClient $client, CardTraderParser $parser)
    {
        // Impostiamo a 1GB come hai suggerito per gestire json massicci
        ini_set('memory_limit', '1G');

        $this->info("Recupero Categorie da CardTrader...");
        $categoriesData = $client->get('/categories');
        $categories = $parser->parseCategories($categoriesData);
        
        $singlesCategoryIds = [];
        foreach ($categories as $cat) {
            if (stripos($cat->name, 'singles') !== false && stripos(strtolower($cat->name), 'booster') === false) {
                $singlesCategoryIds[] = $cat->id;
            }
        }
        $this->comment("  Trovate " . count($singlesCategoryIds) . " categorie contenenti 'singles' (IDs: " . implode(', ', $singlesCategoryIds) . ")");

        $this->info("Recupero Games...");
        $gamesData = $client->get('/games');
        $games = $parser->parseGames($gamesData);
        $gamesInsert = [];
        foreach ($games as $gameDto) {
            $gamesInsert[] = [
                'provider' => 'cardtrader',
                'external_id' => $gameDto->id,
                'name' => $gameDto->displayName,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        // Inserimento o aggiornamento in blocco (massivo)
        TcgExternalProvidersGame::upsert(
            $gamesInsert,
            ['provider', 'external_id'], // Chiavi uniche per il matching
            ['name', 'updated_at']       // Campi da aggiornare se esiste
        );
        $this->comment("  Aggiornati " . count($gamesInsert) . " giochi nel database.");

        $this->info("Recupero Expansions...");
        $expansionsData = $client->get('/expansions');
        $expansions = $parser->parseExpansions($expansionsData);
        
        $gamesMap = TcgExternalProvidersGame::where('provider', 'cardtrader')->pluck('id', 'external_id')->toArray();
        $setsInsert = [];
        
        foreach ($expansions as $expDto) {
            if (!isset($gamesMap[$expDto->gameId])) continue;

           
            
            $setsInsert[] = [
                'external_game_id' => $gamesMap[$expDto->gameId],
                'external_id' => $expDto->id,
                'name' => $expDto->name,
                'abbreviation' => $expDto->code,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Upsert a blocchi di 1000 per evitare query troppo pesanti
        foreach (array_chunk($setsInsert, 1000) as $chunk) {
            TcgExternalProvidersSet::upsert(
                $chunk,
                ['external_game_id', 'external_id'],
                ['name', 'abbreviation', 'updated_at']
            );
        }
        $this->comment("  Aggiornate " . count($setsInsert) . " espansioni nel database.");

        $this->info("Inizio mappatura e fetch prezzi per i Set locali...");
        $tcgSets = TCGSet::whereNotNull('abbreviation_official')->get();
        
        foreach ($tcgSets as $tcgSet) {
            
            $abbr = strtoupper($tcgSet->abbreviation_official);
            // Match esatto o case-insensitive sull'abbreviazione
            $mappedSet = TcgExternalProvidersSet::where('abbreviation', 'LIKE', $abbr)->first();
            if (!$mappedSet) {
                $this->warn("Set '{$tcgSet->name}' (Abbr: {$abbr}) non trovato su CardTrader. Salto...");
                continue;
            }
            $this->info("Set associato: {$tcgSet->name} <-> CardTrader {$mappedSet->name}");
            
            // Cerchiamo anche le sotto-espansioni (es. per "asc" troviamo "e-asc", "p-asc")
            // Queste contengono le stesse carte in varianti diverse (Reverse Holo, Energy ecc.)
            // ma con blueprint_id diversi che dobbiamo mappare alla stessa carta locale.
            $subExpansions = TcgExternalProvidersSet::where('abbreviation', 'LIKE', '%-' . strtolower($abbr))
                ->get();
            
            // Scarichiamo i blueprints per l'espansione principale
            $this->info("  Scarico blueprints...");
            $blueprintsData = $client->get("/blueprints/export", ['expansion_id' => $mappedSet->external_id]);
            $blueprints = $parser->parseBlueprints($blueprintsData);
            $this->comment("    Trovati " . count($blueprints) . " blueprints dall'espansione principale.");

            // Scarica blueprints anche dalle sotto-espansioni e uniscili
            if ($subExpansions->isNotEmpty()) {
                $subNames = $subExpansions->pluck('name')->implode(', ');
                $this->comment("    Trovate " . $subExpansions->count() . " sotto-espansioni: {$subNames}");
                
                foreach ($subExpansions as $subExp) {
                    $subBlueprintsData = $client->get("/blueprints/export", ['expansion_id' => $subExp->external_id]);
                    $subBlueprints = $parser->parseBlueprints($subBlueprintsData);
                    $this->comment("      -> {$subExp->name} ({$subExp->abbreviation}): " . count($subBlueprints) . " blueprints");
                    $blueprints = array_merge($blueprints, $subBlueprints);
                }
            }
            
            $this->comment("    Totale blueprints (principale + sotto-espansioni): " . count($blueprints));

            // Filtro solo 'singles'
            $filteredBlueprints = array_filter($blueprints, function($bp) use ($singlesCategoryIds) {
                return in_array($bp->categoryId, $singlesCategoryIds);
            });
            
            $this->comment("    Filtrati " . count($filteredBlueprints) . " blueprints di categoria 'singles'.");

            $tcgCards = TCGCard::where('set_id', $tcgSet->id)->get()->keyBy('dexId');
            
            $this->comment("    Carte locali nel database per questo set(#{$tcgSet->id}) {$tcgSet->name}: " . count($tcgCards));
            
            $blueprintToCardMap = [];
            $noCollectorNumberCount = 0;
            $notInLocalDbCount = 0;

            foreach ($filteredBlueprints as $bp) {
                if (empty($bp->collectorNumber)) {
                    $noCollectorNumberCount++;
                    continue;
                }
                // Confronto come stringa per preservare gli zeri iniziali (es. "083")
                $collectorNum = $bp->collectorNumber;

                $card = null;
                if (isset($tcgCards[$collectorNum])) {
                    $card = $tcgCards[$collectorNum];
                } else {
                    $card = $tcgCards->first(function($c) use ($collectorNum) {
                        return ltrim((string)$c->dexId, '0') === ltrim((string)$collectorNum, '0');
                    });
                }

                if ($card) {
                    // Previene violazioni di vincoli univoci: se questo external_id era 
                    // assegnato per errore a un'altra carta, lo scolleghiamo.
                    TcgExternalProvidersMapping::where('provider', 'cardtrader')
                        ->where('external_id', $bp->id)
                        ->where('card_id', '!=', $card->id)
                        ->delete();

                    $mapping = TcgExternalProvidersMapping::firstOrCreate([
                        'card_id' => $card->id,
                        'provider' => 'cardtrader'
                    ], [
                        'external_id' => $bp->id
                    ]);
                    
                    if ($mapping->external_id != $bp->id) {
                        $mapping->update(['external_id' => $bp->id]);
                    }

                    $blueprintToCardMap[$bp->id] = $card->id;
                } else {
                    $notInLocalDbCount++;
                }
            }

            $this->comment("    Mappatura completata: " . count($blueprintToCardMap) . " carte mappate con successo.");
            if ($noCollectorNumberCount > 0) {
                $this->warn("      -> {$noCollectorNumberCount} blueprints ignorati perché privi di collectorNumber.");
            }
            if ($notInLocalDbCount > 0) {
                $this->comment("      -> {$notInLocalDbCount} blueprints non mappati perché non presenti nel DB locale.");
            }
            
            if (empty($blueprintToCardMap)) {
                $this->info("  Nessuna carta mappata per questo set.");
                continue;
            }

            $this->info("  Scarico i prezzi dal marketplace per l'intera espansione...");
            
            // Scarica i prodotti dall'espansione principale
            $marketplaceData = $client->get('/marketplace/products', ['expansion_id' => $mappedSet->external_id]);
            $allProducts = is_array($marketplaceData) ? $marketplaceData : [];
            
            // Scarica anche i prodotti dalle sotto-espansioni e uniscili
            if ($subExpansions->isNotEmpty()) {
                foreach ($subExpansions as $subExp) {
                    $subMarketData = $client->get('/marketplace/products', ['expansion_id' => $subExp->external_id]);
                    if (is_array($subMarketData)) {
                        foreach ($subMarketData as $bpId => $listings) {
                            if (isset($allProducts[$bpId])) {
                                // Blueprint già presente: merge delle offerte
                                $allProducts[$bpId] = array_merge($allProducts[$bpId], $listings);
                            } else {
                                $allProducts[$bpId] = $listings;
                            }
                        }
                    }
                }
            }
            
            if (empty($allProducts)) {
                $this->warn("    Nessun prodotto trovato sul marketplace per questa espansione.");
                continue;
            }

            // La risposta è un oggetto { "blueprint_id": [ ...listings ], "blueprint_id": [ ...listings ] }
            // Raggruppiamo per Blueprint -> e per Hash di Attributi
            $groupedByBlueprint = [];

            foreach ($allProducts as $blueprintIdKey => $listings) {
                $bId = (int) $blueprintIdKey;
                
                // Ignoriamo blueprint che non appartengono alle carte mappate
                if (!isset($blueprintToCardMap[$bId])) continue;

                foreach ($listings as $listing) {
                    $priceCents = $listing['price_cents'] ?? ($listing['price']['cents'] ?? 0);
                    if ($priceCents <= 0) continue;

                    $val = $priceCents / 100;
                    $props = $listing['properties_hash'] ?? [];
                    
                    $condition = $this->mapCondition($props['condition'] ?? null) ?? 'Unknown';
                    $language = $props['pokemon_language'] ?? ($props['mtg_language'] ?? ($props['language'] ?? 'en'));
                    $isFirstEd = !empty($props['first_edition']);
                    $isAltered = !empty($props['altered']);
                    $isSigned = !empty($props['signed']);
                    $isReverse = !empty($props['pokemon_reverse']) || !empty($props['pokemon_foil']) || !empty($props['mtg_foil']);

                    $hashKey = "{$condition}|{$language}|" . ($isFirstEd?1:0) . "|" . ($isAltered?1:0) . "|" . ($isSigned?1:0) . "|" . ($isReverse?1:0);

                    if (!isset($groupedByBlueprint[$bId])) {
                        $groupedByBlueprint[$bId] = [];
                    }

                    if (!isset($groupedByBlueprint[$bId][$hashKey])) {
                        $groupedByBlueprint[$bId][$hashKey] = [
                            'prices' => [],
                            'attributes' => [
                                'condition' => $condition,
                                'language' => $language,
                                'is_first_edition' => $isFirstEd,
                                'is_altered' => $isAltered,
                                'is_signed' => $isSigned,
                                'is_reverse' => $isReverse,
                            ]
                        ];
                    }
                    
                    $groupedByBlueprint[$bId][$hashKey]['prices'][] = $val;
                }
            }

            // Funzione per calcolare avg e low escludendo gli outlier usando il metodo IQR (Interquartile Range)
            $calculateStats = function(array $prices) {
                if (empty($prices)) return ['avg' => 0, 'low' => 0];
                if (count($prices) < 4) {
                    return [
                        'avg' => array_sum($prices) / count($prices),
                        'low' => min($prices)
                    ];
                }

                sort($prices);
                $count = count($prices);
                
                $q1Index = (int) floor($count * 0.25);
                $q3Index = (int) floor($count * 0.75);
                
                $q1 = $prices[$q1Index];
                $q3 = $prices[$q3Index];
                $iqr = $q3 - $q1;
                
                $lowerBound = max(0, $q1 - 1.5 * $iqr);
                $upperBound = $q3 + 1.5 * $iqr;
                
                $filteredPrices = array_filter($prices, function($p) use ($lowerBound, $upperBound) {
                    return $p >= $lowerBound && $p <= $upperBound;
                });
                
                if (empty($filteredPrices)) {
                    $filteredPrices = $prices;
                }

                return [
                    'avg' => array_sum($filteredPrices) / count($filteredPrices),
                    'low' => min($filteredPrices)
                ];
            };

            // Pre-carico in RAM tutti i prezzi CardTrader esistenti per le carte di questo set
            $cardIds = array_values($blueprintToCardMap);
            $existingPrices = TCGCardPrice::where('provider', 'cardtrader')
                ->whereIn('card_id', $cardIds)
                ->get();
            
            // Costruisco una mappa di lookup in RAM: "card_id|lang|cond|1ed|alt|sign|rev" => record ID e trend
            $existingLookup = [];
            foreach ($existingPrices as $ep) {
                $lookupKey = "{$ep->card_id}|{$ep->language}|{$ep->condition}|"
                    . ($ep->is_first_edition?1:0) . "|" . ($ep->is_altered?1:0) . "|"
                    . ($ep->is_signed?1:0) . "|" . ($ep->is_reverse?1:0);
                $existingLookup[$lookupKey] = [
                    'id' => $ep->id,
                    'trend' => $ep->trend
                ];
            }
            unset($existingPrices); // Libera memoria

            $toInsert = [];
            $toUpdate = [];
            $historyBatch = [];
            $priceChanges = [];
            $now = now();

            foreach ($groupedByBlueprint as $blueprintId => $variations) {
                $cardId = $blueprintToCardMap[$blueprintId];

                foreach ($variations as $hashKey => $groupData) {
                    $stats = $calculateStats($groupData['prices']);
                    $avg = $stats['avg'];
                    $low = $stats['low'];
                    
                    if ($avg == 0) continue;
                    
                    $attrs = $groupData['attributes'];

                    $lookupKey = "{$cardId}|{$attrs['language']}|{$attrs['condition']}|"
                        . ($attrs['is_first_edition']?1:0) . "|" . ($attrs['is_altered']?1:0) . "|"
                        . ($attrs['is_signed']?1:0) . "|" . ($attrs['is_reverse']?1:0);

                    $priceData = [
                        'card_id' => $cardId,
                        'provider' => 'cardtrader',
                        'card_id_product' => (string) $blueprintId,
                        'unit' => 'EUR',
                        'avg' => $avg,
                        'low' => $low,
                        'trend' => $avg,
                        'avg_1d' => $avg,
                        'avg_7d' => $avg,
                        'avg_30d' => $avg,
                        'avg_holo' => null,
                        'low_holo' => null,
                        'trend_holo' => null,
                        'avg_1d_holo' => null,
                        'avg_7d_holo' => null,
                        'avg_30d_holo' => null,
                        'language' => $attrs['language'],
                        'condition' => $attrs['condition'],
                        'is_first_edition' => $attrs['is_first_edition'],
                        'is_altered' => $attrs['is_altered'],
                        'is_signed' => $attrs['is_signed'],
                        'is_reverse' => $attrs['is_reverse'],
                    ];

                    if (isset($existingLookup[$lookupKey])) {
                        // Record esistente: aggiorniamo per ID (velocissimo)
                        $priceData['updated_at'] = $now;
                        $existingId = $existingLookup[$lookupKey]['id'];
                        $toUpdate[$existingId] = $priceData;

                        // Rilevamento variazione trend
                        $oldTrend = $existingLookup[$lookupKey]['trend'];
                        $newTrend = $avg;
                        
                        // Ignoriamo variazioni nulle o differenze di millesimi
                        if (round((float)$oldTrend, 2) !== round((float)$newTrend, 2)) {
                            $priceChanges[] = [
                                'card_id' => $cardId,
                                'set_id' => $tcgSet->id,
                                'set_name' => $tcgSet->name,
                                'language' => $attrs['language'],
                                'condition' => $attrs['condition'],
                                'is_reverse' => $attrs['is_reverse'],
                                'old_trend' => round((float)$oldTrend, 2),
                                'new_trend' => round((float)$newTrend, 2),
                            ];
                        }
                    } else {
                        // Record nuovo: inserimento in blocco
                        $priceData['created_at'] = $now;
                        $priceData['updated_at'] = $now;
                        $toInsert[] = $priceData;
                    }

                    // History è sempre un nuovo record
                    $historyBatch[] = [
                        'card_id' => $cardId,
                        'provider' => 'cardtrader',
                        'trend' => $avg,
                        'avg' => $avg,
                        'trend_holo' => null,
                        'avg_holo' => null,
                        'condition' => $attrs['condition'],
                        'is_first_edition' => $attrs['is_first_edition'],
                        'is_altered' => $attrs['is_altered'],
                        'is_signed' => $attrs['is_signed'],
                        'is_reverse' => $attrs['is_reverse'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // Inserimento massivo dei nuovi prezzi (chunked per non sfondare il limite MySQL)
            if (!empty($toInsert)) {
                foreach (array_chunk($toInsert, 500) as $chunk) {
                    TCGCardPrice::insert($chunk);
                }
                $this->comment("    Inseriti " . count($toInsert) . " nuovi record di prezzo.");
            }

            // Aggiornamento massivo dei prezzi esistenti (bulk upsert per ID)
            if (!empty($toUpdate)) {
                $updateBatch = [];
                foreach ($toUpdate as $id => $data) {
                    $data['id'] = $id; // Aggiungiamo l'ID per triggerare l'ON DUPLICATE KEY UPDATE
                    $updateBatch[] = $data;
                }
                
                foreach (array_chunk($updateBatch, 500) as $chunk) {
                    TCGCardPrice::upsert(
                        $chunk,
                        ['id'], // Colonna univoca
                        [
                            'avg', 'low', 'trend', 'avg_1d', 'avg_7d', 'avg_30d', 
                            'avg_holo', 'low_holo', 'trend_holo', 'avg_1d_holo', 'avg_7d_holo', 'avg_30d_holo', 
                            'updated_at'
                        ] // Colonne da aggiornare se l'ID esiste
                    );
                }
                $this->comment("    Aggiornati " . count($toUpdate) . " record di prezzo esistenti in modalità bulk.");
            }

            // Inserimento massivo della history
            if (!empty($historyBatch)) {
                foreach (array_chunk($historyBatch, 500) as $chunk) {
                    TCGCardPriceHistory::insert($chunk);
                }
                $this->comment("    Inseriti " . count($historyBatch) . " record nello storico.");
            }

            if (!empty($priceChanges)) {
                $this->comment("    Trovate " . count($priceChanges) . " variazioni di prezzo. Dispatch del job delle notifiche...");
                \App\Jobs\ProcessWatchlistNotificationsJob::dispatch($priceChanges);
            }
        }

        $this->info("Invalidazione cache collezioni in corso...");
        try {
            app(\App\Services\DashboardCacheService::class)->invalidateAll();
            app(\App\Services\CollectionCacheService::class)->invalidateAll();
            $this->info("Cache invalidata con successo.");
        } catch (\Exception $e) {
            $this->error("Errore durante l'invalidazione della cache: " . $e->getMessage());
        }

        $this->info("Procedura completata con successo!");
    }

    /**
     * Mappa la condizione di CardTrader nei 5 gradi base
     */
    private function mapCondition(?string $ctCondition): ?string
    {
        if (!$ctCondition) {
            return null;
        }

        $ctCondition = strtolower($ctCondition);

        if (str_contains($ctCondition, 'mint')) { // Mint, Near Mint
            return 'NM';
        }
        if (str_contains($ctCondition, 'lightly') || str_contains($ctCondition, 'slightly')) {
            return 'LP';
        }
        if (str_contains($ctCondition, 'moderately') || $ctCondition === 'played') {
            return 'MP';
        }
        if (str_contains($ctCondition, 'heavily')) {
            return 'HP';
        }
        if (str_contains($ctCondition, 'poor') || str_contains($ctCondition, 'damaged')) {
            return 'DMG';
        }

        return null;
    }
}
