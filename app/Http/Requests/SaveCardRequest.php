<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_id' => 'required|exists:pokemon_cards,id',
            'card_name' => 'nullable|string',
            'hp' => 'nullable',
            'type' => 'nullable|string',
            'evolution_stage' => 'nullable|string',
            'attacks_json' => 'nullable|string',
            'attacks' => 'nullable|array',
            'weakness' => 'nullable',
            'resistance' => 'nullable',
            'retreat_cost' => 'nullable',
            'rarity' => 'nullable|string',
            'set_number' => 'nullable',
            'illustrator' => 'nullable|string',
            'card_set_id' => 'nullable|exists:card_sets,id',
            'game' => 'required|string',
            'market_card_id' => 'nullable|exists:market_cards,id',
            'pricing' => 'nullable|array',
        ];
    }
}
