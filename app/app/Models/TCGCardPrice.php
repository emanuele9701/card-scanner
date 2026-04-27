<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCGCardPrice extends Model
{
    protected $table = "tcg_card_prices";

    protected $fillable = [
        "card_id",
        'card_id_product', // quello di cardmarket
        'unit', // quello di cardmarket
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
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function card()
    {
        return $this->belongsTo(TCGCard::class, 'card_id', 'id');
    }

    public static function createPrices($idCard, $pricing, $language)
    {
        $price = new TCGCardPrice();
        $price->card_id = $idCard;
        $price->card_id_product = $pricing->idProduct ?? null;
        $price->unit = $pricing->unit ?? null;
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
        $price->save();
    }
}
