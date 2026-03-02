<?php

namespace App\Services;

use App\Models\CardSet;
use App\Models\MarketCard;
use Illuminate\Support\Facades\Log;
use TCGdex\Model\Card;
use TCGdex\Model\CardResume;
use TCGdex\Model\SubModel\Attack;
use TCGdex\Query;
use TCGdex\TCGdex;

class TCGdexLookupService
{
    private const RARITY_MAP = [
        'Common' => 'Common',
        'Comune' => 'Common',
        'Uncommon' => 'Uncommon',
        'Non comune' => 'Uncommon',
        'Rare' => 'Rare',
        'Rara' => 'Rare',
        'Rare Holo' => 'Holo Rare',
        'Olografica rara' => 'Holo Rare',
        'Double Rare' => 'Double Rare',
        'Rara doppia' => 'Double Rare',
        'Illustration Rare' => 'Illustration Rare',
        'Rara illustrazione' => 'Illustration Rare',
        'Special Illustration Rare' => 'Illustration Rare',
        'Rara illustrazione speciale' => 'Illustration Rare',
        'Ultra Rare' => 'Ultra Rare',
        'Ultra rara' => 'Ultra Rare',
        'Secret Rare' => 'Secret Rare',
        'Rara segreta' => 'Secret Rare',
        'Hyper Rare' => 'Secret Rare',
        'Rara Hyper' => 'Secret Rare',
    ];

    /**
     * Look up card details from TCGdex and enrich the mapped data.
     */
    public function lookupAndEnrich(array $mappedData): array
    {
        if (!isset($mappedData['set_number'])) {
            Log::info('Non posso recuperare il set da TCGDex');
            return $mappedData;
        }

        $parts = explode("/", $mappedData['set_number']);
        if (count($parts) !== 2) {
            Log::warning("Formato set_number non valido: " . $mappedData['set_number']);
            return $mappedData;
        }
        [$localId, $totalCards] = $parts;

        $tcgCard = null;

        if ($mappedData['is_old_card']) {
            Log::info("Carta vecchia");
            $result = $this->searchAndMatch($localId, $mappedData['card_name'], $totalCards, true);
            if (!$result) {
                return $mappedData;
            }
            $mappedData['card_set_id'] = $result['card_set_id'];
            $tcgCard = $result['tcg_card'];
        } else {
            Log::info("Non è carta vecchia");
            $set = CardSet::where('card_set_abbreviation', $mappedData['set_code'])->first();

            if (!$set) {
                $result = $this->searchAndMatch($localId, $mappedData['card_name'], $totalCards, false);
                if (!$result) {
                    return $mappedData;
                }
                $mappedData['card_set_id'] = $result['card_set_id'];
                $set = CardSet::find($result['card_set_id']);
            } else {
                Log::info("Set trovato");
                $mappedData['card_set_id'] = $set->id;
            }

            // Fetch full card details with Italian locale for modern cards
            try {
                $tcgIt = new TCGdex('it');
                $tcgCard = $tcgIt->set->getCard($set->abbreviation, $localId);
                if (!($tcgCard instanceof Card)) {
                    $tcgCard = null;
                }
            } catch (\Exception $e) {
                Log::warning("TCGDex error: " . $e->getMessage());
            }
        }

        if ($tcgCard instanceof Card) {
            $mappedData = $this->enrichCardDetails($mappedData, $tcgCard);
            $mappedData = $this->extractPricingAndMarket($mappedData, $tcgCard);
        } else {
            Log::warning('tcgCard non disponibile, skip enrichment');
        }

        return $mappedData;
    }

    /**
     * Search for a card on TCGdex and match it to a local CardSet.
     */
    private function searchAndMatch(string $localId, string $cardName, string $totalCards, bool $isOldCard): ?array
    {
        // Converto il card name in un formato utf8

        $cardName = str_replace("’", "'", $cardName);
        // $cardName = mb_convert_encoding($cardName, 'UTF-8', mb_detect_encoding($cardName, 'UTF-8, ISO-8859-1', true));
        $tcg = new TCGdex();
        $query = Query::create()
            ->equal('localId', $localId)
            ->contains('name', $cardName)
            ->sort('hp', 'desc')
            ->paginate(1, 20);

        $listCards = $tcg->card->list($query);
        Log::alert("Riscontri su TCGDex: (#{$localId}) {$cardName} -> " . count($listCards));

        if (count($listCards) === 0) {
            return null;
        }
        Log::info("Lista cards: " . json_encode($listCards));
        if (count($listCards) === 1) {
            /** @var Card $tcgCard */
            $tcgCard = $listCards[0]->toCard();
            Log::info("Card: " . json_encode($tcgCard));
            $abbreviation = $tcgCard->set->toSet()->abbreviation->official;
            Log::info("Set identificato: " . $abbreviation);

            $set = CardSet::where('card_set_abbreviation', $abbreviation)->first();
            if (!$set) {
                Log::alert("Set non trovato");
                return null;
            }

            Log::info("Set trovato in db");
            return ['card_set_id' => $set->id, 'tcg_card' => $tcgCard];
        }

        // Multiple results — match by localId and card count
        Log::alert("Molteplici riscontri: (#{$localId}) {$cardName}");

        foreach ($listCards as $cardResume) {
            /** @var CardResume $cardResume */
            $resumeLocalId = $cardResume->localId;

            /** @var Card $tcgCard */
            $tcgCard = $cardResume->toCard();
            $tcgSetData = $tcgCard->set->toSet();

            Log::info("Check: {$tcgCard->name} in {$tcgSetData->name} localId:{$resumeLocalId} cardCount:{$tcgSetData->cardCount->total}");

            if ($resumeLocalId == $localId && $tcgSetData->cardCount->official == $totalCards) {
                Log::info("Match: {$tcgCard->name} in {$tcgSetData->name}");

                $abbreviation = $isOldCard
                    ? $tcgSetData->tcgOnline
                    : $tcgSetData->abbreviation->official;

                $set = CardSet::where('card_set_abbreviation', $abbreviation)->first();
                if (!$set) {
                    return null;
                }

                return ['card_set_id' => $set->id, 'tcg_card' => $tcgCard];
            }
        }

        Log::info('Nessun match trovato');
        return null;
    }

    /**
     * Enrich mapped data with card details from TCGdex.
     */
    private function enrichCardDetails(array $mappedData, Card $tcgCard): array
    {
        $mappedData['hp'] = $tcgCard->hp ?? null;
        $mappedData['type'] = $tcgCard->types[0] ?? null;
        $mappedData['evolution_stage'] = $tcgCard->stage ?? null;

        $mappedData['attacks'] = $tcgCard->attacks
            ? array_map(fn(Attack $attack) => [
                'cost' => $attack->cost,
                'name' => $attack->name,
                'text' => $attack->effect,
                'damage' => $attack->damage,
            ], $tcgCard->attacks)
            : [];

        $mappedData['weakness'] = is_array($tcgCard->weaknesses)
            ? implode(', ', array_map(fn($w) => "{$w->type} {$w->value}", $tcgCard->weaknesses))
            : $tcgCard->weaknesses;

        $mappedData['resistance'] = is_array($tcgCard->resistances)
            ? implode(', ', array_map(fn($r) => "{$r->type} {$r->value}", $tcgCard->resistances))
            : $tcgCard->resistances;

        $mappedData['retreat_cost'] = is_array($tcgCard->retreat)
            ? count($tcgCard->retreat)
            : (string) $tcgCard->retreat;

        $mappedData['rarity'] = $this->mapRarity($tcgCard->rarity);

        return $mappedData;
    }

    /**
     * Extract pricing info and create/link MarketCard if available.
     */
    private function extractPricingAndMarket(array $mappedData, Card $tcgCard): array
    {
        if (!isset($tcgCard->pricing) || !isset($tcgCard->pricing->cardmarket)) {
            return $mappedData;
        }

        $cm = $tcgCard->pricing->cardmarket;

        $mappedData['pricing'] = [
            'avg' => $cm->avg ?? null,
            'low' => $cm->low ?? null,
            'trend' => $cm->trend ?? null,
            'updated' => $cm->updated ?? null,
            'unit' => $cm->unit ?? 'EUR',
            'idProduct' => $cm->idProduct ?? null,
        ];

        if (!isset($cm->idProduct)) {
            return $mappedData;
        }

        $gameModel = \App\Models\Game::firstOrCreate(['name' => 'Pokemon']);

        $marketCard = MarketCard::withoutGlobalScope('user')->firstOrCreate(
            ['product_id' => $cm->idProduct],
            [
                'product_name' => $tcgCard->name,
                'card_number' => $tcgCard->localId,
                'set_name' => $tcgCard->set->name,
                'set_abbreviation' => $tcgCard->set->id,
                'rarity' => $tcgCard->rarity ?? 'Unknown',
                'type' => $tcgCard->types[0] ?? 'Unknown',
                'game' => 'Pokemon',
                'game_id' => $gameModel->id,
            ]
        );

        $mappedData['market_card_id'] = $marketCard->id;

        return $mappedData;
    }

    /**
     * Map TCGDex rarity to internal format.
     */
    private function mapRarity(?string $rarity): ?string
    {
        if (!$rarity) {
            return null;
        }

        return self::RARITY_MAP[$rarity] ?? $rarity;
    }
}
