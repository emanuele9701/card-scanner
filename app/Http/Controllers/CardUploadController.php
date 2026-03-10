<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCardRequest;
use App\Http\Requests\UploadRawImageRequest;
use App\Http\Requests\SaveCroppedImageRequest;
use App\Http\Requests\ProcessCardRequest;
use App\Models\CardSet;
use App\Models\MarketCard;
use App\Models\PokemonCard;
use App\Models\MarketPrice;
use App\Models\ProviderPrice;
use App\Services\GeminiService;
use App\Services\GoogleDriveService;
use App\Services\ImageResizeService;
use App\Services\TCGdexLookupService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use TCGdex\Model\Card;

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
            if (isset($mappedData['card_set_id'])) {
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
                    'language_card'     => $mappedData['language_card'],
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
        // return response([], 500)->json([
        //     'success' => false,
        //     'message' => 'Funzione disabilitata temporaneamente per manutenzione. Torna presto!'
        // ])->setStatusCode(500);
        if ($request->has('cards') && is_array($request->cards)) {
            $cardsRequestData = $request->cards;
            $idCardsRequestData = array_map(fn($cardData) => $cardData['card_id'], $cardsRequestData);
            $cards = PokemonCard::where('user_id', auth()->id())->whereIn('id', $idCardsRequestData)->get();
            $responseCards = [];
            foreach ($cards as $card) {
                Log::info("Salvando carta #{$card->id} - {$card->original_filename}");
                $requestDataForCard = collect($cardsRequestData)->firstWhere('card_id', $card->id);
                $esito = $this->saveCardData($card, $requestDataForCard);
                $responseCards[$card->id] = $esito;
            }

            return response()->json([
                'success' => true,
                'message' => 'Carte salvate.',
                'response_cards' => $responseCards
            ]);
        } else if ($request->has('card_id')) {

            $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);
            $driveFileId = $this->saveCardData($card, $request->validated());


            return response()->json([
                'success' => true,
                'message' => 'Carta salvata correttamente!',
                'gfile' => $driveFileId,
                'storage_mode' => config('services.google_drive.enabled') ? 'drive' : 'local'
            ]);
        }
    }

    private function saveCardData(PokemonCard $card, array $request): string|null
    {
        $attacks = null;
        if (isset($request['attacks']) && is_array($request['attacks'])) {
            $attacks = $request['attacks'];
        } elseif (isset($request['attacks_json'])) {
            $attacks = json_decode($request['attacks_json'], true);
        }

        $gameId = null;
        if (isset($request['game'])) {
            $gameModel = \App\Models\Game::firstOrCreate(['name' => $request['game']]);
            $gameId = $gameModel->id;
        }

        $card->update([
            'card_name' => $request['card_name'],
            'hp' => $request['hp'] ?? '-',
            'type' => $request['type'] ?? '-',
            'evolution_stage' => $request['evolution_stage'] ?? '-',
            'attacks' => $attacks,
            'weakness' => $request['weakness'] ?? '-',
            'resistance' => $request['resistance'] ?? '-',
            'retreat_cost' => $request['retreat_cost'] ?? '-',
            'rarity' => $request['rarity'] ?? '-',
            'set_number' => $request['set_number'] ?? '-',
            'illustrator' => $request['illustrator'] ?? '-',
            'flavor_text' => $request['flavor_text'] ?? '-',
            'card_set_id' => $request['card_set_id'] ?? '-',
            'game' => $request['game'] ? $request['game'] : null,
            'game_id' => $gameId,
            'status' => PokemonCard::STATUS_COMPLETED,
        ]);

        $set = $card->cardSet;

        // Recupero il prezzo.
        $tcgdexService = app(TCGdexLookupService::class);
        $isOldCard = $request['is_old_card'] ?? false;
        $parts = explode('/', $card->set_number);
        [$localId, $totalCards] = count($parts) === 2 ? $parts : [$card->set_number, 0];

        $dataService = $tcgdexService->searchAndMatch($localId, $card->card_name, $totalCards, false, $request['language_card']);

        /**
         * @var Card $tcgCard
         */
        $tcgCard = $dataService['tcg_card'] ?? null;

        if ($tcgCard) {
            Log::info("AAA Carta trovata su TCGDex: {$tcgCard->name} ({$tcgCard->set->name})");
            // Si crea il record del market data
            $marketCardData = [
                'product_id' => $tcgCard->id,
                'product_name' => $card->card_name,
                'card_number' => $card->set_number,
                'set_name' => $set ? $set->name : null,
                'set_abbreviation' => $set ? $set->card_set_abbreviation : null,
                'game_id' => $card->game->id ?? null,
                'rarity' => $tcgCard->rarity ?? 'Unknown',
                'type' => implode(', ', $tcgCard->types ?? ['Unknown']),
                'game' => $tcgCard->category ?? 'Unknown',
            ];
            $card->refresh();
            $marketCard = MarketCard::updateOrCreate(['product_id' => $tcgCard->id], $marketCardData);

            $card->update([
                'hp' => $tcgCard->hp,
                'type' => $tcgCard->types[0] ?? 'Unknown',
                'evolution_stage' => $tcgCard->stage ?? 'Unknown',
                'attacks' => $tcgCard->attacks ?? null,
                'weakness' => $tcgCard->weakness ?? 'Unknown',
                'resistance' => $tcgCard->resistance ?? 'Unknown',
                'retreat_cost' => $tcgCard->retreat ?? 'Unknown',
                'rarity' => $tcgCard->rarity ?? 'Unknown',
                'market_card_id' => $marketCard->id ?? null
            ]);

            // Persist market pricing if provided
            $this->persistMarketPricing($card, json_decode(json_encode($tcgCard->pricing), true), $marketCard);
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
        $this->createCardInventory($card, $request['language_card']);


        // Aggiorno i set dell'utente
        if (!Auth::user()->cardSets()->where('card_set_id', $card->card_set_id)->exists()) {
            Auth::user()->cardSets()->attach($card->card_set_id);
        }

        return true;
    }

    /**
     * Discard a card
     */
    public function discard(ProcessCardRequest $request)
    {

        if ($request->has('card_id')) {
            $cards = PokemonCard::where('user_id', auth()->id())->where('id', $request->card_id)->get();

            foreach ($cards as $card) {
                if (Storage::disk('public')->exists($card->storage_path)) {
                    Storage::disk('public')->delete($card->storage_path);
                }
                $card->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Carte eliminate.'
            ]);
        } else if ($request->has('cards_id') && is_array($request->cards_id)) {
            $cards = PokemonCard::where('user_id', auth()->id())->whereIn('id', $request->cards_id)->get();

            foreach ($cards as $card) {
                if (Storage::disk('public')->exists($card->storage_path)) {
                    Storage::disk('public')->delete($card->storage_path);
                }
                $card->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Carte eliminate.'
            ]);
        }
    }

    /**
     * Persist market pricing data from TCGdex
     */
    private function persistMarketPricing(PokemonCard $card, array $pricing, MarketCard $marketCard): void
    {
        if (empty($pricing)) {
            Log::info("I valori di questa carta: {$card->card_name} #{$card->set_number} non esistono");
            return;
        }

        foreach ($pricing as $provider => $price) {
            if (!is_array($price)) continue;

            if (strtolower($provider) == 'cardmarket') {
                $this->TCGGenerateCardMarketsPrice($marketCard, $price);
            } else if (strtolower($provider) === 'tcgplayer') {
                $this->TCGGenerateTcgPlayerPrice($marketCard, $price);
            }
        }
    }

    /**
     * From array tcg's pricing (cardmarket) array generate a MarketPrices data
     */
    private function TCGGenerateCardMarketsPrice(MarketCard $marketCard, array $price): void
    {
        $return = [];
        $providerPrice = ProviderPrice::where('name', 'CardMarket')->first();

        if (!$providerPrice) {
            return;
        }
        $importDate = (date_create_from_format('Y-m-d', explode("T", $price['updated'])[0]))->format("Y-m-d");
        if (MarketPrice::where('market_card_id', $marketCard->id)->where('import_date', $importDate)->exists()) return;

        $return[] = [
            'external_product_id' => $price['idProduct'],
            'market_card_id' => $marketCard->id,
            'provider_id' => $providerPrice->id,
            'condition' => 'Near Mint',
            'printing' => 'Standard',
            'low_price' => $price['low'],
            'trend' => $price['trend'],
            'avg1' => $price['avg1'],
            'avg7' => $price['avg7'],
            'avg30' => $price['avg30'],
            'unit_divisa' => 'eur',
            'market_price' => $price['trend'],
            'import_date' => $importDate
        ];

        if (!empty($price['trend-holo'])) {
            $return[] = [
                'external_product_id' => $price['idProduct'],
                'market_card_id' => $marketCard->id,
                'provider_id' => $providerPrice->id,
                'condition' => 'Near Mint',
                'printing' => 'Holo',
                'low_price' => $price['low-holo'] ?? 0,
                'trend' => $price['trend-holo'],
                'avg1' => $price['avg1-holo'],
                'avg7' => $price['avg7-holo'],
                'avg30' => $price['avg30-holo'],
                'unit_divisa' => 'eur',
                'market_price' => $price['trend'],
                'import_date' => $importDate
            ];
        }
        foreach ($return as $key => $value) {
            MarketPrice::create($value);
        }
    }

    /**
     * From array tcg's pricing (cardmarket) array generate a MarketPrices data
     */
    private function TCGGenerateTcgPlayerPrice(MarketCard $marketCard, array $price): void
    {
        $providerPrice = ProviderPrice::where('name', 'TCG Player')->first();

        $importDate = (date_create_from_format('Y-m-d', explode("T", $price['updated'])[0]))->format("Y-m-d");
        unset($price['updated'], $price['unit']);
        foreach ($price as $printing => $value) {
            $return = [
                'external_product_id' => $value['productId'],
                'market_card_id' => $marketCard->id,
                'provider_id' => $providerPrice->id,
                'condition' => 'Near Mint',
                'printing' => $printing,
                'low_price' => $value['lowPrice'],
                'high_price' => $value['highPrice'],
                'mid_price' => $value['midPrice'],
                'market_price' => $value['marketPrice'],
                'unit_divisa' => 'dol',
                'import_date' => $importDate
            ];
            MarketPrice::create($return);
        }
    }

    /**
     * Auto-create CardInventory for a newly saved card
     */
    private function createCardInventory(PokemonCard $card, string $language_card = "-"): void
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
                'language_card' => $language_card,
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
