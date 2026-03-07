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
        return array_merge(
            $this->singleCardRules(),
            [
                'cards' => 'required_without:card_id|nullable|array',
            ],
            $this->multipleCardsRules()
        );
    }

    private function baseCardRules(): array
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

    private function multipleCardsRules(): array
    {
        $rules = [];

        foreach ($this->baseCardRules() as $field => $rule) {
            $rules["cards.*.$field"] = $rule;
        }

        return $rules;
    }

    private function singleCardRules(): array
    {
        $rules = $this->baseCardRules();

        // card_id deve essere required solo se non c'è cards
        $rules['card_id'] = 'required_without:cards|exists:pokemon_cards,id';
        $rules['game'] = 'required_without:cards|string';

        return $rules;
    }
}
