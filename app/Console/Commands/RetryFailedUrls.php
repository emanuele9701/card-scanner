<?php

namespace App\Console\Commands;

use App\Jobs\ProcessScrapingPageJob;
use App\Models\ScrapingPageQueue;
use Illuminate\Console\Command;

class RetryFailedUrls extends Command
{
    protected $signature = 'scraping:retry-failed
                            {--all : Rimetti in coda tutti i falliti}
                            {--id= : Rimetti in coda un singolo record per ID}
                            {--stale : Recupera anche i job "processing" bloccati da più di 10 minuti}
                            {--dry-run : Mostra cosa verrebbe fatto senza eseguire}';

    protected $description = 'Recupera i job di scraping falliti e li rimette in coda per essere rielaborati';

    public function handle(): int
    {
        $query = ScrapingPageQueue::query();

        // Filtro per ID singolo
        if ($id = $this->option('id')) {
            $query->where('id', $id)
                  ->whereIn('status', ['failed', 'processing']);
        } elseif ($this->option('all')) {
            // Tutti i failed
            $query->where('status', 'failed');

            // Opzionalmente anche i processing bloccati
            if ($this->option('stale')) {
                $query->orWhere(function ($q) {
                    $q->where('status', 'processing')
                      ->where('processed_at', '<', now()->subMinutes(10));
                });
            }
        } else {
            $this->error('Specifica --id=X oppure --all');
            return 1;
        }

        $records = $query->get();

        if ($records->isEmpty()) {
            $this->info('Nessun record da recuperare.');
            return 0;
        }

        // Mostra la tabella dei record trovati
        $this->table(
            ['ID', 'URL', 'Status', 'Ultimo errore'],
            $records->map(fn ($r) => [
                $r->id,
                \Illuminate\Support\Str::limit($r->url, 80),
                $r->status->value,
                \Illuminate\Support\Str::limit($r->last_error_message ?? '-', 50),
            ])
        );

        if ($this->option('dry-run')) {
            $this->warn("[DRY-RUN] Nessuna azione eseguita. {$records->count()} record verrebbero rimessi in coda.");
            return 0;
        }

        $count = 0;
        foreach ($records as $record) {
            $record->update([
                'status'             => 'pending',
                'last_error_message' => null,
            ]);

            ProcessScrapingPageJob::dispatch($record->id)->onQueue('scraping');
            $count++;
        }

        $this->info("✅ {$count} record rimessi in pending e accodati sulla queue 'scraping'.");
        return 0;
    }
}
