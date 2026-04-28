<?php

namespace App\Http\Requests;

use App\Models\UserCardCollection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'variants' => ['sometimes', 'nullable', 'array'],
            'variants.*' => ['string'],
            'condition' => ['sometimes', 'string', Rule::in(UserCardCollection::CONDITIONS)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'mimes:jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'condition.in' => 'La condizione deve essere una tra: ' . implode(', ', UserCardCollection::CONDITIONS),
            'photos.max' => 'Puoi caricare al massimo 5 foto.',
            'photos.*.max' => 'Ogni foto non può superare i 5MB.',
            'photos.*.mimes' => 'Le foto devono essere in formato JPEG, PNG o WebP.',
        ];
    }
}
