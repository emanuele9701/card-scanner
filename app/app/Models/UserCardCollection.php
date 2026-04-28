<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class UserCardCollection extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = "user_card_collections";

    protected $fillable = [
        'user_id',
        'card_id',
        'quantity',
        'variants',
        'condition',
        'notes',
    ];

    protected $casts = [
        'variants' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Condizioni valide per le carte.
     */
    public const CONDITIONS = ['NM', 'LP', 'MP', 'HP', 'DMG'];

    /**
     * Registra la media collection per le foto della carta.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Definisce le conversioni automatiche dei media.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(600)
            ->height(800)
            ->sharpen(5)
            ->nonQueued();
    }

    /**
     * Relazione con l'utente.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relazione con la carta TCG.
     */
    public function card()
    {
        return $this->belongsTo(TCGCard::class, 'card_id', 'id');
    }
}
