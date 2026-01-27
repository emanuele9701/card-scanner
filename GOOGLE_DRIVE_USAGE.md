# Google Drive Integration - Guida all'uso

Questo documento fornisce istruzioni complete su come utilizzare il comando `DriveTest` per gestire file e cartelle su Google Drive.

## Prerequisiti

✅ La libreria `google/apiclient` è già installata
✅ Le credenziali sono già configurate nel file `.env`

## Configurazione

Il file `.env` contiene le seguenti variabili già configurate:

```env
GOOGLE_DRIVE_CLIENT_ID=xxx
GOOGLE_DRIVE_CLIENT_SECRET=xx
GOOGLE_DRIVE_REFRESH_TOKEN=xxx
GOOGLE_DRIVE_FOLDER=TCG_Cards_Image
```

## Gestione del Token di Accesso

Il comando gestisce automaticamente il token di accesso:

- **Verifica della scadenza**: Ad ogni esecuzione, il comando verifica se il token è scaduto
- **Rinnovo automatico**: Se scaduto, usa il `GOOGLE_DRIVE_REFRESH_TOKEN` per ottenere un nuovo token
- **Aggiornamento .env**: Il nuovo token viene salvato automaticamente nel file `.env`

Non devi preoccuparti della scadenza dei token! 🎉

## Comandi Disponibili

### 1. Upload di un File

Carica un file dallo storage privato della tua applicazione Laravel verso Google Drive.

```bash
php artisan app:drive-test upload --file=path/to/file.jpg
```

**Opzioni:**
- `--file` (obbligatorio): Percorso del file nello storage privato (storage/app/private/)
- `--name` (facoltativo): Nome personalizzato per il file su Google Drive

**Esempi:**

```bash
# Upload di un'immagine con nome automatico
php artisan app:drive-test upload --file=images/card.jpg

# Upload con nome personalizzato
php artisan app:drive-test upload --file=images/card.jpg --name="Charizard Card"

# Il file verrà caricato nella cartella specificata in GOOGLE_DRIVE_FOLDER
```

**Output:**
```
Initializing Google Drive client...
Access token is valid.
Google Drive client initialized successfully.
Uploading file: images/card.jpg
Folder 'TCG_Cards_Image' found with ID: xxxxx
File uploaded successfully!

+---------------+----------------------------------------+
| Property      | Value                                  |
+---------------+----------------------------------------+
| ID            | 1a2b3c4d5e6f7g8h9i0j                  |
| Name          | Charizard Card                         |
| MIME Type     | image/jpeg                             |
| Size          | 2.5 MB                                |
| Created       | 2026-01-27T22:30:00.000Z              |
| Web Link      | https://drive.google.com/file/d/...   |
+---------------+----------------------------------------+
```

---

### 2. Ottenere Informazioni su un File

Recupera informazioni dettagliate su un file o cartella specifica.

```bash
php artisan app:drive-test get-info --id=FILE_ID
```

**Opzioni:**
- `--id` (obbligatorio): ID del file/cartella su Google Drive

**Esempio:**

```bash
php artisan app:drive-test get-info --id=1a2b3c4d5e6f7g8h9i0j
```

**Output:**
```
Getting file info for ID: 1a2b3c4d5e6f7g8h9i0j
File information retrieved successfully!

+------------------+----------------------------------------+
| Property         | Value                                  |
+------------------+----------------------------------------+
| ID               | 1a2b3c4d5e6f7g8h9i0j                  |
| Name             | Charizard Card                         |
| MIME Type        | image/jpeg                             |
| Size             | 2.5 MB                                |
| Created          | 2026-01-27T22:30:00.000Z              |
| Modified         | 2026-01-27T22:30:00.000Z              |
| Shared           | No                                     |
| Web View Link    | https://drive.google.com/file/d/...   |
| Download Link    | https://drive.google.com/uc?id=...    |
| Parent Folders   | 1x2y3z4w5v6u7t8s9r0q                  |
| Owners           | user@example.com                       |
+------------------+----------------------------------------+
```

---

### 3. Elencare File in una Cartella

Visualizza tutti i file contenuti in una specifica cartella.

```bash
php artisan app:drive-test list-files --folder=FOLDER_ID
```

**Opzioni:**
- `--folder` (obbligatorio): ID della cartella su Google Drive

**Esempio:**

```bash
php artisan app:drive-test list-files --folder=1x2y3z4w5v6u7t8s9r0q
```

**Output:**
```
Listing files in folder: 1x2y3z4w5v6u7t8s9r0q
Found 5 file(s):

+------------------------+------------------+--------+---------+---------------------------+
| ID                     | Name             | Type   | Size    | Modified                  |
+------------------------+------------------+--------+---------+---------------------------+
| 1a2b3c4d5e6f7g8h9i0j  | Charizard Card   | Image  | 2.5 MB  | 2026-01-27T22:30:00.000Z |
| 2b3c4d5e6f7g8h9i0j1k  | Pikachu Card     | Image  | 1.8 MB  | 2026-01-27T21:15:00.000Z |
| 3c4d5e6f7g8h9i0j1k2l  | Mewtwo Card      | Image  | 3.2 MB  | 2026-01-27T20:00:00.000Z |
| 4d5e6f7g8h9i0j1k2l3m  | Collection       | Folder | N/A     | 2026-01-27T19:45:00.000Z |
| 5e6f7g8h9i0j1k2l3m4n  | Info.txt         | Text   | 1.2 KB  | 2026-01-27T19:30:00.000Z |
+------------------------+------------------+--------+---------+---------------------------+
```

---

### 4. Creare una Cartella

Crea una nuova cartella su Google Drive.

```bash
php artisan app:drive-test create-folder --name="Nome Cartella"
```

**Opzioni:**
- `--name` (obbligatorio): Nome della nuova cartella
- `--folder` (facoltativo): ID della cartella padre (per creare una sottocartella)

**Esempi:**

```bash
# Creare una cartella nella root
php artisan app:drive-test create-folder --name="Pokemon Cards 2026"

# Creare una sottocartella
php artisan app:drive-test create-folder --name="Rare Cards" --folder=1x2y3z4w5v6u7t8s9r0q
```

**Output:**
```
Creating folder: Pokemon Cards 2026
Folder created successfully!

+------------+----------------------------------------+
| Property   | Value                                  |
+------------+----------------------------------------+
| ID         | 9z8y7x6w5v4u3t2s1r0q                  |
| Name       | Pokemon Cards 2026                     |
| Web Link   | https://drive.google.com/drive/...    |
+------------+----------------------------------------+
```

---

### 5. Eliminare File o Cartelle

Elimina un file o una cartella da Google Drive (con conferma).

```bash
php artisan app:drive-test delete --id=FILE_OR_FOLDER_ID
```

**Opzioni:**
- `--id` (obbligatorio): ID del file o cartella da eliminare

**Esempio:**

```bash
php artisan app:drive-test delete --id=1a2b3c4d5e6f7g8h9i0j
```

**Output:**
```
About to delete Image: Charizard Card (ID: 1a2b3c4d5e6f7g8h9i0j)

 Are you sure you want to delete this item? (yes/no) [no]:
 > yes

Deleting Image: Charizard Card
Image deleted successfully!
```

---

## Funzionalità Avanzate

### Auto-creazione Cartelle

Quando carichi un file e la cartella specificata in `GOOGLE_DRIVE_FOLDER` non esiste, il comando:
1. Cerca la cartella per nome
2. Se non la trova, la crea automaticamente
3. Carica il file nella cartella trovata/creata

### Gestione Errori

Il comando gestisce automaticamente:
- ✅ Token scaduti (rinnovo automatico)
- ✅ File non trovati nello storage
- ✅ Errori di rete
- ✅ Errori API Google Drive
- ✅ Permessi insufficienti

### Formattazione Output

- **Dimensioni file**: Formattate automaticamente (B, KB, MB, GB, TB)
- **Date**: Formato ISO 8601 (UTC)
- **Tabelle**: Output formattato e leggibile

---

## Integrazione nel Codice

Puoi utilizzare i metodi del comando anche nel tuo codice:

```php
use App\Console\Commands\DriveTest;

// Creare un'istanza del comando
$driveCommand = new DriveTest();

// Nota: I metodi protected devono essere richiamati tramite reflection
// oppure puoi creare un service separato con la stessa logica
```

### Esempio di Service Class

Puoi creare un service dedicato per usare queste funzionalità in tutta l'applicazione:

```php
// app/Services/GoogleDriveService.php
namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    protected $client;
    protected $driveService;

    public function __construct()
    {
        $this->initializeClient();
    }

    // Copia qui i metodi dal DriveTest command...
}
```

---

## Troubleshooting

### Token Scaduto

**Problema**: `Access token expired`  
**Soluzione**: Il comando rinnova automaticamente il token. Se continua a dare errore:

1. Verifica che `GOOGLE_DRIVE_REFRESH_TOKEN` sia impostato correttamente
2. Cancella il file cache della configurazione: `php artisan config:clear`

### File Non Trovato

**Problema**: `File not found in storage`  
**Soluzione**: 
- Verifica il percorso del file relativo a `storage/app/private/`
- Esempio: se il file è in `storage/app/private/images/card.jpg`, usa `--file=images/card.jpg`

### Permessi Insufficienti

**Problema**: `Permission denied`  
**Soluzione**: Assicurati che l'account Google associato alle credenziali abbia i permessi necessari sul Drive.

---

## Best Practices

1. **Backup**: Prima di eliminare file importanti, fai sempre un backup
2. **Naming**: Usa nomi descrittivi per file e cartelle
3. **Organizzazione**: Crea una struttura di cartelle logica
4. **Logging**: Salva gli ID dei file importanti per riferimenti futuri
5. **Testing**: Testa prima con file di prova prima di usare file reali

---

## Supporto

Per problemi o domande, controlla:
- [Documentazione Google Drive API](https://developers.google.com/drive/api/v3/about-sdk)
- [Google API PHP Client](https://github.com/googleapis/google-api-php-client)

---

## Note Tecniche

- **API Version**: Google Drive API v3
- **Scopes**: `https://www.googleapis.com/auth/drive` (accesso completo)
- **OAuth 2.0**: Implementato con refresh token automatico
- **Rate Limits**: Rispetta i limiti di rate di Google Drive API
