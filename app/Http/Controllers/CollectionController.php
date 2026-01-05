<?php

namespace App\Http\Controllers;

use App\Models\PokemonCard;
use App\Models\CardSet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    /**
     * Show collection value dashboard
     */
    public function value(): Response
    {
        $cards = PokemonCard::with([
            'cardSet',
            'marketCard.latestPrice'
        ])
            ->where('user_id', auth()->id())
            ->where('status', PokemonCard::STATUS_COMPLETED)
            ->get();

        $stats = $this->calculateCollectionStats($cards);

        $cardsWithValue = $cards->map(function ($card) {
            // Get available conditions from the matched market card
            $availableConditions = [];
            if ($card->hasMarketData() && !$card->condition && $card->marketCard) {
                // First try to get conditions for the specific printing
                $availableConditions = $card->marketCard->prices()
                    ->where('printing', $card->printing ?? 'Normal')
                    ->distinct()
                    ->pluck('condition')
                    ->toArray();

                // If no conditions found for this printing, get ALL available conditions
                if (empty($availableConditions)) {
                    $availableConditions = $card->marketCard->prices()
                        ->distinct()
                        ->pluck('condition')
                        ->toArray();
                }
            }

            return [
                'id' => $card->id,
                'name' => $card->card_name,
                'number' => $card->set_number,
                'set' => $card->cardSet?->name,
                'set_abbr' => $card->cardSet?->abbreviation,
                'rarity' => $card->rarity,
                'condition' => $card->condition,
                'printing' => $card->printing,
                'acquisition_price' => $card->acquisition_price,
                'acquisition_date' => $card->acquisition_date ? $card->acquisition_date->format('Y-m-d') : null,
                'estimated_value' => $card->getEstimatedValue(),
                'profit_loss' => $card->getProfitLoss(),
                'profit_loss_percentage' => $card->getProfitLossPercentage(),
                'has_market_data' => $card->hasMarketData(),
                'available_conditions' => $availableConditions,
                'image' => $card->getImageUrl(),
                'game' => $card->game,
            ];
        });

        // Get unique games and sets for filters
        $availableGames = PokemonCard::where('user_id', auth()->id())
            ->where('status', PokemonCard::STATUS_COMPLETED)
            ->distinct()
            ->whereNotNull('game')
            ->orderBy('game')
            ->pluck('game');

        $availableSets = PokemonCard::with('cardSet')
            ->where('user_id', auth()->id())
            ->where('status', PokemonCard::STATUS_COMPLETED)
            ->whereNotNull('card_set_id')
            ->get()
            ->pluck('cardSet.abbreviation')
            ->unique()
            ->filter()
            ->sort()
            ->values();

        return Inertia::render('Collection/Value', [
            'stats' => $stats,
            'cards' => $cardsWithValue,
            'availableGames' => $availableGames,
            'availableSets' => $availableSets,
        ]);
    }

    /**
     * Show collection overview grouped by sets
     */
    public function index(): Response
    {
        $sets = CardSet::withCount('pokemonCards')->get();

        $cards = PokemonCard::with(['cardSet', 'marketCard'])
            ->where('user_id', auth()->id())
            ->get()
            ->groupBy('card_set_id');

        $setsWithCards = $sets->map(function ($set) use ($cards) {
            $setCards = $cards->get($set->id, collect());

            return [
                'id' => $set->id,
                'name' => $set->name,
                'abbreviation' => $set->abbreviation,
                'total_cards' => $set->total_cards,
                'owned_cards' => $setCards->count(),
                'completion_percentage' => $set->total_cards > 0
                    ? ($setCards->count() / $set->total_cards) * 100
                    : 0,
                'total_value' => $setCards->sum(fn($card) => $card->getEstimatedValue() ?? 0),
            ];
        });

        return Inertia::render('Collection/Index', [
            'sets' => $setsWithCards,
        ]);
    }

    /**
     * Calculate collection statistics
     */
    private function calculateCollectionStats($cards): array
    {
        $totalCards = $cards->count();
        $cardsWithMarketData = $cards->filter(fn($card) => $card->hasMarketData())->count();

        $totalValue = 0;
        $totalCost = 0;
        $cardsWithValue = 0;
        $cardsWithCost = 0;

        foreach ($cards as $card) {
            $value = $card->getEstimatedValue();
            if ($value !== null) {
                $totalValue += $value;
                $cardsWithValue++;
            }

            if ($card->acquisition_price) {
                $totalCost += $card->acquisition_price;
                $cardsWithCost++;
            }
        }

        $totalProfitLoss = $totalValue - $totalCost;
        $profitLossPercentage = $totalCost > 0
            ? ($totalProfitLoss / $totalCost) * 100
            : 0;

        return [
            'total_cards' => $totalCards,
            'cards_with_market_data' => $cardsWithMarketData,
            'cards_without_market_data' => $totalCards - $cardsWithMarketData,
            'total_value' => round($totalValue, 2),
            'total_cost' => round($totalCost, 2),
            'cards_with_value' => $cardsWithValue,
            'cards_with_cost' => $cardsWithCost,
            'average_value' => $cardsWithValue > 0
                ? round($totalValue / $cardsWithValue, 2)
                : 0,
            'total_profit_loss' => round($totalProfitLoss, 2),
            'profit_loss_percentage' => round($profitLossPercentage, 2),
            'match_rate' => $totalCards > 0
                ? round(($cardsWithMarketData / $totalCards) * 100, 2)
                : 0,
        ];
    }
}
