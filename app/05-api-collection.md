# 🃏 API — Collezione Carte Utente

**Base URL:** `/api`  
**Autenticazione:** Bearer Token (Sanctum) — richiesto su tutti gli endpoint 🔒  
**Content-Type:** `application/json` (tranne upload foto: `multipart/form-data`)

---

## Endpoints

| Metodo | URL | Descrizione |
|---|---|---|
| GET | `/api/collection` | Lista collezione (paginata, con filtri) |
| GET | `/api/collection/stats` | Statistiche collezione |
| GET | `/api/collection/{id}` | Dettaglio singolo elemento |
| POST | `/api/collection` | Aggiungi carta alla collezione |
| PUT | `/api/collection/{id}` | Aggiorna elemento |
| DELETE | `/api/collection/{id}` | Rimuovi carta dalla collezione |
| DELETE | `/api/collection/{id}/photos/{mediaId}` | Elimina una foto |

---

## GET `/api/collection`

Lista paginata della collezione dell'utente autenticato.

### Query Parameters

| Parametro | Tipo | Default | Descrizione |
|---|---|---|---|
| `set_id` | integer | — | Filtra per set (ID auto-increment di `tcg_sets`) |
| `condition` | string | — | Filtra per condizione (`NM`, `LP`, `MP`, `HP`, `DMG`) |
| `language` | string | — | Filtra per lingua della carta (`it`, `en`) |
| `per_page` | integer | 25 | Elementi per pagina |
| `page` | integer | 1 | Pagina corrente |

### Response `200`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "card_id": 42,
      "quantity": 3,
      "variants": ["holo", "reverse"],
      "condition": "NM",
      "notes": "Carta in condizioni perfette",
      "card": {
        "id": 42,
        "card_id": "base1-1",
        "name": "Alakazam",
        "url_image": "https://assets.tcgdex.net/it/base/base1/001",
        "set": { "id": 1, "set_id": "base1", "name": "Set Base" }
      },
      "media": [
        {
          "id": 1,
          "file_name": "foto_fronte.jpg",
          "original_url": "http://localhost/storage/1/foto_fronte.jpg",
          "preview_url": "http://localhost/storage/1/conversions/foto_fronte-preview.jpg"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 25,
    "total": 72
  }
}
```

---

## GET `/api/collection/{id}`

Dettaglio singolo elemento con carta, abilities, prezzi e foto.

### Response `200`

```json
{
  "success": true,
  "data": {
    "id": 1,
    "quantity": 3,
    "variants": ["holo"],
    "condition": "NM",
    "notes": null,
    "card": {
      "card_id": "base1-1",
      "name": "Alakazam",
      "abilities": [...],
      "prices": [...],
      "set": { "set_id": "base1", "name": "Set Base" }
    },
    "media": [...]
  }
}
```

---

## POST `/api/collection`

Aggiunge una carta alla collezione dell'utente.

> **Content-Type:** `multipart/form-data` se si includono foto.

### Body

| Campo | Tipo | Obbligatorio | Note |
|---|---|---|---|
| `card_id` | integer | ✅ | ID auto-increment della carta in `tcg_cards` |
| `quantity` | integer | ✅ | Almeno 1 |
| `variants` | array | ❌ | Es: `["holo", "reverse"]` |
| `condition` | string | ✅ | `NM`, `LP`, `MP`, `HP`, `DMG` |
| `notes` | string | ❌ | Max 1000 caratteri |
| `photos[]` | file[] | ❌ | Max 5 file, JPEG/PNG/WebP, max 5MB ciascuno |

### Esempio cURL

```bash
curl -X POST http://localhost/api/collection \
  -H "Authorization: Bearer {token}" \
  -F "card_id=42" \
  -F "quantity=2" \
  -F "variants[]=holo" \
  -F "variants[]=reverse" \
  -F "condition=NM" \
  -F "notes=Carta perfetta, appena aperta" \
  -F "photos[]=@fronte.jpg" \
  -F "photos[]=@retro.jpg"
```

### Response `201`

```json
{
  "success": true,
  "message": "Carta aggiunta alla collezione.",
  "data": { ... }
}
```

---

## PUT `/api/collection/{id}`

Aggiorna un elemento della collezione. Tutti i campi sono opzionali (update parziale).

> **Nota:** per inviare foto con PUT, usare `POST` con campo `_method=PUT` per supportare `multipart/form-data`.

### Body

| Campo | Tipo | Note |
|---|---|---|
| `quantity` | integer | Min 1 |
| `variants` | array | Sovrascrive le varianti precedenti |
| `condition` | string | `NM`, `LP`, `MP`, `HP`, `DMG` |
| `notes` | string | Max 1000 caratteri |
| `photos[]` | file[] | Le nuove foto vengono **aggiunte**, non sostituite |

### Response `200`

```json
{
  "success": true,
  "message": "Collezione aggiornata.",
  "data": { ... }
}
```

---

## DELETE `/api/collection/{id}`

Rimuove una carta dalla collezione e tutte le foto associate.

### Response `200`

```json
{ "success": true, "message": "Carta rimossa dalla collezione." }
```

---

## DELETE `/api/collection/{id}/photos/{mediaId}`

Elimina una singola foto da un elemento della collezione.

| Parametro | Tipo | Descrizione |
|---|---|---|
| `id` | integer | ID dell'elemento nella collezione |
| `mediaId` | integer | ID del media (da `media[].id` nella response) |

### Response `200`

```json
{ "success": true, "message": "Foto eliminata." }
```

---

## GET `/api/collection/stats`

Statistiche sulla collezione dell'utente autenticato.

### Response `200`

```json
{
  "success": true,
  "data": {
    "total_cards": 156,
    "unique_cards": 89,
    "by_condition": [
      { "condition": "NM", "count": 60, "total_quantity": 120 },
      { "condition": "LP", "count": 20, "total_quantity": 25 },
      { "condition": "MP", "count": 9, "total_quantity": 11 }
    ]
  }
}
```

---

## Condizioni delle Carte

| Codice | Significato | Descrizione |
|---|---|---|
| `NM` | Near Mint | Carta praticamente perfetta |
| `LP` | Lightly Played | Piccoli segni di usura |
| `MP` | Moderately Played | Segni visibili di usura |
| `HP` | Heavily Played | Usura significativa |
| `DMG` | Damaged | Carta danneggiata |

---

## Errori

Tutte le risposte di errore seguono questo formato:

```json
{
  "message": "Descrizione dell'errore",
  "errors": {
    "campo": ["Dettaglio errore di validazione"]
  }
}
```

| HTTP Code | Significato |
|---|---|
| `401` | Non autenticato (token mancante o invalido) |
| `403` | Non autorizzato |
| `404` | Risorsa non trovata |
| `422` | Errore di validazione |
| `500` | Errore server |

---

## Foto — Conversioni Automatiche

Ogni foto caricata genera automaticamente due versioni ottimizzate:

| Conversione | Dimensioni | Uso |
|---|---|---|
| `thumb` | 200×200px | Anteprime in lista |
| `preview` | 600×800px | Visualizzazione dettaglio |

**Limiti:**
- Formati accettati: JPEG, PNG, WebP
- Dimensione massima: 5MB per file
- Massimo 5 foto per elemento della collezione
