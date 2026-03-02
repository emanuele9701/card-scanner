<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    public function rules(): array
    {
        return [
            'card_name' => 'nullable|string',
            'hp' => 'nullable|string',
            'type' => 'nullable|string',
            'evolution_stage' => 'nullable|string',
            'rarity' => 'nullable|string',
            'set_number' => 'nullable|string',
            'illustrator' => 'nullable|string',
            'card_set_id' => 'nullable|exists:card_sets,id',
            'weakness' => 'nullable|string',
            'resistance' => 'nullable|string',
            'game' => 'nullable|string',
        ];
    }
}
