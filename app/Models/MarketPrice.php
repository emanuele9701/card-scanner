<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketPrice extends Model
{
    public $timestamps = false;

    public const UNITS_DIVISA = [
        'eur' => '€',
        'dol' => '$'
    ];

    protected $fillable = [
        'market_card_id',
        'condition',
        'printing',
        'low_price',
        'high_price',
        'mid_price',
        'market_price',
        'trend',
        'avg1',
        'avg7',
        'avg30',
        'sales_count',
        'unit_divisa',
        'import_date',
        'external_product_id',
        'provider_id'
    ];

    protected $casts = [
        'low_price'     => 'decimal:2',
        'mid_price'     => 'decimal:2',
        'high_price'    => 'decimal:2',
        'market_price'  => 'decimal:2',
        'trend'         => 'decimal:2',
        'avg1'          => 'decimal:2',
        'avg7'          => 'decimal:2',
        'avg30'         => 'decimal:2',
        'import_date'   => 'date',
        'created_at'    => 'datetime',
    ];

    /**
     * Get the market card this price belongs to
     */
    public function marketCard(): BelongsTo
    {
        return $this->belongsTo(MarketCard::class);
    }

    /**
     * Scope to get prices from a specific import date
     */
    public function scopeFromImport($query, string $date)
    {
        return $query->where('import_date', $date);
    }

    /**
     * Relation to provider of the price data (e.g. TCGPlayer, Cardmarket)
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(ProviderPrice::class, 'provider_id');
    }

    /**
     * Scope to get latest prices
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('import_date', 'desc');
    }

    /**
     * Scope to filter by condition
     */
    public function scopeCondition($query, string $condition)
    {
        return $query->where('condition', $condition);
    }

    /**
     * Scope to filter by printing
     */
    public function scopePrinting($query, string $printing)
    {
        return $query->where('printing', $printing);
    }

    /**
     * Get formatted price with currency symbol
     */
    public function getFormattedMarketPriceAttribute(): string
    {
        return '$' . number_format($this->market_price, 2);
    }

    /**
     * Get formatted low price with currency symbol
     */
    public function getFormattedLowPriceAttribute(): string
    {
        return '$' . number_format($this->low_price, 2);
    }

    /**
     * Get formatted trend price with currency symbol
     */
    public function getFormattedTrendAttribute(): string
    {
        return '$' . number_format($this->trend, 2);
    }

    /**
     * Get formatted 1-day average price with currency symbol
     */
    public function getFormattedAvg1Attribute(): string
    {
        return '$' . number_format($this->avg1, 2);
    }

    /**
     * Get formatted 7-day average price with currency symbol
     */
    public function getFormattedAvg7Attribute(): string
    {
        return '$' . number_format($this->avg7, 2);
    }

    /**
     * Get formatted 30-day average price with currency symbol
     */
    public function getFormattedAvg30Attribute(): string
    {
        return '$' . number_format($this->avg30, 2);
    }
}
