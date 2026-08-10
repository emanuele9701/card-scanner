<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TcgExternalProvidersMapping extends Model
{
    protected $table = 'tcg_external_providers_mappings';

    protected $fillable = [
        'card_id',
        'provider',
        'external_id',
    ];

    public function card()
    {
        return $this->belongsTo(TCGCard::class, 'card_id');
    }
}
