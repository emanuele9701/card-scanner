<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class PokemonCard extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEW = 'review';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'original_filename',
        'storage_path',
        'extracted_text',
        'status',
        'card_name',
        'hp',
        'type',
        'evolution_stage',
        'attacks',
        'weakness',
        'resistance',
        'retreat_cost',
        'rarity',
        'set_number',
        'illustrator',
        'flavor_text',
        // Market data fields
        'card_set_id',
        'market_card_id',
        'condition',
        'printing',
        'acquisition_price',
        'acquisition_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'attacks' => 'array',
        'acquisition_price' => 'decimal:2',
        'acquisition_date' => 'date',
    ];

    /**
     * Get the card set this card belongs to
     */
    public function cardSet(): BelongsTo
    {
        return $this->belongsTo(CardSet::class);
    }

    /**
     * Get the market card data for this card
     */
    public function marketCard(): BelongsTo
    {
        return $this->belongsTo(MarketCard::class);
    }

    /**
     * Get the estimated market value for this card based on its condition and printing
     */
    public function getEstimatedValue(): ?float
    {
        if (!$this->marketCard || !$this->condition) {
            Log::error('Market card or condition not found for card: ' . $this->id);
            return null;
        }

        $price = $this->marketCard->prices()
            ->where('condition', $this->condition)
            // ->where('printing', $this->printing ?? 'Normal')
            ->orderBy('import_date', 'desc')
            ->first();


        Log::info('Market price for card: ' . $this->id . ' - ' . json_encode($price));

        return $price?->market_price;
    }

    /**
     * Get the estimated market value as formatted string
     */
    public function getFormattedEstimatedValueAttribute(): string
    {
        $value = $this->getEstimatedValue();
        return $value !== null ? '$' . number_format($value, 2) : 'N/A';
    }

    /**
     * Check if this card has market data linked
     */
    public function hasMarketData(): bool
    {
        return $this->market_card_id !== null;
    }

    /**
     * Get profit/loss if acquisition price is known
     */
    public function getProfitLoss(): ?float
    {
        if (!$this->acquisition_price) {
            return null;
        }

        $currentValue = $this->getEstimatedValue();
        if ($currentValue === null) {
            return null;
        }

        return $currentValue - $this->acquisition_price;
    }

    /**
     * Get profit/loss percentage
     */
    public function getProfitLossPercentage(): ?float
    {
        if (!$this->acquisition_price || $this->acquisition_price == 0) {
            return null;
        }

        $profitLoss = $this->getProfitLoss();
        if ($profitLoss === null) {
            return null;
        }

        return ($profitLoss / $this->acquisition_price) * 100;
    }

    /**
     * Check if OCR processing is complete
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if OCR processing failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if OCR is still pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
