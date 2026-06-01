<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCGCardPriceHistory extends Model
{
    protected $table = 'tcg_card_price_history';

    protected $fillable = [
        'card_id',
        'provider',
        'trend',
        'trend_holo',
        'avg',
        'avg_holo',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function card()
    {
        return $this->belongsTo(TCGCard::class, 'card_id', 'id');
    }
}
