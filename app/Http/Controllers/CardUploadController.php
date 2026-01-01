<?php

namespace App\Http\Controllers;

use App\Models\PokemonCard;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CardUploadController extends Controller
{
    /**
     * Show the upload form interface
     */
    public function showUploadForm()
    {
        $cards = PokemonCard::latest()->take(10)->get();
        return view('cards.upload', compact('cards'));
    }

    /**
     * Step 1: Upload and save the cropped card image
     */
    public function uploadImage(Request $request)
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
            'status' => PokemonCard::STATUS_PENDING,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Immagine caricata con successo.',
            'data' => [
                'id' => $card->id,
                'image_url' => Storage::url($path),
                'status' => 'pending'
            ]
        ]);
    }

    /**
     * Step 2: Enhance card data with Gemini AI
     */
    public function enhanceWithAI(Request $request, GeminiService $geminiService)
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

        // Call Gemini AI for card recognition
        $aiResult = $geminiService->enhanceCardData($base64Image, '');

        if ($aiResult) {
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
        ]);

        $card = PokemonCard::findOrFail($request->card_id);

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

        $card = PokemonCard::findOrFail($request->card_id);

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
     * Get all scanned cards
     */
    public function index()
    {
        $cards = PokemonCard::latest()->paginate(20);
        return view('cards.index', compact('cards'));
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
