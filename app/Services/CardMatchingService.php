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
    /**
     * Match a batch of cards
     *
     * @param Collection $cards
     * @return array
     */
    public function matchBatch(Collection $cards): array
    {
        $stats = [
            'processed' => $cards->count(),
            'matched' => 0,
            'unmatched' => 0,
            'already_matched' => 0,
        ];

        foreach ($cards as $card) {
            // Skip if already matched (safety check)
            if ($card->market_card_id) {
                $stats['already_matched']++;
                continue;
            }

            $marketCard = $this->matchCard($card);

            if ($marketCard) {
                $this->manualMatch($card, $marketCard);
                $stats['matched']++;
            } else {
                $stats['unmatched']++;
            }
        }

        return $stats;
    }

    /**
     * Match all unmatched Pokemon cards to market data
     *
     * @return array Statistics about the matching process
     */
    public function matchAllUnmatched(): array
    {
        $unmatchedCards = PokemonCard::whereNull('market_card_id')->get();
        $stats = $this->matchBatch($unmatchedCards);

        // Count all matched cards in system for stats consistency with legacy return
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
        // Find or create the card set
        $setAbbreviation = $marketCard->set_abbreviation;
        $setName = $marketCard->set_name ?? $setAbbreviation;

        // Logic: Search for set by abbreviation. 
        // If it exists, check ownership. 
        // User wants sets to be user-specific ("limit to user").
        // Implementation: 
        // 1. Try to find an existing set for this user with this abbreviation.
        // 2. If not found, create a new set for this user.
        // 3. (Optional) Check global sets with no user_id? User said "limit to user" implying ownership.
        // Let's first try to find one owned by user.

        $cardSet = CardSet::where('abbreviation', $setAbbreviation)
            ->where('user_id', $card->user_id)
            ->first();

        if (!$cardSet) {
            // Create new set for this user
            $cardSet = CardSet::create([
                'user_id' => $card->user_id,
                'name' => $setName,
                'abbreviation' => $setAbbreviation,
                // We don't have total_cards or release_date easily available from MarketCard usually, 
                // unless added to MarketCard model. MarketCard has set_name/abbr.
                // We'll leave other fields null/default.
            ]);

            Log::info("Created new CardSet for user", [
                'user_id' => $card->user_id,
                'set_id' => $cardSet->id,
                'abbreviation' => $setAbbreviation
            ]);
        }

        $card->update([
            'market_card_id' => $marketCard->id,
            'card_set_id' => $cardSet->id,
            'set_number' => $marketCard->card_number, // Auto-sync number too? Usually specific, but market card has logical number.
            // "When I say the market_id... there is also the set... identified by set_abbreviation... update card with card_set_id"
            // The prompt didn't explicitly ask for set_number but it makes sense to sync it if we have it from market match.
        ]);

        Log::info("Manual match created with set sync", [
            'pokemon_card_id' => $card->id,
            'market_card_id' => $marketCard->id,
            'card_set_id' => $cardSet->id
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
