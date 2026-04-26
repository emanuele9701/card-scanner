<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapingCardPriceSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'scraping_card_id',
        'scraped_at',
        'available_items',
        'price_from',
        'price_trend',
        'avg_price_1d',
        'avg_price_7d',
        'avg_price_30d',
    ];

    protected $casts = [
        'scraped_at' => 'datetime',
    ];

    /**
     * La carta a cui appartiene questo snapshot di prezzo.
     */
    public function scrapingCard(): BelongsTo
    {
        return $this->belongsTo(ScrapingCard::class);
    }

    /**
     * Le offerte dei venditori catturate in questo snapshot.
     */
    public function offers(): HasMany
    {
        return $this->hasMany(ScrapingCardOffer::class, 'snapshot_id');
    }
}
