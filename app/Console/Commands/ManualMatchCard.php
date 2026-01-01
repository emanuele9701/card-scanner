<?php

namespace App\Console\Commands;

use App\Services\CardMatchingService;
use App\Models\PokemonCard;
use App\Models\MarketCard;
use Illuminate\Console\Command;

class ManualMatchCard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cards:match-manual {pokemon_card_id : Pokemon card ID}
                                               {market_card_id : Market card ID to match with}
                                               {--unmatch : Unmatch instead of matching}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually match or unmatch a Pokemon card to/from market data';

    /**
     * Execute the console command.
     */
    public function handle(CardMatchingService $service): int
    {
        $pokemonCardId = $this->argument('pokemon_card_id');
        $marketCardId = $this->argument('market_card_id');

        // Find the Pokemon card
        $pokemonCard = PokemonCard::find($pokemonCardId);
        if (!$pokemonCard) {
            $this->error("Pokemon card with ID {$pokemonCardId} not found.");
            return 1;
        }

        // If unmatch flag is set
        if ($this->option('unmatch')) {
            if (!$pokemonCard->market_card_id) {
                $this->warn('This card is not currently matched to any market card.');
                return 0;
            }

            if ($this->confirm('Are you sure you want to unmatch this card?')) {
                $service->unmatch($pokemonCard);
                $this->info('Card successfully unmatched.');
            } else {
                $this->info('Operation cancelled.');
            }
            return 0;
        }

        // Find the market card
        $marketCard = MarketCard::find($marketCardId);
        if (!$marketCard) {
            $this->error("Market card with ID {$marketCardId} not found.");
            return 1;
        }

        // Show information before matching
        $this->info('Pokemon Card:');
        $this->line("  ID: {$pokemonCard->id}");
        $this->line("  Name: " . ($pokemonCard->card_name ?? 'N/A'));
        $this->line("  Number: " . ($pokemonCard->set_number ?? 'N/A'));
        $this->newLine();

        $this->info('Market Card:');
        $this->line("  ID: {$marketCard->id}");
        $this->line("  Name: {$marketCard->product_name}");
        $this->line("  Number: {$marketCard->card_number}");
        $this->line("  Set: {$marketCard->set_abbreviation}");
        $this->newLine();

        // Confirm matching
        if (!$this->confirm('Do you want to match these cards?')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        // Perform manual matching
        $service->manualMatch($pokemonCard, $marketCard);

        $this->info('Cards matched successfully!');

        // Show estimated value if available
        $estimatedValue = $pokemonCard->fresh()->getEstimatedValue();
        if ($estimatedValue !== null) {
            $this->newLine();
            $this->info(sprintf('Estimated card value: $%.2f', $estimatedValue));
        }

        return 0;
    }
}
