<?php

namespace App\Http\Controllers;

use App\Models\PokemonCard;
use App\Models\CardSet;
use App\Models\GoogleDriveFile;
use App\Services\GeminiService;
use App\Services\GoogleDriveService;
use App\Services\ImageResizeService;
use App\Models\MarketCard;
use App\Models\MarketPrice;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use Inertia\Inertia;
use TCGdex\Model\CardResume;
use TCGdex\Query;
use TCGdex\TCGdex;
use TCGdex\Model\Card;
use TCGdex\Model\SubModel\Attack;

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

        if (!$aiResult) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile ottenere una risposta valida dall\'AI.'
            ], 500);
        }

        // Map new Gemini structured data to legacy format
        $mappedData = $geminiService->mapGeminiToLegacyFormat($aiResult);

        // Check validity FIRST — avoid unnecessary API calls for invalid cards
        if (isset($mappedData['is_valid_card']) && $mappedData['is_valid_card'] === false) {
            $card->update(['status' => PokemonCard::STATUS_FAILED]);
            return response()->json([
                'success' => false,
                'is_not_card' => true,
                'message' => $mappedData['error_message'] ?? 'L\'immagine non sembra essere una carta da gioco collezionabile'
            ], 422);
        }

        Log::info("Card valida");
        // Enrich mapped data with TCGdex API info
        $mappedData = $this->lookupAndEnrichFromTCGdex($mappedData);

        // Normalize game name to match database
        if (isset($mappedData['game']) && preg_match('/Pokemon/i', $mappedData['game'])) {
            $mappedData['game'] = 'Pokemon';
        }

        // Update card status
        $card->update(['status' => PokemonCard::STATUS_REVIEW]);

        return response()->json([
            'success' => true,
            'message' => 'Riconoscimento AI completato!',
            'data' => $mappedData
        ]);
    }

    /**
     * Step 3: Save final card data (from AI or manual entry)
     */
    public function saveCard(Request $request)
    {
        $request->validate([
            'card_id' => 'required|exists:pokemon_cards,id',
            'card_name' => 'nullable|string',
            'hp' => 'nullable',
            'type' => 'nullable|string',
            'evolution_stage' => 'nullable|string',
            'attacks_json' => 'nullable|string',
            'attacks' => 'nullable|array',
            'weakness' => 'nullable',
            'resistance' => 'nullable',
            'retreat_cost' => 'nullable',
            'rarity' => 'nullable|string',
            'set_number' => 'nullable',
            'illustrator' => 'nullable|string',
            'card_set_id' => 'nullable|exists:card_sets,id',
            'game' => 'required|string',
            'market_card_id' => 'nullable|exists:market_cards,id',
            'pricing' => 'nullable|array',
        ]);

        $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);

        // Handle attacks - accept both JSON string and array
        $attacks = null;
        if ($request->filled('attacks') && is_array($request->attacks)) {
            // Direct array from new frontend
            $attacks = $request->attacks;
        } elseif ($request->filled('attacks_json')) {
            // JSON string from old frontend or API
            $attacks = json_decode($request->attacks_json, true);
        }

        // Ensure game exists and get ID
        $gameId = null;
        if ($request->game) {
            $gameModel = \App\Models\Game::firstOrCreate(
                [
                    'name' => $request->game,
                ]
            );
            $gameId = $gameModel->id;
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
            'market_card_id' => $request->market_card_id,
            'game' => $request->game,
            'game_id' => $gameId,
            'status' => PokemonCard::STATUS_COMPLETED,
        ]);

        // Persist market pricing if provided
        if ($request->filled('pricing') && $request->filled('market_card_id')) {
            $pricing = $request->pricing;
            $marketCardId = $request->market_card_id;

            // Convert TCGdex updated timestamp to Y-m-d
            $importDate = isset($pricing['updated']) ? date('Y-m-d', strtotime($pricing['updated'])) : null;

            if ($importDate) {
                // Check if we need to update or create a new price record
                // We only add a new record if the date is more recent than the latest stored for this card
                $latestPrice = MarketPrice::where('market_card_id', $marketCardId)
                    ->orderBy('import_date', 'desc')
                    ->first();

                if (!$latestPrice || $importDate > $latestPrice->getRawOriginal('import_date')) {
                    MarketPrice::create([
                        'market_card_id' => $marketCardId,
                        'condition' => 'Near Mint', // Default condition for imported trend prices
                        'printing' => 'Normal',    // Default printing
                        'low_price' => $pricing['low'] ?? 0,
                        'market_price' => $pricing['avg'] ?? 0,
                        'import_date' => $importDate,
                    ]);

                    Log::info("Created new MarketPrice for MarketCard #{$marketCardId} from {$importDate}");
                }
            }
        }

        // Check if Google Drive upload is enabled
        $driveFileId = null;
        if (config('services.google_drive.enabled', true)) {
            try {
                $googleService = app(GoogleDriveService::class);
                $gdriveFile = $googleService->uploadFile(
                    $card->storage_path,
                    basename($card->storage_path),
                    $card->user->id,
                    $card->id
                );

                // Delete local file after successful upload
                Storage::disk('public')->delete($card->storage_path);

                $driveFileId = $gdriveFile->drive_id;
            } catch (Exception $e) {
                Log::error("Problema nel upload del file relativo alla carta #{$card->id} su google drive: {$e->getMessage()}");
            }
        } else {
            // Google Drive disabled - keep file locally
            Log::info("Google Drive upload disabled - file kept locally for card #{$card->id}");
        }

        // Auto-create CardInventory for this card
        $this->createCardInventory($card);

        return response()->json([
            'success' => true,
            'message' => 'Carta salvata correttamente!',
            'gfile' => $driveFileId,
            'storage_mode' => config('services.google_drive.enabled') ? 'drive' : 'local'
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
     * Get all scanned cards with server-side pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 25);
        $search = $request->input('search', '');
        $game = $request->input('game', '');
        $set = $request->input('set', '');
        $withoutSet = $request->input('without_set', false);
        $withoutRarity = $request->input('without_rarity', false);
        $onlyDuplicates = $request->input('only_duplicates', false);
        $rarityVariant = $request->input('rarity_variant', '');
        $sortColumn = $request->input('sort_column', '');
        $sortDirection = $request->input('sort_direction', 'asc');

        // Base query
        $query = PokemonCard::with('cardSet')
            ->withSum('inventory', 'quantity')
            ->where('user_id', auth()->id())
            ->where('status', PokemonCard::STATUS_COMPLETED);

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('card_name', 'like', '%' . $search . '%')
                    ->orWhere('set_number', 'like', '%' . $search . '%');
            });
        }

        // Apply game filter
        if ($game) {
            $query->where('game', $game);
        }

        // Apply set filter
        if ($set) {
            $query->whereHas('cardSet', function ($q) use ($set) {
                $q->where('name', $set);
            });
        }

        // Apply without set filter
        if ($withoutSet) {
            $query->whereNull('card_set_id');
        }

        // Apply without rarity filter
        if ($withoutRarity) {
            $query->where(function ($q) {
                $q->whereNull('rarity')->orWhere('rarity', '');
            });
        }

        // Apply duplicates filter (cards with quantity > 1)
        if ($onlyDuplicates) {
            $query->whereIn('id', function ($subquery) {
                $subquery->select('pokemon_card_id')
                    ->from('card_inventory')
                    ->where('user_id', auth()->id())
                    ->groupBy('pokemon_card_id')
                    ->havingRaw('SUM(quantity) > 1');
            });
        }

        // Apply rarity variant filter
        if ($rarityVariant) {
            $query->whereHas('inventory', function ($q) use ($rarityVariant) {
                $q->where('rarity_variant', $rarityVariant);
            });
        }

        // Apply sorting
        if ($sortColumn === 'set_number') {
            // Sort numerically by the part before the slash (e.g. "10" from "10/102")
            // CAST ensures "2" comes before "10"
            $query->orderByRaw("CAST(SUBSTRING_INDEX(set_number, '/', 1) AS UNSIGNED) " . $sortDirection)
                // Secondary sort by the full string to handle suffixes or identical numbers
                ->orderBy('set_number', $sortDirection);
        } elseif ($sortColumn) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('card_name');
        }

        // Paginate results
        $cards = $query->paginate($perPage);

        // Get available filters
        $availableGames = PokemonCard::where('user_id', auth()->id())
            ->whereNotNull('game')
            ->distinct()
            ->pluck('game')
            ->sort()
            ->values();

        $availableSets = CardSet::whereHas('pokemonCards', function ($q) {
            $q->where('user_id', auth()->id());
        })
            ->pluck('name')
            ->sort()
            ->values();

        // Get available rarity variants from user's inventory
        $availableVariants = \App\Models\CardInventory::where('user_id', auth()->id())
            ->distinct()
            ->pluck('rarity_variant')
            ->sort()
            ->values();

        return Inertia::render('Cards/Index', [
            'cards' => $cards,
            'availableGames' => $availableGames,
            'availableSets' => $availableSets,
            'availableVariants' => $availableVariants,
            'filters' => [
                'search' => $search,
                'game' => $game,
                'set' => $set,
                'without_set' => $withoutSet,
                'without_rarity' => $withoutRarity,
                'only_duplicates' => $onlyDuplicates,
                'rarity_variant' => $rarityVariant,
                'sort_column' => $sortColumn,
                'sort_direction' => $sortDirection,
            ]
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
            'rarity' => 'nullable|string',
            'set_number' => 'nullable|string',
            'illustrator' => 'nullable|string',
            'card_set_id' => 'nullable|exists:card_sets,id',
            'game' => 'nullable|string',
        ]);

        $data = $request->only([
            'card_name',
            'hp',
            'type',
            'evolution_stage',
            'rarity',
            'set_number',
            'illustrator',
            'card_set_id',
            'game'
        ]);

        if ($request->has('game')) {
            $gameModel = \App\Models\Game::firstOrCreate(
                [
                    'name' => $request->game,
                ]
            );
            $data['game_id'] = $gameModel->id;
        }

        $card->update($data);

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
     * Get all available games for dropdown (from Games table)
     */
    public function getAvailableGames()
    {
        $games = \App\Models\Game::orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $games
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
        $card->load(['cardSet', 'inventory']);

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
                'inventory' => $card->inventory,
                'total_quantity' => $card->getTotalQuantity(),
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

        if ($card->driveFile && $card->driveFile->isUploaded()) {
            $driveService = app(GoogleDriveService::class);
            $driveService->deleteFile($card->driveFile->drive_id);
        } else if ($card->storage_path && Storage::disk('public')->exists($card->storage_path)) {
            Storage::disk('public')->delete($card->storage_path);
        }

        $card->delete();

        return response()->json([
            'success' => true,
            'message' => 'Carta eliminata con successo!'
        ]);
    }

    /**
     * Delete multiple cards at once
     */
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'card_ids' => 'required|array',
            'card_ids.*' => 'exists:pokemon_cards,id',
        ]);

        $cards = PokemonCard::where('user_id', auth()->id())
            ->whereIn('id', $request->card_ids)
            ->get();

        $count = $cards->count();
        $driveService = app(GoogleDriveService::class);

        foreach ($cards as $card) {
            if ($card->driveFile && $card->driveFile->isUploaded()) {
                try {
                    $driveService->deleteFile($card->driveFile->drive_id);
                } catch (Exception $e) {
                    Log::error("Failed to delete file from Drive for card #{$card->id}: " . $e->getMessage());
                }
            } else if ($card->storage_path && Storage::disk('public')->exists($card->storage_path)) {
                Storage::disk('public')->delete($card->storage_path);
            }
            $card->delete();
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} carte eliminate con successo!"
        ]);
    }

    /**
     * Get inventory items for a specific card
     */
    public function getCardInventory(PokemonCard $card)
    {
        if ($card->user_id !== auth()->id()) {
            abort(403);
        }

        $inventory = $card->inventory()->get();

        return response()->json([
            'success' => true,
            'data' => $inventory
        ]);
    }

    /**
     * Add or update inventory item
     */
    public function storeInventory(Request $request, PokemonCard $card)
    {
        if ($card->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'rarity_variant' => 'required|in:' . implode(',', \App\Models\CardInventory::RARITY_VARIANTS),
            'condition' => 'required|in:' . implode(',', \App\Models\CardInventory::CONDITIONS),
            'notes' => 'nullable|string'
        ]);

        // Check if this combination already exists
        $inventory = \App\Models\CardInventory::updateOrCreate(
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
    public function updateInventory(Request $request, \App\Models\CardInventory $inventory)
    {
        if ($inventory->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'quantity' => 'sometimes|required|integer|min:1',
            'rarity_variant' => 'sometimes|required|in:' . implode(',', \App\Models\CardInventory::RARITY_VARIANTS),
            'condition' => 'sometimes|required|in:' . implode(',', \App\Models\CardInventory::CONDITIONS),
            'notes' => 'nullable|string'
        ]);

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
    public function destroyInventory(\App\Models\CardInventory $inventory)
    {
        if ($inventory->user_id !== auth()->id()) {
            abort(403);
        }

        $inventory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Elemento inventario eliminato!'
        ]);
    }

    /**
     * Get available rarity variants and conditions for dropdowns
     */
    public function getInventoryOptions()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'rarity_variants' => \App\Models\CardInventory::RARITY_VARIANTS,
                'conditions' => \App\Models\CardInventory::CONDITIONS
            ]
        ]);
    }

    /**
     * Auto-create CardInventory for a newly saved card
     * Uses visual_analysis data to determine rarity variant and condition
     */
    protected function createCardInventory(PokemonCard $card)
    {
        try {
            // Check if inventory already exists for this card
            $existingInventory = \App\Models\CardInventory::where('pokemon_card_id', $card->id)
                ->where('user_id', $card->user_id)
                ->first();

            if ($existingInventory) {
                Log::info("CardInventory already exists for card #{$card->id}");
                return;
            }

            // Determine rarity variant from visual_analysis
            $rarityVariant = 'Standard'; // Default

            // Use rarity field to determine variant
            if ($card->rarity) {
                $rarityLower = strtolower($card->rarity);

                if (str_contains($rarityLower, 'reverse') || str_contains($rarityLower, 'reverse holo')) {
                    $rarityVariant = 'Reverse Holo';
                } elseif (str_contains($rarityLower, 'holo')) {
                    $rarityVariant = 'Holo';
                } elseif (str_contains($rarityLower, 'first edition') || str_contains($rarityLower, '1st')) {
                    $rarityVariant = 'First Edition';
                } elseif (str_contains($rarityLower, 'promo')) {
                    $rarityVariant = 'Promo';
                }
            }

            // Create inventory record
            \App\Models\CardInventory::create([
                'pokemon_card_id' => $card->id,
                'user_id' => $card->user_id,
                'quantity' => 1,
                'rarity_variant' => $rarityVariant,
                'condition' => 'Near Mint', // Default condition for new cards
                'notes' => 'Auto-created from card upload'
            ]);

            Log::info("Auto-created CardInventory for card #{$card->id} with variant: {$rarityVariant}");
        } catch (\Exception $e) {
            Log::error("Failed to auto-create CardInventory for card #{$card->id}: " . $e->getMessage());
            // Don't throw - inventory creation failure shouldn't block card save
        }
    }

    /**
     * Look up card details from TCGdex and enrich the mapped data.
     *
     * Handles set lookup, card detail fetching, pricing extraction,
     * and MarketCard association. Fails gracefully on TCGdex errors.
     *
     * @param array $mappedData The mapped data from Gemini analysis
     * @return array The enriched mapped data
     */
    private function lookupAndEnrichFromTCGdex(array $mappedData): array
    {
        if (!isset($mappedData['set_number'])) {
            Log::info('Non posso recuperare il set da TCGDex');
            return $mappedData;
        }

        $number = explode("/", $mappedData['set_number']);

        $tcg = new TCGdex();


        $number = explode("/", $mappedData['set_number']);

        if ($mappedData['is_old_card']) {
            Log::info("Carta vecchia");
            // Se la carta è vecchia allora procedo a recuperarmi il set da TCGDex
            $query = Query::create()
                ->equal('localId', $number[0])  // Filter by exact match
                ->contains('name', $mappedData['card_name']) // Filter by partial match
                ->sort('hp', 'desc')          // Sort by HP descending
                ->paginate(1, 20);

            $listCards = $tcg->card->list($query);

            Log::alert("Riscontri su TCGDex per la carta: (#{$number[0]}) " . $mappedData['card_name'] . " -> " . count($listCards));
            if (count($listCards) == 1) {
                /**
                 * @var Card
                 */
                $tcgCard = $listCards[0]->toCard();
                $abbreviation = $tcgCard->set->toSet()->tcgOnline;
                Log::info("Set identificato: " . $abbreviation);
                $set = CardSet::where('card_set_abbreviation', $abbreviation)->first();
                if (!$set) {
                    Log::alert("Set non trovato");
                    return $mappedData;
                }

                $mappedData['card_set_id'] = $set->id;
                Log::info("Set trovato in db");
            } else {
                Log::alert("Molteplici riscontri su TCGDex per la carta: (#{$number[0]}) " . $mappedData['card_name']);
                // dd($listCards);
                foreach ($listCards as $cardResume) {
                    /**
                     * @var CardResume $cardResume
                     */
                    $localId = $cardResume->localId;
                    $tcgCard = $cardResume->toCard();
                    $tcgSetsCard = $tcgCard->set->toSet();
                    Log::info("Check per carta: " . $tcgCard->name . " nel set " . $tcgSetsCard->name . " con ID: " . $tcgCard->id . " con localId: " . $localId . " con cardCount: " . $tcgSetsCard->cardCount->total);
                    if ($localId == $number[0] && $tcgSetsCard->cardCount->total == $number[1]) {
                        Log::info("Trovata carta: " . $tcgCard->name . " nel set " . $tcgSetsCard->name . " con ID: " . $tcgCard->id);
                        $abbreviation = $tcgSetsCard->tcgOnline;
                        Log::info("Abbreviazione: " . $abbreviation);

                        $set = CardSet::where('card_set_abbreviation', $abbreviation)->first();
                        Log::info("Find set db: " . ($set->id ?? 'NO'));
                        if (!$set) {
                            return $mappedData;
                        }

                        $mappedData['card_set_id'] = $set->id;
                        break;
                    }
                }
            }
        } else {
            Log::info("Non è carta vecchia");
            $set = CardSet::where('card_set_abbreviation', $mappedData['set_code'])->first();

            if (!$set) {
                $query = Query::create()
                    ->equal('localId', $number[0])  // Filter by exact match
                    ->contains('name', $mappedData['card_name']) // Filter by partial match
                    ->sort('hp', 'desc')          // Sort by HP descending
                    ->paginate(1, 20);

                $listCards = $tcg->card->list($query);

                Log::alert("Riscontri su TCGDex per la carta: (#{$number[0]}) " . $mappedData['card_name'] . " -> " . count($listCards));
                if (count($listCards) == 1) {
                    /**
                     * @var Card
                     */
                    $tcgCard = $listCards[0]->toCard();
                    $abbreviation = $tcgCard->set->toSet()->tcgOnline;
                    Log::info("Set identificato: " . $abbreviation);
                    $set = CardSet::where('card_set_abbreviation', $abbreviation)->first();
                    if (!$set) {
                        Log::alert("Set non trovato");
                        return $mappedData;
                    }

                    $mappedData['card_set_id'] = $set->id;
                    Log::info("Set trovato in db");
                } else {
                    Log::alert("Molteplici riscontri su TCGDex per la carta: (#{$number[0]}) " . $mappedData['card_name']);
                    // dd($listCards);
                    foreach ($listCards as $cardResume) {
                        /**
                         * @var CardResume $cardResume
                         */
                        $localId = $cardResume->localId;
                        $tcgCard = $cardResume->toCard();
                        $tcgSetsCard = $tcgCard->set->toSet();
                        Log::info("Check per carta: " . $tcgCard->name . " nel set " . $tcgSetsCard->name . " con ID: " . $tcgCard->id . " con localId: " . $localId . " con cardCount: " . $tcgSetsCard->cardCount->total);
                        // dump($localId == $number[0], $tcgSetsCard->cardCount->total == $number[1], "$localId=={$number[0]}", "{$tcgSetsCard->cardCount->total}=={$number[1]}");
                        // if ($localId == $number[0]) {
                        //     dd($tcgSetsCard->toSet());
                        // }
                        if ($localId == $number[0] && $tcgSetsCard->cardCount->official == $number[1]) {
                            Log::info("Trovata carta: " . $tcgCard->name . " nel set " . $tcgSetsCard->name . " con ID: " . $tcgCard->id);
                            $abbreviation = $tcgSetsCard->abbreviation->official;
                            Log::info("Abbreviazione: " . $abbreviation);

                            $set = CardSet::where('card_set_abbreviation', $abbreviation)->first();
                            Log::info("Find set db: " . ($set->id ?? 'NO'));
                            if (!$set) {
                                return $mappedData;
                            }

                            $mappedData['card_set_id'] = $set->id;
                            break;
                        }
                    }

                    if (!isset($mappedData['card_set_id'])) {
                        Log::info("Set non trovato");
                        die;
                        return $mappedData;
                    }
                }

                Log::info("Set trovato");
                $mappedData['card_set_id'] = $set->id;

                try {
                    $tcg = new TCGdex('it');
                    $tcgCard = $tcg->set->getCard($set->abbreviation, $number[0]);
                    // dd($tcgCard);
                    if (!($tcgCard instanceof Card)) {
                        return $mappedData;
                    }

                } catch (\Exception $e) {
                    // TCGdex errors are non-blocking — proceed with Gemini data only
                    Log::warning("TCGDex error: " . $e->getMessage());
                }
            }
        }
        $mappedData = $this->enrichCardDetails($mappedData, $tcgCard);
        $mappedData = $this->extractPricingAndMarket($mappedData, $tcgCard);
        return $mappedData;
    }

    /**
     * Enrich mapped data with card details from TCGdex.
     *
     * @param array $mappedData The base mapped data
     * @param Card  $tcgCard    The TCGdex Card object
     * @return array The enriched data
     */
    private function enrichCardDetails(array $mappedData, Card $tcgCard): array
    {
        $mappedData['hp'] = $tcgCard->hp;
        $mappedData['type'] = $tcgCard->types[0] ?? null;
        $mappedData['evolution_stage'] = $tcgCard->stage;

        $mappedData['attacks'] = $tcgCard->attacks
            ? array_map(fn(Attack $attack) => [
                'cost' => $attack->cost,
                'name' => $attack->name,
                'text' => $attack->effect,
                'damage' => $attack->damage,
            ], $tcgCard->attacks)
            : [];

        $mappedData['weakness'] = is_array($tcgCard->weaknesses)
            ? implode(', ', array_map(fn($w) => "{$w->type} {$w->value}", $tcgCard->weaknesses))
            : $tcgCard->weaknesses;

        $mappedData['resistance'] = is_array($tcgCard->resistances)
            ? implode(', ', array_map(fn($r) => "{$r->type} {$r->value}", $tcgCard->resistances))
            : $tcgCard->resistances;

        $mappedData['retreat_cost'] = is_array($tcgCard->retreat)
            ? count($tcgCard->retreat)
            : (string) $tcgCard->retreat;

        $mappedData['rarity'] = $this->mapRarity($tcgCard->rarity);

        return $mappedData;
    }

    /**
     * Extract pricing info and create/link MarketCard if available.
     *
     * @param array $mappedData The base mapped data
     * @param Card  $tcgCard    The TCGdex Card object
     * @return array The data with pricing and market_card_id added
     */
    private function extractPricingAndMarket(array $mappedData, Card $tcgCard): array
    {
        if (!isset($tcgCard->pricing) || !isset($tcgCard->pricing->cardmarket)) {
            return $mappedData;
        }

        $cm = $tcgCard->pricing->cardmarket;

        $mappedData['pricing'] = [
            'avg' => $cm->avg ?? null,
            'low' => $cm->low ?? null,
            'trend' => $cm->trend ?? null,
            'updated' => $cm->updated ?? null,
            'unit' => $cm->unit ?? 'EUR',
            'idProduct' => $cm->idProduct ?? null,
        ];

        // Create MarketCard association if product ID is available
        if (!isset($cm->idProduct)) {
            return $mappedData;
        }

        $gameModel = \App\Models\Game::firstOrCreate(['name' => 'Pokemon']);

        $marketCard = MarketCard::withoutGlobalScope('user')->firstOrCreate(
            ['product_id' => $cm->idProduct],
            [
                'product_name' => $tcgCard->name,
                'card_number' => $tcgCard->localId,
                'set_name' => $tcgCard->set->name,
                'set_abbreviation' => $tcgCard->set->id,
                'rarity' => $tcgCard->rarity ?? 'Unknown',
                'type' => $tcgCard->types[0] ?? 'Unknown',
                'game' => 'Pokemon',
                'game_id' => $gameModel->id,
            ]
        );

        $mappedData['market_card_id'] = $marketCard->id;

        return $mappedData;
    }

    /**
     * Map TCGDex rarity to internal format
     */
    private function mapRarity(?string $rarity): ?string
    {
        if (!$rarity)
            return null;

        $map = [
            'Common' => 'Common',
            'Comune' => 'Common',
            'Uncommon' => 'Uncommon',
            'Non comune' => 'Uncommon',
            'Rare' => 'Rare',
            'Rara' => 'Rare',
            'Rare Holo' => 'Holo Rare',
            'Olografica rara' => 'Holo Rare',
            'Double Rare' => 'Double Rare',
            'Rara doppia' => 'Double Rare',
            'Illustration Rare' => 'Illustration Rare',
            'Rara illustrazione' => 'Illustration Rare',
            'Special Illustration Rare' => 'Illustration Rare',
            'Rara illustrazione speciale' => 'Illustration Rare',
            'Ultra Rare' => 'Ultra Rare',
            'Ultra rara' => 'Ultra Rare',
            'Secret Rare' => 'Secret Rare',
            'Rara segreta' => 'Secret Rare',
            'Hyper Rare' => 'Secret Rare',
            'Rara Hyper' => 'Secret Rare',
        ];

        return $map[$rarity] ?? $rarity;
    }
}
