<?php

namespace App\Http\Controllers;

use App\Models\PokemonCard;
use App\Models\CardSet;
use App\Services\GeminiService;
use App\Services\ImageResizeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use Inertia\Inertia;

class CardUploadController extends Controller
{
    /**
     * Helper function to log memory usage
     */
    private function logMemoryUsage(string $step, array $extraData = []): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');

        Log::info("MEMORY TRACKING - {$step}", array_merge([
            'memory_current_mb' => round($memoryUsage / 1024 / 1024, 2),
            'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'memory_limit' => $memoryLimit,
            'memory_current_bytes' => $memoryUsage,
            'memory_peak_bytes' => $memoryPeak,
        ], $extraData));
    }

    /**
     * Show the upload form interface
     */
    public function showUploadForm()
    {
        $cards = PokemonCard::where('user_id', auth()->id())
            ->latest()
            ->take(10)
            ->get();
        // Separate cards with and without sets first
        $cardsWithoutSet = $cards->filter(fn($card) => $card->card_set_id === null)->values();
        $cardsWithSet = $cards->filter(fn($card) => $card->card_set_id !== null)->values();

        // Group cards with set by set name - converting to array for Inertia
        $cardsBySet = $cardsWithSet->groupBy(fn($card) => $card->cardSet->name);

        return Inertia::render('Cards/Upload', [
            'initialCards' => $cards,
            'cardsBySet' => $cardsBySet,
            'cardsWithoutSet' => $cardsWithoutSet
        ]);
    }

    /**
     * Step 1: Upload and save the cropped card image
     */
    /**
     * Step 1: Upload raw image (initial state: pending)
     */
    public function uploadRawImage(Request $request)
    {
        try {
            $this->logMemoryUsage('START - Upload raw image');

            Log::info('Starting raw image upload', [
                'user_id' => auth()->id(),
                'has_image' => $request->hasFile('image')
            ]);

            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:30720',
            ]);

            $this->logMemoryUsage('AFTER - Validation');

            $file = $request->file('image');
            $originalFilename = $file->getClientOriginalName();
            $fileSize = $file->getSize();

            Log::info('Image file received', [
                'filename' => $originalFilename,
                'size' => $fileSize,
                'size_mb' => round($fileSize / 1024 / 1024, 2),
                'mime_type' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension()
            ]);

            $this->logMemoryUsage('AFTER - File info extracted', [
                'file_size_mb' => round($fileSize / 1024 / 1024, 2)
            ]);

            $path = $file->store('pokemon_cards', 'public');

            Log::info('Image stored', ['path' => $path]);

            $this->logMemoryUsage('AFTER - File stored to disk');

            // Force garbage collection before resize
            gc_collect_cycles();
            $this->logMemoryUsage('AFTER - Garbage collection');

            // Resize image if needed
            $imageResizeService = app(ImageResizeService::class);
            $wasResized = $imageResizeService->resizeIfNeeded($path, 'public');

            $this->logMemoryUsage('AFTER - Resize service completed', [
                'was_resized' => $wasResized
            ]);

            // Force garbage collection after resize
            gc_collect_cycles();
            $this->logMemoryUsage('AFTER - Garbage collection post-resize');

            Log::info('Image resize check completed', [
                'was_resized' => $wasResized,
                'path' => $path
            ]);

            $card = PokemonCard::create([
                'user_id' => auth()->id(),
                'original_filename' => $originalFilename,
                'storage_path' => $path,
                'status' => PokemonCard::STATUS_PENDING,
            ]);

            $this->logMemoryUsage('AFTER - Database record created');

            Log::info('PokemonCard record created', [
                'card_id' => $card->id,
                'status' => $card->status
            ]);

            $this->logMemoryUsage('END - Upload raw image completed');

            return response()->json([
                'success' => true,
                'message' => 'Immagine caricata.',
                'data' => [
                    'id' => $card->id,
                    'image_url' => $card->getImageUrl(),
                    'status' => PokemonCard::STATUS_PENDING
                ]
            ]);
        } catch (Exception $e) {
            $this->logMemoryUsage('ERROR - Exception caught');

            Log::error('Error uploading raw image', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il caricamento dell\'immagine.'
            ], 500);
        }
    }

    /**
     * Step 1b: Save cropped image (transitions to: ready_for_ai)
     */
    public function saveCroppedImage(Request $request)
    {
        $request->validate([
            'card_id' => 'required|exists:pokemon_cards,id',
            'cropped_image' => 'required|image|mimes:jpeg,png,jpg,webp|max:30720',
        ]);

        $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);

        // Delete old image if it exists and is different (optional optimization)
        // For simplicity, we just overwrite/store new one and update path

        $file = $request->file('cropped_image');
        $path = $file->store('pokemon_cards', 'public');

        // Resize image if needed
        $imageResizeService = app(ImageResizeService::class);
        $imageResizeService->resizeIfNeeded($path, 'public');

        // Delete old file to save space
        if ($card->storage_path && Storage::disk('public')->exists($card->storage_path)) {
            Storage::disk('public')->delete($card->storage_path);
        }

        $card->update([
            'storage_path' => $path,
            'status' => PokemonCard::STATUS_READY_FOR_AI
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ritaglio salvato.',
            'data' => [
                'id' => $card->id,
                'image_url' => $card->getImageUrl(),
                'status' => PokemonCard::STATUS_READY_FOR_AI
            ]
        ]);
    }

    /**
     * Step 1c: Skip cropping (transitions to: ready_for_ai)
     */
    public function skipCrop(Request $request)
    {
        $request->validate([
            'card_id' => 'required|exists:pokemon_cards,id',
        ]);

        $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);

        $card->update([
            'status' => PokemonCard::STATUS_READY_FOR_AI
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ritaglio saltato.',
            'data' => [
                'id' => $card->id,
                'status' => PokemonCard::STATUS_READY_FOR_AI
            ]
        ]);
    }

    /**
     * Legacy method acting as aliases for compatibility if needed, 
     * but we will update routes.
     */
    public function uploadImage(Request $request)
    {
        // Redirect to new logic based on input
        if ($request->has('cropped_image') && !$request->has('image')) {
            // If it has cropped_image but no card_id, it's the old flow. 
            // We can support it by creating a new card directly in ready state or adapting.
            // But for now let's just use uploadRawImage logic adapted.

            // ... adaptation logic omitted, assuming we update frontend routes ...
            // Behaving as uploadRawImage for now but mapping input
            $request->merge(['image' => $request->file('cropped_image')]);
            return $this->uploadRawImage($request);
        }
        return $this->uploadRawImage($request);
    }

    /**
     * Step 2: Enhance card data with Gemini AI
     */
    public function enhanceWithAI(Request $request, GeminiService $geminiService)
    {
        $request->validate([
            'card_id' => 'required|exists:pokemon_cards,id',
        ]);

        $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);

        // Get image content for AI
        $imagePath = Storage::disk('public')->path($card->storage_path);
        if (!file_exists($imagePath)) {
            return response()->json(['success' => false, 'message' => 'File immagine non trovato'], 404);
        }

        $base64Image = base64_encode(file_get_contents($imagePath));

        // Call Gemini AI for card recognition
        $aiResult = $geminiService->enhanceCardData($base64Image, '');

        if ($aiResult) {
            // Check if AI detected this is NOT a valid card
            if (isset($aiResult['is_valid_card']) && $aiResult['is_valid_card'] === false) {
                // Update card status to failed
                $card->update(['status' => PokemonCard::STATUS_FAILED]);

                return response()->json([
                    'success' => false,
                    'is_not_card' => true,
                    'message' => $aiResult['error_message'] ?? 'L\'immagine non sembra essere una carta da gioco collezionabile'
                ], 422);
            }

            // Update card status
            $card->update(['status' => PokemonCard::STATUS_REVIEW]);

            return response()->json([
                'success' => true,
                'message' => 'Riconoscimento AI completato!',
                'data' => $aiResult
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Impossibile ottenere una risposta valida dall\'AI.'
        ], 500);
    }

    /**
     * Step 3: Save final card data (from AI or manual entry)
     */
    public function saveCard(Request $request)
    {
        $request->validate([
            'card_id' => 'required|exists:pokemon_cards,id',
            'card_name' => 'nullable|string',
            'hp' => 'nullable|string',
            'type' => 'nullable|string',
            'evolution_stage' => 'nullable|string',
            'attacks_json' => 'nullable|string',
            'weakness' => 'nullable|string',
            'resistance' => 'nullable|string',
            'retreat_cost' => 'nullable|string',
            'rarity' => 'nullable|string',
            'set_number' => 'nullable|string',
            'illustrator' => 'nullable|string',
            'flavor_text' => 'nullable|string',
            'card_set_id' => 'nullable|exists:card_sets,id',
        ]);

        $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);

        // Decode attacks JSON string if present
        $attacks = null;
        if ($request->attacks_json) {
            $attacks = json_decode($request->attacks_json, true);
        }

        $card->update([
            'card_name' => $request->card_name,
            'hp' => $request->hp,
            'type' => $request->type,
            'evolution_stage' => $request->evolution_stage,
            'attacks' => $attacks,
            'weakness' => $request->weakness,
            'resistance' => $request->resistance,
            'retreat_cost' => $request->retreat_cost,
            'rarity' => $request->rarity,
            'set_number' => $request->set_number,
            'illustrator' => $request->illustrator,
            'flavor_text' => $request->flavor_text,
            'card_set_id' => $request->card_set_id,
            'status' => PokemonCard::STATUS_COMPLETED,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Carta salvata correttamente!'
        ]);
    }

    /**
     * Discard a card
     */
    public function discard(Request $request)
    {
        $request->validate([
            'card_id' => 'required|exists:pokemon_cards,id',
        ]);

        $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);

        if (Storage::disk('public')->exists($card->storage_path)) {
            Storage::disk('public')->delete($card->storage_path);
        }

        $card->delete();

        return response()->json([
            'success' => true,
            'message' => 'Carta eliminata.'
        ]);
    }

    /**
     * Get all scanned cards grouped by set
     */
    public function index()
    {
        $allCards = PokemonCard::with('cardSet')
            ->where('user_id', auth()->id())
            ->where('status', PokemonCard::STATUS_COMPLETED)
            ->orderBy('card_name')
            ->get();

        // Separate cards with and without sets first
        $cardsWithoutSet = $allCards->filter(fn($card) => $card->card_set_id === null);
        $cardsWithSet = $allCards->filter(fn($card) => $card->card_set_id !== null);

        // Group cards with set by set name
        $cardsBySet = $cardsWithSet->groupBy(fn($card) => $card->cardSet->name);

        return Inertia::render('Cards/Index', [
            'cardsBySet' => $cardsBySet,
            'cardsWithoutSet' => $cardsWithoutSet
        ]);
    }

    /**
     * Update a card from the index page
     */
    public function updateCard(Request $request, PokemonCard $card)
    {
        if ($card->user_id !== auth()->id()) {
            abort(403);
        }
        $request->validate([
            'card_name' => 'nullable|string',
            'hp' => 'nullable|string',
            'type' => 'nullable|string',
            'evolution_stage' => 'nullable|string',
            'weakness' => 'nullable|string',
            'resistance' => 'nullable|string',
            'retreat_cost' => 'nullable|string',
            'rarity' => 'nullable|string',
            'set_number' => 'nullable|string',
            'illustrator' => 'nullable|string',
            'flavor_text' => 'nullable|string',
            'card_set_id' => 'nullable|exists:card_sets,id',
        ]);

        $card->update($request->only([
            'card_name',
            'hp',
            'type',
            'evolution_stage',
            'weakness',
            'resistance',
            'retreat_cost',
            'rarity',
            'set_number',
            'illustrator',
            'flavor_text',
            'card_set_id'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Carta aggiornata con successo!',
            'data' => $card->load('cardSet')
        ]);
    }

    /**
     * Assign set to one or more cards
     */
    public function assignSet(Request $request)
    {
        $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'exists:pokemon_cards,id',
            'card_set_id' => 'required|exists:card_sets,id',
        ]);

        PokemonCard::where('user_id', auth()->id())
            ->whereIn('id', $request->card_ids)
            ->update(['card_set_id' => $request->card_set_id]);

        return response()->json([
            'success' => true,
            'message' => 'Set assegnato con successo!'
        ]);
    }

    /**
     * Get all card sets for dropdown
     */
    public function getCardSets()
    {
        $sets = CardSet::orderBy('name')->get(['id', 'name', 'abbreviation']);

        return response()->json([
            'success' => true,
            'data' => $sets
        ]);
    }

    /**
     * Get single card data for modal display
     */
    public function getCardData(PokemonCard $card)
    {
        if ($card->user_id !== auth()->id()) {
            abort(403);
        }
        $card->load('cardSet');

        return response()->json([
            'success' => true,
            'card' => [
                'id' => $card->id,
                'card_name' => $card->card_name,
                'hp' => $card->hp,
                'type' => $card->type,
                'evolution_stage' => $card->evolution_stage,
                'weakness' => $card->weakness,
                'resistance' => $card->resistance,
                'retreat_cost' => $card->retreat_cost,
                'set_number' => $card->set_number,
                'rarity' => $card->rarity,
                'illustrator' => $card->illustrator,
                'flavor_text' => $card->flavor_text,
                'condition' => $card->condition,
                'acquisition_price' => $card->acquisition_price,
                'image_url' => $card->image_url,
                'card_set_id' => $card->card_set_id,
                'card_set' => $card->cardSet ? ['name' => $card->cardSet->name] : null,
                'estimated_value' => $card->formatted_estimated_value,
            ]
        ]);
    }

    /**
     * Delete a card
     */
    public function destroy(PokemonCard $card)
    {
        if ($card->user_id !== auth()->id()) {
            abort(403);
        }
        if (Storage::disk('public')->exists($card->storage_path)) {
            Storage::disk('public')->delete($card->storage_path);
        }

        $card->delete();

        return response()->json([
            'success' => true,
            'message' => 'Carta eliminata con successo!'
        ]);
    }
}
