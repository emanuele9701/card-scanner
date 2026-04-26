<?php

namespace App\Console\Commands;

use App\Models\MarketCard;
use App\Models\ScrapingCard;
use Illuminate\Console\Command;

class LinkScrapingCardsToMarketCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraping:link-market-cards {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collega scraping_cards con market_cards per numero carta e set';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modalità dry-run: nessuna modifica verrà salvata.');
            $this->newLine();
        }

        $unlinked = ScrapingCard::with('expansion')
            ->whereDoesntHave('marketCard')
            ->get();

        if ($unlinked->isEmpty()) {
            $this->info('Tutte le scraping_cards sono già collegate.');
            return 0;
        }

        $this->info("Trovate {$unlinked->count()} scraping_cards non ancora collegate.");
        $this->newLine();

        $linked  = 0;
        $skipped = 0;

        foreach ($unlinked as $scrapingCard) {
            if (!$scrapingCard->expansion) {
                $skipped++;
                continue;
            }

            $marketCard = MarketCard::where('card_number', $scrapingCard->card_number)
                ->where(function ($q) use ($scrapingCard) {
                    // Prova sia abbreviazione che nome del set
                    $q->where('set_abbreviation', $scrapingCard->expansion->abbreviation)
                      ->orWhere('set_name', $scrapingCard->expansion->name);
                })
                ->whereNull('scraping_card_id')
                ->first();

            if ($marketCard) {
                if (!$dryRun) {
                    $marketCard->update(['scraping_card_id' => $scrapingCard->id]);
                }

                $this->line(
                    ($dryRun ? '[DRY-RUN] ' : '')
                    . "Match: [{$scrapingCard->card_number}] {$scrapingCard->name} → MarketCard #{$marketCard->id}"
                );
                $linked++;
            }
        }

        $this->newLine();
        $this->info("Completato: {$linked} carte collegate" . ($skipped ? ", {$skipped} saltate (senza espansione)" : '') . '.');

        if ($dryRun && $linked > 0) {
            $this->newLine();
            $this->comment("Rimuovi --dry-run per applicare le modifiche.");
        }

        return 0;
    }
}
