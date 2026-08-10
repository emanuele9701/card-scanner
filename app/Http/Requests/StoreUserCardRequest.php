<?php

namespace App\Http\Requests;

use App\Models\UserCardCollection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_id' => ['required', 'integer', 'exists:tcg_cards,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'variants' => ['nullable', 'array'],
            'variants.*' => ['string'],
            'condition' => ['required', 'string', Rule::in(UserCardCollection::CONDITIONS)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:5120'], // Max 5MB per foto
        ];
    }

    public function messages(): array
    {
        return [
            'card_id.exists' => 'La carta selezionata non esiste.',
            'condition.in' => 'La condizione deve essere una tra: ' . implode(', ', UserCardCollection::CONDITIONS),
            'photos.max' => 'Puoi caricare al massimo 5 foto.',
            'photos.*.max' => 'Ogni foto non può superare i 5MB.',
            'photos.*.mimes' => 'Le foto devono essere in formato JPEG, PNG o WebP.',
        ];
    }
}
