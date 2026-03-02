<?php

namespace App\Http\Requests;

use App\Models\CardInventory;
use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1',
            'rarity_variant' => 'required|in:' . implode(',', CardInventory::RARITY_VARIANTS),
            'condition' => 'required|in:' . implode(',', CardInventory::CONDITIONS),
            'notes' => 'nullable|string',
        ];
    }
}
