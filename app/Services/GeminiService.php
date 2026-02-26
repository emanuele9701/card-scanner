<?php

namespace App\Services;

use App\Models\CardSet;
use App\Models\Game;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemma-3-27b-it:generateContent';

    public function __construct()
    {
        // Fallback to the provided key if not in .env for now, as requested.
        //Ideally this should be: config('services.gemini.api_key');
        $this->apiKey = env('GEMINI_API_KEY', 'default_key_if_needed');
    }

    /**
     * Enhance card data using Gemini AI
     * 
     * @param string $base64Image Base64 encoded image string (without prefix)
     * @param string $ocrText Text extracted by Tesseract
     * @return array|null Structured data or null on failure
     */
    public function enhanceCardData(string $base64Image, string $ocrText): ?array
    {
        // Hardcoded key from user request for this specific implementation as per instructions
        // In a real app, I'd rely solely on env.
        $apiKey = $this->apiKey;

        $gameId = Game::where('name', 'Pokemon')->first()->id;
        $allSets = CardSet::where('game_id', $gameId)->pluck('name', 'card_set_abbreviation');
        $jsonSets = json_encode($allSets);
        $prompt = <<<TEXT
      Sei un sistema OCR rigoroso per l'estrazione dati da carte Pokémon TCG.
ATTENZIONE CRITICA: Se un dato non è fisicamente e testualmente presente, restituisci "null".
NON unire testi lontani tra loro. NON inventare dati.

--- STEP 0: DETERMINA L'ERA DELLA CARTA ---
Osserva il layout e il design della carta:

CARTA MODERNA (2023+) → bordi argentati/bianchi, design pulito, codice testuale di set stampato in basso (es. "PFLit", "sv4pt5it", "OBFit").

CARTA VECCHIA (pre-2023) → bordi gialli/colorati, design più semplice, NESSUN codice testuale di set, solo simboli grafici.

Se la carta è VECCHIA → segui il PERCORSO A.
Se la carta è MODERNA → segui il PERCORSO B.

========== PERCORSO A: CARTA VECCHIA (pre-2023) ==========
Estrai SOLO queste informazioni:

NOME DELLA CARTA:
Il nome del Pokémon, visibile in alto. La carta potrebbe essere ruotata, cerca il nome in tutte le direzioni.

NUMERO DELLA CARTA:

DOVE: ESCLUSIVAMENTE nel bordo inferiore estremo della carta.

TRABOCCHETTO: sotto l’illustrazione c’è il numero del Pokédex (es. "N. 0045", "No. 260"). IGNORALO TOTALMENTE.

Cerca SOLO il formato “XX/YYY” o “XXX/YYY” nel bordo inferiore e trascrivi esattamente.

Restituisci questo JSON:

json
{
  "card_identity": {
    "pokemon_name": "Testo",
    "set_number": "Formato XX/YYY o null",
    "illustrator": null,
    "anno_carta": "indica l’anno della carta"
  },
  "set_details": {
    "raw_printed_code": null,
    "set_abbreviation": null,
    "set_name": null,
    "is_new_set": false
  },
  "is_old_card": true
}
========== PERCORSO B: CARTA MODERNA (2023+) ==========
--- DATABASE SET CONOSCIUTI ---
$jsonSets

1. NOME DELLA CARTA
Cerca il nome del Pokémon (in alto o in verticale se ruotata). Trascrivi esattamente come appare.

2. NUMERO DELLA CARTA (IGNORA IL POKÉDEX)
DOVE: Solo sul bordo inferiore estremo.

IGNORA qualsiasi “No.” o “N.” sotto l’illustrazione.

Cerca il formato “XX/YYY”, vicino al simbolo del set.

3. ILLUSTRATORE
Cerca “Illus.” o “Ill.” nel bordo.

Estrai il nome subito dopo; se assente, restituisci "null".

4. RILEVAZIONE DEL SET — METODO A DOPPIA VERIFICA
PASSO 1: Estrai il denominatore dal numero carta (es. “27/100” → 100).

PASSO 2: Osserva il simbolo vicino al numero e descrivilo brevemente in "set_symbol_description".

PASSO 3:

Filtra dal database i set con total = denominatore.

Se più set hanno lo stesso totale, usa la descrizione del simbolo per disambiguare.

Se nessun set corrisponde, imposta "set_abbreviation" e "set_name" a null.

5. REGOLA SULLA SIGLA DEL SET
Quando estrai set_abbreviation da un codice come "PFLit", rimuovi sempre il suffisso linguistico (it, en, es, ecc.).

La set_abbreviation deve contenere solo la sigla del set base (es. "PFL").

Mantieni set_name completo (“Phantasmal Flames”) e raw_printed_code come appare originariamente.

FORMATO OUTPUT JSON
json
{
  "card_identity": {
    "pokemon_name": "Testo",
    "set_number": "Testo formato XX/YYY o null",
    "illustrator": "Nome o null",
    "anno_carta": "indica l’anno della carta"
  },
  "set_details": {
    "raw_printed_code": "Codice completo stampato (es. 'PFLit') o null",
    "set_symbol_description": "Descrizione visiva del simbolo o null",
    "set_abbreviation": "Sigla base del set senza suffisso lingua (es. 'PFL') o null",
    "set_name": "Nome completo del set o null",
    "is_new_set": false
  }
}

TEXT;

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "inline_data" => [
                                "mime_type" => "image/jpeg",
                                "data" => $base64Image
                            ]
                        ],
                        [
                            "text" => $prompt
                        ]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => 0.1,
                "topP" => 0.8,
                "topK" => 10,
            ]
        ];
        Log::info('Gemini Service - ' . $this->baseUrl);
        Log::info('Gemini Service - Headers: ' . json_encode([
            'Content-Type' => 'application/json',
            'x-goog-api-key' => $apiKey
        ], JSON_PRETTY_PRINT));
        // Log::info('Gemini Service: ' . json_encode($payload, JSON_PRETTY_PRINT));
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey
            ])->post($this->baseUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Extract text from Gemini response structure
                $generatedText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Clean up potential markdown code blocks
                $jsonString = str_replace(['```json', '```'], '', $generatedText);
                $jsonString = trim($jsonString);
                $result = json_decode($jsonString, true);
                Log::info("Response: " . json_encode($result, JSON_PRETTY_PRINT));

                // Check if it's a valid card
                if ($result && isset($result['is_valid_card']) && $result['is_valid_card'] === false) {
                    Log::info('Image rejected: not a valid trading card');
                    return [
                        'is_valid_card' => false,
                        'error_message' => $result['error_message'] ?? 'L\'immagine non è una carta da gioco valida'
                    ];
                }

                return $result;
            } else {
                Log::error('Gemini API Error: ' . $response->body());
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            return null;
        }
    }


    /**
     * Map new Gemini AI structured data to legacy database format
     * This allows backward compatibility without database schema changes
     * 
     * @param array $geminiData New structured data from Gemini AI
     * @return array Legacy format data ready for database insertion
     */
    public function mapGeminiToLegacyFormat(array $geminiData): array
    {
        // Handle invalid cards
        if (isset($geminiData['is_valid_card']) && $geminiData['is_valid_card'] === false) {
            return [
                'is_valid_card' => false,
                'error_message' => $geminiData['error_message'] ?? 'Carta non valida'
            ];
        }

        $cardIdentity = $geminiData['card_identity'] ?? [];
        $setDetails = $geminiData['set_details'] ?? [];

        return [
            'is_valid_card' => true,
            'is_old_card' => $cardIdentity['anno_carta'] < 2023 ? true : false,
            'card_name' => $cardIdentity['pokemon_name'] ?? null,
            'set_code' => $setDetails['set_abbreviation'] ?? null,
            'set_number' => $cardIdentity['set_number'] ?? null,
            'illustrator' => $cardIdentity['illustrator'] ?? null,
            'set_info' => $setDetails,

            // Fields not present in the new prompt but required by legacy format/UI
            'hp' => null,
            'type' => null,
            'evolution_stage' => null,
            'attacks' => [],
            'weakness' => null,
            'resistance' => null,
            'retreat_cost' => null,
            'rarity' => null,
            'flavor_text' => null,
            'game' => 'Pokémon',
            'card_language' => null,
        ];
    }
}