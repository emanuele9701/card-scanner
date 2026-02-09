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
        Sei un'AI esperta in visione artificiale e catalogazione di carte collezionabili (TCG).
Il tuo compito è analizzare l'immagine fornita ed estrarre dati strutturati per un database di collezionismo.

--- FASE 1: VALIDAZIONE ---
Verifica se l'immagine mostra una carta da gioco collezionabile (Pokemon, Magic, Yu-Gi-Oh!, One Piece, Lorcana, etc.).
Se l'immagine NON è una carta, restituisci SOLO questo JSON:
{ "is_valid_card": false, "error_message": "L'immagine non sembra essere una carta da gioco." }

--- FASE 2: GUIDA RICONOSCIMENTO VISIVO (Solo per POKÉMON) ---
Se identifichi la carta come "Pokemon", segui RIGOROSAMENTE questi passaggi logici prima di compilare il JSON.

A. IDENTIFICAZIONE TIPO (Elemento) - NON INDOVINARE DAL DISEGNO:
1.  Localizza il piccolo simbolo sferico nell'angolo in ALTO A DESTRA.
2.  Confronta la forma del simbolo:
    -   PUGNO chiuso -> "Fighting"
    -   GOCCIA d'acqua -> "Water"
    -   FIAMMA -> "Fire"
    -   FOGLIA -> "Grass"
    -   FULMINE -> "Lightning"
    -   OCCHIO -> "Psychic"
    -   LUNA/Teschio/Nero -> "Darkness"
    -   BULLONE/Ingranaggio -> "Metal"
    -   STELLA (sfondo bianco) -> "Colorless"
    -   Z stilizzata (sfondo oro) -> "Dragon"
    -   ALA (sfondo rosa) -> "Fairy"
3.  Conferma col colore di sfondo (es. Marrone=Fighting, Blu=Water).

B. IDENTIFICAZIONE RARITÀ (Simbolo in basso):
Guarda nell'angolo in BASSO A SINISTRA o BASSO A DESTRA (vicino al numero).
Mappa il simbolo trovato:
    -   Cerchio (●) -> "Common"
    -   Rombo (◆) -> "Uncommon"
    -   Stella (★) -> "Rare"
    -   Stella Argento/Brillante -> "Holo Rare"
    -   Lettere "C"/"U" -> "Common/Uncommon"
    -   Lettera "R" -> "Rare"
    -   Lettere "RR" -> "Double Rare"
    -   Lettere "AR"/"IR" -> "Illustration Rare"
    -   Lettere "SR"/"SAR"/"UR" -> "Ultra/Secret Rare"

--- FASE 3: ESTRAZIONE DATI ---
Se l'immagine è valida, restituisci ESCLUSIVAMENTE un oggetto JSON.
IMPORTANTE: Se la carta è straniera, traduci il nome in 'standardized_name_en'.

{
    "is_valid_card": true,
    "game": "Nome del gioco (es. Pokemon)",
    "detected_language": "Lingua (es. Italian, English, Japanese)",
    "card_header": {
        "name_on_card": "Nome come scritto sulla carta",
        "standardized_name_en": "Nome inglese ufficiale (es. 'Croagunk')",
        "hp": "Valore numerico (es. 70) o null",
        "type": "Usa Guida A (es. Fighting)",
        "evolution_stage": "Basic, Stage 1, Stage 2, V, etc."
    },
    "attacks_and_abilities": [
        {
            "type": "Attack o Ability",
            "name": "Nome mossa",
            "cost": ["Water", "Colorless"], 
            "damage": "Danno (es. 20) o null",
            "text": "Testo effetto"
        }
    ],
    "bottom_stats": {
        "weakness": {"type": "Tipo", "value": "Valore"},
        "resistance": {"type": "Tipo", "value": "Valore"},
        "retreat_cost": "Numero intero (conta le stelle)"
    },
    "set_info": {
        "set_code": "Codice in basso a sx (es. MEG, sv9a). ATTENZIONE: Leggi lettera per lettera, non allucinare codici famosi come MEW.",
        "set_number": "Numero (es. 078/132)",
        "regulation_mark": "Lettera isolata in basso a sx (es. F, G, H, I) o null",
        "rarity_details": {
             "symbol_visible": "Descrivi cosa vedi (es. 'Cerchio', 'Stella', 'RR')",
             "rarity_type": "Usa Guida B (es. Common, Rare)"
        },
        "illustrator": "Nome artista"
    },
    "visual_analysis": {
        "is_holo": boolean,
        "is_reverse_holo": boolean (true se brilla il testo ma NON l'immagine),
        "is_full_art": boolean,
        "texture_notes": "Note su texture/rilievo"
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

        // Extract nested data with safe defaults - UPDATED STRUCTURE
        $cardHeader = $geminiData['card_header'] ?? [];
        $attacksAndAbilities = $geminiData['attacks_and_abilities'] ?? [];
        $bottomStats = $geminiData['bottom_stats'] ?? [];
        $setInfo = $geminiData['set_info'] ?? [];
        $visualAnalysis = $geminiData['visual_analysis'] ?? [];

        // Map to legacy format
        return [
            'is_valid_card' => true,
            'card_name' => $cardHeader['standardized_name_en'] ?? $cardHeader['name_on_card'] ?? null,
            'hp' => $cardHeader['hp'] ?? null,
            'type' => $cardHeader['type'] ?? null,
            'evolution_stage' => $cardHeader['evolution_stage'] ?? null,
            'attacks' => $attacksAndAbilities,

            // Bottom stats (weakness, resistance, retreat_cost)
            'weakness' => isset($bottomStats['weakness']) ?
                trim(($bottomStats['weakness']['type'] ?? '') . ' ' . ($bottomStats['weakness']['value'] ?? '')) : null,
            'resistance' => isset($bottomStats['resistance']) ?
                trim(($bottomStats['resistance']['type'] ?? '') . ' ' . ($bottomStats['resistance']['value'] ?? '')) : null,
            'retreat_cost' => isset($bottomStats['retreat_cost']) ? (string)$bottomStats['retreat_cost'] : null,

            'rarity' => $setInfo['rarity_details']['rarity_type'] ?? $setInfo['rarity_symbol'] ?? null,
            'set_number' => $setInfo['set_number'] ?? null,
            'illustrator' => $setInfo['illustrator'] ?? null,
            'flavor_text' => $visualAnalysis['texture_notes'] ?? null,

            // Additional fields that might be useful
            'game' => $geminiData['game'] ?? null,
            'card_language' => $geminiData['detected_language'] ?? null,

            // Store original Gemini data for future reference (optional)
            '_gemini_raw' => $geminiData
        ];
    }
}
