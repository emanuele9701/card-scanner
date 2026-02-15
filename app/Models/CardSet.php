<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardSet extends Model
{
    protected $fillable = [
        'game_id',
        'name',
        'abbreviation',
        'card_set_abbreviation',
        'release_date',
        'total_cards',
        'external_set_id',
        'external_category_id',
        'is_supplemental',
        'is_active',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    /**
     * Get the users that follow this set
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'card_set_user');
    }

    /**
     * Get the game this set belongs to
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }


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
