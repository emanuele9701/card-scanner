<?php

namespace App\Services;

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

        $prompt = <<<TEXT
        Sei un sistema OCR di precisione per l'archiviazione di carte collezionabili.
Il tuo compito è scansionare la carta per estrarre 4 dati identificativi specifici.
Ignora HP, Attacchi, Descrizioni e tutto ciò che riguarda il gioco.

--- ISTRUZIONI DI ESTRAZIONE ---

1. NOME DEL POKEMON (Target: Alto Sinistra):
   - Cerca il testo più grande in alto.
   - Trascrivilo esattamente (es. "Genesect", "Ludicolo").

2. DATI DEL SET (Target: Basso Sinistra/Destra):
   - CODICE SET: Cerca una sigla alfanumerica breve, spesso in un rettangolino o vicino al numero (es. "PFLit", "sv4pt5", "SVI").
   - NUMERO SET: Cerca il formato "XXX/YYY" (es. "008/094"). Riporta l'intera stringa con lo slash.

3. ILLUSTRATORE (Target: Bordo Inferiore Sinistro/Destro):
   - Cerca il testo preceduto da "Ill." o "Illus.".
   - Esempio: Se leggi "Ill. Mitsuhiro Arita", estrai "Mitsuhiro Arita".
   - Esempio: Se leggi "Illus. Gemi", estrai "Gemi".
   - ATTENZIONE: NON estrarre i testi di copyright come "©2023 Pokémon", "Nintendo", "Creatures" o "GAME FREAK". Quelli NON sono l'illustratore.

--- FORMATO OUTPUT ---
Restituisci SOLO questo JSON.

{
    "card_metadata": {
        "pokemon_name": "Nome estratto",
        "set_code": "Codice set (es. PFLit)",
        "set_number": "Numero completo (es. 008/094)",
        "illustrator": "Nome Artista (Senza 'Ill.' o 'Illus.')"
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

        // Extract the main metadata object from the new prompt structure
        $cardMetadata = str_replace(["it", "en"], "", $geminiData['card_metadata']) ?? [];

        // Map to legacy format
        return [
            'is_valid_card' => true,
            'card_name' => $cardMetadata['pokemon_name'] ?? null,
            'set_code' => $cardMetadata['set_code'] ?? null,
            'set_number' => $cardMetadata['set_number'] ?? null,
            'illustrator' => $cardMetadata['illustrator'] ?? null,

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
            'game' => $geminiData['game'] ?? 'Pokémon',
            'card_language' => $geminiData['detected_language'] ?? null,
        ];
    }
}
