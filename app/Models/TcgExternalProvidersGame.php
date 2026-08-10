<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TcgExternalProvidersGame extends Model
{
    protected $table = 'tcg_external_providers_games';

    protected $fillable = [
        'provider',
        'external_id',
        'name',
    ];

    public function sets()
    {
        return $this->hasMany(TcgExternalProvidersSet::class, 'external_game_id');
    }
}
