# Memory Tracking - Debugging Guide

## Problema
Il server Altervista ha un limite di memoria di **512MB** (`memory_limit = 536870912 bytes`).
Durante l'upload di immagini grandi (es. 10MB) si verifica l'errore:
```
Allowed memory size of 536870912 bytes exhausted (tried to allocate 28672 bytes)
```

## Sistema di Monitoraggio Implementato

Abbiamo aggiunto logging dettagliato del consumo di memoria in **tutti i punti critici** del processo di upload e resize.

### Punti di Tracking - CardUploadController

1. **START - Upload raw image** - Inizio processo
2. **AFTER - Validation** - Dopo validazione Laravel
3. **AFTER - File info extracted** - Dopo estrazione metadati file
4. **AFTER - File stored to disk** - Dopo salvataggio su disco
5. **AFTER - Garbage collection** - Dopo pulizia memoria pre-resize
6. **AFTER - Resize service completed** - Dopo chiamata al servizio resize
7. **AFTER - Garbage collection post-resize** - Dopo pulizia memoria post-resize
8. **AFTER - Database record created** - Dopo creazione record DB
9. **END - Upload raw image completed** - Fine processo

### Punti di Tracking - ImageResizeService

1. **START - Resize check** - Inizio controllo resize
2. **AFTER - Path resolved** - Dopo risoluzione path
3. **BEFORE - Loading image** - Prima di caricare immagine in memoria
4. **AFTER - Image loaded into memory** - ⚠️ Punto critico: immagine caricata
5. **BEFORE - Resize operation** - Prima dell'operazione di resize
6. **AFTER - Image scaled/covered** - Dopo il resize
7. **BEFORE - Saving image** - Prima del salvataggio
8. **AFTER - Image saved** - Dopo il salvataggio
9. **END - After cleanup** - Dopo pulizia memoria

## Come Analizzare i Log

### Log Format
Ogni log di memoria mostra:
```
MEMORY TRACKING - [STEP_NAME]
  - memory_current_mb: Memoria attualmente in uso (MB)
  - memory_peak_mb: Picco massimo di memoria raggiunto (MB)
  - memory_limit: Limite configurato
  - memory_current_bytes: Memoria in uso (bytes)
  - memory_peak_bytes: Picco massimo (bytes)
```

### Cosa Controllare

1. **Identificare il collo di bottiglia**
   - Trova il log appena prima dell'errore
   - Confronta i valori di `memory_current_mb` tra i vari step
   - Il punto dove c'è il salto più grande è il problema

2. **Punti critici noti**
   - `AFTER - Image loaded into memory`: Questo è quasi sempre il punto più critico
   - L'immagine viene caricata completamente in RAM
   - Per un'immagine 10MB compressa, in memoria decompressa può occupare 50-100MB+

3. **Calcolo approssimativo memoria necessaria**
   ```
   Memoria necessaria ≈ width × height × 4 bytes (per pixel RGBA)
   
   Esempio:
   Immagine 4000×3000 px = 12,000,000 pixel
   12,000,000 × 4 = 48,000,000 bytes ≈ 45.8 MB
   
   + overhead PHP e Intervention Image ≈ 2-3x
   = ~90-140 MB necessari
   ```

## Soluzioni Possibili

### Soluzione 1: Ridurre max_width e max_height
In `config/images.php`:
```php
'max_width' => 1024,   // invece di 1920
'max_height' => 768,   // invece di 1080
```

### Soluzione 2: Disabilitare resize per immagini troppo grandi
Aggiungere check preventivo:
```php
if ($fileSize > 5 * 1024 * 1024) { // Se > 5MB
    return response()->json([
        'success' => false,
        'message' => 'Immagine troppo grande. Max 5MB'
    ], 413);
}
```

### Soluzione 3: Resize in 2 passaggi
Per immagini molto grandi, fare resize graduale:
1. Prima riduzione al 50%
2. Poi resize finale

### Soluzione 4: Aumentare memory_limit server
⚠️ Dipende da Altervista - potrebbero non permetterlo.

## Comandi Utili

### Visualizzare ultimi log memoria
```bash
# Linux/Mac
tail -f storage/logs/laravel.log | grep "MEMORY TRACKING"

# Windows PowerShell
Get-Content storage/logs/laravel.log -Tail 100 | Select-String "MEMORY TRACKING"
```

### Filtrare solo errori
```bash
# Linux/Mac
tail -f storage/logs/laravel.log | grep -E "MEMORY TRACKING|ERROR"

# Windows PowerShell
Get-Content storage/logs/laravel.log -Tail 100 | Select-String "MEMORY TRACKING|ERROR"
```

## Test Consigliati

1. Testare con immagini di diverse dimensioni:
   - 1MB, 2MB, 5MB, 10MB
2. Verificare nei log quale dimensione causa l'errore
3. Impostare un limite preventivo basato sui risultati
4. Aggiungere validazione lato frontend per dimensione file

## Note Aggiuntive

- `gc_collect_cycles()`: Forza PHP a liberare memoria non più utilizzata
- `unset($image)`: Esplicita rimozione variabile per liberare memoria
- Il limite 512MB include TUTTA la memoria PHP, non solo dell'immagine
- Altri processi/middleware Laravel consumano memoria base (~50-100MB)
