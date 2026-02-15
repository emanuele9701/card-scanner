<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = [
        'name',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'game_user');
    }

    public function cardSets()
    {
        return $this->hasMany(CardSet::class);
    }
}
