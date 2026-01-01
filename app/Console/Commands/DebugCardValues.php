<?php

namespace App\Console\Commands;

use App\Models\PokemonCard;
use Illuminate\Console\Command;

class DebugCardValues extends Command
{
    protected $signature = 'cards:debug-values';
    protected $description = 'Debug card values and matching';

    public function handle()
    {
        $cards = PokemonCard::with(['marketCard.prices', 'cardSet'])->get();

        $this->info("Total cards: " . $cards->count());
        $this->newLine();

        foreach ($cards as $card) {
            $this->info("Card: {$card->card_name} ({$card->set_number})");
            $this->line("  Set ID: {$card->card_set_id}");
            $this->line("  Condition: {$card->condition}");
            $this->line("  Printing: {$card->printing}");
            $this->line("  Market Card ID: " . ($card->market_card_id ?? 'NULL'));

            if ($card->marketCard) {
                $this->line("  Market Card: {$card->marketCard->product_name}");
                $pricesCount = $card->marketCard->prices->count();
                $this->line("  Available Prices: {$pricesCount}");

                if ($pricesCount > 0) {
                    $this->line("  Price breakdown:");
                    foreach ($card->marketCard->prices->take(3) as $price) {
                        $this->line("    - {$price->condition} / {$price->printing}: \${$price->market_price}");
                    }
                }
            }

            $estimatedValue = $card->getEstimatedValue();
            $this->line("  Estimated Value: " . ($estimatedValue ? '$' . $estimatedValue : 'NULL'));
            $this->newLine();
        }

        return 0;
    }
}
