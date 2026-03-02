<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_ids' => 'required|array',
            'card_ids.*' => 'exists:pokemon_cards,id',
            'card_set_id' => 'required|exists:card_sets,id',
        ];
    }
}
