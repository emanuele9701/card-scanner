<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardSet;
use App\Models\PokemonCard;
use Illuminate\Http\Request;

class CardSetApiController extends Controller
{
    /**
     * Get all card sets
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = CardSet::query();

        // Filter by game if provided
        if ($request->has('game')) {
            // Get set IDs that have cards of the specified game
            $setIds = PokemonCard::where('game', $request->input('game'))
                ->whereNotNull('card_set_id')
                ->distinct('card_set_id')
                ->pluck('card_set_id');

            $query->whereIn('id', $setIds);
        }

        // Search by name or abbreviation
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('abbreviation', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'release_date');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['name', 'abbreviation', 'release_date', 'total_cards'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min($request->input('per_page', 20), 100);
        $sets = $query->paginate($perPage);

        // Transform the data and include user's collection stats
        $userId = $request->user()->id;
        $setsData = $sets->getCollection()->map(function ($set) use ($userId) {
            return $this->transformSet($set, $userId);
        });

        return response()->json([
            'data' => $setsData,
            'meta' => [
                'current_page' => $sets->currentPage(),
                'last_page' => $sets->lastPage(),
                'per_page' => $sets->perPage(),
                'total' => $sets->total(),
                'from' => $sets->firstItem(),
                'to' => $sets->lastItem(),
            ],
            'links' => [
                'first' => $sets->url(1),
                'last' => $sets->url($sets->lastPage()),
                'prev' => $sets->previousPageUrl(),
                'next' => $sets->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * Get a specific card set with details
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id)
    {
        $set = CardSet::findOrFail($id);
        $userId = $request->user()->id;

        // Get user's cards in this set
        $userCards = PokemonCard::with(['marketCard.latestPrice'])
            ->where('user_id', $userId)
            ->where('card_set_id', $id)
            ->where('status', PokemonCard::STATUS_COMPLETED)
            ->get();

        $setData = $this->transformSet($set, $userId);

        // Add detailed card list
        $setData['cards'] = $userCards->map(function ($card) {
            return [
                'id' => $card->id,
                'name' => $card->card_name,
                'set_number' => $card->set_number,
                'rarity' => $card->rarity,
                'condition' => $card->condition,
                'printing' => $card->printing,
                'image_url' => $card->image_url,
                'estimated_value' => $card->getEstimatedValue(),
                'has_market_data' => $card->hasMarketData(),
            ];
        })->values();

        return response()->json([
            'data' => $setData,
        ], 200);
    }

    /**
     * Transform a CardSet model to API response format
     *
     * @param CardSet $set
     * @param int $userId
     * @return array
     */
    private function transformSet(CardSet $set, int $userId): array
    {
        // Get user's cards for this set
        $userCards = PokemonCard::with(['marketCard.latestPrice'])
            ->where('user_id', $userId)
            ->where('card_set_id', $set->id)
            ->where('status', PokemonCard::STATUS_COMPLETED)
            ->get();

        $ownedCount = $userCards->count();
        $completionPercentage = $set->total_cards > 0
            ? ($ownedCount / $set->total_cards) * 100
            : 0;

        // Calculate total value
        $totalValue = $userCards->sum(function ($card) {
            return $card->getEstimatedValue() ?? 0;
        });

        return [
            'id' => $set->id,
            'name' => $set->name,
            'abbreviation' => $set->abbreviation,
            'release_date' => $set->release_date?->format('Y-m-d'),
            'total_cards' => $set->total_cards,
            'collection_stats' => [
                'owned_cards' => $ownedCount,
                'completion_percentage' => round($completionPercentage, 2),
                'total_value' => round($totalValue, 2),
            ],
            'created_at' => $set->created_at?->toIso8601String(),
            'updated_at' => $set->updated_at?->toIso8601String(),
        ];
    }
}
