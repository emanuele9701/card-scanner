<?php

namespace App\Console\Commands;

use App\Enums\UrlMappingType;
use App\Models\TCGCard;
use App\Models\TCGCardOffer;
use App\Models\TCGSet;
use App\Models\UrlMapping;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:scrape-url-single-card {--limit=10 : Numero massimo di URL da processare in questa esecuzione}')]
#[Description('Esegue lo scraping degli URL in stato pending utilizzando Puppeteer e FlareSolverr')]
class ScrapeUrlSingleCard extends Command
{

    private int $baseLimit = 10;

    public function handle()
    {
        $limit = $this->option('limit') ?? $this->baseLimit;
        
        $scriptPath = base_path('jsScripts/scraper-puppeteer.js');
        if (!file_exists($scriptPath)) {
            $this->error("❌ Script Node.js non trovato: {$scriptPath}");
            return Command::FAILURE;
        }

        for ($i = 0; $i < $limit; $i++) {
            $urlMapping = null;

            // Transazione per ottenere una riga in stato pending, lockarla per evitare 
            // che altri processi la prendano, e passarla a scraping.
            \Illuminate\Support\Facades\DB::transaction(function () use (&$urlMapping) {
                $urlMapping = UrlMapping::where('status', \App\Enums\UrlMappingStatus::Pending)
                    ->where('type', UrlMappingType::SingleCard)
                    ->lockForUpdate()
                    ->first();

                if ($urlMapping) {
                    $urlMapping->update(['status' => \App\Enums\UrlMappingStatus::Scraping]);
                }
            });

            if (!$urlMapping) {
                if ($i === 0) {
                    $this->info("✅ Nessun URL (singola carta) in coda per lo scraping.");
                } else {
                    $this->info("✅ Nessun altro URL in coda. Scraping terminato.");
                }
                break;
            }

            if ($i > 0) {
                $sleepSecond = random_int(5, 10);
                $this->info("⏳ Sleep per {$sleepSecond} secondi");
                sleep($sleepSecond);
            }

            $this->info("--------------------------------------------------");
            $this->info("🔄 Scraping Singola Carta URL ID: {$urlMapping->id} | Path: {$urlMapping->url_path}");

            $fullUrl = $this->buildFullUrl($urlMapping->site_name, $urlMapping->url_path) . "?sellerCountry=17&sellerType=1&language=5&minCondition=2";

            $this->info("🌍 Full URL: {$fullUrl}");
            $this->info("⏳ Esecuzione script Node.js (Modalità: single_card)...");

            // timeout molto alto perché la singola carta può dover scorrere molte offerte
            $result = \Illuminate\Support\Facades\Process::timeout(300)
                ->run(['node', $scriptPath, $fullUrl, 'single_card'], function (string $type, string $output) {
                    $this->output->write($output);
                });

            if (! $result->successful()) {
                $this->error("❌ Errore critico nell'esecuzione dello script Node.js per {$fullUrl}");
                $urlMapping->markFailed();
                continue;
            }

            $output = $result->output();
            $data = json_decode(trim($output), true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['status'])) {
                $this->error("❌ L'output dello script non è un JSON valido.");
                $this->error("Raw Output: " . substr($output, 0, 500) . "...");
                $urlMapping->markFailed();
                continue;
            }

            if ($data['status'] === 'success') {
                $this->info("✅ Scraping carta completato con successo!");
                
                $cardData = $data['data'] ?? [];
                $datiCarta = $cardData['dati_carta'] ?? [];
                $offers = $cardData['offers'] ?? [];

                // Ricerco la carta di riferimento
                $tcgCard = null;
                $tcgSet = TCGSet::where('name', $datiCarta['expansion'])->first();
                
                if ($tcgSet) {
                    $tcgCard = TCGCard::where('set_id', $tcgSet->id)->where('dexId', (int)$datiCarta['number'])->first();
                    if ($tcgCard) { 
                        $this->info("✅ Carta trovata: {$tcgCard->id}");

                        foreach ($offers as $offer) {
                            $existingOffer = TCGCardOffer::where('card_id', $tcgCard->id)
                                ->where('article_id', $offer['articleId'])
                                ->where('seller_name', $offer['sellerName'])
                                ->first();
                            
                            if ($existingOffer) { 
                                $this->info("❌ Offerta già presente: {$offer['articleId']} - Aggiorno le informazioni");
                                $existingOffer->update([
                                    'seller_sales_count' => $offer['sellerSalesCount'],
                                    'seller_available_items' => $offer['sellerAvailableItems'],
                                    'seller_country' => $offer['sellerCountry'],
                                    'card_condition' => $offer['cardCondition'],
                                    'card_condition_code' => $offer['cardConditionCode'],
                                    'card_language' => $offer['cardLanguage'],
                                    'is_reverse_holo' => $offer['isReverseHolo'],
                                    'is_holo' => $offer['isHolo'],
                                    'card_special_type' => $offer['cardSpecialType'],
                                    'seller_comment' => $offer['sellerComment'],
                                    'price_eur' => $offer['priceEur'],
                                    'quantity' => $offer['quantity'],
                                ]);
                                continue;
                            }
                            $this->info("✅ Aggiungo la carta: {$tcgCard->id} - {$offer['articleId']}");
                            TCGCardOffer::create([
                                'card_id' => $tcgCard->id,
                                'article_id' => $offer['articleId'],
                                'seller_name' => $offer['sellerName'],
                                'seller_profile_url' => $offer['sellerProfileUrl'],
                                'seller_country' => $offer['sellerCountry'],
                                'seller_sales_count' => $offer['sellerSalesCount'],
                                'seller_available_items' => $offer['sellerAvailableItems'],
                                'card_condition' => $offer['cardCondition'],
                                'card_condition_code' => $offer['cardConditionCode'],
                                'card_language' => $offer['cardLanguage'],
                                'is_reverse_holo' => $offer['isReverseHolo'],
                                'is_holo' => $offer['isHolo'],
                                'card_special_type' => $offer['cardSpecialType'],
                                'seller_comment' => $offer['sellerComment'],
                                'price_eur' => $offer['priceEur'],
                                'quantity' => $offer['quantity'],
                            ]);
                        }
                    } else {
                        $this->error("❌ Carta non trovata: {$datiCarta['cardName']}");
                    }
                } else {
                    $this->error("❌ Set non trovato: {$datiCarta['expansion']}");
                }
                
                $urlMapping->update(['status' => \App\Enums\UrlMappingStatus::Done]);
            } else {
                $this->error("❌ Lo script ha riportato un errore: " . ($data['message'] ?? 'Sconosciuto'));
                $urlMapping->markFailed();
            }
        }

        $this->info("--------------------------------------------------");
        $this->info("🏁 Esecuzione comando terminata.");
        return Command::SUCCESS;
    }

    private function buildFullUrl(string $siteName, string $path): string
    {
        $path = ltrim($path, '/');
        return match(strtolower($siteName)) {
            'cardmarket' => "{$path}",
            default => "https://{$siteName}/{$path}"
        };
    }
}
