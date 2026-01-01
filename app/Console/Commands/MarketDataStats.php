<?php

namespace App\Console\Commands;

use App\Services\MarketDataImportService;
use Illuminate\Console\Command;

class MarketDataStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'market:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display statistics about market data';

    /**
     * Execute the console command.
     */
    public function handle(MarketDataImportService $service): int
    {
        $this->info('Market Data Statistics');
        $this->newLine();

        $stats = $service->getStats();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Sets', number_format($stats['total_sets'])],
                ['Total Market Cards', number_format($stats['total_cards'])],
                ['Total Price Records', number_format($stats['total_prices'])],
                ['Latest Import Date', $stats['latest_import'] ?? 'N/A'],
                ['Total Import Sessions', $stats['unique_import_dates']],
            ]
        );

        return 0;
    }
}
