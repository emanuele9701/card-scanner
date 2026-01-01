<?php

namespace App\Services;

use App\Models\PokemonCard;
use App\Models\MarketCard;
use App\Models\CardSet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class CardMatchingService
{
    /**
     * Try to match a Pokemon card to market data
     *
     * @param PokemonCard $card The card to match
     * @return MarketCard|null The matched market card or null if no match found
     */
    public function matchCard(PokemonCard $card): ?MarketCard
    {
        // Strategy 1: Exact match by card number and set
        if ($card->set_number && $card->cardSet) {
            $match = $this->matchByNumberAndSet($card);
            if ($match) {
                Log::info("Exact match found (number+set)", [
                    'pokemon_card_id' => $card->id,
                    'market_card_id' => $match->id,
                    'card_number' => $card->set_number,
                    'set' => $card->cardSet->abbreviation,
                ]);
                return $match;
            }
        }

        // Strategy 2: Match by card number only (if set not available)
        if ($card->set_number) {
            $match = $this->matchByNumberOnly($card);
            if ($match) {
                Log::info("Match found (number only)", [
                    'pokemon_card_id' => $card->id,
                    'market_card_id' => $match->id,
                    'card_number' => $card->set_number,
                ]);
                return $match;
            }
        }

        // Strategy 3: Fuzzy match by card name
        if ($card->card_name) {
            $match = $this->matchByName($card);
            if ($match) {
                Log::info("Fuzzy match found (name)", [
                    'pokemon_card_id' => $card->id,
                    'market_card_id' => $match->id,
                    'card_name' => $card->card_name,
                    'matched_name' => $match->product_name,
                ]);
                return $match;
            }
        }

        Log::warning("No match found", [
            'pokemon_card_id' => $card->id,
            'card_name' => $card->card_name,
            'set_number' => $card->set_number,
        ]);

        return null;
    }

    /**
     * Match by card number and set abbreviation (most accurate)
     *
     * @param PokemonCard $card
     * @return MarketCard|null
     */
    private function matchByNumberAndSet(PokemonCard $card): ?MarketCard
    {
        return MarketCard::where('card_number', $card->set_number)
            ->where('set_abbreviation', $card->cardSet->abbreviation)
            ->first();
    }

    /**
     * Match by card number only (less accurate, might match wrong set)
     *
     * @param PokemonCard $card
     * @return MarketCard|null
     */
    private function matchByNumberOnly(PokemonCard $card): ?MarketCard
    {
        return MarketCard::where('card_number', $card->set_number)
            ->first();
    }

    /**
     * Match by card name using fuzzy matching
     *
     * @param PokemonCard $card
     * @return MarketCard|null
     */
    private function matchByName(PokemonCard $card): ?MarketCard
    {
        // Clean the card name for better matching
        $cleanName = $this->cleanCardName($card->card_name);

        // Try exact name match first
        $match = MarketCard::where('product_name', 'LIKE', $cleanName)->first();
        if ($match) {
            return $match;
        }

        // Try partial name match
        $match = MarketCard::where('product_name', 'LIKE', "%{$cleanName}%")->first();
        if ($match) {
            return $match;
        }

        // Try reverse: check if card name contains market card name
        $matches = MarketCard::all()->filter(function ($marketCard) use ($cleanName) {
            return stripos($cleanName, $marketCard->product_name) !== false;
        });

        return $matches->first();
    }

    /**
     * Clean card name for better matching
     *
     * @param string $name
     * @return string
     */
    private function cleanCardName(string $name): string
    {
        // Remove common suffixes and prefixes
        $cleaned = preg_replace('/\s*-\s*\d+\/\d+\s*$/', '', $name); // Remove " - 001/094"
        $cleaned = trim($cleaned);

        return $cleaned;
    }

    /**
     * Match all unmatched Pokemon cards to market data
     *
     * @return array Statistics about the matching process
     */
    public function matchAllUnmatched(): array
    {
        $stats = [
            'processed' => 0,
            'matched' => 0,
            'unmatched' => 0,
            'already_matched' => 0,
        ];

        $unmatchedCards = PokemonCard::whereNull('market_card_id')->get();
        $stats['processed'] = $unmatchedCards->count();

        foreach ($unmatchedCards as $card) {
            $marketCard = $this->matchCard($card);

            if ($marketCard) {
                $card->update(['market_card_id' => $marketCard->id]);
                $stats['matched']++;
            } else {
                $stats['unmatched']++;
            }
        }

        // Count already matched cards
        $stats['already_matched'] = PokemonCard::whereNotNull('market_card_id')->count();

        Log::info('Batch matching completed', $stats);

        return $stats;
    }

    /**
     * Get detailed report of unmatched cards
     *
     * @return Collection
     */
    public function getUnmatchedCardsReport(): Collection
    {
        return PokemonCard::whereNull('market_card_id')
            ->get()
            ->map(function ($card) {
                return [
                    'id' => $card->id,
                    'card_name' => $card->card_name,
                    'set_number' => $card->set_number,
                    'rarity' => $card->rarity,
                    'set' => $card->cardSet?->name,
                    'set_abbreviation' => $card->cardSet?->abbreviation,
                ];
            });
    }

    /**
     * Suggest market cards for a given Pokemon card
     *
     * @param PokemonCard $card
     * @param int $limit Maximum number of suggestions
     * @return Collection
     */
    public function suggestMatches(PokemonCard $card, int $limit = 5): Collection
    {
        $suggestions = collect();

        // Suggest by number and set
        if ($card->set_number && $card->cardSet) {
            $byNumberAndSet = MarketCard::where('card_number', $card->set_number)
                ->where('set_abbreviation', $card->cardSet->abbreviation)
                ->get();
            $suggestions = $suggestions->merge($byNumberAndSet);
        }

        // Suggest by number only
        if ($card->set_number && $suggestions->count() < $limit) {
            $byNumber = MarketCard::where('card_number', $card->set_number)
                ->whereNotIn('id', $suggestions->pluck('id'))
                ->limit($limit - $suggestions->count())
                ->get();
            $suggestions = $suggestions->merge($byNumber);
        }

        // Suggest by name
        if ($card->card_name && $suggestions->count() < $limit) {
            $cleanName = $this->cleanCardName($card->card_name);
            $byName = MarketCard::where('product_name', 'LIKE', "%{$cleanName}%")
                ->whereNotIn('id', $suggestions->pluck('id'))
                ->limit($limit - $suggestions->count())
                ->get();
            $suggestions = $suggestions->merge($byName);
        }

        return $suggestions->take($limit);
    }

    /**
     * Manually link a Pokemon card to a market card
     *
     * @param PokemonCard $card
     * @param MarketCard $marketCard
     * @return bool
     */
    public function manualMatch(PokemonCard $card, MarketCard $marketCard): bool
    {
        $card->update(['market_card_id' => $marketCard->id]);

        Log::info("Manual match created", [
            'pokemon_card_id' => $card->id,
            'market_card_id' => $marketCard->id,
        ]);

        return true;
    }

    /**
     * Unmatch a Pokemon card from market data
     *
     * @param PokemonCard $card
     * @return bool
     */
    public function unmatch(PokemonCard $card): bool
    {
        $previousMarketCardId = $card->market_card_id;
        $card->update(['market_card_id' => null]);

        Log::info("Card unmatched", [
            'pokemon_card_id' => $card->id,
            'previous_market_card_id' => $previousMarketCardId,
        ]);

        return true;
    }
}
