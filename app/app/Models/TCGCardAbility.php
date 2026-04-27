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

    public static function createAbilities($idCard, $abilities, $language)
    {
        foreach ($abilities as $key => $value) {
            $ability = new TCGCardAbility();
            $ability->card_id = $idCard;
            $ability->type = $value->type ?? "";
            $ability->cost = $value->cost ?? "";
            $ability->name = $value->name ?? "";
            $ability->effect = $value->effect ?? "";
            $ability->damage = $value->damage ?? "";
            $ability->language = $language;
            $ability->save();
        }
    }
}
