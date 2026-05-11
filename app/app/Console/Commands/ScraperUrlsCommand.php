<?php

namespace App\Console\Commands;

use App\Enums\UrlMappingStatus;
use App\Enums\UrlMappingType;
use App\Helpers\CardMarketParser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\UrlMapping;
use Illuminate\Support\Facades\Process;

#[Signature('app:scraper-run {--limit=1 : Numero massimo di URL da processare in questa esecuzione}')]
#[Description('Esegue lo scraping degli URL in stato pending utilizzando Puppeteer e FlareSolverr')]
class ScraperUrlsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        
        $urls = UrlMapping::pending()->where('type', UrlMappingType::ListCard)->limit($limit)->get();

        if ($urls->isEmpty()) {
            $this->info("✅ Nessun URL in coda per lo scraping.");
            return Command::SUCCESS;
        }

        $scriptPath = base_path('jsScripts/scraper-puppeteer.js');
        if (!file_exists($scriptPath)) {
            $this->error("❌ Script Node.js non trovato: {$scriptPath}");
            return Command::FAILURE;
        }

        foreach ($urls as $urlMapping) {
            $this->info("--------------------------------------------------");
            $this->info("🔄 Scraping URL ID: {$urlMapping->id} | Path: {$urlMapping->url_path} ({$urlMapping->site_name})");
            
            // Impostiamo lo stato a scraping per evitare che altri processi lo prendano
            $urlMapping->update(['status' => UrlMappingStatus::Scraping]);

            // Costruiamo l'URL completo
            $fullUrl = $this->buildFullUrl($urlMapping->site_name, $urlMapping->url_path);

            $this->info("🌍 Full URL: {$fullUrl}");
            $this->info("⏳ Esecuzione script Node.js in corso (potrebbe richiedere fino a 60-90 secondi)...");

            // Eseguiamo il processo. Aumentiamo il timeout perché FlareSolverr + Puppeteer + sleep possono impiegare tempo
            $result = Process::timeout(120)->run(['node', $scriptPath, $fullUrl], function (string $type, string $output) {
                if ($type === 'err') {
                    // Stampiamo i log intermedi generati dallo script JS in tempo reale
                    $this->output->write($output);
                }
            });

            if (! $result->successful()) {
                $this->error("❌ Errore critico nell'esecuzione dello script Node.js per {$fullUrl}");
                // $urlMapping->markFailed();
                continue;
            }

            $output = $result->output();
            
            // Lo script JS dovrebbe restituire un JSON pulito su stdout (senza altri log testuali)
            $data = json_decode(trim($output), true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['status'])) {
                $this->error("❌ L'output dello script non è un JSON valido.");
                $this->error("Raw Output: " . substr($output, 0, 500) . "...");
                $urlMapping->markFailed();
                continue;
            }

            if ($data['status'] === 'success') {
                $html = $data['html'] ?? '';
                $htmlSize = mb_strlen($html, '8bit');
                
                $this->info("✅ Scraping completato con successo!");
                $this->info("📄 HTML recuperato: " . number_format($htmlSize) . " bytes.");
                
                $cards = (new CardMarketParser($html))->run();
                $dataCards = json_decode($cards,true);

                if(json_last_error() != JSON_ERROR_NONE || (!isset($dataCards['cards']) || empty($dataCards['cards']))) {
                    $this->error("❌ Il parser non ha trovato carte valide nell'HTML.");
                    $urlMapping->markFailed();
                    continue;
                }

                // Se ci sono altre pagine, le aggiungiamo alla lista se non sono già state aggiunte. 
                if((int) $dataCards['maxPages'] > 1 && $urlMapping->type === UrlMappingType::ListCard && !empty($dataCards['paginationUrls'])) {
                    foreach($dataCards['paginationUrls'] as $url) {
                        $urlMappingNew = UrlMapping::where('url_path', $url)->where('site_name',$urlMapping->site_name)->where('type', UrlMappingType::ListCard)->first();
                        if(empty($urlMappingNew)) {
                            UrlMapping::create([
                                'url_path' => $url,
                                'site_name' => $urlMapping->site_name,
                                'type' => UrlMappingType::ListCard,
                            ]);
                        }
                    }
                }

                $cardsUrl = $dataCards['cards'];

                foreach($cardsUrl as $card) {
                    if(empty($card['url'])) continue;

                    $urlMappingCard = UrlMapping::where('url_path', $card['url'])->where('site_name',$urlMapping->site_name)->where('type', UrlMappingType::SingleCard)->first();
                    if(empty($urlMappingCard)) {
                        UrlMapping::create([
                            'url_path' => $card['url'],
                            'site_name' => $urlMapping->site_name,
                            'type' => UrlMappingType::SingleCard,
                        ]);
                    }
                }

                $urlMapping->update(['status' => UrlMappingStatus::Done]);
                
            } else {
                $this->error("❌ Lo script ha riportato un errore gestito: " . ($data['message'] ?? 'Errore sconosciuto'));
                $urlMapping->markFailed();
            }
        }

        $this->info("--------------------------------------------------");
        $this->info("🏁 Esecuzione comando terminata.");

        return Command::SUCCESS;
    }

    /**
     * Costruisce l'URL completo basato sul nome del sito e il path relativo.
     */
    private function buildFullUrl(string $siteName, string $path): string
    {
        $path = ltrim($path, '/');
        
        return match(strtolower($siteName)) {
            'cardmarket' => "https://www.cardmarket.com/{$path}",
            default => "https://{$siteName}/{$path}" // Fallback generico
        };
    }
}
