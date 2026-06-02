<?php

namespace App\Jobs;

use App\Models\TCGCard;
use App\Models\TCGCardAbility;
use App\Models\TCGCardPrice;
use App\Models\TCGCardPriceHistory;
use App\Models\TCGCardTranslation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use TCGdex\TCGdex;

class FetchPokemonSetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutes timeout for a large set

    protected $tcgDexSetId;
    protected $localSetId;
    protected $masterLang;
    protected $translationLangs;

    /**
     * Create a new job instance.
     */
    public function __construct(string $tcgDexSetId, int $localSetId, string $masterLang = 'en', array $translationLangs = ['it', 'fr'])
    {
        $this->tcgDexSetId = $tcgDexSetId;
        $this->localSetId = $localSetId;
        $this->masterLang = $masterLang;
        $this->translationLangs = $translationLangs;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("FetchPokemonSetJob Iniziato per Set: {$this->tcgDexSetId}");
        
        $tcgEn = new TCGdex($this->masterLang);
        $set = $tcgEn->set->get($this->tcgDexSetId);
        
        if (!$set) {
            Log::error("Set {$this->tcgDexSetId} non trovato in TCGdex.");
            return;
        }

        // Recuperiamo i Set tradotti per fare matching rapido senza scaricare ogni carta
        $translatedSets = [];
        foreach ($this->translationLangs as $lang) {
            try {
                $tcgLang = new TCGdex($lang);
                $translatedSet = $tcgLang->set->get($this->tcgDexSetId);
                if ($translatedSet && isset($translatedSet->cards)) {
                    // Mappiamo card_id => card (resume)
                    $map = [];
                    foreach ($translatedSet->cards as $c) {
                        $map[$c->id] = $c;
                    }
                    $translatedSets[$lang] = $map;
                }
            } catch (\Exception $e) {
                Log::warning("Impossibile recuperare traduzioni set [{$lang}] per {$this->tcgDexSetId}: " . $e->getMessage());
            }
        }

        $cards = $set->cards;
        $now = Carbon::now();

        $bulkCards = [];
        $bulkTranslations = [];
        $bulkAbilities = [];
        $bulkPrices = [];
        $bulkPricesHistory = [];

        foreach ($cards as $cardResume) {
            $card = $cardResume->toCard(); // API call (inevitabile per le stats complete)
            if (!$card) continue;

            // 1. Dati Carta Master
            $bulkCards[] = [
                'card_id' => $card->id,
                'set_id' => $this->localSetId,
                'name' => $card->name,
                'url_image' => $card->image,
                'illustrator' => $card->illustrator ?? null,
                'rarity' => $card->rarity ?? null,
                'variants' => isset($card->variants) ? json_encode($card->variants) : null,
                'dexId' => $card->localId ?? null,
                'types' => isset($card->types) ? json_encode($card->types) : null,
                'level_stage' => $card->stage ?? null,
                'language' => $this->masterLang,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // 2. Prezzi (Cardmarket)
            if (isset($card->pricing) && property_exists($card->pricing, 'cardmarket')) {
                $cmData = TCGCardPrice::preparePriceData($card->id, $card->pricing->cardmarket, $this->masterLang, $now);
                if ($cmData) {
                    $bulkPrices[] = $cmData['price'];
                    $bulkPricesHistory[] = $cmData['history'];
                }
            }
            // 2. Prezzi (TCGPlayer)
            if (isset($card->pricing) && property_exists($card->pricing, 'tcgplayer')) {
                $tcgData = TCGCardPrice::prepareTcgPlayerPriceData($card->id, $card->pricing->tcgplayer, $this->masterLang, $now);
                if ($tcgData) {
                    $bulkPrices[] = $tcgData['price'];
                    $bulkPricesHistory[] = $tcgData['history'];
                }
            }

            // 3. Abilità e Attacchi
            $abilitiesAndAttacks = array_merge($card->abilities ?? [], $card->attacks ?? []);
            if (!empty($abilitiesAndAttacks)) {
                $abs = TCGCardAbility::prepareAbilitiesData($card->id, $abilitiesAndAttacks, $this->masterLang, $now);
                foreach ($abs as $ab) {
                    $bulkAbilities[] = $ab;
                }
            }

            // 4. Traduzioni (Master Fallback)
            $bulkTranslations[] = [
                'card_id' => $card->id, // Da aggiornare dopo insert per ID interno DB, oppure usare TCGdex id?
                // Nota: la logica originale di TCGCardTranslation usava la Foreign Key verso tcg_cards.id
                // Dobbiamo estrarre la FK dopo l'upsert, oppure cambiare il riferimento. 
                // Per ora, teniamo traccia del card_id e la inseriremo in un secondo step.
                'tcg_card_id' => $card->id,
                'language' => $this->masterLang,
                'name' => $card->name,
                'url_image' => $card->image,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // 5. Traduzioni Aggiuntive
            foreach ($this->translationLangs as $lang) {
                if (isset($translatedSets[$lang][$card->id])) {
                    $tCard = $translatedSets[$lang][$card->id];
                    $bulkTranslations[] = [
                        'tcg_card_id' => $card->id,
                        'language' => $lang,
                        'name' => $tCard->name ?? $card->name,
                        'url_image' => $tCard->image ?? $card->image,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // DB Transactions
        DB::transaction(function () use ($bulkCards, $bulkTranslations, $bulkAbilities, $bulkPrices, $bulkPricesHistory) {
            // Upsert delle Carte (chiave unica: card_id)
            TCGCard::upsert(
                $bulkCards,
                ['card_id'], // unique columns
                ['set_id', 'name', 'url_image', 'illustrator', 'rarity', 'variants', 'dexId', 'types', 'level_stage', 'language', 'updated_at'] // update columns
            );

            // Per Abilità, Traduzioni e Prezzi, dobbiamo usare l'ID interno del database (tcg_cards.id)
            // L'upsert precedente ci assicura che esistano. Li recuperiamo tutti:
            $cardIds = array_column($bulkCards, 'card_id');
            $dbCards = TCGCard::whereIn('card_id', $cardIds)->pluck('id', 'card_id')->toArray();

            // Mappiamo i dati con l'ID interno
            $mappedTranslations = [];
            foreach ($bulkTranslations as $t) {
                if (isset($dbCards[$t['tcg_card_id']])) {
                    $mappedTranslations[] = [
                        'card_id' => $dbCards[$t['tcg_card_id']],
                        'language' => $t['language'],
                        'name' => $t['name'],
                        'url_image' => $t['url_image'],
                        'created_at' => $t['created_at'],
                        'updated_at' => $t['updated_at'],
                    ];
                }
            }

            $mappedAbilities = [];
            foreach ($bulkAbilities as $a) {
                if (isset($dbCards[$a['card_id']])) {
                    $a['card_id'] = $dbCards[$a['card_id']];
                    $mappedAbilities[] = $a;
                }
            }

            $mappedPrices = [];
            foreach ($bulkPrices as $p) {
                if (isset($dbCards[$p['card_id']])) {
                    $p['card_id'] = $dbCards[$p['card_id']];
                    $mappedPrices[] = $p;
                }
            }

            $mappedPricesHistory = [];
            foreach ($bulkPricesHistory as $ph) {
                if (isset($dbCards[$ph['card_id']])) {
                    $ph['card_id'] = $dbCards[$ph['card_id']];
                    $mappedPricesHistory[] = $ph;
                }
            }

            // Upsert Traduzioni (chiavi uniche: card_id, language)
            if (!empty($mappedTranslations)) {
                TCGCardTranslation::upsert(
                    $mappedTranslations,
                    ['card_id', 'language'],
                    ['name', 'url_image', 'updated_at']
                );
            }

            // Le Abilità le inseriamo in blocco. Visto che la card può già avere abilità (se stiamo riaggiornando), 
            // potremmo cancellare quelle esistenti e reinserirle, oppure fare un upsert se c'è una chiave unica.
            // Cancelliamo e ricreiamo per sicurezza
            if (!empty($mappedAbilities)) {
                $internalCardIds = array_values($dbCards);
                TCGCardAbility::whereIn('card_id', $internalCardIds)->delete();
                // Insert a chunk per evitare limiti DB
                foreach (array_chunk($mappedAbilities, 500) as $chunk) {
                    TCGCardAbility::insert($chunk);
                }
            }

            // Upsert Prezzi Attuali (chiavi uniche: card_id, provider, language)
            if (!empty($mappedPrices)) {
                TCGCardPrice::upsert(
                    $mappedPrices,
                    ['card_id', 'provider', 'language'],
                    ['card_id_product', 'unit', 'avg', 'low', 'trend', 'avg_1d', 'avg_7d', 'avg_30d', 'avg_holo', 'low_holo', 'trend_holo', 'avg_1d_holo', 'avg_7d_holo', 'avg_30d_holo', 'updated_at']
                );
            }

            // Insert Storico Prezzi (sono nuovi record sempre)
            if (!empty($mappedPricesHistory)) {
                foreach (array_chunk($mappedPricesHistory, 500) as $chunk) {
                    TCGCardPriceHistory::insert($chunk);
                }
            }
        });

        Log::info("FetchPokemonSetJob Completato per Set: {$this->tcgDexSetId}");
    }
}
