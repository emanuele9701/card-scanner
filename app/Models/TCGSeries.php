<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCGSeries extends Model
{
    protected $table = "tcg_series";

    protected $fillable = [
        "serie_id",
        "name",
        'logo',
        'language',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function sets()
    {
        return $this->hasMany(TCGSet::class, 'serie_id', 'id');
    }
}
