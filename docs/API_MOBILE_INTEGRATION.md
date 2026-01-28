# Documentazione Integrazione API Mobile (Flutter)

Questa guida descrive come integrare la funzionalità di scansione e analisi delle carte Pokémon nell'applicazione mobile Flutter.

## Prerequisiti

L'applicazione comunica con il backend tramite API REST. Tutte le chiamate protette richiedono un token di autenticazione (Sanctum).

Base URL: `http://<IL_TUO_SERVER_IP>/api` (in locale potrebbe essere `http://10.0.2.2:8000/api` per emulatore Android)

## Autenticazione

Prima di caricare un'immagine, l'utente deve essere autenticato.

### Login

**Endpoint:** `POST /auth/login`

**Body:**
```json
{
    "email": "user@example.com",
    "password": "password"
}
```

**Risposta (Successo):**
```json
{
    "status": true,
    "message": "User Logged In Successfully",
    "token": "1|laravel_sanctum_token_string..."
}
```

Salva il `token` restituito. Dovrà essere inviato nell'header `Authorization` di tutte le richieste successive:
`Authorization: Bearer <token>`

---

## 1. Analisi Carta con AI

Questo endpoint permette di caricare un'immagine di una carta, salvarla sul server e ottenere immediatamente l'analisi dettagliata generata da Google Gemini.

**Endpoint:** `POST /card/analyze`

**Headers:**
- `Authorization: Bearer <tuo_token>`
- `Content-Type: multipart/form-data`
- `Accept: application/json`

**Body (Multipart):**
- `image`: Il file dell'immagine (jpg, png, webp). Max 30MB.

### Esempio di Risposta (Successo)

```json
{
    "success": true,
    "message": "Analisi completata con successo.",
    "data": {
        "card_id": 15,
        "image_url": "http://server/api/image/card/15",
        "analysis": {
            "is_valid_card": true,
            "game": "Pokemon",
            "card_name": "Pikachu",
            "hp": "60 HP",
            "type": "Elettro",
            "evolution_stage": "Base",
            "attacks": [
                 { "name": "Tuonoshock", "damage": "10", "cost": "L", "effect": "Lancia una moneta..." }
            ],
            "weakness": "Terra",
            "resistance": "",
            "retreat_cost": "1",
            "rarity": "Comune",
            "set_number": "001/151",
            "illustrator": "Mitsuhiro Arita",
            "flavor_text": "...",
            "analysis_notes": "Identificata carta base set"
        }
    }
}
```

> [!IMPORTANT]
> **Nota sull'URL dell'immagine:** L'URL restituito in `image_url` punta a una rotta protetta (`/api/image/card/{id}`).
> Per scaricare o visualizzare l'immagine, l'app **deve** includere l'header `Authorization: Bearer <token>` nella richiesta GET dell'immagine.
> Se usi Flutter, considera di usare librerie che supportano headers (es. `CachedNetworkImage` con `httpHeaders`).

### Esempio di Risposta (Errore - Non è una carta)

```json
{
    "success": false,
    "message": "L'immagine non sembra essere una carta da gioco collezionabile",
    "data": {
        "card_id": 16,
        "is_valid_card": false
    }
}
```

---

## 2. Conferma e Salvataggio Carta

Dopo che l'utente ha validato i dati ricevuti dall'analisi (e eventualmente modificato dei campi), l'app deve chiamare questo endpoint per confermare il salvataggio.
Questo passaggio aggiorna i dati nel database, carica l'immagine su Google Drive e segna la carta come "Completata".

**Endpoint:** `POST /card/confirm`

**Headers:**
- `Authorization: Bearer <tuo_token>`
- `Content-Type: application/json`
- `Accept: application/json`

**Body (JSON):**
```json
{
    "card_id": 15,
    "game": "Pokemon",
    "card_name": "Pikachu",
    "hp": "60 HP",
    "type": "Elettro",
    "evolution_stage": "Base",
    "attacks_json": "[{\"name\":\"Tuonoshock\",\"damage\":\"10\"}]", 
    "weakness": "Terra",
    "resistance": "",
    "retreat_cost": "1",
    "rarity": "Comune",
    "set_number": "001/151",
    "illustrator": "Mitsuhiro Arita",
    "flavor_text": "...",
    "card_set_id": null 
}
```
*Nota: Puoi inviare `attacks` come array JSON oppure `attacks_json` come stringha JSON.*

### Esempio di Risposta (Successo)

```json
{
    "success": true,
    "message": "Carta salvata correttamente!",
    "data": {
        "card_id": 15,
        "drive_file_id": "1A-xxxx..."
    }
}
```

---

## Esempio di Implementazione Flutter

Esempio utilizzando il pacchetto `http`.

```dart
// 1. Analisi
Future<Map<String, dynamic>> analyzeCard(File imageFile, String authToken) async {
  // ... vedere esempio precedente ...
}

// 2. Conferma
Future<void> confirmCard(int cardId, Map<String, dynamic> cardData, String authToken) async {
  var url = Uri.parse('http://il-tuo-indrizzo-api/api/card/confirm');
  
  var body = {
    'card_id': cardId,
    ...cardData // Spread operatore per inserire tutti i campi
  };

  var response = await http.post(
    url,
    headers: {
      'Authorization': 'Bearer $authToken',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
    body: jsonEncode(body),
  );

  if (response.statusCode == 200) {
    print('Carta salvata con successo!');
  } else {
    print('Errore: ${response.body}');
  }
}
```
