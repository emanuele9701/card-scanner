<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapingCardOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_id',
        'article_id',
        'seller_name',
        'seller_url',
        'language',
        'comment',
        'price',
    ];

    /**
     * Lo snapshot di prezzo a cui appartiene questa offerta.
     */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(ScrapingCardPriceSnapshot::class, 'snapshot_id');
    }
}
