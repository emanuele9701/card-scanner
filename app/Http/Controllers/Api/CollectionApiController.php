<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PokemonCard;
use App\Models\Game;
use Illuminate\Http\Request;

class CollectionApiController extends Controller
{
    /**
     * Get all cards in user's collection
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cards(Request $request)
    {
        $query = PokemonCard::with([
            'cardSet',
            'game',
            'marketCard.latestPrice'
        ])
            ->where('user_id', $request->user()->id)
            ->where('status', PokemonCard::STATUS_COMPLETED);

        // Apply filters if provided
        if ($request->has('game')) {
            $query->where('game', $request->input('game'));
        }

        if ($request->has('set_id')) {
            $query->where('card_set_id', $request->input('set_id'));
        }

        if ($request->has('rarity')) {
            $query->where('rarity', $request->input('rarity'));
        }

        if ($request->has('condition')) {
            $query->where('condition', $request->input('condition'));
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('card_name', 'LIKE', "%{$search}%");
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['created_at', 'card_name', 'set_number', 'rarity', 'acquisition_date'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min($request->input('per_page', 15), 100); // Max 100 items per page
        $cards = $query->paginate($perPage);

        // Transform the data
        $cardsData = $cards->getCollection()->map(function ($card) {
            return $this->transformCard($card);
        });

        return response()->json([
            'data' => $cardsData,
            'meta' => [
                'current_page' => $cards->currentPage(),
                'last_page' => $cards->lastPage(),
                'per_page' => $cards->perPage(),
                'total' => $cards->total(),
                'from' => $cards->firstItem(),
                'to' => $cards->lastItem(),
            ],
            'links' => [
                'first' => $cards->url(1),
                'last' => $cards->url($cards->lastPage()),
                'prev' => $cards->previousPageUrl(),
                'next' => $cards->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * Get all games (collectibles) in user's collection
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function games(Request $request)
    {
        // Get distinct games from user's cards
        $games = PokemonCard::where('user_id', $request->user()->id)
            ->where('status', PokemonCard::STATUS_COMPLETED)
            ->whereNotNull('game')
            ->select('game')
            ->distinct()
            ->get()
            ->map(function ($item) use ($request) {
                $game = $item->game;

                // Count cards for this game
                $cardCount = PokemonCard::where('user_id', $request->user()->id)
                    ->where('status', PokemonCard::STATUS_COMPLETED)
                    ->where('game', $game)
                    ->count();

                // Count unique sets for this game
                $setCount = PokemonCard::where('user_id', $request->user()->id)
                    ->where('status', PokemonCard::STATUS_COMPLETED)
                    ->where('game', $game)
                    ->whereNotNull('card_set_id')
                    ->distinct('card_set_id')
                    ->count('card_set_id');

                // Calculate total value
                $cards = PokemonCard::with('marketCard.latestPrice')
                    ->where('user_id', $request->user()->id)
                    ->where('status', PokemonCard::STATUS_COMPLETED)
                    ->where('game', $game)
                    ->get();

                $totalValue = $cards->sum(function ($card) {
                    return $card->getEstimatedValue() ?? 0;
                });

                return [
                    'name' => $game,
                    'card_count' => $cardCount,
                    'set_count' => $setCount,
                    'total_value' => round($totalValue, 2),
                ];
            })
            ->values();

        return response()->json([
            'data' => $games,
            'meta' => [
                'total' => $games->count(),
            ],
        ], 200);
    }

    /**
     * Transform a PokemonCard model to API response format
     *
     * @param PokemonCard $card
     * @return array
     */
    private function transformCard(PokemonCard $card): array
    {
        return [
            'id' => $card->id,
            'name' => $card->card_name,
            'hp' => $card->hp,
            'type' => $card->type,
            'evolution_stage' => $card->evolution_stage,
            'attacks' => $card->attacks,
            'weakness' => $card->weakness,
            'resistance' => $card->resistance,
            'retreat_cost' => $card->retreat_cost,
            'rarity' => $card->rarity,
            'set_number' => $card->set_number,
            'illustrator' => $card->illustrator,
            'flavor_text' => $card->flavor_text,
            'game' => $card->game,
            'condition' => $card->condition,
            'printing' => $card->printing,
            'acquisition_price' => $card->acquisition_price,
            'acquisition_date' => $card->acquisition_date?->format('Y-m-d'),
            'image_url' => $card->image_url,
            'set' => $card->cardSet ? [
                'id' => $card->cardSet->id,
                'name' => $card->cardSet->name,
                'abbreviation' => $card->cardSet->abbreviation,
                'release_date' => $card->cardSet->release_date?->format('Y-m-d'),
                'total_cards' => $card->cardSet->total_cards,
            ] : null,
            'market_data' => [
                'has_data' => $card->hasMarketData(),
                'estimated_value' => $card->getEstimatedValue(),
                'profit_loss' => $card->getProfitLoss(),
                'profit_loss_percentage' => $card->getProfitLossPercentage(),
            ],
            'created_at' => $card->created_at?->toIso8601String(),
            'updated_at' => $card->updated_at?->toIso8601String(),
        ];
    }
}
