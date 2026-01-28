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
- `rarity` (string, optional): Filtra per rarità
- `condition` (string, optional): Filtra per condizione
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
