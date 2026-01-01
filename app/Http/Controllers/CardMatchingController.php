<?php

namespace App\Http\Controllers;

use App\Models\PokemonCard;
use App\Models\MarketCard;
use App\Services\CardMatchingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CardMatchingController extends Controller
{
    public function __construct(
        private CardMatchingService $matchingService
    ) {}

    /**
     * Show cards matching interface
     */
    public function index(): Response
    {
        $unmatchedCards = PokemonCard::with('cardSet')
            ->whereNull('market_card_id')
            ->get()
            ->map(function ($card) {
                return [
                    'id' => $card->id,
                    'name' => $card->card_name,
                    'number' => $card->set_number,
                    'set' => $card->cardSet?->name,
                    'set_abbreviation' => $card->cardSet?->abbreviation,
                    'rarity' => $card->rarity,
                    'image' => $card->storage_path,
                ];
            });

        $stats = [
            'total_cards' => PokemonCard::count(),
            'matched_cards' => PokemonCard::whereNotNull('market_card_id')->count(),
            'unmatched_cards' => PokemonCard::whereNull('market_card_id')->count(),
        ];

        return Inertia::render('Matching/Index', [
            'unmatchedCards' => $unmatchedCards,
            'stats' => $stats,
        ]);
    }

    /**
     * Auto-match all unmatched cards
     */
    public function autoMatch()
    {
        $stats = $this->matchingService->matchAllUnmatched();

        return back()->with([
            'success' => 'Auto-matching completato!',
            'match_stats' => $stats,
        ]);
    }

    /**
     * Get suggestions for a specific card
     */
    public function suggestions(PokemonCard $card)
    {
        $suggestions = $this->matchingService->suggestMatches($card, 10);

        return response()->json([
            'card' => [
                'id' => $card->id,
                'name' => $card->card_name,
                'number' => $card->set_number,
                'set' => $card->cardSet?->name,
                'image' => $card->storage_path,
            ],
            'suggestions' => $suggestions->map(function ($marketCard) {
                $latestPrice = $marketCard->latestPrice;
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
     */
    public function match(Request $request, PokemonCard $card)
    {
        $request->validate([
            'market_card_id' => 'required|exists:market_cards,id',
        ]);

        $marketCard = MarketCard::findOrFail($request->market_card_id);
        $this->matchingService->manualMatch($card, $marketCard);

        return back()->with('success', 'Carta matchata con successo!');
    }

    /**
     * Unmatch a card
     */
    public function unmatch(PokemonCard $card)
    {
        $this->matchingService->unmatch($card);

        return back()->with('success', 'Match rimosso!');
    }
}
