# Implementazione Flusso di Upload a Stati e Azioni Massive

**Data:** 2026-01-02
**Stato:** In Implementazione

## Obiettivo
Riorganizzare il processo di upload delle carte Pokemon introducendo un flusso di lavoro a "pipeline" visiva con schede (Tabs) e funzionalità di elaborazione massiva (Bulk Actions). L'obiettivo è migliorare l'efficienza quando si gestiscono molte carte contemporaneamente.

## Nuovo Flusso degli Stati

Abbiamo aggiornato il modello `PokemonCard` introducendo nuovi stati per tracciare il progresso della carta nella pipeline:

1.  **Da Ritagliare (`pending`)**
    *   Stato iniziale dopo l'upload dell'immagine grezza.
    *   L'immagine è stata salvata sul server ma non è ancora stata elaborata.
    *   **Azioni possibili:**
        *   *Ritagliare*: Apre il cropper manuale.
        *   *Salta Ritaglio*: Passa direttamente allo stato successivo usando l'immagine originale come "ritagliata".

2.  **Da Analizzare (`ready_for_ai`)**
    *   La carta ha un'immagine definita (ritagliata o originale).
    *   È pronta per essere inviata all'analizzatore AI (Gemini).
    *   **Azioni possibili:**
        *   *Analisi AI*: Invia l'immagine a Gemini per estrarre i dati.
        *   *Inserimento Manuale*: L'utente inserisce i dati manualmente senza AI.

3.  **In Revisione (`review`)**
    *   Lanciata l'analisi AI, i dati sono tornati e sono in attesa di conferma/modifica da parte dell'utente.
    *   Visivamente, queste carte rimarranno nella scheda "Da Analizzare" o avranno uno stato evidenziato finché non vengono salvate.

4.  **Completate (`completed`)**
    *   La carta è stata salvata nel database con tutti i metadati confermati.
    *   Appare nella lista finale della collezione.

## Modifiche Tecniche

### Backend (Laravel)

1.  **Model `PokemonCard`**:
    *   Aggiunta costante `STATUS_READY_FOR_AI = 'ready_for_ai'`.
    
2.  **`CardUploadController`**:
    *   Refactoring di `uploadImage` in `uploadRawImage`: Salva solo il file raw.
    *   Nuovo metodo `saveCroppedImage`: Salva il ritaglio e aggiorna lo stato a `ready_for_ai`.
    *   Nuovo metodo `skipCrop`: Aggiorna lo stato a `ready_for_ai` senza modificare l'immagine.
    
3.  **Routes `web.php`** (Da implementare):
    *   `POST /cards/upload-raw`
    *   `POST /cards/save-crop`
    *   `POST /cards/skip-crop`

### Frontend (Blade + JS)

1.  **Interfaccia a Schede (Tabs)**:
    *   Divisione visiva in 3 sezioni: "Da Ritagliare", "Da Analizzare", "Completate".
    *   Contatori badge per ogni scheda (es. "Da Ritagliare (5)").

2.  **Gestione dello Stato Locale**:
    *   La mappa `galleryCards` gestirà lo stato locale.
    *   La funzione `renderGallery` filtrerà le carte da mostrare in base alla scheda attiva.

3.  **Azioni Massive (Bulk Actions)**:
    *   Aggiunta di checkbox su ogni card in stato elaborabile.
    *   Barra delle azioni flottante che appare quando si selezionano elementi.
    *   Pulsanti "Analizza Selezionati" e "Conferma Selezionati".

## Vantaggi
- **Chiarezza**: L'utente sa esattamente a che punto è ogni carta.
- **Velocità**: Possibilità di caricare 50 foto, saltare il ritaglio per tutte in un click, e lanciare l'AI per tutte in un altro click.
- **Flessibilità**: Possibilità di intervenire manualmente su singole carte "problematiche" senza bloccare il flusso delle altre.
