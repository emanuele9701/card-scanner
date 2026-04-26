<?php

namespace App\Console\Commands;

use App\Jobs\ProcessScrapingPageJob;
use App\Models\ScrapingCard;
use App\Models\ScrapingExpansion;
use App\Models\ScrapingPageQueue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryMissingCards extends Command
{
    protected $signature = 'scraping:retry-missing
                            {--expansion= : ID dell\'espansione da controllare}
                            {--all : Controlla tutte le espansioni}
                            {--dry-run : Mostra cosa verrebbe fatto senza eseguire}';

    protected $description = 'Trova le carte mancanti (buchi nella numerazione) e rimette in coda le URL corrispondenti';

    public function handle(): int
    {
        if ($this->option('expansion')) {
            $expansions = ScrapingExpansion::where('id', $this->option('expansion'))->get();
        } elseif ($this->option('all')) {
            $expansions = ScrapingExpansion::whereHas('users')->get();
        } else {
            $this->error('Specifica --expansion=ID oppure --all');
            return 1;
        }

        if ($expansions->isEmpty()) {
            $this->warn('Nessuna espansione trovata.');
            return 0;
        }

        $totalRetried = 0;

        foreach ($expansions as $expansion) {
            $this->info("📦 Espansione: {$expansion->name} (ID: {$expansion->id})");

            // Recupera i numeri delle carte esistenti per questa espansione
            $existingNumbers = ScrapingCard::where('scraping_expansion_id', $expansion->id)
                ->pluck('card_number')
                ->map(fn($n) => intval($n))
                ->sort()
                ->values()
                ->toArray();

            if (empty($existingNumbers)) {
                $this->warn("   Nessuna carta trovata per questa espansione, skip.");
                continue;
            }

            $maxNumber = max($existingNumbers);
            $allNumbers = range(1, $maxNumber);
            $missingNumbers = array_values(array_diff($allNumbers, $existingNumbers));

            if (empty($missingNumbers)) {
                $this->info("   ✅ Nessun buco trovato ({$maxNumber} carte complete).");
                continue;
            }

            $this->warn("   Carte: {$this->countExisting($existingNumbers)}/{$maxNumber} — Mancanti: " . count($missingNumbers));

            // Per ogni numero mancante, cerca l'URL nella coda
            $retried = 0;
            $abbreviation = $expansion->abbreviation ?? '';

            foreach ($missingNumbers as $num) {
                $suffix = str_pad($num, 3, '0', STR_PAD_LEFT);

                // Cerca l'URL che finisce col suffisso della carta (es. ASC004, BRS004, ecc.)
                $queueItem = ScrapingPageQueue::where('url', 'LIKE', "%{$suffix}")
                    ->orWhere('url', 'LIKE', "%{$suffix}%")
                    ->when($abbreviation, function ($q) use ($abbreviation, $suffix) {
                        // Se c'è l'abbreviazione, cerchiamo specificamente per essa
                        $q->where('url', 'LIKE', "%{$abbreviation}{$suffix}%");
                    })
                    ->first();

                if (!$queueItem) {
                    $this->line("   [{$suffix}] ⚠️  Nessun URL trovato nella coda");
                    continue;
                }

                $statusLabel = $queueItem->status->value;

                if ($this->option('dry-run')) {
                    $this->line("   [{$suffix}] 🔍 {$statusLabel} → verrebbe rimesso in pending | {$queueItem->url}");
                    $retried++;
                    continue;
                }

                // Resetta a pending e dispatcha il job
                $queueItem->update([
                    'status'             => 'pending',
                    'last_error_message' => null,
                ]);

                ProcessScrapingPageJob::dispatch($queueItem->id)->onQueue('scraping');
                $this->line("   [{$suffix}] 🔄 {$statusLabel} → pending | {$queueItem->url}");
                $retried++;
            }

            $totalRetried += $retried;
            $this->newLine();
        }

        if ($this->option('dry-run')) {
            $this->warn("[DRY-RUN] {$totalRetried} URL verrebbero rimessi in coda.");
        } else {
            $this->info("✅ {$totalRetried} URL rimessi in pending e accodati sulla queue 'scraping'.");
            Log::info("[RetryMissingCards] Rimessi in coda {$totalRetried} URL per carte mancanti.");
        }

        return 0;
    }

    private function countExisting(array $numbers): int
    {
        return count($numbers);
    }
}
