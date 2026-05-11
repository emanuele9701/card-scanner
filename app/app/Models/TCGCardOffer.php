<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCGCardOffer extends Model
{
    protected $table = 'tcg_card_offers';

    protected $fillable = [
        'card_id',
        'article_id',
        'seller_name',
        'seller_profile_url',
        'seller_country',
        'seller_sales_count',
        'seller_available_items',
        'card_condition',
        'card_condition_code',
        'card_language',
        'is_reverse_holo',
        'is_holo',
        'card_special_type',
        'seller_comment',
        'price_eur',
        'quantity',
    ];

    protected $casts = [
        'seller_sales_count' => 'integer',
        'seller_available_items' => 'integer',
        'is_reverse_holo' => 'boolean',
        'is_holo' => 'boolean',
        'price_eur' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Relazione con la carta TCG
     */
    public function card()
    {
        return $this->belongsTo(TCGCard::class, 'card_id', 'id');
    }
}
