<?php

namespace App\Jobs;

use App\Models\ScrapingPageQueue;
use App\Services\CardmarketScraper;
use App\Services\Scraping\ScrapingDataPersistenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessScrapingPageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero massimo di tentativi prima di marcare come failed.
     */
    public int $tries = 3;

    /**
     * Secondi di attesa tra un retry e l'altro.
     */
    public int $backoff = 60;

    public function __construct(
        private readonly int $queueId
    ) {}

    public function handle(
        CardmarketScraper $scraper,
        ScrapingDataPersistenceService $persistence
    ): void {
        $queueItem = ScrapingPageQueue::findOrFail($this->queueId);

        // Marca come "in lavorazione"
        $queueItem->update([
            'status'       => 'processing',
            'processed_at' => now(),
        ]);

        try {
            // Rate limiter: attesa random tra 10 e 20 secondi per evitare ban Cloudflare
            $delay = rand(10, 20);
            Log::debug("[ProcessScrapingPage] Attesa {$delay}s prima dello scraping...", ['url' => $queueItem->url]);
            sleep($delay);

            // 1. Scarica l'HTML della pagina prodotto
            $html = $scraper->scrapeFromUrl($queueItem->url);

            Storage::disk('local')->put('scraping_html_raw_' . basename($queueItem->url) . "html", $html);
            // 2. Parsa l'HTML per estrarre product_info e offers
            $data = $scraper->parseHtml($html);

            // Validazione: se il parsing non ha trovato né info prodotto né offerte,
            // l'HTML probabilmente non è la pagina prodotto (es. challenge Cloudflare)
            $hasProductInfo = !empty($data['product_info']['numero']);
            $hasOffers      = $data['offers']->isNotEmpty();

            if (!$hasProductInfo && !$hasOffers) {
                Log::warning('[ProcessScrapingPage] HTML ricevuto ma parsing vuoto (possibile pagina Cloudflare)', [
                    'url'         => $queueItem->url,
                    'html_length' => strlen($html),
                ]);
                throw new \RuntimeException(
                    "Parsing vuoto per {$queueItem->url}: nessun dato prodotto né offerte trovati nell'HTML."
                );
            }

            // 3. Persisti i dati (carta, snapshot, offerte, link)
            $persistence->saveScrapedCard($data, $queueItem);

            $queueItem->update(['status' => 'completed']);

            Log::info('[ProcessScrapingPage] Completato', ['url' => $queueItem->url]);
        } catch (Throwable $e) {
            Log::error('[ProcessScrapingPage] Scraping fallito', [
                'url'     => $queueItem->url,
                'error'   => $e->getMessage(),
                'attempt' => $this->attempts(),
                'max'     => $this->tries,
            ]);

            // Se ci sono ancora tentativi disponibili, mantieni lo status 'pending'
            // così il record resta coerente con il fatto che verrà ri-provato
            if ($this->attempts() < $this->tries) {
                $queueItem->update([
                    'status'             => 'pending',
                    'last_error_message' => "Tentativo {$this->attempts()}/{$this->tries}: " . $e->getMessage(),
                ]);
            } else {
                // Ultimo tentativo: marca come failed
                $queueItem->update([
                    'status'             => 'failed',
                    'last_error_message' => $e->getMessage(),
                ]);
            }

            throw $e; // permette il retry automatico
        }
    }

    /**
     * Dopo tutti i retry esauriti, marca definitivamente come failed.
     */
    public function failed(Throwable $e): void
    {
        ScrapingPageQueue::where('id', $this->queueId)
            ->update([
                'status'             => 'failed',
                'last_error_message' => 'Tutti i tentativi esauriti: ' . $e->getMessage(),
            ]);
    }
}
