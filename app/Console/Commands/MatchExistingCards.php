<?php

namespace App\Console\Commands;

use App\Services\CardMatchingService;
use Illuminate\Console\Command;

class MatchExistingCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cards:match
                            {--force : Force re-matching of already matched cards}
                            {--report : Show detailed report of unmatched cards}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Match existing Pokemon cards to market data';

    /**
     * Execute the console command.
     */
    public function handle(CardMatchingService $service): int
    {
        $this->info('Starting card matching process...');
        $this->newLine();

        // If force flag is set, reset all matches first
        if ($this->option('force')) {
            $this->warn('Force flag detected: resetting all existing matches...');
            \App\Models\PokemonCard::query()->update(['market_card_id' => null]);
            $this->info('All matches reset.');
            $this->newLine();
        }

        // Create progress bar
        $unmatchedCount = \App\Models\PokemonCard::whereNull('market_card_id')->count();

        if ($unmatchedCount === 0) {
            $this->info('No unmatched cards found. All cards are already matched!');
            $this->newLine();

            if ($this->option('report')) {
                $this->showMatchedCardsStats();
            }

            return 0;
        }

        $this->info("Found {$unmatchedCount} unmatched cards to process.");
        $bar = $this->output->createProgressBar($unmatchedCount);
        $bar->start();

        // Perform matching
        $stats = $service->matchAllUnmatched();

        $bar->finish();
        $this->newLine(2);

        // Display results
        $this->info('Matching process completed!');
        $this->newLine();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Cards Processed', $stats['processed']],
                ['Successfully Matched', $stats['matched']],
                ['Not Matched', $stats['unmatched']],
                ['Already Matched', $stats['already_matched']],
            ]
        );

        // Calculate match rate
        $totalCards = $stats['matched'] + $stats['already_matched'] + $stats['unmatched'];
        $totalMatched = $stats['matched'] + $stats['already_matched'];
        $matchRate = $totalCards > 0 ? ($totalMatched / $totalCards) * 100 : 0;

        $this->newLine();
        $this->info(sprintf('Overall Match Rate: %.2f%%', $matchRate));

        // Show detailed report if requested
        if ($this->option('report') && $stats['unmatched'] > 0) {
            $this->newLine();
            $this->showUnmatchedReport($service);
        }

        // Show warning if many cards are unmatched
        if ($stats['unmatched'] > 0) {
            $this->newLine();
            $this->warn("{$stats['unmatched']} cards could not be matched automatically.");
            $this->info('Use --report flag to see detailed information about unmatched cards.');
            $this->info('You can manually match these cards through the web interface.');
        }

        return 0;
    }

    /**
     * Show detailed report of unmatched cards
     */
    private function showUnmatchedReport(CardMatchingService $service): void
    {
        $this->warn('Unmatched Cards Report:');
        $this->newLine();

        $unmatchedCards = $service->getUnmatchedCardsReport();

        if ($unmatchedCards->isEmpty()) {
            $this->info('No unmatched cards to report.');
            return;
        }

        $headers = ['ID', 'Card Name', 'Number', 'Rarity', 'Set'];
        $rows = $unmatchedCards->map(function ($card) {
            return [
                $card['id'],
                $card['card_name'] ?? 'N/A',
                $card['set_number'] ?? 'N/A',
                $card['rarity'] ?? 'N/A',
                $card['set_abbreviation'] ?? 'N/A',
            ];
        })->toArray();

        $this->table($headers, $rows);
    }

    /**
     * Show statistics about matched cards
     */
    private function showMatchedCardsStats(): void
    {
        $totalCards = \App\Models\PokemonCard::count();
        $matchedCards = \App\Models\PokemonCard::whereNotNull('market_card_id')->count();
        $matchRate = $totalCards > 0 ? ($matchedCards / $totalCards) * 100 : 0;

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cards in Collection', $totalCards],
                ['Matched to Market Data', $matchedCards],
                ['Match Rate', sprintf('%.2f%%', $matchRate)],
            ]
        );
    }
}
