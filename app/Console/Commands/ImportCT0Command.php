<?php

namespace App\Console\Commands;

use App\Models\TcgExternalProvidersMapping;
use App\Models\UserIncomingCard;
use App\Models\TCGCard;
use App\Services\CardTrader\CardTraderClient;
use App\Services\CardTrader\CardTraderParser;
use App\Services\CardTrader\DTO\CT0BoxItemDto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCT0Command extends Command
{
    protected $signature = 'app:import-ct0 {user_id=1 : L\'ID dell\'utente per cui importare le carte in arrivo}';
    protected $description = 'Importa gli articoli CT0 Box da CardTrader come carte in arrivo (incoming cards)';

    public function handle(CardTraderClient $client, CardTraderParser $parser)
    {
        $userId = (int) $this->argument('user_id');

        $this->info("Inizio importazione CT0 Box Items per l'utente ID: {$userId}");

        try {
            $this->comment("Recupero dati dalle API CardTrader (GET /ct0_box_items)...");
            $ct0Data = $client->getCT0BoxItems();
            $items = $parser->parseCT0BoxItems($ct0Data);
        } catch (\Exception $e) {
            $this->error("Errore durante la comunicazione con CardTrader: " . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Trovati " . count($items) . " articoli totali nel CT0 Box.");

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $notMapped = 0;

        $unmappedItems = []; // Per il riepilogo finale

        // Pre-carichiamo tutti i mapping esistenti per performance
        $allMappings = TcgExternalProvidersMapping::where('provider', 'cardtrader')
            ->pluck('card_id', 'external_id')
            ->toArray();
            
        // Raggruppiamo tutte le carte locali necessarie in una volta per recuperare il set_id
        $cardIdsToFetch = array_unique(array_values($allMappings));
        $cardsSetMap = TCGCard::whereIn('id', $cardIdsToFetch)
            ->pluck('set_id', 'id')
            ->toArray();

        // Usiamo una transazione DB per sicurezza e performance
        DB::beginTransaction();

        try {
            foreach ($items as $item) {
                // 1. Filtraggio stati
                if ($item->cancelledAt !== null) {
                    $skipped++;
                    continue;
                }

                $activeQuantity = $item->quantityPending + $item->quantityOk;
                if ($activeQuantity <= 0) {
                    // Significa che ha solo missing (rimborsato) o è vuoto
                    $skipped++;
                    continue;
                }

                // Filtraggio opzionale: importiamo solo Pokemon (game_id = 1, spesso su CardTrader Pokemon è 1, controlliamo)
                // In realtà mappiamo solo se abbiamo il blueprint. Se non è Pokemon, semplicemente non troverà il mapping.
                // Procediamo.

                // 2. Mapping blueprint -> card locale
                if (!isset($allMappings[$item->blueprintId])) {
                    $notMapped++;
                    $unmappedItems[] = "{$item->name} ({$item->expansion}) [Blueprint: {$item->blueprintId}]";
                    continue;
                }

                $cardId = $allMappings[$item->blueprintId];
                $setId = $cardsSetMap[$cardId] ?? null;

                // 3. Mapping proprietà
                $foilType = 'normal';
                if ($item->isFoil && !$item->isReverse) {
                    $foilType = 'holo';
                } elseif ($item->isReverse) {
                    $foilType = 'reverse';
                }

                $condition = $this->mapCondition($item->condition);
                
                $notes = "CT0 #{$item->id} | {$item->formattedPrice}";

                // 4. Upsert (Creazione o aggiornamento idempotente)
                $existing = UserIncomingCard::where('source', 'cardtrader_ct0')
                    ->where('external_id', (string) $item->id)
                    ->first();

                if ($existing) {
                    // Controlliamo se qualcosa è cambiato (ad es. la quantità che da pending passa a ok non cambia la riga, ma se ne ordina un'altra forse sì)
                    if ($existing->quantity != $activeQuantity || $existing->condition != $condition || $existing->foil_type != $foilType) {
                        $existing->update([
                            'quantity' => $activeQuantity,
                            'condition' => $condition,
                            'foil_type' => $foilType,
                            'notes' => $notes,
                        ]);
                        $updated++;
                    } else {
                        // Già presente e aggiornato
                        $skipped++;
                    }
                } else {
                    UserIncomingCard::create([
                        'user_id' => $userId,
                        'card_id' => $cardId,
                        'set_id' => $setId,
                        'language' => $item->language,
                        'foil_type' => $foilType,
                        'is_first_edition' => $item->isFirstEdition,
                        'is_signed' => $item->isSigned,
                        'is_altered' => $item->isAltered,
                        'quantity' => $activeQuantity,
                        'notes' => $notes,
                        'source' => 'cardtrader_ct0',
                        'external_id' => (string) $item->id,
                        'condition' => $condition,
                    ]);
                    $imported++;
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Errore durante il salvataggio nel database: " . $e->getMessage());
            return self::FAILURE;
        }

        // Riepilogo finale
        $this->newLine();
        $this->info("====== RIEPILOGO IMPORTAZIONE CT0 ======");
        $this->line("Totali processati : " . count($items));
        $this->line("Nuovi inseriti    : <info>{$imported}</info>");
        $this->line("Aggiornati        : <comment>{$updated}</comment>");
        $this->line("Saltati/Invariati : {$skipped}");
        
        if ($notMapped > 0) {
            $this->line("Non mappati (DB)  : <error>{$notMapped}</error>");
            $this->newLine();
            $this->warn("Alcuni articoli non sono stati mappati. Assicurati di aver lanciato 'php artisan app:fetch-cardtrader' di recente.");
            $this->warn("Esempi non mappati:");
            
            foreach ($unmappedItems as $sample) {
                $this->line("  - {$sample}");
            }
        }
        $this->newLine();
        
        if ($imported > 0 || $updated > 0) {
            $this->info("Importazione completata con successo!");
        } else {
            $this->info("Nessuna nuova carta da importare.");
        }

        return self::SUCCESS;
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

        return null; // Condizione sconosciuta
    }
}
