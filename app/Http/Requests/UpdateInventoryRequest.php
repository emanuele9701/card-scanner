<?php

namespace App\Http\Requests;

use App\Models\CardInventory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('inventory')->user_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'sometimes|required|integer|min:1',
            'rarity_variant' => 'sometimes|required|in:' . implode(',', CardInventory::RARITY_VARIANTS),
            'condition' => 'sometimes|required|in:' . implode(',', CardInventory::CONDITIONS),
            'notes' => 'nullable|string',
        ];
    }
}
