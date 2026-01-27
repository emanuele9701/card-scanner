# Database Token Management - Google Drive

## ✅ Implementazione Completata

Ho creato un sistema completo di gestione dei token OAuth 2.0 per Google Drive usando il database invece del file `.env`.

---

## 🗄️ Tabella `google_drive_tokens`

### Schema

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | bigint | ID primario |
| `service` | string | Nome servizio (default: 'google_drive') |
| `access_token` | text | Access token OAuth 2.0 |
| `refresh_token` | text | Refresh token OAuth 2.0 |
| `expires_at` | timestamp | **Scadenza del token** |
| `token_type` | string | Tipo token (default: 'Bearer') |
| `scopes` | json | Scopes autorizzati |
| `refresh_count` | integer | Numero di volte rinnovato |
| `last_refreshed_at` | timestamp | Ultima volta rinnovato |
| `created_at` | timestamp | Data creazione |
| `updated_at` | timestamp | Data aggiornamento |

### Indexes

- `UNIQUE(service)` - Un solo token per servizio
- `INDEX(expires_at)` - Per query veloci sulla scadenza

---

## 📱 Model `GoogleDriveToken`

### Metodi Principali

```php
// Ottenere il token per Google Drive
$token = GoogleDriveToken::getGoogleDriveToken();

// Verificare se è scaduto
if ($token->isExpired()) {
    // Token scaduto
}

if ($token->isExpiringSoon()) {
    // Scade tra meno di 5 minuti
}

// Aggiornare il token dopo refresh
$token->updateAccessToken($newAccessToken, $expiresIn);

// Creare o aggiornare token
GoogleDriveToken::createOrUpdateToken($tokenData);

// Ottenere in formato Google Client
$googleArray = $token->toGoogleClientArray();
```

---

## 🔧 GoogleDriveService - Cambiamenti

### Prima (File .env)

```php
// Caricava da .env
$accessToken = env('GOOGLE_DRIVE_ACCESS_TOKEN');
$this->client->setAccessToken($accessToken);

// Scriveva su .env
file_put_contents($envPath, $newEnvContent);
```

### Dopo (Database)

```php
// Carica dal database
$tokenRecord = GoogleDriveToken::getGoogleDriveToken();
$this->client->setAccessToken($tokenRecord->toGoogleClientArray());

// Verifica scadenza
if ($tokenRecord->isExpired() || $tokenRecord->isExpiringSoon()) {
    $this->refreshAccessToken();
}

// Salva nel database
$tokenRecord->updateAccessToken($newToken, $expiresIn);
```

---

## 🚀 Funzionamento

### 1. **Prima Esecuzione** (Setup Iniziale)

Se il database è vuoto, il service:
1. Cerca il token nel file `.env`
2. Lo importa nel database
3. Verifica se è scaduto
4. Se scaduto, lo rinnova immediatamente

```
.env → Database → Verifica → Rinnova (se necessario)
```

### 2. **Esecuzioni Successive**

Ad ogni richiesta:
1. Carica il token dal database
2. **Verifica la scadenza** controllando `expires_at`
3. Se scaduto o sta per scadere (< 5 min), lo rinnova automaticamente
4. Salva il nuovo token nel database con nuova `expires_at`

```
Database → Verifica expires_at → Rinnova se necessario → Aggiorna DB
```

### 3. **Refresh Token**

Quando il token scade:
```php
protected function refreshAccessToken()
{
    // 1. Ottieni refresh token dal database
    $tokenRecord = GoogleDriveToken::getGoogleDriveToken();
    $refreshToken = $tokenRecord->refresh_token;
    
    // 2. Chiedi nuovo access token a Google
    $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
    $newTokenData = $this->client->getAccessToken();
    
    // 3. Salva nel database con nuova scadenza
    $tokenRecord->updateAccessToken(
        $newTokenData['access_token'],
        $newTokenData['expires_in'] // es: 3600 secondi (1 ora)
    );
    
    // expires_at = now() + 3600 secondi
}
```

---

## 📊 Vantaggi del Database vs .env

| Aspetto | .env File | Database |
|---------|-----------|----------|
| **Scadenza** | ❌ Non tracciata | ✅ Campo `expires_at` |
| **Verifica** | ❌ Manuale | ✅ Automatica |
| **Statistiche** | ❌ Nessuna | ✅ Conta refresh |
| **Concorrenza** | ❌ Problemi | ✅ Transazioni |
| **Performance** | ⚠️ Slow I/O | ✅ Query veloce |
| **Logs** | ❌ Nessuno | ✅ `last_refreshed_at` |
| **Multi-servizio** | ❌ 1 servizio | ✅ Scalabile |

---

## 🔍 Verificare Scadenza Token

Il controllo della scadenza avviene in **due modi**:

### 1. **Controllo Preventivo** (Prima del refresh)

```php
if ($tokenRecord->isExpired()) {
    // Token già scaduto
    $this->refreshAccessToken();
}

if ($tokenRecord->isExpiringSoon()) {
    // Token scade tra meno di 5 minuti
    $this->refreshAccessToken();
}
```

### 2. **Metodi nel Model**

```php
// In GoogleDriveToken model

public function isExpired(): bool
{
    return $this->expires_at->isPast();
}

public function isExpiringSoon(): bool
{
    return $this->expires_at->diffInMinutes(now()) < 5;
}

public function isValid(): bool
{
    return !$this->isExpired();
}
```

---

## 📝 Esempio Pratico

### Scenario: Caricare un file

```php
// 1. Utente chiama GoogleDriveService
$driveService = new GoogleDriveService();

// 2. __construct() chiama initializeClient()
// initializeClient() chiede il token dal DB
$tokenRecord = GoogleDriveToken::getGoogleDriveToken();

// 3. Controlla expires_at
if ($tokenRecord->expires_at < now()) {
    // È scaduto! Rinnova
    refreshAccessToken();
    // Nuovo token salvato con expires_at = now() + 3600s
}

// 4. Usa il token (valido) per caricare il file
$driveService->uploadFile(...);
```

---

## 🎯 Migration Eseguita

```bash
✅ 2026_01_27_233948_create_google_drive_tokens_table.php
```

La tabella è stata creata con successo nel database.

---

## ⚙️ Setup Iniziale per Nuovi Utenti

Se non hai ancora token nel database:

1. Assicurati che `.env` contenga:
   ```env
   GOOGLE_DRIVE_ACCESS_TOKEN=ya29.a0...
   GOOGLE_DRIVE_REFRESH_TOKEN=1//04r3BTYebxIx...
   GOOGLE_DRIVE_CLIENT_ID=972430454005-dbb...
   GOOGLE_DRIVE_CLIENT_SECRET=GOCSPX-SEQi...
   ```

2. Alla prima esecuzione del service:
   - Importa automaticamente i token dal `.env`
   - Crea il record nel database
   - Verifica e rinnova se necessario

3. Dalle successive esecuzioni:
   - Usa solo il database
   - Ignora il `.env` (tranne client_id e client_secret)

---

## 🔐 Sicurezza

- ✅ Access token salvato come `TEXT` (supporta token lunghi)
- ✅ Refresh token salvato in modo sicuro
- ✅ Nessuna scrittura sul file system (`.env`)
- ✅ Transazioni database per aggiornamenti atomici
- ✅ Campo `service` permette multi-tenant in futuro

---

## 📈 Statistiche

Il sistema traccia:
- **`refresh_count`**: Quante volte il token è stato rinnovato
- **`last_refreshed_at`**: Ultima volta che è stato rinnovato
- **Logging completo**: Ogni refresh è loggato con timestamp

Puoi usarle per:
- Monitorare la salute del sistema
- Identificare problemi con i token
- Analizzare pattern di utilizzo

---

## ✅ Checklist Completamento

- [x] Tabella `google_drive_tokens` creata
- [x] Model `GoogleDriveToken` con helper methods
- [x] Service aggiornato per usare database
- [x] Controllo automatico scadenza
- [x] Refresh automatico quando necessario
- [x] Import da `.env` per setup iniziale
- [x] Migration eseguita
- [x] Statistiche e logging
- [x] Documentazione completa

**Il sistema è pronto all'uso!** 🎉
