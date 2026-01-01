<?php

namespace App\Http\Controllers;

use App\Models\PokemonCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use thiagoalessio\TesseractOCR\TesseractOCR;

class OcrController extends Controller
{
    /**
     * Show the upload form with Cropper.js
     */
    public function showUploadForm()
    {
        $cards = PokemonCard::latest()->take(10)->get();
        return view('ocr.upload', compact('cards'));
    }

    /**
     * Step 1: Process the cropped image and perform initial Tesseract OCR
     */
    public function process(Request $request)
    {
        $request->validate([
            'cropped_image' => 'required|image|mimes:jpeg,png,jpg|max:30720',
        ]);

        // Save the file
        $file = $request->file('cropped_image');
        $originalFilename = $file->getClientOriginalName();
        $path = $file->store('pokemon_cards', 'public');

        // Create initial record
        $card = PokemonCard::create([
            'original_filename' => $originalFilename,
            'storage_path' => $path,
            'status' => PokemonCard::STATUS_REVIEW, // Waiting for user review
        ]);

        // Get full path for Tesseract
        $fullPath = Storage::disk('public')->path($path);

        try {
            // Run OCR with Italian and English support
            $text = (new TesseractOCR($fullPath))
                ->lang('ita', 'eng')
                ->run();

            // Update record with initial extracted text
            $card->update([
                'extracted_text' => $text,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OCR base completato. Revisiona il risultato.',
                'data' => [
                    'id' => $card->id,
                    'extracted_text' => $text,
                    'image_url' => Storage::url($path),
                    'status' => 'review'
                ]
            ]);
        } catch (\Exception $e) {
            $card->update(['status' => PokemonCard::STATUS_FAILED]);

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'elaborazione OCR: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Step 2 (Optional): Enhance data with Gemini AI
     */
    public function enhance(Request $request, \App\Services\GeminiService $geminiService)
    {
        $request->validate([
            'card_id' => 'required|exists:pokemon_cards,id',
        ]);

        $card = PokemonCard::findOrFail($request->card_id);

        // Get image content for AI
        $imagePath = Storage::disk('public')->path($card->storage_path);
        if (!file_exists($imagePath)) {
            return response()->json(['success' => false, 'message' => 'File immagine non trovato'], 404);
        }

        $base64Image = base64_encode(file_get_contents($imagePath));

        $aiResult = $geminiService->enhanceCardData($base64Image, $card->extracted_text ?? '');

        if ($aiResult) {
            return response()->json([
                'success' => true,
                'message' => 'Analisi AI completata!',
                'data' => $aiResult
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Impossibile ottenere una risposta valida dall\'AI.'
        ], 500);
    }

    /**
     * Step 3: Confirm and Save Final Data
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'card_id' => 'required|exists:pokemon_cards,id',
            // fields are optional as per user request
            'card_name' => 'nullable|string',
            'hp' => 'nullable|string',
            'type' => 'nullable|string',
            'evolution_stage' => 'nullable|string',
            'attacks_json' => 'nullable|string', // We'll receive this as a JSON string from the textarea or hidden field
            'weakness' => 'nullable|string',
            'resistance' => 'nullable|string',
            'retreat_cost' => 'nullable|string',
            'rarity' => 'nullable|string',
            'set_number' => 'nullable|string',
            'illustrator' => 'nullable|string',
            'flavor_text' => 'nullable|string',
        ]);

        $card = PokemonCard::findOrFail($request->card_id);

        // Decode attacks JSON string to valid array/object if present
        $attacks = null;
        if ($request->attacks_json) {
            $attacks = json_decode($request->attacks_json, true);
        }

        $card->update([
            'extracted_text' => $request->final_text, // Keep the full text for reference if needed
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
            'status' => PokemonCard::STATUS_COMPLETED,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Carta salvata correttamente!'
        ]);
    }

    /**
     * Discard the current scan
     */
    public function discard(Request $request)
    {
        $request->validate([
            'card_id' => 'required|exists:pokemon_cards,id',
        ]);

        $card = PokemonCard::findOrFail($request->card_id);

        if (Storage::disk('public')->exists($card->storage_path)) {
            Storage::disk('public')->delete($card->storage_path);
        }

        $card->delete();

        return response()->json([
            'success' => true,
            'message' => 'Scansione annullata.'
        ]);
    }

    /**
     * Get all scanned cards
     */
    public function index()
    {
        $cards = PokemonCard::latest()->paginate(20);
        return view('ocr.index', compact('cards'));
    }

    /**
     * Delete a card
     */
    public function destroy(PokemonCard $card)
    {
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
