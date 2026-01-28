# Guida Installazione API

## Passi per completare l'installazione delle API

### 1. Installare Laravel Sanctum (se non già fatto)

```bash
composer require laravel/sanctum
```

### 2. Pubblicare la configurazione e le migrazioni di Sanctum

```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 3. Eseguire le migrazioni

```bash
php artisan migrate
```

Questo creerà la tabella `personal_access_tokens` necessaria per i token di autenticazione.

### 4. Aggiornare il modello User

Il modello `User` deve utilizzare il trait `HasApiTokens`. Verifica che il file `app/Models/User.php` contenga:

```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    
    // resto del codice...
}
```

### 5. Configurare il middleware Sanctum

Apri il file `bootstrap/app.php` e assicurati che il middleware API sia configurato:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ]);
})
```

Se usi una versione precedente di Laravel, verifica che nel file `app/Http/Kernel.php` sia presente:

```php
'api' => [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    'throttle:api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

### 6. Configurare CORS (opzionale, se necessario per SPA)

Se utilizzi le API da un frontend separato (SPA), modifica il file `config/cors.php`:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],

'allowed_origins' => [
    'http://localhost:3000', // Il tuo frontend
    'http://localhost:5173', // Vite dev server
],

'supports_credentials' => true,
```

### 7. Testare le API

Puoi testare le API usando cURL, Postman, o qualsiasi client HTTP.

#### Esempio con cURL:

**Registrazione:**
```bash
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"test@example.com\",\"password\":\"password123\",\"password_confirmation\":\"password123\"}"
```

**Login:**
```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"email\":\"test@example.com\",\"password\":\"password123\"}"
```

Salva il token dalla risposta e usalo per le richieste successive:

**Ottenere le carte:**
```bash
curl -X GET http://localhost/api/collection/cards \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Accept: application/json"
```

### 8. Configurazione .env

Assicurati che nel file `.env` siano presenti le seguenti configurazioni:

```env
# Session driver (importante per Sanctum)
SESSION_DRIVER=cookie

# Sanctum stateful domains (solo se usi SPA)
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,localhost:5173

# App URL
APP_URL=http://localhost
```

### 9. Verificare le route API

Puoi vedere tutte le route API disponibili con:

```bash
php artisan route:list --path=api
```

## Troubleshooting

### Token non funziona (401 Unauthorized)

1. Verifica che l'header `Authorization: Bearer {token}` sia corretto
2. Assicurati che l'header `Accept: application/json` sia presente
3. Verifica che le migrazioni siano state eseguite correttamente
4. Controlla che il trait `HasApiTokens` sia presente nel modello User

### CORS issues

Se ricevi errori CORS:

1. Configura correttamente `config/cors.php`
2. Aggiungi i domini consentiti in `SANCTUM_STATEFUL_DOMAINS` nel file `.env`
3. Assicurati che `supports_credentials` sia `true` se usi cookies

### Errori di validazione

Gli errori di validazione restituiscono sempre un codice 422 con dettagli degli errori:

```json
{
  "message": "The email field is required. (and 1 more error)",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field must be at least 8 characters."]
  }
}
```

## Sicurezza

### Rate Limiting

Laravel include già il rate limiting per le API. Puoi modificarlo in `app/Providers/RouteServiceProvider.php` o nel file di configurazione delle route.

### HTTPS in produzione

**IMPORTANTE**: In produzione, assicurati sempre di usare HTTPS per proteggere i token di autenticazione.

Nel file `.env` di produzione:

```env
APP_URL=https://your-domain.com
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=your-domain.com
```

### Token Expiration

Di default, i token di Sanctum non hanno scadenza. Se vuoi aggiungere una scadenza, puoi configurarlo in `config/sanctum.php`:

```php
'expiration' => 60, // Token scadono dopo 60 minuti
```

## Prossimi passi

1. ✅ Installazione completata
2. ✅ Documentazione API creata
3. 📝 Testa gli endpoint con Postman o cURL
4. 📝 Integra le API nel tuo frontend
5. 📝 Aggiungi eventuali endpoint personalizzati di cui hai bisogno

Per la documentazione completa delle API, consulta il file `docs/API.md`.
