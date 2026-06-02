<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TcgExternalProvidersSet extends Model
{
    protected $table = 'tcg_external_providers_sets';

    protected $fillable = [
        'external_game_id',
        'external_id',
        'name',
        'abbreviation',
    ];

    public function game()
    {
        return $this->belongsTo(TcgExternalProvidersGame::class, 'external_game_id');
    }
}
