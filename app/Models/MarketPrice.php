<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketPrice extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'market_card_id',
        'condition',
        'printing',
        'low_price',
        'market_price',
        'sales_count',
        'import_date',
    ];

    protected $casts = [
        'low_price' => 'decimal:2',
        'market_price' => 'decimal:2',
        'import_date' => 'date',
        'created_at' => 'datetime',
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
}
