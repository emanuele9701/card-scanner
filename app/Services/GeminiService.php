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
- CARTA MODERNA (2023+): bordi argentati/bianchi, design pulito, codice testuale di set stampato in basso (es. "PFLit", "sv4pt5it", "OBFit").
- CARTA VECCHIA (pre-2023): bordi gialli/colorati, design più semplice, NESSUN codice testuale di set, solo simboli grafici.
Se la carta è VECCHIA → segui il PERCORSO A.
Se la carta è MODERNA → segui il PERCORSO B.
========== PERCORSO A: CARTA VECCHIA (pre-2023) ==========
Estrai SOLO queste informazioni:
1. NOME DELLA CARTA: Il nome del Pokémon, visibile in alto. La carta potrebbe essere ruotata, cerca il nome in tutte le direzioni.
2. NUMERO DELLA CARTA:
   - DOVE: ESCLUSIVAMENTE nel bordo inferiore estremo della carta.
   - TRABOCCHETTO: Sotto l'illustrazione c'è il numero del Pokédex (es. "N. 0045", "No. 260"). IGNORA TOTALMENTE questo numero.
   - Cerca SOLO il formato "XX/YYY" o "XXX/YYY" nel bordo inferiore. Trascrivi ESATTAMENTE.
Restituisci questo JSON:
{
    "card_identity": {
        "pokemon_name": "Testo",
        "set_number": "Formato XX/YYY o null",
        "illustrator": null,
        "anno_carta": "indica l'anno della carta",
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

--- REGOLE DI ESTRAZIONE PER CARTE VECCHIE ---

⚠️ LAYOUT IMPORTANTE: Le carte vecchie possono avere il testo RUOTATO di 90° o disposto in modo diverso. Analizza l'intera superficie della carta in tutte le direzioni.

1. NOME DELLA CARTA:
   - Cerca il nome del Pokémon. Può trovarsi in alto a sinistra O in verticale se la carta è ruotata.
   - Trascrivi esattamente come lo leggi.

2. NUMERO DELLA CARTA (IL TRABOCCHETTO DEL POKÉDEX):
   - DOVE: ESCLUSIVAMENTE nella zona del bordo inferiore estremo.
   - IL TRABOCCHETTO: Sotto l'illustrazione c'è il numero enciclopedico (es. "N. 0045", "No. 260"). IGNORA TOTALMENTE QUESTO NUMERO.
   - Cerca il formato "XX/YYY" vicino al simbolo del set nel bordo inferiore. Trascrivi ESATTAMENTE.
   - DIVIETO ASSOLUTO: Non prendere MAI il numero del Pokédex.

3. ILLUSTRATORE:
   - Cerca ESPLICITAMENTE "Illus." o "Ill." nel bordo della carta (spesso in basso a sinistra o lungo il bordo, anche in verticale).
   - Estrai SOLO il nome dopo il prefisso.
   - SE NON C'È PREFISSO, restituisci null.

4. RILEVAZIONE DEL SET — METODO A DOPPIA VERIFICA:
   
   PASSO 1 — Estrai il denominatore dal numero carta:
   - Se il numero è "27/100", il denominatore è 100. Il set DEVE avere esattamente 100 carte totali.
   
   PASSO 2 — Osserva il simbolo grafico:
   - Cerca il piccolo simbolo/icona nel bordo inferiore accanto al numero.
   - Descrivi il simbolo nel campo "set_symbol_description".
   
   PASSO 3 — CROSS-VALIDAZIONE OBBLIGATORIA:
   - Filtra dal DATABASE solo i set il cui campo "total" corrisponde ESATTAMENTE al denominatore.
   - Tra questi candidati, scegli quello il cui simbolo corrisponde meglio alla tua osservazione.
   - Se solo UN set ha quel totale → è quasi certamente quello.
   - Se PIÙ set hanno lo stesso totale → usa il simbolo per disambiguare.
   - Se NESSUN set corrisponde → imposta abbreviation e name a null.
   
   Esempio: Numero "27/100" → denominatore 100 → set candidati con total=100: SS (Sandstorm), DR (Dragon), CG (Crystal Guardians), MD (Majestic Dawn). Osservo un cristallo raggiato → è CG (Crystal Guardians).

   REGOLE FINALI:
   - "raw_printed_code" è SEMPRE null per carte vecchie.
   - "is_new_set" è SEMPRE false.

--- FORMATO OUTPUT JSON ---
Restituisci ESCLUSIVAMENTE questo JSON senza aggiungere altro testo.

{
    "card_identity": {
        "pokemon_name": "Testo",
        "set_number": "Testo formato XX/YYY o null",
        "illustrator": "Nome o null",
        "anno_carta": "indica l'anno della carta",
    },
    "set_details": {
        "raw_printed_code": null,
        "set_symbol_description": "Descrizione del simbolo visivo o null",
        "set_abbreviation": "Sigla da DB o null",
        "set_name": "Nome da DB o null",
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