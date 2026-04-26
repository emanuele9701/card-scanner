<?php

namespace App\Jobs;

use App\Models\ScrapingExpansion;
use App\Models\ScrapingPageQueue;
use App\Services\CardmarketScraper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

class DispatchExpansionScrapingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $expansionId
    ) {}

    public function handle(CardmarketScraper $scraper): void
    {
        $expansion = ScrapingExpansion::with('scraperProvider')->findOrFail($this->expansionId);
        $provider  = $expansion->scraperProvider;

        if (!$provider || $provider->name !== 'CardMarket') {
            Log::warning("[DispatchExpansionScraping] Provider non Cardmarket per espansione #{$this->expansionId}, skip.");
            return;
        }

        $baseUrl       = $provider->base_url;
        $expansionSlug = $expansion->getUrlEncodedName();

        // URL della gallery (prima pagina)
        $galleryBaseUrl = $baseUrl
            . 'en/Pokemon/Products/Singles/'
            . $expansionSlug
            . '?idRarity=0&sortBy=collectorsnumber_asc&perSite=100';

        Log::info("[DispatchExpansionScraping] Raccolta URL per espansione: {$expansion->name}", [
            'gallery_url' => $galleryBaseUrl,
        ]);

        // Raccogli tutti gli URL delle carte con paginazione completa
        $cardUrls = $this->collectAllCardUrls($scraper, $galleryBaseUrl, $baseUrl);

        Log::info("[DispatchExpansionScraping] Trovate {$cardUrls->count()} carte per {$expansion->name}.");

        // Inserisci ogni URL nella coda e dispatcha il job di processing
        foreach ($cardUrls as $cardUrl) {
            // Sostituisce la lingua italiana con inglese nell'URL
            $cardUrl = str_replace('/it/', '/en/', $cardUrl);
            // Rimuove eventuali doppi slash nel path, preservando il protocollo (https://)
            $cardUrl = preg_replace('#(?<!:)//+#', '/', $cardUrl);

            $queueItem = ScrapingPageQueue::firstOrCreate(
                ['url' => $cardUrl, 'provider_id' => $provider->id],
                [
                    'status' => 'pending',
                    'type'   => 'product',
                ]
            );

            // Se il record esisteva già ed è in stato 'failed', lo resettiamo a 'pending'
            // così può essere rielaborato al prossimo giro
            if ($queueItem->status === \App\Enums\ScraperStatus::FAILED) {
                $queueItem->update([
                    'status'             => 'pending',
                    'last_error_message' => null,
                ]);
                $queueItem->refresh();
                Log::debug("[DispatchExpansionScraping] Reset da failed a pending: {$cardUrl}");
            }

            // Se il record è 'processing' da più di 10 minuti, probabilmente è rimasto appeso
            if (
                $queueItem->status === \App\Enums\ScraperStatus::PROCESSING
                && $queueItem->processed_at
                && $queueItem->processed_at->lt(now()->subMinutes(10))
            ) {
                $queueItem->update(['status' => 'pending']);
                $queueItem->refresh();
                Log::debug("[DispatchExpansionScraping] Reset da processing stale a pending: {$cardUrl}");
            }

            // Dispatcha il job di processing solo se è pending
            if ($queueItem->status === \App\Enums\ScraperStatus::PENDING) {
                ProcessScrapingPageJob::dispatch($queueItem->id)
                    ->onQueue('scraping');
            }
        }
    }

    /**
     * Percorre tutte le pagine della gallery e raccoglie gli URL completi
     * di ogni singola carta, con paginazione completa.
     */
    private function collectAllCardUrls(CardmarketScraper $scraper, string $galleryBaseUrl, string $baseUrl): \Illuminate\Support\Collection
    {
        $allCardUrls = collect();
        $currentPage = 1;
        $totalPages  = 1;

        do {
            $pageUrl = $currentPage === 1
                ? $galleryBaseUrl
                : $galleryBaseUrl . '&site=' . $currentPage;

            Log::debug("[DispatchExpansionScraping] Scarico pagina gallery {$currentPage}/{$totalPages}: {$pageUrl}");

            // Rate limiter: attendi tra le richieste per evitare ban Cloudflare
            // La prima pagina non ha bisogno di delay
            if ($currentPage > 1) {
                $delay = rand(5, 10);
                Log::debug("[DispatchExpansionScraping] Attesa {$delay}s prima della prossima richiesta...");
                sleep($delay);
            }

            try {
                $html    = $scraper->scrapeFromUrl($pageUrl);
                $crawler = new Crawler($html);

                // Aggiorna il numero totale di pagine dalla paginazione (solo alla prima pagina)
                if ($currentPage === 1) {
                    $totalPages = $this->parseTotalPages($crawler);
                    Log::debug("[DispatchExpansionScraping] Pagine totali rilevate: {$totalPages}");
                }

                // Estrae i link delle carte dalla pagina corrente
                $pageCardUrls = $crawler
                    ->filter('a.galleryBox')
                    ->each(function (Crawler $node) use ($baseUrl): string {
                        $href = $node->attr('href');
                        return rtrim($baseUrl, '/') . $href;
                    });

                $allCardUrls = $allCardUrls->merge($pageCardUrls);

            } catch (\Throwable $e) {
                Log::warning("[DispatchExpansionScraping] Errore su pagina gallery {$currentPage}: {$e->getMessage()}");
                // Continua con le carte raccolte finora senza bloccare tutto
                break;
            }

            $currentPage++;

        } while ($currentPage <= $totalPages);

        return $allCardUrls->unique()->values();
    }

    /**
     * Legge il numero totale di pagine dal blocco di paginazione.
     */
    private function parseTotalPages(Crawler $crawler): int
    {
        $paginationNode = $crawler->filter('#pagination span.mx-1');

        if (! $paginationNode->count()) {
            return 1;
        }

        $text = $paginationNode->text();

        if (preg_match('/\d+\s+di\s+(\d+)/i', $text, $matches)) {
            return (int) $matches[1];
        }

        // Fallback per versione inglese: "Page 1 of 3"
        if (preg_match('/\d+\s+of\s+(\d+)/i', $text, $matches)) {
            return (int) $matches[1];
        }

        return 1;
    }
}
