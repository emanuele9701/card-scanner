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
        "condition",
        "is_first_edition",
        "is_altered",
        "is_signed",
        "is_reverse",
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
    public static function preparePriceData($idCard, $pricing, $language, $now)
    {
        if (!$pricing) return null;

        $priceData = [
            'card_id' => $idCard,
            'provider' => 'cardmarket',
            'card_id_product' => $pricing->idProduct ?? null,
            'unit' => $pricing->unit ?? 'EUR',
            'avg' => $pricing->avg ?? null,
            'low' => $pricing->low ?? null,
            'trend' => $pricing->trend ?? null,
            'avg_1d' => $pricing->avg1 ?? null,
            'avg_7d' => $pricing->avg7 ?? null,
            'avg_30d' => $pricing->avg30 ?? null,
            'avg_holo' => $pricing->{'avg-holo'} ?? null,
            'low_holo' => $pricing->{'low-holo'} ?? null,
            'trend_holo' => $pricing->{'trend-holo'} ?? null,
            'avg_1d_holo' => $pricing->{'avg1-holo'} ?? null,
            'avg_7d_holo' => $pricing->{'avg7-holo'} ?? null,
            'avg_30d_holo' => $pricing->{'avg30-holo'} ?? null,
            'language' => $language,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $historyData = [
            'card_id' => $idCard,
            'provider' => 'cardmarket',
            'trend' => $priceData['trend'],
            'trend_holo' => $priceData['trend_holo'],
            'avg' => $priceData['avg'],
            'avg_holo' => $priceData['avg_holo'],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        return [
            'price' => $priceData,
            'history' => $historyData
        ];
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
    public static function prepareTcgPlayerPriceData($idCard, $pricing, $language, $now)
    {
        if (!$pricing) return null;
        
        $priceData = [
            'card_id' => $idCard,
            'provider' => 'tcgplayer',
            'card_id_product' => null,
            'unit' => $pricing->unit ?? 'USD',
            'avg' => null,
            'low' => null,
            'trend' => null,
            'avg_1d' => null,
            'avg_7d' => null,
            'avg_30d' => null,
            'avg_holo' => null,
            'low_holo' => null,
            'trend_holo' => null,
            'avg_1d_holo' => null,
            'avg_7d_holo' => null,
            'avg_30d_holo' => null,
            'language' => $language,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // ── Variante Normal ──
        $normal = $pricing->normal ?? null;
        if ($normal) {
            $priceData['low'] = $normal->lowPrice ?? null;
            $priceData['avg'] = $normal->midPrice ?? null;
            $priceData['trend'] = $normal->marketPrice ?? null;
            $priceData['avg_30d'] = $normal->highPrice ?? null;
            $priceData['avg_1d'] = $normal->directLowPrice ?? null;
        }

        // ── Variante Holo (holofoil o reverse-holofoil) ──
        // Priorità: holofoil > reverse-holofoil
        $holo = $pricing->holofoil ?? $pricing->{'reverse-holofoil'} ?? null;
        if ($holo) {
            $priceData['low_holo'] = $holo->lowPrice ?? null;
            $priceData['avg_holo'] = $holo->midPrice ?? null;
            $priceData['trend_holo'] = $holo->marketPrice ?? null;
            $priceData['avg_30d_holo'] = $holo->highPrice ?? null;
            $priceData['avg_1d_holo'] = $holo->directLowPrice ?? null;
        }

        // Se c'è anche la reverse separata dalla holofoil, usiamo i campi _7d_holo per non perderla
        if (isset($pricing->holofoil) && isset($pricing->{'reverse-holofoil'})) {
            $reverse = $pricing->{'reverse-holofoil'};
            $priceData['avg_7d'] = $reverse->midPrice ?? null;
            $priceData['avg_7d_holo'] = $reverse->marketPrice ?? null;
        }

        $historyData = [
            'card_id' => $idCard,
            'provider' => 'tcgplayer',
            'trend' => $priceData['trend'],
            'trend_holo' => $priceData['trend_holo'],
            'avg' => $priceData['avg'],
            'avg_holo' => $priceData['avg_holo'],
            'created_at' => $now,
            'updated_at' => $now,
        ];

        return [
            'price' => $priceData,
            'history' => $historyData
        ];
    }
}
