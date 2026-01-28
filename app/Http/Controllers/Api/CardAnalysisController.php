<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PokemonCard;
use App\Models\Game;
use App\Services\GeminiService;
use App\Services\ImageResizeService;
use App\Services\GoogleDriveService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

            // 6. Handle Gemini Result
            if ($aiResult) {
                if (isset($aiResult['is_valid_card']) && $aiResult['is_valid_card'] === false) {
                    $card->update(['status' => PokemonCard::STATUS_FAILED]);
                    return response()->json([
                        'success' => false,
                        'message' => $aiResult['error_message'] ?? 'L\'immagine non sembra essere una carta da gioco valida',
                        'data' => [
                            'card_id' => $card->id,
                            'is_valid_card' => false
                        ]
                    ], 422); // Unprocessable Entity
                }

                // I dati strutturati restituiti dall'IA vengono forniti al client per la validazione da parte dell'utente.
                // Lo stato della carta viene aggiornato a STATUS_REVIEW per indicare il completamento dell'analisi automatica.
                $card->update(['status' => PokemonCard::STATUS_REVIEW]);

                return response()->json([
                    'success' => true,
                    'message' => 'Analisi completata con successo.',
                    'data' => [
                        'card_id' => $card->id,
                        'image_url' => route('api.image.card', ['card' => $card->id]),
                        'analysis' => $aiResult
                    ]
                ]);
            } else {
                $card->update(['status' => PokemonCard::STATUS_FAILED]);
                return response()->json([
                    'success' => false,
                    'message' => 'Impossibile ottenere una risposta valida dall\'AI.'
                ], 500);
            }
        } catch (Exception $e) {
            Log::error('API Analysis Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Si è verificato un errore durante l\'elaborazione.'
            ], 500);
        }
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
                'type' => 'nullable|string',
                'evolution_stage' => 'nullable|string',
                'attacks_json' => 'nullable|string', // Expecting JSON string for complexity, or array if handled by validation
                'weakness' => 'nullable|string',
                'resistance' => 'nullable|string',
                'retreat_cost' => 'nullable|string',
                'rarity' => 'nullable|string',
                'set_number' => 'nullable|string',
                'illustrator' => 'nullable|string',
                'flavor_text' => 'nullable|string',
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
                        'user_id' => auth()->id()
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
                'game' => $request->game,
                'game_id' => $gameId,
                'status' => PokemonCard::STATUS_COMPLETED,
            ]);

            // Upload to Google Drive
            $gdriveFile = null;
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
}
