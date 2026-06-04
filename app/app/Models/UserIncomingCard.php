<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserIncomingCard extends Model
{
    protected $table = 'user_incoming_cards';

    protected $fillable = [
        'user_id',
        'card_id',
        'set_id',
        'language',
        'foil_type',
        'is_first_edition',
        'is_signed',
        'is_altered',
        'quantity',
        'notes',
    ];

    protected $casts = [
        'is_first_edition' => 'boolean',
        'is_signed' => 'boolean',
        'is_altered' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function card()
    {
        return $this->belongsTo(TCGCard::class, 'card_id');
    }

    public function set()
    {
        return $this->belongsTo(TCGSet::class, 'set_id');
    }
}
