<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

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

        $prompt = "Sei un esperto di carte Pokemon TCG. Analizza l'immagine fornita.

        IMPORTANTE: Prima di tutto, verifica se l'immagine mostra una carta da gioco collezionabile (Pokemon, Magic, Yu-Gi-Oh, o simili).
        Se l'immagine NON è una carta da gioco, restituisci SOLO questo JSON:
        {
            \"is_valid_card\": false,
            \"error_message\": \"L'immagine non sembra essere una carta da gioco collezionabile\"
        }

        Se l'immagine È una carta da gioco Pokemon, analizza il testo OCR grezzo per estrarre le informazioni dettagliate.
        
        Testo OCR grezzo:
        {$ocrText}
        
        Rispondi ESCLUSIVAMENTE con un oggetto JSON valido (senza markdown o altro testo) con questa struttura esatta:
        {
            \"is_valid_card\": true,
            \"card_name\": \"Nome della carta\",
            \"hp\": \"HP (es. 120)\",
            \"type\": \"Tipo (es. Fuoco, Acqua)\",
            \"evolution_stage\": \"Stadio (es. Base, Fase 1)\",
            \"attacks\": [
                { \"name\": \"Nome Attacco\", \"damage\": \"Danno\", \"cost\": \"Costo (es. 2 Fuoco)\", \"effect\": \"Descrizione effetto\" }
            ],
            \"weakness\": \"Debolezza\",
            \"resistance\": \"Resistenza\",
            \"retreat_cost\": \"Costo ritirata\",
            \"rarity\": \"Rarità\",
            \"set_number\": \"Numero serie (es. 001/151)\",
            \"illustrator\": \"Illustratore\",
            \"flavor_text\": \"Testo descrittivo\",
            \"analysis_notes\": \"Breve nota su cosa hai corretto dall'OCR\"
        }";

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
