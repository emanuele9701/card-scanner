<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UrlMapping;
use Illuminate\Support\Facades\Process;

class ScrapePendingUrlsCommand extends Command
{
    protected $signature = 'scraper:run {--limit=1 : Numero massimo di URL da processare in questa esecuzione}';

    protected $description = 'Esegue lo scraping degli URL in stato pending utilizzando Puppeteer e FlareSolverr';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        
        $urls = UrlMapping::pending()->limit($limit)->get();

        if ($urls->isEmpty()) {
            $this->info("✅ Nessun URL in coda per lo scraping.");
            return Command::SUCCESS;
        }

        $scriptPath = base_path('app/jsScripts/scraper-puppeteer.js');
        if (!file_exists($scriptPath)) {
            $this->error("❌ Script Node.js non trovato: {$scriptPath}");
            return Command::FAILURE;
        }

        foreach ($urls as $urlMapping) {
            $this->info("--------------------------------------------------");
            $this->info("🔄 Scraping URL ID: {$urlMapping->id} | Path: {$urlMapping->url_path} ({$urlMapping->site_name})");
            
            // Impostiamo lo stato a scraping per evitare che altri processi lo prendano
            // $urlMapping->update(['status' => UrlMappingStatus::Scraping]);

            // Costruiamo l'URL completo
            $fullUrl = $this->buildFullUrl($urlMapping->site_name, $urlMapping->url_path);

            $this->info("🌍 Full URL: {$fullUrl}");
            $this->info("⏳ Esecuzione script Node.js in corso (potrebbe richiedere fino a 60-90 secondi)...");

            // Eseguiamo il processo. Aumentiamo il timeout perché FlareSolverr + Puppeteer + sleep possono impiegare tempo
            $result = Process::timeout(120)->run(['node', $scriptPath, $fullUrl]);

            dd($result);

            // Stampiamo i log intermedi generati dallo script JS (log con console.error finiscono su stderr)
            if ($result->errorOutput()) {
                $this->comment("--- Log dello script Node.js ---");
                $this->line(trim($result->errorOutput()));
                $this->comment("--------------------------------");
            }

            if (! $result->successful()) {
                $this->error("❌ Errore critico nell'esecuzione dello script Node.js per {$fullUrl}");
                $urlMapping->markFailed();
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
                
                // Aggiorniamo il mapping nel DB per segnarlo come Completato
                $urlMapping->markSuccess();
                
                // TODO: Il prossimo step sarà parsare questo HTML usando DOMDocument/Crawler
                // Per ora ci fermiamo qui come richiesto!
                
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
