<?php

namespace App\Console\Commands;

use App\Models\TCGCard;
use App\Models\TCGCardAbility;
use App\Models\TCGCardPrice;
use App\Models\TCGSeries;
use App\Models\TCGSet;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use TCGdex\TCGdex;

#[Signature('app:fetch-pokemon')]
#[Description('Aggiorna database carte Pokemon')]
class FetchPokemonCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {   
        $masterLang = 'en';
        $now = \Carbon\Carbon::now();
        
        $this->info("Inizializzo TCGdex master (en)...");
        $tcgEn = new TCGdex($masterLang);

        $this->info("Recupero tutte le espansioni...");
        $series = $tcgEn->serie->list();

        $bulkSeries = [];
        foreach ($series as $serieResume) {
            $serie = $serieResume->toSerie(); // Fetch single serie
            $bulkSeries[] = [
                'serie_id' => $serie->id,
                'name' => $serie->name,
                'logo' => $serie->logo,
                'language' => $masterLang,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->info("Salvataggio " . count($bulkSeries) . " Series a DB...");
        \App\Models\TCGSeries::upsert($bulkSeries, ['serie_id'], ['name', 'logo', 'language', 'updated_at']);

        // Recuperiamo gli ID interni appena creati
        $dbSeries = \App\Models\TCGSeries::pluck('id', 'serie_id')->toArray();

        foreach ($series as $serieResume) {
            $serie = $serieResume->toSerie();
            $this->info("Elaboro Serie: " . $serie->name);
            
            if (!isset($dbSeries[$serie->id])) {
                $this->error("Serie {$serie->id} non trovata nel DB, salto.");
                continue;
            }
            $localSerieId = $dbSeries[$serie->id];

            $sets = $serie->sets;
            $bulkSets = [];

            foreach ($sets as $setResume) {
                $set = $setResume->toSet();
                
                $hasAbbr = isset($set->abbreviation);
                $abbreviation = $hasAbbr ? $set->abbreviation : '-';
                $abbreviationOfficial = null;
                
                if ($hasAbbr) {
                    if (is_object($abbreviation) && isset($abbreviation->official)) {
                        $abbreviationOfficial = strtoupper($abbreviation->official);
                    } elseif (is_array($abbreviation) && isset($abbreviation['official'])) {
                        $abbreviationOfficial = strtoupper($abbreviation['official']);
                    }
                }

                $bulkSets[] = [
                    'set_id' => $set->id,
                    'serie_id' => $localSerieId,
                    'name' => $set->name,
                    'logo' => $set->logo,
                    'symbol' => $set->symbol,
                    'card_total' => $set->cardCount->total ?? 0,
                    'card_official' => $set->cardCount->official ?? 0,
                    'card_normal' => $set->cardCount->normal ?? 0,
                    'card_reverse' => $set->cardCount->reverse ?? 0,
                    'card_holo' => $set->cardCount->holo ?? 0,
                    'card_first_edition' => $set->cardCount->firstEd ?? 0,
                    'release_date' => $set->releaseDate ?? null,
                    'abbreviation' => json_encode($abbreviation),
                    'abbreviation_official' => $abbreviationOfficial,
                    'language' => $masterLang,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($bulkSets)) {
                $this->info("  Salvataggio " . count($bulkSets) . " Sets a DB per {$serie->name}...");
                \App\Models\TCGSet::upsert(
                    $bulkSets, 
                    ['set_id'], 
                    ['serie_id', 'name', 'logo', 'symbol', 'card_total', 'card_official', 'card_normal', 'card_reverse', 'card_holo', 'card_first_edition', 'release_date', 'abbreviation', 'abbreviation_official', 'language', 'updated_at']
                );
            }

            // Recuperiamo gli ID dei Set dal DB
            $setIds = array_column($bulkSets, 'set_id');
            $dbSets = \App\Models\TCGSet::whereIn('set_id', $setIds)->pluck('id', 'set_id')->toArray();

            // Dispatch Jobs per scaricare le carte
            foreach ($sets as $setResume) {
                if (isset($dbSets[$setResume->id])) {
                    $this->info("  -> Dispatch Job per il Set: " . $setResume->name);
                    \App\Jobs\FetchPokemonSetJob::dispatch($setResume->id, $dbSets[$setResume->id]);
                }
            }
        }

        $this->info("Comando dispatcher completato. I Job elaboreranno le carte in background.");
    }
}
