<?php

namespace App\Console\Commands;

use App\Services\CardMatchingService;
use App\Models\PokemonCard;
use Illuminate\Console\Command;

class SuggestCardMatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cards:suggest {card_id : The ID of the Pokemon card to suggest matches for}
                                         {--limit=5 : Number of suggestions to show}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Suggest market card matches for a Pokemon card';

    /**
     * Execute the console command.
     */
    public function handle(CardMatchingService $service): int
    {
        $cardId = $this->argument('card_id');
        $limit = (int) $this->option('limit');

        // Find the Pokemon card
        $card = PokemonCard::find($cardId);

        if (!$card) {
            $this->error("Pokemon card with ID {$cardId} not found.");
            return 1;
        }

        // Show card information
        $this->info('Pokemon Card Information:');
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $card->id],
                ['Name', $card->card_name ?? 'N/A'],
                ['Number', $card->set_number ?? 'N/A'],
                ['Rarity', $card->rarity ?? 'N/A'],
                ['Set', $card->cardSet?->name ?? 'N/A'],
                ['Current Match', $card->market_card_id ? "Yes (ID: {$card->market_card_id})" : 'No'],
            ]
        );

        $this->newLine();

        // Get suggestions
        $this->info("Fetching top {$limit} suggestions...");
        $suggestions = $service->suggestMatches($card, $limit);

        if ($suggestions->isEmpty()) {
            $this->warn('No suggestions found for this card.');
            return 0;
        }

        $this->info("Found {$suggestions->count()} suggestions:");
        $this->newLine();

        // Display suggestions
        $headers = ['Market ID', 'Product Name', 'Number', 'Set', 'Rarity', 'Latest Price'];
        $rows = $suggestions->map(function ($marketCard) {
            $latestPrice = $marketCard->latestPrice;
            $priceInfo = $latestPrice
                ? sprintf('$%.2f (%s)', $latestPrice->market_price, $latestPrice->condition)
                : 'N/A';

            return [
                $marketCard->id,
                $marketCard->product_name,
                $marketCard->card_number,
                $marketCard->set_abbreviation,
                $marketCard->rarity,
                $priceInfo,
            ];
        })->toArray();

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('To manually match this card, use: php artisan cards:match-manual ' . $card->id . ' [market_card_id]');

        return 0;
    }
}
