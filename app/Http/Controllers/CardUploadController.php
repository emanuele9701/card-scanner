<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCardRequest;
use App\Http\Requests\UploadRawImageRequest;
use App\Http\Requests\SaveCroppedImageRequest;
use App\Http\Requests\ProcessCardRequest;
use App\Models\CardSet;
use App\Models\PokemonCard;
use App\Models\MarketPrice;
use App\Services\GeminiService;
use App\Services\GoogleDriveService;
use App\Services\ImageResizeService;
use App\Services\TCGdexLookupService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CardUploadController extends Controller
{
    /**
     * Show the upload form interface
     */
    public function showUploadForm()
    {
        $cards = PokemonCard::with('cardSet')->where('user_id', auth()->id())
            ->latest()
            ->take(10)
            ->get();

        $cardsWithoutSet = $cards->filter(fn($card) => $card->card_set_id === null)->values();
        $cardsWithSet = $cards->filter(fn($card) => $card->card_set_id !== null)->values();
        $cardsBySet = $cardsWithSet->groupBy(fn($card) => $card->cardSet->name);
        $sets = array_map(function ($cardSet) {
            return ['id' => $cardSet['id'], 'name' => $cardSet['name'] . " ( " . $cardSet['card_set_abbreviation'] . " )"];
        }, CardSet::all()->toArray());

        return \Inertia\Inertia::render('Cards/Upload', [
            'sets' => $sets
        ]);
    }

    /**
     * Step 1: Upload raw image (initial state: pending)
     */
    public function uploadRawImage(UploadRawImageRequest $request)
    {
        try {

            $file = $request->file('image');
            $originalFilename = $file->getClientOriginalName();

            Log::info('Image file received', [
                'filename' => $originalFilename,
                'size_mb' => round($file->getSize() / 1024 / 1024, 2),
            ]);

            $path = $file->store('pokemon_cards', 'public');

            // Resize image if needed
            $imageResizeService = app(ImageResizeService::class);
            $imageResizeService->resizeIfNeeded($path, 'public');

            $card = PokemonCard::create([
                'user_id' => auth()->id(),
                'original_filename' => $originalFilename,
                'storage_path' => $path,
                'status' => PokemonCard::STATUS_PENDING,
            ]);

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
    public function saveCroppedImage(SaveCroppedImageRequest $request)
    {

        $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);

        $file = $request->file('cropped_image');
        $path = $file->store('pokemon_cards', 'public');

        $imageResizeService = app(ImageResizeService::class);
        $imageResizeService->resizeIfNeeded($path, 'public');

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
    public function skipCrop(ProcessCardRequest $request)
    {

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
     * Upload an image and immediately run AI recognition — for the Dropzone UI.
     * Returns only the fields shown in the Upload page table (name, type, set, card number, illustrator).
     */
    public function uploadAndEnhance(UploadRawImageRequest $request, GeminiService $geminiService, TCGdexLookupService $tcgdexService)
    {
        try {
            $file = $request->file('image');
            $originalFilename = $file->getClientOriginalName();

            $path = $file->store('pokemon_cards', 'public');

            $imageResizeService = app(ImageResizeService::class);
            $imageResizeService->resizeIfNeeded($path, 'public');

            $card = PokemonCard::create([
                'user_id'           => auth()->id(),
                'original_filename' => $originalFilename,
                'storage_path'      => $path,
                'status'            => PokemonCard::STATUS_READY_FOR_AI,
            ]);

            // --- AI recognition ---
            $imagePath = Storage::disk('public')->path($path);
            if (!file_exists($imagePath)) {
                return response()->json(['success' => false, 'message' => 'File non trovato dopo il salvataggio.'], 500);
            }

            $base64Image = base64_encode(file_get_contents($imagePath));
            $aiResult    = $geminiService->enhanceCardData($base64Image);

            if (!$aiResult) {
                return response()->json(['success' => false, 'message' => "L'AI non ha restituito una risposta valida."], 500);
            }

            $mappedData = $geminiService->mapGeminiToLegacyFormat($aiResult);

            if (isset($mappedData['is_valid_card']) && $mappedData['is_valid_card'] === false) {
                $card->update(['status' => PokemonCard::STATUS_FAILED]);
                return response()->json([
                    'success'    => false,
                    'is_not_card' => true,
                    'message'    => $mappedData['error_message'] ?? "L'immagine non sembra una carta Pokémon.",
                ], 422);
            }

            $mappedData = $tcgdexService->lookupAndEnrich($mappedData);
            if (isset($mappedData['game']) && preg_match('/Pokemon/i', $mappedData['game'])) {
                $mappedData['game'] = 'Pokemon';
            }
            if ($mappedData['card_set_id']) {
                $setCard = CardSet::findOrFail($mappedData['card_set_id']);
                $mappedData['set_code']             = $setCard->card_set_abbreviation;
                $mappedData['set_info']['set_name'] = $setCard->name;
                $mappedData['set_info']['set_id']   = $setCard->id;
            }

            $card->update(['status' => PokemonCard::STATUS_REVIEW]);

            // Return only the fields needed by the Upload page table
            return response()->json([
                'success' => true,
                'message' => 'Riconoscimento completato!',
                'data'    => [
                    'card_id'           => $card->id,
                    'image_url'         => $card->getImageUrl(),
                    'name'              => $mappedData['card_name']             ?? null,
                    'type'              => $mappedData['type']                  ?? null,
                    'set'               => $mappedData['set_info']['set_name']  ?? ($mappedData['set_code'] ?? null),
                    'set_id'            => $mappedData['set_info']['set_id']    ?? null,
                    'set_code'          => $mappedData['set_code']              ?? null,
                    'card_number'       => $mappedData['set_number']            ?? null,
                    'illustrator'       => $mappedData['illustrator']           ?? null,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('uploadAndEnhance error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['success' => false, 'message' => 'Errore interno del server.'], 500);
        }
    }

    /**
     * Step 2: Enhance card data with Gemini AI
     */
    public function enhanceWithAI(ProcessCardRequest $request, GeminiService $geminiService, TCGdexLookupService $tcgdexService)
    {

        $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);

        $imagePath = Storage::disk('public')->path($card->storage_path);
        if (!file_exists($imagePath)) {
            return response()->json(['success' => false, 'message' => 'File immagine non trovato'], 404);
        }

        $base64Image = base64_encode(file_get_contents($imagePath));

        $aiResult = $geminiService->enhanceCardData($base64Image);

        if (!$aiResult) {
            return response()->json([
                'success' => false,
                'message' => 'Impossibile ottenere una risposta valida dall\'AI.'
            ], 500);
        }

        $mappedData = $geminiService->mapGeminiToLegacyFormat($aiResult);

        if (isset($mappedData['is_valid_card']) && $mappedData['is_valid_card'] === false) {
            $card->update(['status' => PokemonCard::STATUS_FAILED]);
            return response()->json([
                'success' => false,
                'is_not_card' => true,
                'message' => $mappedData['error_message'] ?? 'L\'immagine non sembra essere una carta da gioco collezionabile'
            ], 422);
        }

        Log::info("Card valida");
        $mappedData = $tcgdexService->lookupAndEnrich($mappedData);

        if (isset($mappedData['game']) && preg_match('/Pokemon/i', $mappedData['game'])) {
            $mappedData['game'] = 'Pokemon';
        }

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
    public function saveCard(SaveCardRequest $request)
    {
        $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);

        // Handle attacks - accept both JSON string and array
        $attacks = null;
        if ($request->filled('attacks') && is_array($request->attacks)) {
            $attacks = $request->attacks;
        } elseif ($request->filled('attacks_json')) {
            $attacks = json_decode($request->attacks_json, true);
        }

        $gameId = null;
        if ($request->game) {
            $gameModel = \App\Models\Game::firstOrCreate(['name' => $request->game]);
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
            $this->persistMarketPricing($request->pricing, $request->market_card_id);
        }

        // Google Drive upload
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
                Storage::disk('public')->delete($card->storage_path);
                $driveFileId = $gdriveFile->drive_id;
            } catch (Exception $e) {
                Log::error("Problema upload Google Drive per carta #{$card->id}: {$e->getMessage()}");
            }
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
    public function discard(ProcessCardRequest $request)
    {

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
     * Persist market pricing data from TCGdex
     */
    private function persistMarketPricing(array $pricing, int $marketCardId): void
    {
        $importDate = isset($pricing['updated']) ? date('Y-m-d', strtotime($pricing['updated'])) : null;

        if (!$importDate) {
            return;
        }

        $latestPrice = MarketPrice::where('market_card_id', $marketCardId)
            ->orderBy('import_date', 'desc')
            ->first();

        if (!$latestPrice || $importDate > $latestPrice->getRawOriginal('import_date')) {
            MarketPrice::create([
                'market_card_id' => $marketCardId,
                'condition' => 'Near Mint',
                'printing' => 'Normal',
                'low_price' => $pricing['low'] ?? 0,
                'market_price' => $pricing['avg'] ?? 0,
                'import_date' => $importDate,
            ]);

            Log::info("Created new MarketPrice for MarketCard #{$marketCardId} from {$importDate}");
        }
    }

    /**
     * Auto-create CardInventory for a newly saved card
     */
    private function createCardInventory(PokemonCard $card): void
    {
        try {
            $existingInventory = \App\Models\CardInventory::where('pokemon_card_id', $card->id)
                ->where('user_id', $card->user_id)
                ->first();

            if ($existingInventory) {
                return;
            }

            $rarityVariant = 'Standard';

            if ($card->rarity) {
                $rarityLower = strtolower($card->rarity);

                if (str_contains($rarityLower, 'reverse')) {
                    $rarityVariant = 'Reverse Holo';
                } elseif (str_contains($rarityLower, 'holo')) {
                    $rarityVariant = 'Holo';
                } elseif (str_contains($rarityLower, 'first edition') || str_contains($rarityLower, '1st')) {
                    $rarityVariant = 'First Edition';
                } elseif (str_contains($rarityLower, 'promo')) {
                    $rarityVariant = 'Promo';
                }
            }

            \App\Models\CardInventory::create([
                'pokemon_card_id' => $card->id,
                'user_id' => $card->user_id,
                'quantity' => 1,
                'rarity_variant' => $rarityVariant,
                'condition' => 'Near Mint',
                'notes' => 'Auto-created from card upload'
            ]);

            Log::info("Auto-created CardInventory for card #{$card->id} with variant: {$rarityVariant}");
        } catch (\Exception $e) {
            Log::error("Failed to auto-create CardInventory for card #{$card->id}: " . $e->getMessage());
        }
    }
}
