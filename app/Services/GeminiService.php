<?php

namespace App\Services;

use App\Models\CardSet;
use App\Models\Game;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', '');
        $this->baseUrl = config(
            'services.gemini.base_url',
            'https://generativelanguage.googleapis.com/v1beta/models/gemma-3-27b-it:generateContent'
        );
    }

    /**
     * Enhance card data using Gemini AI
     *
     * @param string $base64Image Base64 encoded image string (without prefix)
     * @return array|null Structured data or null on failure
     */
    public function enhanceCardData(string $base64Image): ?array
    {
        $prompt = $this->buildOcrPrompt();

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

        Log::info('Gemini Service - Sending request to: ' . $this->baseUrl);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $this->apiKey
            ])->post($this->baseUrl, $payload);
            // dd($response->body());
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
     * Build the OCR prompt for Gemini AI card recognition.
     * Caches the known sets list to avoid DB queries on each call.
     *
     * @return string The complete prompt text
     */
    private function buildOcrPrompt(): string
    {
        $jsonSets = Cache::remember('pokemon_sets_for_prompt', 3600, function () {
            $gameId = Game::where('name', 'Pokemon')->first()?->id;
            if (!$gameId) {
                return '{}';
            }
            return json_encode(CardSet::where('game_id', $gameId)->pluck('name', 'card_set_abbreviation'));
        });

        return <<<TEXT
      Sei un sistema OCR rigoroso per l'estrazione dati da carte Pokémon TCG.
ATTENZIONE CRITICA: Se un dato non è fisicamente e testualmente presente, restituisci "null".
NON unire testi lontani tra loro. NON dedurre. NON interpretare. NON inventare dati.
REGOLE DI OUTPUT: Devi restituire ESCLUSIVAMENTE il codice JSON valido. NON aggiungere saluti, NON aggiungere spiegazioni, NON usare formattazione markdown fuori dal JSON.

---

# --- STEP 0: DETERMINA L'ERA DELLA CARTA (REGOLA VINCOLANTE) ---

Cerca l'anno di copyright stampato in basso (es. 1999, 2002, 2023, 2025). Questo è il TUO UNICO CRITERIO per decidere.

### CARTA MODERNA (2023+)
Se l'anno di copyright è **2023 o superiore** (es. 2023, 2024, 2025)
OPPURE è visibile un riquadro nero/grigio con il codice del set (es. "MEG IT")
→ È TASSATIVAMENTE una carta moderna.
→ Segui il **PERCORSO B**.

### CARTA VECCHIA (pre-2023)
Se l'anno di copyright è **2022 o inferiore** (es. 1999, 2015, 2022)
→ È TASSATIVAMENTE una carta vecchia.
→ Segui il **PERCORSO A**.

Non usare il colore dei bordi come criterio decisionale.

## 1. NOME DELLA CARTA

Il nome del Pokémon visibile **in alto**.

Se la carta è ruotata, analizza **tutte le direzioni**.

### Eccezione alla regola "NON DEDURRE"

Se il font presenta **spaziature anomale** che spezzano il nome di un Pokémon noto  
(es. `"Magne mite"`, `"Pika chu"`)

→ unisci le lettere per formare il nome corretto del Pokémon  
(es. `"Magnemite"`, `"Pikachu"`).

---

## 2. NUMERO DELLA CARTA

### Dove cercare

Cerca **ESCLUSIVAMENTE nell'angolo in basso a sinistra**,  
sulla stessa riga o immediatamente accanto:

- al **simbolo dell'espansione**
- al **nome dell'illustratore**

Deve essere **un blocco unico** nel formato:


XX/YYY


oppure


XXX/YYY


**Esempio valido**


105/132


### Non prendere MAI numeri:

- al **centro della carta**
- **sotto l'illustrazione** del Pokémon
- preceduti da `"No."` o `"N."`

Se **non presente in quell'area precisa** → `"null"`

---

## 3. LINGUA DELLA CARTA

Determina la lingua leggendo:

- il testo degli attacchi
- le etichette della salute  
  - `"HP"` → inglese  
  - `"PS"` → italiano
- le scritte in basso  
  - `"weakness"` vs `"debolezza"`

Restituisci la **sigla della lingua in maiuscolo**.

Esempi:


IT
EN
FR
JP


---

# OUTPUT JSON (Carta Vecchia)

```json
{
  "card_identity": {
    "pokemon_name": "Testo",
    "set_number": "Formato XX/YYY o null",
    "language": "Sigla lingua o null",
    "illustrator": null,
    "anno_carta": "anno visibile in basso"
  },
  "set_details": {
    "raw_printed_code": null,
    "set_abbreviation": null,
    "set_name": null,
    "is_new_set": false
  },
  "is_old_card": true
}
PERCORSO B: CARTA MODERNA (2023+)
DATABASE SET CONOSCIUTI
$jsonSets
1. NOME DELLA CARTA

Il nome del Pokémon visibile in alto.

Se la carta è ruotata, analizza tutte le direzioni.

Eccezione alla regola "NON DEDURRE"

Se il font presenta spaziature anomale che spezzano il nome di un Pokémon noto
(es. "Magne mite", "Pika chu")

→ unisci le lettere per formare il nome corretto del Pokémon
(es. "Magnemite", "Pikachu").

2. NUMERO DELLA CARTA
Dove cercare

Cerca ESCLUSIVAMENTE nell'angolo in basso a sinistra,
sulla stessa riga o immediatamente accanto:

al simbolo dell'espansione

al nome dell'illustratore

Deve essere un blocco unico nel formato:

XX/YYY

oppure

XXX/YYY

Esempio valido

27/100
Non prendere MAI numeri:

al centro della carta

sotto il disegno del Pokémon

preceduti da "No." o "N."

Se non presente in quell'area precisa → "null"

3. ILLUSTRATORE

Cerca "Illus." o "Ill." nella stessa fascia inferiore.

Estrai il nome subito dopo.

Se assente → "null".

4. RILEVAZIONE DEL SET E DELLA LINGUA
PASSO 1

Estrai il denominatore dal numero carta.

Esempio:

27/100 → totale = 100
PASSO 2

Osserva il simbolo accanto al numero carta.

Descrivilo brevemente in:

set_symbol_description
PASSO 3 (LINGUA)

Determina la lingua analizzando:

il testo degli attacchi (HP vs PS)

oppure il suffisso letterale del codice stampato

Esempio:

PFLit → "it" indica italiano

Restituisci la sigla in maiuscolo:

IT
EN
5. REGOLA SULLA SIGLA DEL SET

Se il codice stampato è ad esempio:

PFLit

Allora:

raw_printed_code = "PFLit"
set_abbreviation = "PFL"
set_name = nome completo del set
is_new_set = false

⚠️ Rimuovi sempre il suffisso della lingua (it, en, es, ecc.).

La sigla del set deve essere pulita.

OUTPUT JSON (Carta Moderna)
{
  "card_identity": {
    "pokemon_name": "Testo",
    "set_number": "Formato XX/YYY o null",
    "language": "Sigla lingua o null",
    "illustrator": "Nome o null",
    "anno_carta": "anno visibile in basso"
  },
  "set_details": {
    "raw_printed_code": "Codice completo stampato o null",
    "set_symbol_description": "Descrizione visiva del simbolo o null",
    "set_abbreviation": "Sigla base del set senza suffisso lingua o null",
    "set_name": "Nome completo del set o null",
    "is_new_set": false
  }
}
TEXT;
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
            'language_card' => $cardIdentity['language'],
            'is_valid_card' => true,
            'is_old_card' => ((int)($cardIdentity['anno_carta'] ?? 0)) < 2023,
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
