# API Documentation - Carte Pokemon Collection

## Base URL
```
http://your-domain.com/api
```

## Autenticazione

Questa API utilizza Laravel Sanctum per l'autenticazione basata su token.

### Headers richiesti per endpoint protetti:
```
Authorization: Bearer {your-token}
Accept: application/json
Content-Type: application/json
```

---

## Endpoints

### 1. Autenticazione

#### 1.1 Registrazione
Registra un nuovo utente e restituisce un token di autenticazione.

**Endpoint:** `POST /api/auth/register`

**Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "name": "Nome Utente" // optional
}
```

**Risposta (201 Created):**
```json
{
  "message": "Registrazione completata con successo",
  "user": {
    "id": 1,
    "name": "Nome Utente",
    "email": "user@example.com",
    "display_name": "Nome"
  },
  "token": "1|abc123..."
}
```

**Validazioni:**
- `email`: obbligatorio, deve essere un'email valida, massimo 255 caratteri, deve essere univoca
- `password`: obbligatorio, minimo 8 caratteri, deve corrispondere a `password_confirmation`
- `name`: opzionale, massimo 255 caratteri (se non fornito, viene usato il prefisso dell'email)

---

#### 1.2 Login
Effettua il login e restituisce un token di autenticazione.

**Endpoint:** `POST /api/auth/login`

**Body:**
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

**Risposta (200 OK):**
```json
{
  "message": "Login effettuato con successo",
  "user": {
    "id": 1,
    "name": "Nome Utente",
    "email": "user@example.com",
    "display_name": "Nome"
  },
  "token": "2|xyz789..."
}
```

**Errori (422 Unprocessable Entity):**
```json
{
  "message": "The email field is invalid.",
  "errors": {
    "email": ["Credenziali non valide."]
  }
}
```

---

#### 1.3 Logout
Revoca il token corrente (richiede autenticazione).

**Endpoint:** `POST /api/auth/logout`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Risposta (200 OK):**
```json
{
  "message": "Logout effettuato con successo"
}
```

---

#### 1.4 Ottieni utente corrente
Restituisce i dati dell'utente autenticato.

**Endpoint:** `GET /api/auth/user`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Risposta (200 OK):**
```json
{
  "user": {
    "id": 1,
    "name": "Nome Utente",
    "email": "user@example.com",
    "display_name": "Nome",
    "full_name": "Nome Completo Utente",
    "avatar_url": "http://domain.com/storage/avatars/user.jpg"
  }
}
```

---

### 2. Collezione

#### 2.1 Lista carte della collezione
Restituisce tutte le carte nella collezione dell'utente autenticato con paginazione.

**Endpoint:** `GET /api/collection/cards`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Query Parameters:**
- `game` (string, optional): Filtra per gioco (es. "Pokemon", "Yu-Gi-Oh")
- `set_id` (integer, optional): Filtra per ID del set
- `rarity` (string, optional): Filtra per rarità. Valori consentiti:
  - `Comune`
  - `Non Comune`
  - `Rara`
  - `Rara Olografica/Foil`
  - `Rara Doppia/Ultrarara`
  - `Rara Illustrazione`
  - `Rara Illustrazione Speciale`
  - `Secret Rare`
  - `Rara Cromatica`
  - `Vintage/1ª Edizione`
- `condition` (string, optional): Filtra per condizione
- `type` (string, optional): Filtra per tipo di carta. Valori consentiti:
  - `Normale`
  - `Fuoco`
  - `Acqua`
  - `Erba`
  - `Elettro`
  - `Ghiaccio`
  - `Lotta`
  - `Veleno`
  - `Terra`
  - `Volante`
  - `Psico`
  - `Coleottero`
  - `Roccia`
  - `Spettro`
  - `Drago`
  - `Buio`
  - `Acciaio`
  - `Folletto`
  - `Strumento`
- `search` (string, optional): Cerca nel nome della carta
- `sort_by` (string, optional): Campo per ordinamento (created_at, card_name, set_number, rarity, acquisition_date). Default: created_at
- `sort_order` (string, optional): Direzione ordinamento (asc, desc). Default: desc
- `per_page` (integer, optional): Numero di risultati per pagina (max 100). Default: 15
- `page` (integer, optional): Numero della pagina

**Esempio:**
```
GET /api/collection/cards?game=Pokemon&rarity=Rare&per_page=20&page=1
```

**Risposta (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Charizard",
      "hp": "120",
      "type": "Fire",
      "evolution_stage": "Stage 2",
      "attacks": [
        {
          "name": "Fire Blast",
          "cost": ["Fire", "Fire", "Fire"],
          "damage": "100"
        }
      ],
      "weakness": "Water",
      "resistance": null,
      "retreat_cost": 3,
      "rarity": "Rare Holo",
      "set_number": "4",
      "illustrator": "Mitsuhiro Arita",
      "flavor_text": "Spits fire that is hot enough to melt boulders.",
      "game": "Pokemon",
      "condition": "Near Mint",
      "printing": "1st Edition",
      "acquisition_price": "50.00",
      "acquisition_date": "2024-01-15",
      "is_matched": true,
      "image_url": "http://domain.com/image/card/1",
      "set": {
        "id": 1,
        "name": "Base Set",
        "abbreviation": "BS",
        "release_date": "1999-01-09",
        "total_cards": 102
      },
      "market_data": {
        "has_data": true,
        "estimated_value": 150.00,
        "profit_loss": 100.00,
        "profit_loss_percentage": 200.00
      },
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "http://domain.com/api/collection/cards?page=1",
    "last": "http://domain.com/api/collection/cards?page=5",
    "prev": null,
    "next": "http://domain.com/api/collection/cards?page=2"
  }
}
```

---

#### 2.2 Lista collezionabili (Games)
Restituisce tutti i giochi/collezionabili nella collezione dell'utente con statistiche.

**Endpoint:** `GET /api/collection/games`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Risposta (200 OK):**
```json
{
  "data": [
    {
      "name": "Pokemon",
      "card_count": 45,
      "set_count": 3,
      "total_value": 1250.50
    },
    {
      "name": "Yu-Gi-Oh",
      "card_count": 28,
      "set_count": 2,
      "total_value": 850.00
    }
  ],
  "meta": {
    "total": 2
  }
}
```

---

#### 2.3 Lista carte non matchate
Restituisce tutte le carte nella collezione dell'utente che **non hanno ancora un match** con i dati di mercato (market_card_id IS NULL).

**Endpoint:** `GET /api/collection/cards/unmatched`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Query Parameters:**
- `game` (string, optional): Filtra per gioco
- `set_id` (integer, optional): Filtra per ID del set
- `rarity` (string, optional): Filtra per rarità (valori: vedi sezione 2.1)
- `condition` (string, optional): Filtra per condizione
- `type` (string, optional): Filtra per tipo di carta (valori: vedi sezione 2.1)
- `search` (string, optional): Cerca nel nome della carta
- `sort_by` (string, optional): Campo per ordinamento (created_at, card_name, set_number, rarity, acquisition_date). Default: created_at
- `sort_order` (string, optional): Direzione ordinamento (asc, desc). Default: desc
- `per_page` (integer, optional): Numero di risultati per pagina (max 100). Default: 15
- `page` (integer, optional): Numero della pagina

**Esempio:**
```
GET /api/collection/cards/unmatched?game=Pokemon&sort_by=card_name&per_page=20
```

**Risposta (200 OK):**
```json
{
  "data": [
    {
      "id": 5,
      "name": "Pikachu",
      "hp": "60",
      "type": "Elettro",
      "rarity": "Comune",
      "set_number": "25",
      "game": "Pokemon",
      "condition": "Near Mint",
      "is_matched": false,
      "image_url": "http://domain.com/api/image/card/5",
      "inventory_sum_quantity": 1,
      "set": {
        "id": 1,
        "name": "Base Set",
        "abbreviation": "BS"
      },
      "market_data": {
        "has_data": false,
        "estimated_value": null,
        "profit_loss": null,
        "profit_loss_percentage": null
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 2,
    "per_page": 15,
    "total": 23,
    "from": 1,
    "to": 15
  },
  "links": {
    "first": "http://domain.com/api/collection/cards/unmatched?page=1",
    "last": "http://domain.com/api/collection/cards/unmatched?page=2",
    "prev": null,
    "next": "http://domain.com/api/collection/cards/unmatched?page=2"
  },
  "stats": {
    "unmatched_cards": 23,
    "total_collection_cards": 150
  }
}
```

**Note:**
- `stats.unmatched_cards`: Numero totale di carte non matchate
- `stats.total_collection_cards`: Numero totale di carte nella collezione (matchate + non matchate)
- Utile per visualizzare il progresso del matching o preparare l'auto-match

---

### 3. Sets (Espansioni)

#### 3.1 Lista tutti i sets
Restituisce tutti i sets con statistiche della collezione dell'utente.

**Endpoint:** `GET /api/sets`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Query Parameters:**
- `game` (string, optional): Filtra per gioco
- `search` (string, optional): Cerca nel nome o abbreviazione del set
- `sort_by` (string, optional): Campo per ordinamento (name, abbreviation, release_date, total_cards). Default: release_date
- `sort_order` (string, optional): Direzione ordinamento (asc, desc). Default: desc
- `per_page` (integer, optional): Numero di risultati per pagina (max 100). Default: 20
- `page` (integer, optional): Numero della pagina

**Esempio:**
```
GET /api/sets?game=Pokemon&per_page=10
```

**Risposta (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Base Set",
      "abbreviation": "BS",
      "release_date": "1999-01-09",
      "total_cards": 102,
      "collection_stats": {
        "owned_cards": 45,
        "completion_percentage": 44.12,
        "total_value": 2500.00
      },
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 52,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "http://domain.com/api/sets?page=1",
    "last": "http://domain.com/api/sets?page=3",
    "prev": null,
    "next": "http://domain.com/api/sets?page=2"
  }
}
```

---

#### 3.2 Dettagli di un set specifico
Restituisce i dettagli di un set specifico con le carte dell'utente.

**Endpoint:** `GET /api/sets/{id}`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Risposta (200 OK):**
```json
{
  "data": {
    "id": 1,
    "name": "Base Set",
    "abbreviation": "BS",
    "release_date": "1999-01-09",
    "total_cards": 102,
    "collection_stats": {
      "owned_cards": 45,
      "completion_percentage": 44.12,
      "total_value": 2500.00
    },
    "cards": [
      {
        "id": 1,
        "name": "Charizard",
        "set_number": "4",
        "rarity": "Rare Holo",
        "condition": "Near Mint",
        "printing": "1st Edition",
        "image_url": "http://domain.com/image/card/1",
        "estimated_value": 150.00,
        "has_market_data": true
      }
    ],
    "created_at": "2024-01-01T00:00:00Z",
    "updated_at": "2024-01-01T00:00:00Z"
  }
}
```

**Errori (404 Not Found):**
```json
{
  "message": "No query results for model [App\\Models\\CardSet] {id}"
}
```

---

### 4. Gestione Carte Individuali

#### 4.1 Aggiorna dati carta
Aggiorna i dati di una carta esistente nella collezione dell'utente.

**Endpoint:** `POST /api/collection/cards/{card}`

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: multipart/form-data (se includi immagine) o application/json
```

**Path Parameters:**
- `card` (integer, required): ID della carta

**Body Parameters (form-data o JSON):**
- `card_name` (string, optional): Nome della carta
- `hp` (string, optional): Punti vita
- `type` (string, optional): Tipo carta (Normale, Fuoco, Acqua, Erba, Elettro, Ghiaccio, Lotta, Veleno, Terra, Volante, Psico, Coleottero, Roccia, Spettro, Drago, Buio, Acciaio, Folletto, Strumento)
- `evolution_stage` (string, optional): Stadio evoluzione
- `rarity` (string, optional): Rarità (vedi sezione 2.1)
- `set_number` (string, optional): Numero nel set
- `illustrator` (string, optional): Nome illustratore
- `flavor_text` (string, optional): Testo flavor
- `game` (string, optional): Nome gioco
- `condition` (string, optional): Condizione carta
- `printing` (string, optional): Tipo di stampa
- `acquisition_price` (numeric, optional): Prezzo di acquisto
- `acquisition_date` (date, optional): Data di acquisto (formato: YYYY-MM-DD)
- `image` (file, optional): Nuova immagine (max 10MB, formati: jpeg, png, jpg, webp)

**Esempio:**
```json
{
  "card_name": "Charizard",
  "hp": "120",
  "condition": "Near Mint",
  "acquisition_price": 50.00,
  "acquisition_date": "2024-01-15"
}
```

**Risposta (200 OK):**
```json
{
  "message": "Card updated successfully",
  "data": {
    "id": 1,
    "card_name": "Charizard",
    "hp": "120",
    "condition": "Near Mint",
    "acquisition_price": "50.00",
    "acquisition_date": "2024-01-15",
    "image_url": "http://domain.com/api/image/card/1",
    "updated_at": "2024-01-20T10:30:00Z"
  }
}
```

**Errori:**
- `403 Forbidden`: L'utente non possiede questa carta
- `422 Unprocessable Entity`: Errori di validazione
- `500 Internal Server Error`: Errore durante upload su Google Drive o salvataggio

**Note:**
- Solo il proprietario può modificare la carta
- L'update dell'immagine elimina automaticamente la precedente da Google Drive
- Se fornisci una nuova immagine, viene caricata su Google Drive

---

#### 4.2 Elimina carta
Elimina una carta dalla collezione dell'utente, inclusa l'immagine da Google Drive.

**Endpoint:** `DELETE /api/collection/cards/{card}`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Path Parameters:**
- `card` (integer, required): ID della carta

**Risposta (200 OK):**
```json
{
  "message": "Card deleted successfully"
}
```

**Errori:**
- `403 Forbidden`: L'utente non possiede questa carta
- `404 Not Found`: Carta non trovata
- `500 Internal Server Error`: Errore durante eliminazione

**Note:**
- Elimina anche il file immagine da Google Drive (se presente)
- Elimina il file locale
- L'operazione è definitiva e non reversibile

---

#### 4.3 Ottieni condizioni disponibili
Restituisce le condizioni disponibili per una carta matchata basate sui dati di mercato.

**Endpoint:** `GET /api/collection/cards/{card}/conditions`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Path Parameters:**
- `card` (integer, required): ID della carta

**Risposta (200 OK):**
```json
{
  "card_id": 1,
  "market_card_id": 662125,
  "conditions": [
    "Near Mint",
    "Lightly Played",
    "Moderately Played",
    "Heavily Played",
    "Damaged"
  ]
}
```

**Risposta (422 Unprocessable Entity) - Carta non matchata:**
```json
{
  "message": "Card must be matched to get market conditions",
  "conditions": []
}
```

**Errori:**
- `403 Forbidden`: L'utente non possiede questa carta
- `422 Unprocessable Entity`: La carta non è matchata (market_card_id è null)

**Note:**
- Restituisce le condizioni disponibili per la carta di mercato associata
- Se la carta non ha market data, restituisce le condizioni standard

---

#### 4.4 Aggiorna condizione carta
Aggiorna la condizione di una carta nella collezione.

**Endpoint:** `POST /api/collection/cards/{card}/condition`

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

**Path Parameters:**
- `card` (integer, required): ID della carta

**Body:**
```json
{
  "condition": "Near Mint"
}
```

**Risposta (200 OK):**
```json
{
  "message": "Card condition updated successfully",
  "data": {
    "id": 1,
    "condition": "Near Mint",
    "estimated_value": 150.00,
    "formatted_value": "$150.00"
  }
}
```

**Validazioni:**
- `condition`: obbligatorio, stringa, max 255 caratteri

**Errori:**
- `403 Forbidden`: L'utente non possiede questa carta
- `422 Unprocessable Entity`: Validazione fallita

**Note:**
- Aggiorna automaticamente l'`estimated_value` basato sulla nuova condizione e sui dati di mercato

---

#### 4.5 Associa set a carta
Associa un set (espansione) a una carta nella collezione.

**Endpoint:** `POST /api/collection/cards/{card}/set`

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

**Path Parameters:**
- `card` (integer, required): ID della carta

**Body:**
```json
{
  "card_set_id": 1,
  "set_number": "4/102"
}
```

**Validazioni:**
- `card_set_id`: obbligatorio, deve esistere nella tabella card_sets
- `set_number`: opzionale, stringa, max 255 caratteri

**Risposta (200 OK):**
```json
{
  "message": "Card set updated successfully",
  "data": {
    "id": 1,
    "card_set_id": 1,
    "set_number": "4/102",
    "set": {
      "id": 1,
      "name": "Base Set",
      "abbreviation": "BS"
    }
  }
}
```

**Errori:**
- `403 Forbidden`: L'utente non possiede questa carta
- `422 Unprocessable Entity`: card_set_id non valido o non esistente

---

#### 4.6 Rimuovi set da carta
Rimuove l'associazione set da una carta (imposta card_set_id a null).

**Endpoint:** `DELETE /api/collection/cards/{card}/set`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Path Parameters:**
- `card` (integer, required): ID della carta

**Risposta (200 OK):**
```json
{
  "message": "Card set removed successfully",
  "data": {
    "id": 1,
    "card_set_id": null
  }
}
```

**Errori:**
- `403 Forbidden`: L'utente non possiede questa carta

---

### 5. Analisi e Riconoscimento Carte (AI)

Questi endpoint utilizzano **Gemini AI** per analizzare automaticamente le immagini delle carte e riconoscere i dati.

#### 5.1 Analizza carta da immagine
Carica un'immagine di una carta e utilizza Gemini AI per riconoscere automaticamente i dati.

**Endpoint:** `POST /api/card/analyze`

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: multipart/form-data
```

**Body (form-data):**
- `image` (file, required): Immagine della carta (max 30MB, formati: jpeg, png, jpg, webp)

**Risposta (200 OK) - Carta valida riconosciuta:**
```json
{
  "success": true,
  "message": "Analisi completata con successo.",
  "data": {
    "card_id": 42,
    "image_url": "http://domain.com/api/image/card/42",
    "analysis": {
      "is_valid_card": true,
      "card_name": "Pikachu",
      "hp": "60",
      "type": "Elettro",
      "evolution_stage": "Base",
      "rarity": "Comune",
      "set_number": "25/102",
      "attacks": [
        {
          "name": "Thunder Shock",
          "cost": ["Elettro"],
          "damage": "10"
        }
      ],
      "weakness": "Lotta",
      "resistance": null,
      "retreat_cost": "1",
      "illustrator": "Atsuko Nishida",
      "flavor_text": "When several of these Pokémon gather...",
      "game": "Pokemon"
    }
  }
}
```

**Risposta (422 Unprocessable Entity) - Immagine non valida:**
```json
{
  "success": false,
  "message": "L'immagine non sembra essere una carta da gioco valida",
  "data": {
    "card_id": 42,
    "is_valid_card": false
  }
}
```

**Validazioni:**
- `image`: obbligatorio, deve essere un'immagine valida (jpeg, png, jpg, webp), max 30MB

**Errori:**
- `422 Unprocessable Entity`: File non valido o immagine non riconosciuta come carta
- `500 Internal Server Error`: Errore durante l'elaborazione o chiamata a Gemini AI

**Note:**
- La carta viene salvata con status `PENDING` durante l'analisi
- Se l'analisi ha successo, lo status diventa `REVIEW`
- Se l'analisi fallisce, lo status diventa `FAILED`
- L'immagine viene ridimensionata automaticamente se troppo grande
- I dati analizzati devono essere verificati e confermati dall'utente (vedi endpoint 5.2)

---

#### 5.2 Conferma e salva carta analizzata
Conferma i dati riconosciuti dall'AI (eventualmente modificati dall'utente) e salva definitivamente la carta.

**Endpoint:** `POST /api/card/confirm`

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

**Body:**
```json
{
  "card_id": 42,
  "card_name": "Pikachu",
  "hp": "60",
  "type": "Elettro",
  "evolution_stage": "Base",
  "attacks": [
    {
      "name": "Thunder Shock",
      "cost": ["Elettro"],
      "damage": "10"
    }
  ],
  "weakness": "Lotta",
  "resistance": null,
  "retreat_cost": "1",
  "rarity": "Comune",
  "set_number": "25/102",
  "illustrator": "Atsuko Nishida",
  "flavor_text": "When several of these Pokémon gather...",
  "card_set_id": 1,
  "game": "Pokemon"
}
```

**Validazioni:**
- `card_id`: obbligatorio, deve esistere
- `game`: obbligatorio, stringa
- `type`: opzionale, deve essere uno dei valori validi (vedi sezione 2.1)
- `rarity`: opzionale, deve essere uno dei valori validi (vedi sezione 2.1)
- `card_set_id`: opzionale, deve esistere nella tabella card_sets
- `attacks_json`: opzionale, stringa JSON (alternativa ad `attacks` array)

**Risposta (200 OK):**
```json
{
  "success": true,
  "message": "Carta salvata correttamente!",
  "data": {
    "card_id": 42,
    "drive_file_id": "1a2b3c4d5e6f7g8h9i"
  }
}
```

**Errori:**
- `422 Unprocessable Entity`: Validazione fallita o card_id non trovato
- `500 Internal Server Error`: Errore durante il salvataggio o upload su Google Drive

**Note:**
- Lo status della carta diventa `COMPLETED`
- L'immagine viene caricata su Google Drive
- Il file locale viene eliminato dopo l'upload su Drive
- Se il gioco non esiste, viene creato automaticamente per l'utente
- L'utente può modificare i dati riconosciuti dall'AI prima della conferma

---

#### 5.3 Elimina carta temporanea
Elimina una carta in stato PENDING o REVIEW se l'utente decide di non confermarla.

**Endpoint:** `DELETE /api/card/delete`

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

**Body:**
```json
{
  "card_id": 42
}
```

**Validazioni:**
- `card_id`: obbligatorio, deve esistere

**Risposta (200 OK):**
```json
{
  "success": true,
  "message": "Carta eliminata correttamente."
}
```

**Errori:**
- `403 Forbidden`: L'utente non possiede questa carta
- `422 Unprocessable Entity`: card_id non valido
- `500 Internal Server Error`: Errore durante eliminazione

**Note:**
- Elimina il file immagine locale (storage/public)
- Elimina il record dal database
- Utile per scartare analisi non corrette o immagini caricate per errore

---

### 6. Sistema di Matching

Il sistema di matching associa le carte della collezione con i dati di mercato (market_cards) per ottenere prezzi e valutazioni.

#### 6.1 Ottieni suggerimenti di match
Restituisce suggerimenti di carte di mercato corrispondenti per una carta specifica.

**Endpoint:** `GET /api/matching/cards/{card}/suggestions`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Path Parameters:**
- `card` (integer, required): ID della carta

**Risposta (200 OK):**
```json
{
  "card": {
    "id": 1,
    "name": "Charizard",
    "number": "4",
    "set": "Base Set",
    "image": "http://domain.com/api/image/card/1"
  },
  "suggestions": [
    {
      "id": 662125,
      "name": "Charizard",
      "number": "004/102",
      "set": "BS",
      "rarity": "Rara Olografica/Foil",
      "price": {
        "market": 150.00,
        "low": 120.00,
        "condition": "Near Mint"
      }
    },
    {
      "id": 662126,
      "name": "Charizard",
      "number": "004/102",
      "set": "BS",
      "rarity": "Rara Olografica/Foil",
      "price": {
        "market": 90.00,
        "low": 75.00,
        "condition": "Lightly Played"
      }
    }
  ]
}
```

**Errori:**
- `403 Forbidden`: L'utente non possiede questa carta
- `404 Not Found`: Carta non trovata

**Note:**
- Restituisce massimo 10 suggerimenti
- I suggerimenti sono ordinati per rilevanza (algoritmo di matching)
- Include prezzi di mercato più recenti per ogni suggerimento

---

#### 6.2 Effettua match manuale
Associa manualmente una carta della collezione a una carta di mercato specifica.

**Endpoint:** `POST /api/matching/cards/{card}/match`

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

**Path Parameters:**
- `card` (integer, required): ID della carta

**Body:**
```json
{
  "market_card_id": 662125
}
```

**Validazioni:**
- `market_card_id`: obbligatorio, deve esistere nella tabella market_cards

**Risposta (200 OK):**
```json
{
  "message": "Card matched successfully",
  "matched_card": {
    "id": 1,
    "market_card_id": 662125,
    "status": "matched"
  }
}
```

**Errori:**
- `403 Forbidden`: L'utente non possiede questa carta
- `422 Unprocessable Entity`: market_card_id non valido
- `500 Internal Server Error`: Errore durante il matching

**Note:**
- Imposta `market_card_id` sulla carta
- Abilita la visualizzazione dei prezzi di mercato
- Permette il calcolo di `estimated_value` e `profit_loss`

---

#### 6.3 Auto-match multiplo
Esegue il matching automatico di tutte le carte non matchate (o di carte specifiche).

**Endpoint:** `POST /api/matching/auto-match`

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

**Body (opzionale):**
```json
{
  "card_ids": [1, 5, 10, 15]
}
```

**Validazioni:**
- `card_ids`: opzionale, array di integer

**Risposta (200 OK):**
```json
{
  "message": "Auto-match completed",
  "stats": {
    "total_processed": 42,
    "matched": 38,
    "failed": 4,
    "already_matched": 0
  }
}
```

**Comportamento:**
- **Senza `card_ids`**: Matcha tutte le carte dell'utente con `market_card_id = null`
- **Con `card_ids`**: Matcha solo le carte specificate (se non matchate)

**Errori:**
- `500 Internal Server Error`: Errore durante il processo di matching batch

**Note:**
- Utilizza l'algoritmo di matching automatico del `CardMatchingService`
- Processa le carte in batch per performance
- Restituisce statistiche dettagliate del processo
- Le carte già matchate vengono saltate

---

### 8. Immagini

#### 8.1 Ottieni immagine carta
Restituisce l'immagine di una carta specifica.

**Endpoint:** `GET /api/image/card/{card}`

**Headers:**
```
Authorization: Bearer {your-token}
```

**Path Parameters:**
- `card` (integer, required): ID della carta

**Risposta (200 OK):**
- Content-Type: `image/jpeg` o `image/png` o `image/webp`
- Body: Binary image data

**Errori:**
- `403 Forbidden`: L'utente non possiede questa carta o manca autenticazione
- `404 Not Found`: Carta non trovata o immagine non disponibile

**Note:**
- **Richiede autenticazione** (Bearer token)
- Restituisce l'immagine binaria direttamente
- Utilizzato negli altri endpoint come `image_url`
- L'immagine può provenire da Google Drive o storage locale

**Esempio utilizzo (HTML):**
```html
<img src="http://domain.com/api/image/card/1" 
     headers='{"Authorization": "Bearer YOUR_TOKEN"}' />
```

**Esempio utilizzo (JavaScript Fetch):**
```javascript
const response = await fetch('http://domain.com/api/image/card/1', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
const blob = await response.blob();
const imageUrl = URL.createObjectURL(blob);
```

---

## Gestione Errori

### Errori comuni

#### 401 Unauthorized
Token mancante o non valido.
```json
{
  "message": "Unauthenticated."
}
```

#### 422 Unprocessable Entity
Errori di validazione.
```json
{
  "message": "The email field is required. (and 1 more error)",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field must be at least 8 characters."]
  }
}
```

#### 404 Not Found
Risorsa non trovata.
```json
{
  "message": "No query results for model..."
}
```

#### 500 Internal Server Error
Errore del server.
```json
{
  "message": "Server Error"
}
```

---

## Note

1. **Paginazione**: Tutti gli endpoint che restituiscono liste supportano la paginazione. Il numero massimo di elementi per pagina è limitato a 100.

2. **Token di autenticazione**: 
   - Il token viene restituito dopo la registrazione o il login
   - Deve essere incluso nell'header `Authorization` come `Bearer {token}`
   - Il token rimane valido finché non viene revocato tramite logout
   - Al login, tutti i token precedenti vengono revocati (rimuovi questa funzionalità se vuoi permettere login multipli)

3. **Date**: Tutte le date sono restituite in formato ISO 8601 (es. "2024-01-15T10:30:00Z")

4. **Immagini**: Gli URL delle immagini richiedono autenticazione. Per visualizzarle, includi il token nell'header della richiesta.

5. **Filtri e Ricerca**: Combina più parametri per ottenere risultati più specifici.

---

## Esempi di utilizzo

### JavaScript (Fetch API)

```javascript
// Login
const loginResponse = await fetch('http://domain.com/api/auth/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'password123'
  })
});

const { token } = await loginResponse.json();

// Get cards
const cardsResponse = await fetch('http://domain.com/api/collection/cards?game=Pokemon&per_page=20', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});

const cards = await cardsResponse.json();
console.log(cards.data);
```

### cURL

```bash
# Login
curl -X POST http://domain.com/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Get cards (replace YOUR_TOKEN with actual token)
curl -X GET "http://domain.com/api/collection/cards?game=Pokemon" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### Axios (JavaScript)

```javascript
import axios from 'axios';

// Set base URL
const api = axios.create({
  baseURL: 'http://domain.com/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
});

// Login
async function login(email, password) {
  const response = await api.post('/auth/login', { email, password });
  const { token } = response.data;
  
  // Set token for future requests
  api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
  
  return response.data;
}

// Get cards
async function getCards(params = {}) {
  const response = await api.get('/collection/cards', { params });
  return response.data;
}

// Usage
await login('user@example.com', 'password123');
const cards = await getCards({ game: 'Pokemon', per_page: 20 });
console.log(cards.data);
```

---

## 7. Import Market Data

### POST /api/market-data/import

Importa dati di mercato da JSON.

**Headers:**
```
Authorization: Bearer {your-token}
Content-Type: application/json
```

**Body della Richiesta:**
```json
{
  "count": 577,
  "total": 577,
  "result": [
    {
      "productID": 662125,
      "productConditionID": 0,
      "condition": "Lightly Played",
      "game": "Pokemon",
      "isSupplemental": false,
      "lowPrice": 0.01,
      "marketPrice": 0.09,
      "number": "063/094",
      "printing": "Normal",
      "productName": "Absol",
      "rarity": "Common",
      "sales": 0,
      "set": "ME02: Phantasmal Flames",
      "setAbbrv": "PFL",
      "type": "Cards"
    }
  ]
}
```

**Risposta (200 OK):**
```json
{
  "success": true,
  "message": "Market data imported successfully",
  "stats": {
    "total_processed": 544,
    "cards_created": 500,
    "cards_updated": 44,
    "prices_created": 1200
  }
}
```

**Errori Possibili:**
- `401 Unauthorized`: Token mancante o non valido
- `422 Unprocessable Entity`: Dati non validi o struttura JSON errata
- `500 Internal Server Error`: Errore durante l'importazione

**Note:**
- Il campo `result` deve essere un array non vuoto
- Ogni oggetto in `result` rappresenta una carta di mercato con i suoi prezzi
- L'import è idempotente: carte esistenti vengono aggiornate, nuove vengono create
- I campi `count` e `total` sono opzionali e vengono ignorati

