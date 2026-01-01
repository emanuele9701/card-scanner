<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardSet extends Model
{
    protected $fillable = [
        'name',
        'abbreviation',
        'release_date',
        'total_cards',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    /**
     * Get all Pokemon cards in this set
     */
    public function pokemonCards(): HasMany
    {
        return $this->hasMany(PokemonCard::class);
    }

    /**
     * Get all market cards in this set
     */
    public function marketCards(): HasMany
    {
        return $this->hasMany(MarketCard::class, 'set_abbreviation', 'abbreviation');
    }

    /**
     * Get the number of cards in user's collection for this set
     */
    public function getCollectionCountAttribute(): int
    {
        return $this->pokemonCards()->count();
    }

    /**
     * Get completion percentage for this set in user's collection
     */
    public function getCompletionPercentageAttribute(): float
    {
        if (!$this->total_cards || $this->total_cards === 0) {
            return 0;
        }

        return ($this->collection_count / $this->total_cards) * 100;
    }
}
