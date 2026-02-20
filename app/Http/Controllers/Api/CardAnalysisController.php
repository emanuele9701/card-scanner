<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardSet;
use App\Models\PokemonCard;
use App\Models\Game;
use App\Services\GeminiService;
use App\Services\ImageResizeService;
use App\Services\GoogleDriveService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use TCGdex\Model\Card;
use TCGdex\Model\CardResume;
use TCGdex\Model\SubModel\Attack;
use TCGdex\Query;
use TCGdex\TCGdex;

class CardAnalysisController extends Controller
{
    protected $geminiService;
    protected $imageResizeService;
    protected $googleDriveService;

    public function __construct(
        GeminiService $geminiService,
        ImageResizeService $imageResizeService,
        GoogleDriveService $googleDriveService
    ) {
        $this->geminiService = $geminiService;
        $this->imageResizeService = $imageResizeService;
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Upload image, save locally, and analyze with Gemini.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function analyze(Request $request)
    {
        try {
            // 1. Validation
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:30720', // Max 30MB
            ]);

            Log::info('API: Starting card analysis', ['user_id' => auth()->id()]);

            // 2. Store Image Locally
            $file = $request->file('image');
            $originalFilename = $file->getClientOriginalName();

            // Store in 'pokemon_cards' directory in 'public' disk
            $path = $file->store('pokemon_cards', 'public');

            // Resize if needed
            $this->imageResizeService->resizeIfNeeded($path, 'public');

            // 3. Create Database Record (Pending Status)
            $card = PokemonCard::create([
                'user_id' => auth()->id(),
                'original_filename' => $originalFilename,
                'storage_path' => $path,
                'status' => PokemonCard::STATUS_PENDING,
            ]);

            // 4. Prepare for Gemini
            $fullPath = Storage::disk('public')->path($path);
            if (!file_exists($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errore nel salvataggio del file.'
                ], 500);
            }

            $base64Image = base64_encode(file_get_contents($fullPath));

            // 5. Call Gemini Service
            // We pass empty string for OCR text as it's not strictly required by the service signature if we just want image analysis
            $aiResult = $this->geminiService->enhanceCardData($base64Image, '');


            // 6. Handle Gemini Result — early return on failure
            if (!$aiResult) {
                $card->update(['status' => PokemonCard::STATUS_FAILED]);
                return response()->json([
                    'success' => false,
                    'message' => 'Impossibile ottenere una risposta valida dall\'AI.'
                ], 500);
            }

            // Map new Gemini structured data to legacy format
            $mappedData = $this->geminiService->mapGeminiToLegacyFormat($aiResult);

            // Check validity FIRST — avoid unnecessary API calls for invalid cards
            if (isset($mappedData['is_valid_card']) && $mappedData['is_valid_card'] === false) {
                $card->update(['status' => PokemonCard::STATUS_FAILED]);
                return response()->json([
                    'success' => false,
                    'message' => $mappedData['error_message'] ?? 'L\'immagine non sembra essere una carta da gioco valida',
                    'data' => [
                        'card_id' => $card->id,
                        'is_valid_card' => false
                    ]
                ], 422);
            }

            // Enrich mapped data with TCGdex API info
            $tcgCard = $this->fetchCardFromTCGdex($mappedData);
            if ($tcgCard instanceof Card) {
                $mappedData = $this->enrichWithTCGdexData($mappedData, $tcgCard);
            }

            $card->update(['status' => PokemonCard::STATUS_REVIEW]);

            return response()->json([
                'success' => true,
                'message' => 'Analisi completata con successo.',
                'data' => [
                    'card_id' => $card->id,
                    'image_url' => route('api.image.card', ['card' => $card->id]),
                    'analysis' => $mappedData
                ]
            ]);
        } catch (Exception $e) {
            Log::error('API Analysis Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'elaborazione.'
            ], 500);
        }
    }

    /**
     * Fetch card details from TCGdex API based on mapped analysis data.
     *
     * @param array $mappedData The mapped data from Gemini analysis
     * @return Card|null The TCGdex Card object, or null if not found
     */
    private function fetchCardFromTCGdex(array $mappedData): ?Card
    {
        $setNumber = $mappedData['set_number'] ?? null;
        if (!$setNumber) {
            return null;
        }

        $number = explode("/", $setNumber)[0];

        if (!empty($mappedData['is_old_card'])) {
            // Old cards: search by localId + name via TCGdex English API
            $tcg = new TCGdex('en');
            $query = Query::create()
                ->equal('localId', $number)
                ->contains('name', $mappedData['card_name'] ?? '')
                ->sort('hp', 'desc')
                ->paginate(1, 20);

            $results = $tcg->card->list($query);

            if (count($results) == 1) {
                return $results[0]->toCard();
            } else {
                foreach ($results as $cardResume) {
                    /**
                     * @var CardResume $cardResume
                     */
                    $localId = $cardResume->localId;
                    $tcgCard = $cardResume->toCard();
                    $tcgSetsCard = $tcgCard->set->toSet();
                    Log::info("API CHECK FOR: " . $tcgCard->name . " nel set " . $tcgSetsCard->name . " con ID: " . $tcgCard->id . " con localId: " . $localId . " con cardCount: " . $tcgSetsCard->cardCount->total);
                    if ($localId == explode("/", $setNumber)[0] && $tcgSetsCard->cardCount->total == explode("/", $setNumber)[1]) {
                        return $tcgCard;
                    }
                }
            }
        }

        // Modern cards: look up set in local DB, then fetch from TCGdex Italian API
        $setName = $mappedData['set_info']['set_name'] ?? null;
        if (!$setName) {
            return null;
        }

        $set = CardSet::where('name', $setName)->first();
        if (!$set) {
            return null;
        }

        $tcg = new TCGdex('it');
        return $tcg->set->getCard($set->abbreviation, intval($number));
    }

    /**
     * Enrich mapped analysis data with details from a TCGdex Card object.
     *
     * @param array $mappedData The base mapped data
     * @param Card  $card       The TCGdex Card with detailed info
     * @return array The enriched mapped data
     */
    private function enrichWithTCGdexData(array $mappedData, Card $card): array
    {
        $mappedData['hp'] = $card->hp;
        $mappedData['type'] = $card->types[0] ?? null;
        $mappedData['evolution_stage'] = $card->stage;

        $mappedData['attacks'] = $card->attacks
            ? array_map(fn(Attack $attack) => [
                'costo' => $attack->cost,
                'name' => $attack->name,
                'effect' => $attack->effect,
                'damage' => $attack->damage,
            ], $card->attacks)
            : [];

        $mappedData['weakness'] = $card->weaknesses;
        $mappedData['resistance'] = $card->resistances;
        $mappedData['retreat_cost'] = $card->retreat;
        $mappedData['rarity'] = $card->rarity;
        $mappedData['pricing'] = $card->pricing;

        return $mappedData;
    }

    /**
     * Confirm card data and save to database/drive
     */
    public function confirm(Request $request)
    {
        try {
            $request->validate([
                'card_id' => 'required|exists:pokemon_cards,id',
                'card_name' => 'nullable|string',
                'hp' => 'nullable|string',
                'type' => 'nullable|in:Normale,Fuoco,Acqua,Erba,Elettro,Ghiaccio,Lotta,Veleno,Terra,Volante,Psico,Coleottero,Roccia,Spettro,Drago,Buio,Acciaio,Folletto,Strumento',
                'evolution_stage' => 'nullable|string',
                'attacks_json' => 'nullable|string',
                'attacks' => 'nullable|array',
                'weakness' => 'nullable|string',
                'resistance' => 'nullable|string',
                'retreat_cost' => 'nullable|integer',
                'rarity' => 'nullable|in:Comune,Non Comune,Rara,Rara Olografica/Foil,Rara Doppia/Ultrarara,Rara Illustrazione,Rara Illustrazione Speciale,Secret Rare,Rara Cromatica,Vintage/1ª Edizione',
                'set_number' => 'nullable|string',
                'illustrator' => 'nullable|string',
                'card_set_id' => 'nullable|exists:card_sets,id',
                'game' => 'required|string',
            ]);

            $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);

            // Decode attacks JSON string if present or use directly if array (depending on how Flutter sends it)
            // Assuming simplified implementation where mobile acts like frontend
            $attacks = null;
            if ($request->filled('attacks')) {
                $attacks = $request->input('attacks'); // If sent as array
            } elseif ($request->filled('attacks_json')) {
                $attacks = json_decode($request->attacks_json, true);
            }

            // Ensure game exists
            $gameId = null;
            if ($request->game) {
                $gameModel = Game::firstOrCreate(
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
                'card_set_id' => $request->card_set_id,
                'game' => $request->game,
                'game_id' => $gameId,
                'status' => PokemonCard::STATUS_COMPLETED,
            ]);

            // Upload to Google Drive if enabled
            $gdriveFile = null;
            if (config('services.google_drive.enabled', true)) {
                try {
                    if ($card->storage_path && Storage::disk('public')->exists($card->storage_path)) {
                        $gdriveFile = $this->googleDriveService->uploadFile(
                            $card->storage_path,
                            basename($card->storage_path),
                            $card->user->id,
                            $card->id
                        );

                        // Delete local file after successful upload
                        Storage::disk('public')->delete($card->storage_path);
                    } else {
                        Log::warning("File locale non trovato per upload Drive: {$card->storage_path}");
                    }
                } catch (Exception $e) {
                    Log::error("Problema nel upload del file relativo alla carta #{$card->id} su google drive: {$e->getMessage()}");
                    // We don't fail the request here, but log it.
                    // Status is COMPLETED but file might not be on Drive.
                }
            } else {
                // Google Drive disabled - keep file locally
                Log::info("Google Drive upload disabled - file kept locally for card #{$card->id}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Carta salvata correttamente!',
                'data' => [
                    'card_id' => $card->id,
                    'drive_file_id' => $gdriveFile ? $gdriveFile->drive_id : null
                ]
            ]);
        } catch (Exception $e) {
            Log::error('API Confirm Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Delete a card (if user cancels or rejects analysis)
     */
    public function delete(Request $request)
    {
        try {
            $request->validate([
                'card_id' => 'required|exists:pokemon_cards,id',
            ]);

            $card = PokemonCard::where('user_id', auth()->id())->findOrFail($request->card_id);

            // Delete storage file
            if ($card->storage_path && Storage::disk('public')->exists($card->storage_path)) {
                Storage::disk('public')->delete($card->storage_path);
            }

            // Delete database record
            $card->delete();

            return response()->json([
                'success' => true,
                'message' => 'Carta eliminata correttamente.'
            ]);
        } catch (Exception $e) {
            Log::error('API Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione.'
            ], 500);
        }
    }
}
