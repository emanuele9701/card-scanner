<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCGCardPriceHistory extends Model
{
    protected $table = 'tcg_card_price_history';

    protected $fillable = [
        'card_id',
        'trend',
        'trend_holo',
        'avg',
        'avg_holo',
    ];

    public function card()
    {
        return $this->belongsTo(TCGCard::class, 'card_id', 'id');
    }
}
