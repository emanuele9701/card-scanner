<?php

namespace App\Console\Commands;

use App\Jobs\DispatchExpansionScrapingJob;
use App\Models\ScrapingExpansion;
use Illuminate\Console\Command;

class CardMarketScraping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:card-market-scraping
                            {--expansion= : ID specifico di un\'espansione da scrapare}
                            {--sync : Esegue il job in modo sincrono (utile per debug)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatcha i job di scraping Cardmarket per le espansioni configurate';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expansionId = $this->option('expansion');
        $sync        = $this->option('sync');

        if ($expansionId) {
            // Singola espansione
            $expansion = ScrapingExpansion::find($expansionId);
            if (!$expansion) {
                $this->error("Espansione con ID {$expansionId} non trovata.");
                return 1;
            }

            $expansions = collect([$expansion]);
        } else {
            // Tutte le espansioni con almeno un utente associato
            $expansions = ScrapingExpansion::whereHas('users')->get();
        }

        if ($expansions->isEmpty()) {
            $this->warn('Nessuna espansione trovata con utenti associati.');
            return 0;
        }

        $this->info("Dispatching scraping per {$expansions->count()} espansione/i...");

        foreach ($expansions as $expansion) {
            $this->line("  → {$expansion->name} (ID: {$expansion->id})");

            if ($sync) {
                // Esecuzione sincrona: il job viene eseguito immediatamente in-process
                $this->line("    [SYNC] Esecuzione in corso...");
                DispatchExpansionScrapingJob::dispatchSync($expansion->id);
                $this->info("    [SYNC] Completato.");
            } else {
                // Esecuzione asincrona: il job viene messo in coda
                DispatchExpansionScrapingJob::dispatch($expansion->id)
                    ->onQueue('scraping');
                $this->line("    [QUEUE] Job dispatchato sulla coda 'scraping'.");
            }
        }

        $this->newLine();
        $this->info('Fatto! ' . ($sync
            ? 'Tutti i job sono stati eseguiti.'
            : 'I job sono in coda. Avvia il worker con: php artisan queue:work --queue=scraping'
        ));

        return 0;
    }
}