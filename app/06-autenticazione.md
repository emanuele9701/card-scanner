# 🔐 Autenticazione — Laravel Sanctum

## Panoramica

L'applicazione usa **Laravel Sanctum** per l'autenticazione API tramite token Bearer. Ogni utente può avere più token attivi simultaneamente.

## Setup

### Pacchetto
```bash
composer require laravel/sanctum
```

### Model User
Il model `User` usa il trait `HasApiTokens`:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;
}
```

### Routing
Le API routes sono registrate in `bootstrap/app.php`:

```php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    // ...
)
```

---

## Endpoints

### POST `/api/auth/register`

Registra un nuovo utente e restituisce un token API.

**Body:**
```json
{
  "name": "Mario Rossi",
  "email": "mario@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response `201`:**
```json
{
  "success": true,
  "message": "Registrazione avvenuta con successo.",
  "data": {
    "user": {
      "id": 1,
      "name": "Mario Rossi",
      "email": "mario@example.com"
    },
    "token": "1|abc123..."
  }
}
```

---

### POST `/api/auth/login`

Autentica un utente esistente.

**Body:**
```json
{
  "email": "mario@example.com",
  "password": "password123"
}
```

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "user": { "id": 1, "name": "Mario Rossi", "email": "mario@example.com" },
    "token": "2|def456..."
  }
}
```

**Errore `422`:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["Credenziali non valide."]
  }
}
```

---

### POST `/api/auth/logout` 🔒

Revoca il token corrente.

**Header:** `Authorization: Bearer {token}`

**Response `200`:**
```json
{
  "success": true,
  "message": "Logout effettuato."
}
```

---

### GET `/api/auth/me` 🔒

Restituisce i dati dell'utente autenticato.

**Header:** `Authorization: Bearer {token}`

**Response `200`:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Mario Rossi",
    "email": "mario@example.com",
    "email_verified_at": null,
    "created_at": "2026-04-27T22:00:00.000000Z",
    "updated_at": "2026-04-27T22:00:00.000000Z"
  }
}
```

---

## Uso del Token

Dopo register o login, includere il token in tutte le richieste protette:

```
Authorization: Bearer 1|abc123...
```

### Esempio cURL

```bash
# Login
TOKEN=$(curl -s -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"mario@example.com","password":"password123"}' \
  | jq -r '.data.token')

# Usa il token
curl http://localhost/api/collection \
  -H "Authorization: Bearer $TOKEN"
```

### Esempio JavaScript (fetch)

```javascript
// Login
const response = await fetch('/api/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email: 'mario@example.com', password: 'password123' })
});
const { data } = await response.json();
const token = data.token;

// Richiesta autenticata
const collection = await fetch('/api/collection', {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

---

## Sicurezza

- I token sono hashati nel database (tabella `personal_access_tokens`)
- Il token in chiaro è visibile **solo** al momento della creazione (register/login)
- Il logout revoca solo il token corrente, altri token dello stesso utente restano attivi
- Le password sono hashate automaticamente via il cast `hashed` nel model User
