<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveCroppedImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'card_id' => 'required|exists:pokemon_cards,id',
            'cropped_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:30720',
        ];
    }
}
