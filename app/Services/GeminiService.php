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

        $prompt = "Sei un esperto di carte collezionabili (TCG) e visione artificiale.
        Il tuo compito è estrarre dati strutturati dall'immagine di una carta per un database di collezionismo.

        Fase 1: Validazione
        Verifica se l'immagine è una carta collezionabile (TCG).
        Se NO: Restituisci JSON con { \"is_valid_card\": false, \"error_message\": \"...\" }

        Fase 2: Estrazione Dati (Se valida)
        Identifica il gioco e tutti i dettagli tecnici.
        IMPORTANTE: Se il testo sulla carta è in una lingua diversa dall'INGLESE (es. Giapponese), devi fornire ANCHE la traduzione inglese standard nel campo \"standardized_name\".

        Restituisci SOLO un JSON con questa struttura:

        {
            \"is_valid_card\": true,
            \"game\": \"Nome del gioco (Pokemon, Magic, Yu-Gi-Oh!, etc.)\",
            \"card_language\": \"Lingua rilevata del testo sulla carta (es. Japanese, English, Italian)\",
            \"card_attributes\": {
                \"name_on_card\": \"Nome esattamente come appare sulla carta\",
                \"standardized_name\": \"Nome standard in INGLESE (per ricerca DB)\",
                \"hp\": \"Valore numerico (es. 180) o null\",
                \"primary_type\": \"Tipo principale (es. Water, Fire)\",
                \"evolution_stage\": \"Basic, Stage 1, Stage 2, VMAX, etc.\",
                \"attacks\": [
                    { 
                        \"name\": \"Nome mossa\", 
                        \"cost\": [\"Water\", \"Colorless\"], 
                        \"damage\": \"Danno (es. 70x)\", 
                        \"effect_summary\": \"Riassunto breve effetto\" 
                    }
                ]
            },
            \"set_details\": {
                \"set_code\": \"Codice del set stampato sulla carta (es. sv9a, MEW, OP05)\",
                \"set_number\": \"Numero della carta (es. 026/063)\",
                \"regulation_mark\": \"Lettera di regolamento se presente (es. E, F, G, H)\",
                \"rarity_symbol\": \"Descrizione simbolo rarità (es. Star, Circle, R, SR)\"
            },
            \"visual_analysis\": {
                \"is_holo\": boolean,
                \"is_full_art\": boolean,
                \"illustrator\": \"Nome artista\"
            },
            \"notes\": \"Eventuali note su condizioni visibili o particolarità\"
        }
        ";

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
        Log::info('Gemini Service: ' . json_encode($payload, JSON_PRETTY_PRINT));
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
}
