<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use App\Models\CardInventory;
use App\Models\PokemonCard;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CardInventoryController extends Controller
{
    use AuthorizesRequests;

    /**
     * Get inventory items for a specific card
     */
    public function index(PokemonCard $card)
    {
        $this->authorize('view', $card);

        return response()->json([
            'success' => true,
            'data' => $card->inventory()->get()
        ]);
    }

    /**
     * Add or update inventory item
     */
    public function store(StoreInventoryRequest $request, PokemonCard $card)
    {
        $this->authorize('update', $card);

        $inventory = CardInventory::updateOrCreate(
            [
                'pokemon_card_id' => $card->id,
                'user_id' => auth()->id(),
                'rarity_variant' => $request->rarity_variant,
                'condition' => $request->condition
            ],
            [
                'quantity' => $request->quantity,
                'notes' => $request->notes
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Inventario aggiornato con successo!',
            'data' => $inventory
        ]);
    }

    /**
     * Update an inventory item
     */
    public function update(UpdateInventoryRequest $request, CardInventory $inventory)
    {
        // Authorization is handled by UpdateInventoryRequest::authorize()
        $inventory->update($request->only(['quantity', 'rarity_variant', 'condition', 'notes']));

        return response()->json([
            'success' => true,
            'message' => 'Inventario aggiornato con successo!',
            'data' => $inventory
        ]);
    }

    /**
     * Delete an inventory item
     */
    public function destroy(CardInventory $inventory)
    {
        $this->authorize('delete', $inventory);

        $inventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Elemento inventario eliminato!'
        ]);
    }

    /**
     * Get available rarity variants and conditions for dropdowns
     */
    public function options()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'rarity_variants' => CardInventory::RARITY_VARIANTS,
                'conditions' => CardInventory::CONDITIONS
            ]
        ]);
    }
}
