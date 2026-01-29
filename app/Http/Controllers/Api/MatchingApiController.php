<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PokemonCard;
use App\Models\MarketCard;
use App\Services\CardMatchingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MatchingApiController extends Controller
{
    public function __construct(
        private CardMatchingService $matchingService
    ) {}

    /**
     * Get suggestions for a specific card
     *
     * @param PokemonCard $card
     * @return JsonResponse
     */
    public function suggestions(PokemonCard $card): JsonResponse
    {
        if ($card->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $suggestions = $this->matchingService->suggestMatches($card, 10);

        return response()->json([
            'card' => [
                'id' => $card->id,
                'name' => $card->card_name,
                'number' => $card->set_number,
                'set' => $card->cardSet?->name,
                'image' => $card->getImageUrl(),
            ],
            'suggestions' => $suggestions->map(function ($marketCard) {
                $latestPrice = $marketCard->latestPrice; // Assuming relationship exists as in original controller
                return [
                    'id' => $marketCard->id,
                    'name' => $marketCard->product_name,
                    'number' => $marketCard->card_number,
                    'set' => $marketCard->set_abbreviation,
                    'rarity' => $marketCard->rarity,
                    'price' => $latestPrice ? [
                        'market' => $latestPrice->market_price,
                        'low' => $latestPrice->low_price,
                        'condition' => $latestPrice->condition,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Manually match a card
     *
     * @param Request $request
     * @param PokemonCard $card
     * @return JsonResponse
     */
    public function match(Request $request, PokemonCard $card): JsonResponse
    {
        if ($card->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'market_card_id' => 'required|exists:market_cards,id',
        ]);

        $marketCard = MarketCard::findOrFail($request->market_card_id);

        try {
            $this->matchingService->manualMatch($card, $marketCard);

            return response()->json([
                'message' => 'Card matched successfully',
                'matched_card' => [
                    'id' => $card->id,
                    'market_card_id' => $marketCard->id,
                    'status' => 'matched'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error matching card: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Auto-match all single user's unmatched cards or specific cards if IDs provided
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function autoMatch(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PokemonCard::where('user_id', $user->id)
            ->whereNull('market_card_id');

        // Filter by specific IDs if provided
        if ($request->has('card_ids')) {
            $ids = $request->input('card_ids');
            if (is_array($ids) && !empty($ids)) {
                $query->whereIn('id', $ids);
            }
        }

        $unmatchedCards = $query->get();

        // Use the service's batch matching logic
        $stats = $this->matchingService->matchBatch($unmatchedCards);

        return response()->json([
            'message' => 'Auto-match completed',
            'stats' => $stats
        ]);
    }
}
