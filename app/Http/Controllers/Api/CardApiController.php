<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PokemonCard;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CardApiController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Update the specified card in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PokemonCard  $card
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, PokemonCard $card)
    {
        // Check if user owns the card
        if ($request->user()->id !== $card->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validation
        $validated = $request->validate([
            'card_name' => 'nullable|string|max:255',
            'hp' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'evolution_stage' => 'nullable|string|max:255',
            'rarity' => 'nullable|string|max:255',
            'set_number' => 'nullable|string|max:255',
            'illustrator' => 'nullable|string|max:255',
            'flavor_text' => 'nullable|string',
            'game' => 'nullable|string|max:255',
            'condition' => 'nullable|string|max:255',
            'printing' => 'nullable|string|max:255',
            'acquisition_price' => 'nullable|numeric',
            'acquisition_date' => 'nullable|date',
            'image' => 'nullable|image|max:10240', // Max 10MB
        ]);

        DB::beginTransaction();
        try {
            // Update basic fields
            $card->fill($request->only([
                'card_name',
                'hp',
                'type',
                'evolution_stage',
                'rarity',
                'set_number',
                'illustrator',
                'flavor_text',
                'game',
                'condition',
                'printing',
                'acquisition_price',
                'acquisition_date'
            ]));

            // Handle Image Upload
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $timestamp = now()->timestamp;
                $extension = $file->getClientOriginalExtension();
                $filename = "card_{$card->id}_{$timestamp}.{$extension}";

                // Store locally (public disk)
                $path = $file->storeAs('cards/user_' . $request->user()->id, $filename, 'public');

                // Delete old Drive file if exists
                if ($card->driveFile) {
                    try {
                        $this->googleDriveService->deleteFile($card->driveFile->drive_id);
                    } catch (\Exception $e) {
                        Log::warning("Failed to delete old Drive file: " . $e->getMessage());
                        // Continue even if delete fails
                    }
                }

                // Upload new file to Drive
                try {
                    $this->googleDriveService->uploadFile(
                        $path,
                        $filename,
                        $request->user()->id,
                        $card->id,
                        true // Make public
                    );
                } catch (\Exception $e) {
                    Log::error("Failed to upload new image to Drive: " . $e->getMessage());
                    // Decide if we want to fail the whole transaction or just log it. 
                    // Requirement says "modification must also happen on Drive", so failing seems appropriate.
                    throw $e;
                }

                // Update storage path
                $card->storage_path = $path;
                $card->original_filename = $file->getClientOriginalName();
            }

            $card->save();
            DB::commit();

            return response()->json([
                'message' => 'Card updated successfully',
                'data' => $card,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Card update failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update card',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified card from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PokemonCard  $card
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, PokemonCard $card)
    {
        // Check if user owns the card
        if ($request->user()->id !== $card->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        try {
            // Delete file from Google Drive
            if ($card->driveFile) {
                try {
                    $this->googleDriveService->deleteFile($card->driveFile->drive_id);
                } catch (\Exception $e) {
                    Log::warning("Failed to delete File from Drive during card deletion: " . $e->getMessage());
                    // Continue deletion even if Drive fails (avoid blocking user)
                }
            }

            // Delete local file
            if ($card->storage_path) {
                if (Storage::disk('public')->exists($card->storage_path)) {
                    Storage::disk('public')->delete($card->storage_path);
                } elseif (Storage::disk('local')->exists($card->storage_path)) {
                    Storage::disk('local')->delete($card->storage_path);
                }
            }

            // Delete card record
            $card->delete();

            DB::commit();

            return response()->json([
                'message' => 'Card deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Card deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete card',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available conditions for a matched card based on market data
     *
     * @param Request $request
     * @param PokemonCard $card
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConditions(Request $request, PokemonCard $card)
    {
        if ($request->user()->id !== $card->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$card->market_card_id) {
            return response()->json([
                'message' => 'Card must be matched to get market conditions',
                'conditions' => []
            ], 422);
        }

        // Get unique conditions from market prices for this card
        $conditions = \App\Models\MarketPrice::where('market_card_id', $card->market_card_id)
            ->distinct()
            ->pluck('condition')
            ->filter()
            ->values();

        // Fallback to standard conditions if no market data found
        if ($conditions->isEmpty()) {
            $conditions = collect(\App\Models\CardInventory::CONDITIONS);
        }

        return response()->json([
            'card_id' => $card->id,
            'market_card_id' => $card->market_card_id,
            'conditions' => $conditions
        ]);
    }

    /**
     * Update the condition of a card
     *
     * @param Request $request
     * @param PokemonCard $card
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCondition(Request $request, PokemonCard $card)
    {
        if ($request->user()->id !== $card->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'condition' => 'required|string|max:255',
        ]);

        $card->condition = $validated['condition'];
        $card->save();

        return response()->json([
            'message' => 'Card condition updated successfully',
            'data' => [
                'id' => $card->id,
                'condition' => $card->condition,
                'estimated_value' => $card->getEstimatedValue(),
                'formatted_value' => $card->formatted_estimated_value
            ]
        ]);
    }

    /**
     * Remove the set association from a card
     *
     * @param Request $request
     * @param PokemonCard $card
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeSet(Request $request, PokemonCard $card)
    {
        if ($request->user()->id !== $card->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $card->card_set_id = null;
        $card->set_number = null; // Usually if set is removed, set number might also be irrelevant or kept? 
        // User asked "remove set". Typically implies dissociating.
        // Let's safe keep set_number if they want to reassign, but usually they go together.
        // For now, just nulling card_set_id is safer/sufficient.
        $card->save();

        return response()->json([
            'message' => 'Card set removed successfully',
            'data' => [
                'id' => $card->id,
                'card_set_id' => null
            ]
        ]);
    }

    /**
     * Set the card set for a card
     *
     * @param Request $request
     * @param PokemonCard $card
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateSet(Request $request, PokemonCard $card)
    {
        if ($request->user()->id !== $card->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'card_set_id' => 'required|exists:card_sets,id',
            'set_number' => 'nullable|string|max:255',
        ]);

        $card->card_set_id = $validated['card_set_id'];

        if (isset($validated['set_number'])) {
            $card->set_number = $validated['set_number'];
        }

        $card->save();
        $card->load('cardSet'); // Reload relationship for response

        return response()->json([
            'message' => 'Card set updated successfully',
            'data' => [
                'id' => $card->id,
                'card_set_id' => $card->card_set_id,
                'set_number' => $card->set_number,
                'set' => $card->cardSet ? [
                    'id' => $card->cardSet->id,
                    'name' => $card->cardSet->name,
                    'abbreviation' => $card->cardSet->abbreviation,
                ] : null
            ]
        ]);
    }
}
