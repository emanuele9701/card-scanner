<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TCGCardAbility extends Model
{
    protected $table = "tcg_card_abilities";

    protected $fillable = [
        "card_id",
        'type',
        'cost',
        'name',
        'effect',
        'damage',
        'language',
    ];

    protected $casts = [
        'cost' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function card()
    {
        return $this->belongsTo(TCGCard::class, 'card_id', 'id');
    }

    public static function prepareAbilitiesData($idCard, $abilities, $language, $now)
    {
        $data = [];
        foreach ($abilities as $value) {
            $data[] = [
                'card_id' => $idCard,
                'type' => $value->type ?? "",
                'cost' => isset($value->cost) ? json_encode($value->cost) : null,
                'name' => $value->name ?? "",
                'effect' => $value->effect ?? "",
                'damage' => $value->damage ?? "",
                'language' => $language,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        return $data;
    }
}
