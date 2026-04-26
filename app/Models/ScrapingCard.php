<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ScrapingCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'scraping_provider_id',
        'scraping_expansion_id',
        'card_number',
        'name',
        'rarity',
        'species',
        'product_url',
        'reprint_url',
        'reprint_offers_url',
    ];

    /**
     * Il provider (Cardmarket, CardTrader, ecc.) da cui proviene questa carta.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ScraperProvider::class, 'scraping_provider_id');
    }

    /**
     * L'espansione (set) a cui appartiene questa carta.
     */
    public function expansion(): BelongsTo
    {
        return $this->belongsTo(ScrapingExpansion::class, 'scraping_expansion_id');
    }

    /**
     * Tutti gli snapshot di prezzo storici per questa carta.
     */
    public function priceSnapshots(): HasMany
    {
        return $this->hasMany(ScrapingCardPriceSnapshot::class);
    }

    /**
     * Lo snapshot di prezzo più recente.
     */
    public function latestSnapshot(): HasOne
    {
        return $this->hasOne(ScrapingCardPriceSnapshot::class)
                    ->latestOfMany('scraped_at');
    }

    /**
     * La market_card (TCGDex) collegata a questa carta di scraping.
     */
    public function marketCard(): HasOne
    {
        return $this->hasOne(MarketCard::class);
    }
}
