<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCGCardPrice extends Model
{
    protected $table = "tcg_card_prices";

    protected $fillable = [
        "card_id",
        "provider",
        'card_id_product', // quello di cardmarket
        'unit', // valuta (EUR / USD)
        "avg",
        "low",
        "trend",
        "avg_1d",
        "avg_7d",
        "avg_30d",
        "avg_holo",
        "low_holo",
        "trend_holo",
        "avg_1d_holo",
        "avg_7d_holo",
        "avg_30d_holo",
        "language",
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'date',
    ];

    public function card()
    {
        return $this->belongsTo(TCGCard::class, 'card_id', 'id');
    }

    /**
     * Crea un record di prezzo da dati CardMarket.
     */
    public static function createPrices($idCard, $pricing, $language)
    {
        if (!$pricing) return;

        $price = new TCGCardPrice();
        $price->card_id = $idCard;
        $price->provider = 'cardmarket';
        $price->card_id_product = $pricing->idProduct ?? null;
        $price->unit = $pricing->unit ?? 'EUR';
        $price->avg = $pricing->avg ?? null;
        $price->low = $pricing->low ?? null;
        $price->trend = $pricing->trend ?? null;
        $price->avg_1d = $pricing->avg1 ?? null;
        $price->avg_7d = $pricing->avg7 ?? null;
        $price->avg_30d = $pricing->avg30 ?? null;
        $price->avg_holo = $pricing->{'avg-holo'} ?? null;
        $price->low_holo = $pricing->{'low-holo'} ?? null;
        $price->trend_holo = $pricing->{'trend-holo'} ?? null;
        $price->avg_1d_holo = $pricing->{'avg1-holo'} ?? null;
        $price->avg_7d_holo = $pricing->{'avg7-holo'} ?? null;
        $price->avg_30d_holo = $pricing->{'avg30-holo'} ?? null;
        $price->language = $language;
        $price->save();
    }

    /**
     * Crea un record di prezzo da dati TCGPlayer adattandoli ai campi esistenti.
     *
     * Mappatura TCGPlayer → Colonne DB:
     *  - normal.lowPrice      → low
     *  - normal.midPrice      → avg
     *  - normal.marketPrice   → trend
     *  - normal.highPrice     → avg_30d (prezzo massimo, utile come riferimento)
     *  - normal.directLowPrice→ avg_1d  (prezzo diretto più basso)
     *  - holofoil/reverse varianti → campi _holo
     */
    public static function createTcgPlayerPrices($idCard, $pricing, $language)
    {
        if (!$pricing) return;

        $price = new TCGCardPrice();
        $price->card_id = $idCard;
        $price->provider = 'tcgplayer';
        $price->unit = $pricing->unit ?? 'USD';
        $price->language = $language;

        // ── Variante Normal ──
        $normal = $pricing->normal ?? null;
        if ($normal) {
            $price->low = $normal->lowPrice ?? null;
            $price->avg = $normal->midPrice ?? null;
            $price->trend = $normal->marketPrice ?? null;
            $price->avg_30d = $normal->highPrice ?? null;
            $price->avg_1d = $normal->directLowPrice ?? null;
        }

        // ── Variante Holo (holofoil o reverse-holofoil) ──
        // Priorità: holofoil > reverse-holofoil
        $holo = $pricing->holofoil ?? $pricing->{'reverse-holofoil'} ?? null;
        if ($holo) {
            $price->low_holo = $holo->lowPrice ?? null;
            $price->avg_holo = $holo->midPrice ?? null;
            $price->trend_holo = $holo->marketPrice ?? null;
            $price->avg_30d_holo = $holo->highPrice ?? null;
            $price->avg_1d_holo = $holo->directLowPrice ?? null;
        }

        // Se c'è anche la reverse separata dalla holofoil, usiamo i campi _7d_holo per non perderla
        if (isset($pricing->holofoil) && isset($pricing->{'reverse-holofoil'})) {
            $reverse = $pricing->{'reverse-holofoil'};
            $price->avg_7d = $reverse->midPrice ?? null;
            $price->avg_7d_holo = $reverse->marketPrice ?? null;
        }

        $price->save();
    }
}
