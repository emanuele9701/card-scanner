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
        $allSets = CardSet::where('game_id', $gameId)->where('user_id', auth()->id())->pluck('name', 'card_set_abbreviation');
        $jsonSets = json_encode($allSets);
        $prompt = <<<TEXT
        Sei un sistema OCR rigoroso per l'estrazione dati da carte TCG.
ATTENZIONE CRITICA: Se un dato non è fisicamente e testualmente presente sulla carta, DEVI restituire "null". 
NON usare mai la tua conoscenza pregressa per indovinare. NON usare mai i testi forniti negli esempi qui sotto come risposte reali.

--- DATABASE SET CONOSCIUTI ---
$jsonSets

--- REGOLE DI ESTRAZIONE E GESTIONE DEI VUOTI (NULL) ---

1. NOME DELLA CARTA (Alto Sinistra):
   - Trascrivi il testo in alto (es. "Imakuni?").

2. NUMERO DELLA CARTA (Basso Sinistra/Destra):
   - Cerca il formato "XXX/YYY" o solo "XXX". Se lo trovi, riportalo (es. "63/83").

3. ILLUSTRATORE (Regola Zero Tolleranza):
   - Cerca ESPLICITAMENTE il prefisso "Ill." o "Illus." sul bordo inferiore.
   - Se c'è, estrai il nome.
   - SE NON C'È (es. la carta ha una fotografia o è vecchia), devi OBBLIGATORIAMENTE restituire null. NON inventare artisti.

4. RILEVAZIONE DEL SET (IL PROBLEMA DELLE CARTE VECCHIE):
   - Le carte moderne hanno un codice alfanumerico stampato (es. "PFLit", "SVI").
   - Le carte più vecchie NON HANNO codici testuali, ma solo SIMBOLI GRAFICI.
   
   SCENARIO A (Vedi un codice di testo di 3-6 lettere):
   - Leggi il codice grezzo (es. "sv4pt5it"). Rimuovi la lingua ("it") per trovare la radice ("sv4pt5").
   - Cerca la radice nel [DATABASE SET CONOSCIUTI].
   - Compila "set_name" (da DB o null se non trovato), "set_abbreviation" (radice calcolata) e imposta "is_new_set" a true/false di conseguenza.

   SCENARIO B (NON vedi nessun codice testuale in basso):
   - Se vedi solo un simbolo grafico, o non c'è nulla vicino al numero della carta, FERMATI.
   - Devi OBBLIGATORIAMENTE impostare "raw_printed_code", "set_abbreviation" e "set_name" a null.
   - Imposta "is_new_set" a false.

--- FORMATO OUTPUT JSON ---
Restituisci ESCLUSIVAMENTE questo JSON senza aggiungere altro testo.

{
    "card_identity": {
        "pokemon_name": "Testo",
        "set_number": "Testo",
        "illustrator": "Nome o null"
    },
    "set_details": {
        "raw_printed_code": "Codice testuale stampato o null",
        "set_abbreviation": "Radice calcolata o null",
        "set_name": "Nome da DB o null",
        "is_new_set": boolean
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
            'card_name' => $cardIdentity['pokemon_name'] ?? null,
            'set_code' => $setDetails['set_abbreviation'] ?? null,
            'set_number' => $cardIdentity['set_number'] ?? null,
            'illustrator' => $cardIdentity['illustrator'] ?? null,

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
            'set_info' => $geminiData['set_details'] ?? null,
        ];
    }
}