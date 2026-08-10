<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCGCardTranslation extends Model
{
    protected $table = 'tcg_cards_translations';

    protected $fillable = [
        'card_id',
        'language',
        'name',
        'url_image',
    ];

    public function card()
    {
        return $this->belongsTo(TCGCard::class, 'card_id', 'id');
    }
}
