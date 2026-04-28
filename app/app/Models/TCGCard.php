<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCGCard extends Model
{
    protected $table = "tcg_cards";

    protected $fillable = [
        "card_id",
        "set_id",
        "name",
        'url_image',
        'illustrator',
        'rarity',
        'variants',
        'dexId', // Identifica il numero di carta nel mazzo
        "types", // Identifica il tipo
        "evolve_from", // Indica da quale carta si evolve
        'level_stage', // Indica lo stato di evoluzione
        'language',
    ];

    protected $casts = [
        'variants' => 'array',
        'types' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function abilities()
    {
        return $this->hasMany(TCGCardAbility::class, 'card_id', 'id');
    }

    public function set()
    {
        return $this->belongsTo(TCGSet::class, 'set_id', 'id');
    }

    public function prices()
    {
        return $this->hasMany(TCGCardPrice::class, 'card_id', 'id');
    }

    /**
     * Utenti che possiedono questa carta.
     */
    public function collectors()
    {
        return $this->hasMany(UserCardCollection::class, 'card_id', 'id');
    }
}
