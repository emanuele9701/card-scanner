<?php

namespace App\Console\Commands;

use App\Services\MarketDataImportService;
use Illuminate\Console\Command;

class ImportMarketData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'market:import {file : Path to JSON file containing market data}
                                          {--cleanup : Clean up old price records after import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import market data from JSON file';

    /**
     * Execute the console command.
     */
    public function handle(MarketDataImportService $service): int
    {
        $filePath = $this->argument('file');

        // Check if file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info('Reading JSON file...');
        $jsonContent = file_get_contents($filePath);
        $jsonData = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: ' . json_last_error_msg());
            return 1;
        }

        if (!isset($jsonData['result']) || !is_array($jsonData['result'])) {
            $this->error('Invalid JSON structure. Expected "result" array.');
            return 1;
        }

        $count = $jsonData['count'] ?? count($jsonData['result']);
        $this->info("Processing {$count} records...");

        // Create progress bar
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        try {
            $stats = $service->importFromJson($jsonData['result']);
            $bar->finish();
            $this->newLine(2);

            // Display results
            $this->info('Import completed successfully!');
            $this->newLine();

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Records', $stats['total']],
                    ['Sets Created', $stats['sets_created']],
                    ['Cards Created', $stats['cards_created']],
                    ['Cards Updated', $stats['cards_updated']],
                    ['Prices Created', $stats['prices_created']],
                    ['Errors', count($stats['errors'])],
                ]
            );

            if (!empty($stats['errors'])) {
                $this->newLine();
                $this->warn('Errors encountered during import:');
                foreach ($stats['errors'] as $error) {
                    $this->line("  - {$error}");
                }
            }

            // Optional cleanup
            if ($this->option('cleanup')) {
                $this->newLine();
                $this->info('Cleaning up old price records...');
                $deleted = $service->cleanupOldPrices(12);
                $this->info("Deleted {$deleted} old price records.");
            }

            return 0;
        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}
