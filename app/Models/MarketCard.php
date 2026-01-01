<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketCard extends Model
{
    protected $fillable = [
        'product_id',
        'product_name',
        'card_number',
        'set_name',
        'set_abbreviation',
        'rarity',
        'type',
        'game',
        'is_supplemental',
    ];

    protected $casts = [
        'is_supplemental' => 'boolean',
    ];

    /**
     * Get all price records for this card
     */
    public function prices(): HasMany
    {
        return $this->hasMany(MarketPrice::class);
    }

    /**
     * Get all Pokemon cards in user's collection linked to this market card
     */
    public function pokemonCards(): HasMany
    {
        return $this->hasMany(PokemonCard::class);
    }

    /**
     * Get the most recent price record
     */
    public function latestPrice(): HasOne
    {
        return $this->hasOne(MarketPrice::class)
            ->latestOfMany('import_date');
    }

    /**
     * Get price for specific condition and printing
     */
    public function getPriceFor(string $condition, string $printing = 'Normal'): ?MarketPrice
    {
        return $this->prices()
            ->where('condition', $condition)
            ->where('printing', $printing)
            ->orderBy('import_date', 'desc')
            ->first();
    }

    /**
     * Get the latest market price for a specific condition and printing
     */
    public function getLatestMarketPrice(string $condition, string $printing = 'Normal'): ?float
    {
        $price = $this->getPriceFor($condition, $printing);
        return $price?->market_price;
    }

    /**
     * Scope to search by card number and set
     */
    public function scopeByNumberAndSet($query, string $cardNumber, string $setAbbreviation)
    {
        return $query->where('card_number', $cardNumber)
            ->where('set_abbreviation', $setAbbreviation);
    }

    /**
     * Scope to search by product name
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('product_name', 'LIKE', "%{$name}%");
    }
}
