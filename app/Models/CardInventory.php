<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardInventory extends Model
{
    protected $table = 'card_inventory';

    protected $fillable = [
        'pokemon_card_id',
        'user_id',
        'quantity',
        'rarity_variant',
        'condition',
        'notes'
    ];

    protected $casts = [
        'quantity' => 'integer'
    ];

    // Constants for rarity variants
    public const RARITY_VARIANTS = [
        'Standard',
        'Reverse Holo',
        'Holo',
        'First Edition',
        'Shadowless',
        'Error Card',
        'Promo',
        'Altro'
    ];

    // Constants for conditions
    public const CONDITIONS = [
        'Mint',
        'Near Mint',
        'Excellent',
        'Good',
        'Light Played',
        'Played',
        'Poor'
    ];

    /**
     * Get the Pokemon card that this inventory item belongs to
     */
    public function pokemonCard(): BelongsTo
    {
        return $this->belongsTo(PokemonCard::class);
    }

    /**
     * Get the user that owns this inventory item
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
