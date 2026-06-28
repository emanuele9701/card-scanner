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
        'set_id',
        'serie_id',
        'condition',
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

    /**
     * Relazione con il set TCG.
     */
    public function set()
    {
        return $this->belongsTo(TCGSet::class, 'set_id', 'id');
    }
    /**
     * Relazione con il set TCG.
     */
    public function serie()
    {
        return $this->belongsTo(TCGSeries::class, 'serie_id', 'id');
    }

    /**
     * Trova il miglior record di prezzo corrispondente a lingua, condizione, edizione e variante.
     */
    public function getMatchingPriceModel()
    {
        if (!$this->card || $this->card->prices->isEmpty()) {
            return null;
        }

        $prices = $this->card->prices;

        $foil = strtolower(trim($this->foil_type ?? ''));
        $isHoloOrReverse = in_array($foil, ['holo', 'reverse']);

        // 1. Match Esatto (Lingua, Condizione, Edizione, Variante)
        $match = $prices
            ->where('language', $this->language)
            ->where('condition', $this->condition)
            ->where('is_first_edition', (bool)$this->is_first_edition)
            ->where('is_reverse', $isHoloOrReverse)
            ->sortByDesc('updated_at')
            ->first();

        if ($match) {
            return $match;
        }

        // 2. Fallback 1: Ignora edizione, match Variante, Lingua, Condizione
        $match = $prices
            ->where('language', $this->language)
            ->where('condition', $this->condition)
            ->where('is_reverse', $isHoloOrReverse)
            ->sortByDesc('updated_at')
            ->first();

        if ($match) {
            return $match;
        }

        // 3. Fallback 2: Ignora Variante, match Lingua e Condizione 
        // (utile per vecchi record dove si useranno le vecchie colonne _holo)
        $match = $prices
            ->where('language', $this->language)
            ->where('condition', $this->condition)
            ->sortByDesc('updated_at')
            ->first();

        if ($match) {
            return $match;
        }

        // 4. Fallback 3: Match solo Lingua
        $match = $prices
            ->where('language', $this->language)
            ->sortByDesc('updated_at')
            ->first();

        if ($match) {
            return $match;
        }

        // 5. Fallback Estremo: Ultimo prezzo inserito
        return $prices->sortByDesc('updated_at')->first();
    }

    /**
     * Calcola il prezzo della carta in base alle varianti possedute (foil/normal).
     */
    public function getCalculatedPrice(): float
    {
        $priceModel = $this->getMatchingPriceModel();
        if (!$priceModel) {
            return 0.0;
        }

        $foil = strtolower(trim($this->foil_type ?? ''));
        $isHoloOrReverse = in_array($foil, ['holo', 'reverse']);

        // Se l'utente ha una variante foil e il record trovato NON ha is_reverse = true
        // (quindi siamo cascati nel Fallback 2 o successivi), tentiamo di usare le vecchie colonne holo.
        // Altrimenti, per le nuove righe multi-dimensionali, 'trend' o 'avg' è già il prezzo holo corretto!
        if ($isHoloOrReverse && !$priceModel->is_reverse) {
            return (float) ($priceModel->trend_holo ?? $priceModel->avg_holo ?? $priceModel->trend ?? $priceModel->avg ?? 0);
        }

        return (float) ($priceModel->trend ?? $priceModel->avg ?? 0);
    }
}
