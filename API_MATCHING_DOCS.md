# Documentazione API Matching Carte Pokemon

## Introduzione
Questa documentazione descrive le API per il sistema di matching delle carte scansionate con i dati di mercato. Queste API permettono di ottenere suggerimenti per carte non abbinate, confermare l'abbinamento e gestire le condizioni della carta per ottenere valutazioni precise.

## Autenticazione
Tutte le chiamate API richiedono l'autenticazione tramite token Bearer (Sanctum).
Header richiesto:
`Authorization: Bearer <tuo_token>`

## Base URL
`/api`

## Endpoints

### 1. Ottieni Suggerimenti per una Carta
Restituisce una lista di carte di mercato suggerite per una specifica carta scansionata (PokemonCard) che non ha ancora un abbinamento.

**Endpoint:** `GET /matching/cards/{card_id}/suggestions`

**Parametri URL:**
- `card_id` (integer, obbligatorio): L'ID della carta scansionata (PokemonCard).

**Risposta di Successo (200 OK):**
```json
{
    "card": {
        "id": 123,
        "name": "Charizard",
        "number": "4/102",
        "set": "Base Set",
        "image": "https://example.com/storage/cards/123.jpg"
    },
    "suggestions": [
        {
            "id": 456,
            "name": "Charizard",
            "number": "4/102",
            "set": "Base Set",
            "rarity": "Holo Rare",
            "price": {
                "market": 350.00,
                "low": 300.00,
                "condition": "Near Mint"
            }
        },
        // ... altri suggerimenti
    ]
}
```

---

### 2. Conferma Abbinamento
Collega una carta scansionata ad una specifica carta di mercato selezionata.
*Nota: Questa operazione assegnerà automaticamente anche il set alla carta (creandolo se necessario per l'utente) e imposterà il numero della carta.*

**Endpoint:** `POST /matching/cards/{card_id}/match`

**Parametri URL:**
- `card_id` (integer, obbligatorio): L'ID della carta scansionata (PokemonCard).

**Body della Richiesta (JSON):**
```json
{
    "market_card_id": 456
}
```

**Risposta di Successo (200 OK):**
```json
{
    "message": "Card matched successfully",
    "matched_card": {
        "id": 123,
        "market_card_id": 456,
        "status": "matched"
    }
}
```

---

### 3. Ottieni Condizioni Disponibili
Restituisce le condizioni disponibili per una carta che ha già un abbinamento di mercato. Le condizioni derivano dai prezzi di mercato disponibili o, in assenza di dati, dalle condizioni standard.

**Endpoint:** `GET /collection/cards/{card_id}/conditions`

**Parametri URL:**
- `card_id` (integer, obbligatorio): L'ID della carta scansionata (PokemonCard). Deve essere già abbinata ad una carta di mercato.

**Risposta di Successo (200 OK):**
```json
{
    "card_id": 123,
    "market_card_id": 456,
    "conditions": [
        "Near Mint",
        "Lightly Played",
        "Moderately Played",
        "Heavily Played",
        "Damaged"
    ]
}
```

**Errori Possibili:**
- `422 Unprocessable Entity`: La carta non è ancora abbinata ad una carta di mercato.

---

### 4. Aggiorna Condizione Carta
Imposta la condizione della carta. Questo aggiornerà automaticamente anche il valore stimato della carta basato sui dati di mercato per quella condizione.

**Endpoint:** `POST /collection/cards/{card_id}/condition`

**Parametri URL:**
- `card_id` (integer, obbligatorio): L'ID della carta scansionata (PokemonCard).

**Body della Richiesta (JSON):**
```json
{
    "condition": "Near Mint"
}
```

**Risposta di Successo (200 OK):**
```json
{
    "message": "Card condition updated successfully",
    "data": {
        "id": 123,
        "condition": "Near Mint",
        "estimated_value": 350.00,
        "formatted_value": "$350.00"
    }
}
```

### 5. Rimuovi Set Carta
Rimuove l'associazione della carta ad un set (imposta il set a null).

**Endpoint:** `DELETE /collection/cards/{card_id}/set`

**Parametri URL:**
- `card_id` (integer, obbligatorio): L'ID della carta scansionata.

**Risposta di Successo (200 OK):**
```json
{
    "message": "Card set removed successfully",
    "data": {
        "id": 123,
        "card_set_id": null
    }
}
```

**Errori Possibili:**
- `401 Unauthorized`: Token mancante o non valido.

---

### 6. Assegna Set Carta
Assegna manualmente un set ad una carta. È possibile specificare anche il numero della carta nel set.

**Endpoint:** `POST /collection/cards/{card_id}/set`

**Parametri URL:**
- `card_id` (integer, obbligatorio): L'ID della carta scansionata.

**Body della Richiesta (JSON):**
```json
{
    "card_set_id": 456,
    "set_number": "4/102"
}
```
*`set_number` è opzionale.*

**Risposta di Successo (200 OK):**
```json
{
    "message": "Card set updated successfully",
    "data": {
        "id": 123,
        "card_set_id": 456,
        "set_number": "4/102",
        "set": {
            "id": 456,
            "name": "Base Set",
            "abbreviation": "BS"
        }
    }
}
```

**Errori Possibili:**
- `401 Unauthorized`: Token mancante o non valido.
- `422 Unprocessable Entity`: ID set non valido.

---

### 7. Auto-Match Carte
Esegue un tentativo di abbinamento automatico per tutte le carte dell'utente che non hanno ancora un abbinamento. Opzionalmente, è possibile specificare una lista di ID carte da processare.
*Nota: L'auto-match assegnerà automaticamente anche il set alle carte (creandolo se necessario per l'utente) e imposterà il numero della carta.*

**Endpoint:** `POST /matching/auto-match`

**Body della Richiesta (JSON) - Opzionale:**
```json
{
    "card_ids": [123, 124, 125]
}
```
*Se `card_ids` non viene fornito, verranno processate tutte le carte non abbinate dell'utente.*

**Risposta di Successo (200 OK):**
```json
{
    "message": "Auto-match completed",
    "stats": {
        "processed": 50,
        "matched": 12,
        "unmatched": 38,
        "already_matched": 0
    }
}
```

**Errori Possibili:**
- `401 Unauthorized`: Token mancante o non valido.
