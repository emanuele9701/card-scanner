<?php

namespace App\Services\Scraping;

use App\Models\MarketCard;
use App\Models\ScrapingCard;
use App\Models\ScrapingCardOffer;
use App\Models\ScrapingCardPriceSnapshot;
use App\Models\ScrapingExpansion;
use App\Models\ScrapingPageQueue;
use App\Services\CardmarketScraper;
use Illuminate\Support\Facades\DB;

class ScrapingDataPersistenceService
{
    public function __construct(
        private readonly CardmarketScraper $scraper
    ) {}

    /**
     * Salva tutti i dati scraping (carta, snapshot, offerte) in una transazione
     * e prova il match con market_cards.
     */
    public function saveScrapedCard(array $data, ScrapingPageQueue $queueItem): ScrapingCard
    {
        return DB::transaction(function () use ($data, $queueItem) {

            $info = $data['product_info'];

            // 1. Trova o crea la scraping_card
            $card = $this->upsertCard($info, $queueItem);

            // 2. Crea lo snapshot di prezzo per questo momento
            $snapshot = $this->createSnapshot($card, $info);

            // 3. Salva le offerte dei venditori
            $offers = $data['offers'] ?? [];
            // Se 'offers' è una Collection (dal parser HTML), convertila in array
            if ($offers instanceof \Illuminate\Support\Collection) {
                $offers = $offers->toArray();
            }
            $this->saveOffers($snapshot, $offers);

            // 4. Prova il match con market_cards
            $this->linkToMarketCard($card);

            return $card;
        });
    }

    /**
     * Trova o crea la ScrapingCard usando provider + expansion + card_number come chiave univoca.
     */
    private function upsertCard(array $info, ScrapingPageQueue $queueItem): ScrapingCard
    {
        // Recupera l'expansion_id cercando per nome espansione nel provider corretto
        $expansionId = ScrapingExpansion::where(
            'scraper_provider_id', $queueItem->provider_id
        )->where('name', $info['stampata_in'] ?? '')->value('id');

        return ScrapingCard::updateOrCreate(
            [
                'scraping_provider_id'  => $queueItem->provider_id,
                'scraping_expansion_id' => $expansionId,
                'card_number'           => $info['numero'],
            ],
            [
                'name'               => $info['nome'] ?? '',
                'rarity'             => $info['rarita'] ?? null,
                'species'            => $info['specie'] ?? null,
                'product_url'        => $queueItem->url,
                'reprint_url'        => $info['ristampe_url'] ?? null,
                'reprint_offers_url' => $info['offerte_ristampe_url'] ?? null,
            ]
        );
    }

    /**
     * Crea un nuovo snapshot con i prezzi attuali.
     */
    private function createSnapshot(ScrapingCard $card, array $info): ScrapingCardPriceSnapshot
    {
        return ScrapingCardPriceSnapshot::create([
            'scraping_card_id' => $card->id,
            'scraped_at'       => now(),
            'available_items'  => (int) ($info['articoli_disponibili'] ?? 0),
            'price_from'       => $this->parsePrice(
                $info['prezzo_da'] ?? null,
                $info['prezzo_da_numeric'] ?? null
            ),
            'price_trend'   => $this->parsePrice($info['tendenza_prezzo'] ?? null, null),
            'avg_price_30d' => $this->parsePrice($info['prezzo_medio_30g'] ?? null, null),
            'avg_price_7d'  => $this->parsePrice($info['prezzo_medio_7g'] ?? null, null),
            'avg_price_1d'  => $this->parsePrice($info['prezzo_medio_1g'] ?? null, null),
        ]);
    }

    /**
     * Salva le offerte dei venditori usando upsert per gestire i retry senza duplicati.
     */
    private function saveOffers(ScrapingCardPriceSnapshot $snapshot, array $offers): void
    {
        if (empty($offers)) {
            return;
        }

        $rows = array_map(fn($offer) => [
            'snapshot_id' => $snapshot->id,
            'article_id'  => $offer['article_id'],
            'seller_name' => $offer['seller_name'],
            'seller_url'  => $offer['seller_url'] ?? null,
            'language'    => $offer['language'] ?? null,
            'comment'     => $offer['comment'] ?? null,
            'price'       => $offer['price_numeric'] ?? $this->parsePrice($offer['price'] ?? null, null),
            'created_at'  => now(),
            'updated_at'  => now(),
        ], $offers);

        // upsert per evitare duplicati in caso di retry del job
        ScrapingCardOffer::upsert(
            $rows,
            ['snapshot_id', 'article_id'],
            ['price', 'comment', 'updated_at']
        );
    }

    /**
     * Collega automaticamente la ScrapingCard alla MarketCard
     * per numero carta + abbreviazione set.
     */
    private function linkToMarketCard(ScrapingCard $card): void
    {
        if ($card->scraping_expansion_id === null) {
            return;
        }

        $expansion = $card->expansion;

        if (!$expansion) {
            return;
        }

        MarketCard::where('card_number', $card->card_number)
            ->where('set_abbreviation', $expansion->abbreviation)
            ->whereNull('scraping_card_id')
            ->update(['scraping_card_id' => $card->id]);
    }

    /**
     * Converte il valore stringa prezzo ("0,02 €") in float.
     * Usa direttamente il campo *_numeric se disponibile.
     */
    private function parsePrice(?string $raw, ?float $numeric): ?float
    {
        if ($numeric !== null) {
            return $numeric;
        }

        if (empty($raw)) {
            return null;
        }

        // "9,30 €" → 9.30
        $normalized = str_replace(['€', ' ', "\u{00a0}"], '', $raw);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
