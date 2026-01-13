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

        $prompt = "Sei un esperto di carte collezionabili (TCG/CCG). Analizza l'immagine fornita.

        IMPORTANTE: Prima di tutto, verifica se l'immagine mostra una carta da gioco collezionabile (Pokemon, Magic: The Gathering, Yu-Gi-Oh!, Digimon, One Piece, Dragon Ball, o simili).
        Se l'immagine NON è una carta da gioco, restituisci SOLO questo JSON:
        {
            \"is_valid_card\": false,
            \"error_message\": \"L'immagine non sembra essere una carta da gioco collezionabile\"
        }

        Se l'immagine È una carta da gioco, identifica PRIMA il tipo di gioco (Pokemon, Yu-Gi-Oh!, Magic: The Gathering, etc.) e poi analizza i dettagli.
        
        Rispondi ESCLUSIVAMENTE con un oggetto JSON valido (senza markdown o altro testo) con questa struttura esatta:
        {
            \"is_valid_card\": true,
            \"game\": \"Nome del gioco (es. Pokemon, Yu-Gi-Oh!, Magic: The Gathering, Digimon, One Piece, etc.)\",
            \"card_name\": \"Nome della carta\",
            \"hp\": \"HP/ATK/DEF (a seconda del gioco)\",
            \"type\": \"Tipo/Colore (es. Fuoco, Acqua per Pokemon; Creatura, Stregoneria per Magic; Mostro, Magia per Yu-Gi-Oh!)\",
            \"evolution_stage\": \"Stadio evolutivo (se applicabile, es. Base, Fase 1 per Pokemon)\",
            \"attacks\": [
                { \"name\": \"Nome Attacco/Abilità\", \"damage\": \"Danno\", \"cost\": \"Costo\", \"effect\": \"Descrizione effetto\" }
            ],
            \"weakness\": \"Debolezza\",
            \"resistance\": \"Resistenza\",
            \"retreat_cost\": \"Costo ritirata (o equivalente)\",
            \"rarity\": \"Rarità\",
            \"set_number\": \"Numero serie (es. 001/151)\",
            \"illustrator\": \"Illustratore/Artista\",
            \"flavor_text\": \"Testo descrittivo\",
            \"analysis_notes\": \"Breve nota su cosa hai identificato\"
        }
        
        NOTA: Il campo 'game' è OBBLIGATORIO e deve essere il nome preciso del gioco di carte.";

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
