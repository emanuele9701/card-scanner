# Integrazione Google Drive - Implementazione Completata

## 📋 Riepilogo

Ho creato un sistema completo per gestire il caricamento delle immagini delle carte su Google Drive invece che sul server locale. Il sistema include:

1. ✅ **Database Structure** - Tabella per salvare i metadati dei file di Google Drive
2. ✅ **Model** - GoogleDriveFile model con relazioni e helper methods
3. ✅ **Service** - GoogleDriveService con tutte le funzionalità di Google Drive
4. ✅ **Command** - DriveTest command per testare tutte le operazioni
5. ⏳ **Integration** - Da integrare nel controller di upload delle carte

---

## 🗄️ Database - Tabella `google_drive_files`

### Campi Principali

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `id` | bigint | ID primario |
| `pokemon_card_id` | foreignId (nullable) | Relazione con la carta |
| `user_id` | foreignId | Relazione con l'utente |
| `drive_id` | string (unique) | ID del file su Google Drive |
| `name` | string | Nome del file |
| `mime_type` | string | Tipo MIME (image/jpeg, etc.) |
| `size` | bigInteger | Dimensione in bytes |

### Permessi e Condivisione

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `is_public` | boolean | Se il file è pubblico |
| `is_shared` | boolean | Se il file è condiviso |

### Links

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `web_view_link` | text | Link per visualizzare il file |
| `web_content_link` | text | Link per scaricare il file |
| `thumbnail_link` | text | Link thumbnail |

### Metadati

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `parent_folder_id` | string | ID della cartella padre su Drive |
| `drive_created_at` | timestamp | Data creazione su Drive |
| `drive_modified_at` | timestamp | Data modifica su Drive |
| `owners` | json | Array di proprietari |
| `metadata` | json | Altri metadati |
| `status` | enum | `uploading`, `uploaded`, `failed`, `deleted` |
| `error_message` | text | Messaggio di errore se fallito |

### Soft Deletes

La tabella supporta soft deletes, quindi i record eliminati vengono mantenuti con `deleted_at`.

---

## 🔧 Model - GoogleDriveFile

### Relazioni

```php
// Relazione con PokemonCard
$driveFile->pokemonCard;

// Relazione con User
$driveFile->user;
```

### Helper Methods

```php
// Formatta dimensione file
$driveFile->formatted_size; // "2.5 MB"

// Check stato
$driveFile->isUploaded();  // true se caricato
$driveFile->hasFailed();   // true se fallito

// Aggiorna stato
$driveFile->markAsUploaded();
$driveFile->markAsFailed('Error message');
```

---

## 🚀 Service - GoogleDriveService

Il service gestisce tutte le interazioni con Google Drive.

### Metodo Principale: `uploadFile()`

```php
use App\Services\GoogleDriveService;

$driveService = new GoogleDriveService();

$driveFile = $driveService->uploadFile(
    localPath: 'pokemon_cards/image.jpg',  // Path nello storage
    fileName: 'Charizard_Card.jpg',        // Nome su Drive
    userId: auth()->id(),                  // ID utente
    pokemonCardId: $card->id,              // ID carta (opzionale)
    makePublic: true                       // Rendi pubblico
);
```

### Altri Metodi Disponibili

```php
// Ottieni informazioni file
$fileInfo = $driveService->getFileInfo($driveId);

// Elimina file
$driveService->deleteFile($driveId);

// Lista file in cartella
$files = $driveService->listFilesInFolder($folderId);

// Crea cartella
$folder = $driveService->createFolder('My Folder', $parentFolderId);

// Rendi file pubblico
$driveService->makeFilePublic($driveId);

// Ottieni URL pubblici
$downloadUrl = $driveService->getPublicDownloadUrl($driveId);
$viewUrl = $driveService->getPublicViewUrl($driveId);
```

### Gestione Token Automatica

Il service gestisce automaticamente:
- ✅ Verifica scadenza token ad ogni operazione
- ✅ Refresh automatico del token quando scade
- ✅ Aggiornamento del file `.env` con il nuovo token
- ✅ Logging di tutte le operazioni

---

## 💻 Command - DriveTest

Ho già creato e testato il command per te! Funziona perfettamente.

### Esempi d'Uso

```bash
# Crea una cartella
php artisan app:drive-test create-folder --name="Pokemon Cards"

# Upload file
php artisan app:drive-test upload --file=pokemon_cards/card.jpg --name="Pikachu"

# Info file
php artisan app:drive-test get-info --id=FILE_ID

# Lista file
php artisan app:drive-test list-files --folder=FOLDER_ID

# Elimina file/cartella
php artisan app:drive-test delete --id=FILE_ID
```

### Test Eseguito

Ho già testato il command e funziona perfettamente:
- ✅ Token scaduto → rinnovato automaticamente
- ✅ Cartella creata su Google Drive
- ✅ Info recuperate correttamente

---

## 🔄 Prossimi Passi - Integrazione nell'Upload

Per integrare Google Drive nel processo di upload delle carte, dovrai:

### 1. Modificare `CardUploadController::uploadRawImage()`

Attualmente il metodo salva l'immagine nello storage locale:

```php
$path = $file->store('pokemon_cards', 'public');
```

### 2. Dopo il salvataggio della carta, caricare su Google Drive

```php
// Dopo la creazione del record PokemonCard
$card = PokemonCard::create([...]);

// Carica su Google Drive
try {
    $driveService = app(GoogleDriveService::class);
    
    $driveFile = $driveService->uploadFile(
        localPath: $path,
        fileName: $originalFilename,
        userId: auth()->id(),
        pokemonCardId: $card->id,
        makePublic: true  // Rende l'immagine pubblica
    );
    
    // Salva il link pubblico nella carta
    $card->update([
        'google_drive_file_id' => $driveFile->id,
        'google_drive_url' => $driveFile->web_view_link,
    ]);
    
    Log::info('File uploaded to Google Drive', [
        'card_id' => $card->id,
        'drive_id' => $driveFile->drive_id,
    ]);
    
} catch (Exception $e) {
    Log::error('Failed to upload to Google Drive', [
        'card_id' => $card->id,
        'error' => $e->getMessage(),
    ]);
    // Il file rimarrà sul server locale come fallback
}
```

### 3. (Opzionale) Aggiungere campi a PokemonCard

Potresti voler aggiungere questi campi alla tabella `pokemon_cards`:

```php
// Migration
$table->foreignId('google_drive_file_id')->nullable()->constrained('google_drive_files');
$table->text('google_drive_url')->nullable();
```

### 4. Modificare `getImageUrl()` accessor

Se vuoi usare l'immagine da Google Drive invece che dal server:

```php
// In PokemonCard model
public function getImageUrl()
{
    // Se esiste su Google Drive, usa quello
    if ($this->google_drive_url) {
        return $this->driveFile->web_view_link ?? $this->google_drive_url;
    }
    
    // Altrimenti fallback al server locale
    if ($this->storage_path) {
        return Storage::disk('public')->url($this->storage_path);
    }
    
    return null;
}
```

---

## ⚠️ Note Importanti

### Permessi File

Quando `makePublic: true`, il file viene reso pubblicamente accessibile con permessi `anyone` -> `reader`. Questo significa che:
- ✅ Chiunque con il link può vedere l'immagine
- ✅ Perfetto per visualizzare immagini nelle pagine web
- ❌ Nessuna autenticazione richiesta

### Struttura Cartelle

Il servizio usa la variabile `GOOGLE_DRIVE_FOLDER` dal `.env`:
- Se la cartella non esiste, viene creata automaticamente
- Tutti i file vengono caricati in questa cartella
- La cartella corrente è: `TCG_Cards_Image`

### Gestione Errori

Il service logga tutti gli errori e:
- Aggiorna lo stato del record a `failed`
- Salva il messaggio d'errore
- Lancia l'eccezione per permetterti di gestirla

### Performance

- ⚡ Upload asincrono consigliato per file grandi
- 📊 Aggiungi a una queue per non bloccare la risposta HTTP
- 🔄 Considera il caching dei link pubblici

---

## 📚 Documentazione Completa

Ho creato una guida completa in: `GOOGLE_DRIVE_USAGE.md`

Controlla quel file per:
- Esempi dettagliati di tutti i comandi
- Troubleshooting
- Best practices
- Note tecniche

---

## ✅ Checklist Implementazione

- [x] Creata migration `google_drive_files`
- [x] Creato model `GoogleDriveFile`
- [x] Creato service `GoogleDriveService`
- [x] Creato command `DriveTest`
- [x] Testato token refresh automatico
- [x] Testato creazione cartella
- [x] Documentazione completa
- [ ] **PROSSIMO**: Eseguire migration (`php artisan migrate`)
- [ ] **PROSSIMO**: Integrare nel `CardUploadController`
- [ ] **PROSSIMO**: Testare upload completo
- [ ] **OPZIONALE**: Aggiungere campi a `pokemon_cards`
- [ ] **OPZIONALE**: Implementare queue per upload asincroni

---

## 🎯 Pronto per l'Integrazione

Tutto il codice è pronto! Quando avvii il database MySQL con Laragon, esegui:

```bash
php artisan migrate
```

Poi modificherò il controller per integrare l'upload su Google Drive nel flusso esistente.

Vuoi che proceda con l'integrazione nel controller?
