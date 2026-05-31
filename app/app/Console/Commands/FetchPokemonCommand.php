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
        $pricings = [
            'cardmarket' => [],
            'tcgplayer' => []
        ];
        $languages = ['it','en'];
        foreach ($languages as $language) {
            $this->info("Language: " . $language);
            $tcg = new TCGdex($language);
            // 1. Recupero tutte le espansioni
            $series = $tcg->serie->list();

            foreach ($series as $k => $serie) {
                $this->info("Serie: " . $serie->name);
                /**
                 * @var \TCGdex\Models\Serie $serie
                 */
                $serie = $serie->toSerie();

                $tcgSerie = TCGSeries::where('serie_id', $serie->id)->where('language', $language)->first();
                if (!$tcgSerie) {
                    $this->info("Inserisco Serie: " . $serie->name);
                    $tcgSerie = new TCGSeries();
                    $tcgSerie->serie_id = $serie->id;
                    $tcgSerie->name = $serie->name;
                    $tcgSerie->logo = $serie->logo;
                    $tcgSerie->language = $language;
                    $tcgSerie->save();
                }

                // Sets
                $sets = $serie->sets;
                foreach ($sets as $k => $setResume) {
                    $this->info("Set: " . $setResume->name);
                    /**
                     * @var \TCGdex\Models\Set $set
                     */
                    $set = $setResume->toSet();

                    $tcgSet = TCGSet::where('set_id', $set->id)->where('language', $language)->first();
                    if (!$tcgSet) {
                        $this->info("Inserisco Set: " . $set->name);
                        $tcgSet = new TCGSet();
                        $tcgSet->set_id = $set->id;
                        $tcgSet->serie_id = $tcgSerie->id; // FK auto-increment
                        $tcgSet->name = $set->name;
                        $tcgSet->logo = $set->logo;
                        $tcgSet->symbol = $set->symbol;
                        $tcgSet->card_total = $set->cardCount->total;
                        $tcgSet->card_official = $set->cardCount->official;
                        $tcgSet->card_normal = $set->cardCount->normal;
                        $tcgSet->card_reverse = $set->cardCount->reverse;
                        $tcgSet->card_holo = $set->cardCount->holo;
                        $tcgSet->card_first_edition = $set->cardCount->firstEd;
                        $tcgSet->release_date = $set->releaseDate;
                        $tcgSet->abbreviation = $set->abbreviation ?? '-';
                        // Denormalizza abbreviazione ufficiale per ricerca indicizzata
                        $abbr = $set->abbreviation;
                        if (is_object($abbr) && isset($abbr->official)) {
                            $tcgSet->abbreviation_official = strtoupper($abbr->official);
                        } elseif (is_array($abbr) && isset($abbr['official'])) {
                            $tcgSet->abbreviation_official = strtoupper($abbr['official']);
                        }
                        $tcgSet->language = $language;
                        $tcgSet->save();
                    }

                    $cards = $set->cards;
                    foreach ($cards as $k => $card) {
                        $this->info("Card: " . $card->name);
                        /**
                         * @var \TCGdex\Models\Card $card
                         */
                        $card = $card->toCard();

                        $tcgCard = TCGCard::where('card_id', $card->id)->where('set_id', $tcgSet->id)->first();
                        if ($tcgCard) {
                            $this->info("Aggiorno Prezzi: " . $tcgCard->name . " ({$tcgCard->card_id}) ");
                            TCGCardPrice::createPrices($tcgCard->id, $card->pricing->cardmarket, $language);
                            continue;
                        }

                        $this->info("Inserisco Card: " . $card->name . " ({$card->id}) ");
                        $tcgCard = new TCGCard();
                        $tcgCard->card_id = $card->id;
                        $tcgCard->set_id = $tcgSet->id; // FK auto-increment
                        $tcgCard->name = $card->name;
                        $tcgCard->url_image = $card->image;
                        $tcgCard->illustrator = $card->illustrator;
                        $tcgCard->rarity = $card->rarity;
                        $tcgCard->variants = $card->variants;
                        $tcgCard->dexId = $card->localId; // Numero della carta.
                        $tcgCard->types = $card->types; // Tipo di carta.
                        $tcgCard->level_stage = $card->stage;
                        $tcgCard->language = $language;
                        $tcgCard->save();

                        $pricings['cardmarket'][$tcgCard->id][] = $card->pricing->cardmarket;
                        $pricings['tcgplayer'][$tcgCard->id][] = $card->pricing->tcgplayer;

                        // Abilities
                        TCGCardAbility::createAbilities($tcgCard->id, array_merge($card->abilities ?? [], $card->attacks ?? []), $language);

                        // Prices
                        TCGCardPrice::createPrices($tcgCard->id, $card->pricing->cardmarket, $language);
                    }
                }
                $this->info("Salvo i prezzi...");
                file_put_contents("Pricings_{$language}.json",json_encode($pricings,JSON_PRETTY_PRINT));
            }
        }
        
    }
}
