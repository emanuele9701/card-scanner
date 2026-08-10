<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCGSet extends Model
{
    protected $table = "tcg_sets";

    protected $fillable = [
        "set_id",
        "serie_id",
        "name",
        "logo",
        "symbol",
        "card_total",
        "card_official",
        "card_normal",
        "card_reverse",
        "card_holo",
        "card_first_edition",
        "release_date",
        'variants',
        "abbreviation",
        "abbreviation_official",
        "language",
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'variants' => 'array',
        'abbreviation' => 'array',
        'release_date' => 'date',
    ];

    public function serie()
    {
        return $this->belongsTo(TCGSeries::class, 'serie_id', 'id');
    }

    public function cards()
    {
        return $this->hasMany(TCGCard::class, 'set_id', 'id');
    }
}
