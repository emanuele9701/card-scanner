<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserCardWatchlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'card_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function card()
    {
        return $this->belongsTo(TCGCard::class, 'card_id');
    }
}
