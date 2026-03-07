<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Gestiamo la validazione per entrambi i casi: card_id singolo o cards_id array
        return [
            // card_id singolo
            'card_id' => [
                'required_without:cards_id',
                'nullable',
                'integer',
                'exists:pokemon_cards,id',
            ],

            // cards_id array
            'cards_id' => [
                'required_without:card_id',
                'nullable',
                'array',
            ],

            // ogni elemento dell'array
            'cards_id.*' => [
                'integer',
                'exists:pokemon_cards,id',
            ],
        ];
    }
}
