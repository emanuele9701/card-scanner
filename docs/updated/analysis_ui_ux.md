# Analisi Funzionale e UI/UX: Caricamento Multiplo Immagini - Gallery Mode

## 1. Obiettivo
Abilitare il caricamento simultaneo di più immagini (carte Pokemon) con un'interfaccia a **galleria visuale** che permette all'utente di scegliere liberamente quale carta elaborare, in qualsiasi ordine.

## 2. Evoluzione del Design

### Versione 1.0 - Queue Mode (Implementata inizialmente)
- ❌ Flusso sequenziale forzato
- ❌ Mancanza di overview visuale
- ❌ Troppi click per accedere ai dettagli
- ❌ Risultati nascosti in fondo alla pagina

### Versione 2.0 - Gallery Mode (Implementazione attuale)
- ✅ Vista a griglia di tutte le carte
- ✅ Selezione libera dell'ordine di elaborazione
- ✅ Stati visivi chiari per ogni carta
- ✅ Modifica inline direttamente nella card
- ✅ Cropper in modal (non blocca la vista)

## 3. Nuova UI/UX - Gallery Mode

### A. Struttura Visuale

```
┌─────────────────────────────────────────────────────┐
│  [Upload Area - Drag & Drop]                        │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│  Carte Caricate (5)                    [Reset]      │
└─────────────────────────────────────────────────────┘

┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│ [IMG 1]  │ │ [IMG 2]  │ │ [IMG 3]  │ │ [IMG 4]  │
│ ⏳ Pending│ │ ✓ Complet│ │ 🔄 Process│ │ ⏳ Pending│
│          │ │          │ │          │ │          │
│[Elabora] │ │[Dettagli]│ │ Loading..│ │[Elabora] │
│          │ │  [AI]    │ │          │ │          │
└──────────┘ └──────────┘ └──────────┘ └──────────┘
              ↓ Espandi
         ┌────────────────┐
         │ OCR: "Pikachu" │
         │ HP: [60]       │
         │ Tipo: [Elettro]│
         │ [Elimina][Salva]│
         └────────────────┘
```

### B. Stati delle Carte

Ogni carta nella galleria può trovarsi in uno di questi stati:

| Stato | Badge | Colore Bordo | Azioni Disponibili |
|-------|-------|--------------|-------------------|
| **Pending** | ⏳ Da elaborare | Grigio | `[Elabora]` |
| **Processing** | 🔄 Elaborazione... | Giallo (animato) | Spinner |
| **Completed** | ✓ Completato | Verde | `[Dettagli]` `[AI]` |
| **Error** | ✗ Errore | Rosso | `[Riprova]` `[Elimina]` |
| **Saved** | 💾 Salvato | Blu (opaco) | Read-only |

### C. Flusso Utente

#### Scenario: Upload di 5 carte

1. **Upload**
   - Utente trascina 5 immagini
   - Appare griglia con 5 card in stato "Pending"
   - Ogni card mostra miniatura dell'immagine

2. **Elaborazione Libera**
   - Click "Elabora" su Card 3
   - Si apre **Modal Cropper** (fullscreen)
   - Utente ritaglia → Click "Elabora con OCR"
   - Modal si chiude
   - Card 3 passa a "Processing" (spinner)
   - Dopo 2-3 sec → Card 3 diventa "Completed"

3. **Elaborazione Parallela**
   - Mentre Card 3 è in processing, utente può:
     - Click "Elabora" su Card 1 (apre cropper)
     - Vedere lo stato di tutte le altre card
     - Non è bloccato

4. **Modifica e AI**
   - Click "Dettagli" su Card 3 completata
   - Si espande inline mostrando:
     - Testo OCR estratto
     - Form con campi (Nome, HP, Tipo)
   - Click "AI" → Form si popola automaticamente
   - Modifica manualmente se necessario
   - Click "Salva" → Card diventa "Saved" (opaca)

5. **Gestione Flessibile**
   - Può elaborare le card in qualsiasi ordine (3, 1, 5, 2, 4)
   - Può eliminare card prima o dopo l'elaborazione
   - Può salvare alcune e continuare con altre
   - Click "Reset" per ricominciare da capo

## 4. Componenti Tecnici

### Frontend (Blade + JavaScript)

#### HTML Structure
- `#uploadArea`: Zona drag & drop
- `#gallerySection`: Contenitore griglia
- `#galleryGrid`: Griglia responsive (CSS Grid)
- `#cropperModal`: Modal fullscreen per cropper
- `.gallery-card`: Singola card con stati

#### JavaScript State Management
```javascript
galleryCards = Map<fileId, {
    file: File,
    state: 'pending' | 'processing' | 'completed' | 'error' | 'saved',
    data: {id, extracted_text, image_url},
    thumbnail: base64,
    cardId: number
}>
```

#### Key Functions
- `createGalleryCard(fileId, file)`: Crea card e genera thumbnail
- `renderGalleryCard(fileId)`: Aggiorna UI della card
- `openCropperModal(fileId)`: Apre modal cropper
- `processCard(fileId)`: Crop → OCR → Update state
- `enhanceGalleryCard(fileId)`: AI enhancement
- `saveGalleryCard(fileId)`: Salva nel database
- `discardGalleryCard(fileId)`: Elimina card

### Backend (Laravel)
**Nessuna modifica richiesta.** Gli endpoint esistenti supportano già il flusso:
- `POST /ocr/process`: Riceve immagine croppata, restituisce OCR
- `POST /ocr/enhance`: Riceve card_id, restituisce dati AI
- `POST /ocr/confirm`: Salva carta finale
- `POST /ocr/discard`: Elimina carta

## 5. Vantaggi della Gallery Mode

### UX Improvements
1. **Vista d'insieme**: Tutte le carte visibili contemporaneamente
2. **Libertà di scelta**: Elabora nell'ordine preferito
3. **Feedback visivo**: Stati chiari con colori e badge
4. **Meno click**: Dettagli inline, non serve navigare
5. **Non bloccante**: Modal cropper non nasconde la galleria

### Technical Benefits
1. **Scalabilità**: Gestisce facilmente 10+ immagini
2. **State management**: Map-based, facile da debuggare
3. **Indipendenza**: Ogni card è autonoma
4. **Riutilizzo backend**: Zero modifiche server-side
5. **Responsive**: Grid si adatta a mobile/tablet/desktop

## 6. Confronto con Queue Mode

| Aspetto | Queue Mode | Gallery Mode |
|---------|------------|--------------|
| **Ordine elaborazione** | Sequenziale forzato | Libero |
| **Vista carte** | Una alla volta | Tutte insieme |
| **Navigazione** | Lineare (next/prev) | Click diretto |
| **Modifica dati** | Form separato | Inline nella card |
| **Cropper** | Inline (nasconde tutto) | Modal (non blocca) |
| **Complessità UX** | Media | Bassa |
| **Flessibilità** | Bassa | Alta |

## 7. Mockup Dettagliato

### Card States Visual

```
┌─────────────────┐
│ ⏳ Da elaborare │ ← Badge
├─────────────────┤
│                 │
│   [Thumbnail]   │ ← Miniatura 200x200px
│                 │
├─────────────────┤
│ filename.jpg    │ ← Nome file
│ [Elabora]       │ ← Azione primaria
└─────────────────┘
   Stato: PENDING

┌─────────────────┐
│ ✓ Completato    │
├─────────────────┤
│   [Thumbnail]   │
├─────────────────┤
│ pikachu.jpg     │
│ [Dettagli] [AI] │
│                 │
│ ▼ Espanso:      │
│ ┌─────────────┐ │
│ │OCR: "Pikach"│ │
│ │HP: [60]     │ │
│ │[💾][🗑️]    │ │
│ └─────────────┘ │
└─────────────────┘
   Stato: COMPLETED

┌─────────────────┐
│ 💾 Salvato      │
├─────────────────┤
│   [Thumbnail]   │ ← Opacità 60%
│   (opaco)       │
├─────────────────┤
│ charizard.jpg   │
│ ✓ Salvato       │
└─────────────────┘
   Stato: SAVED (read-only)
```

## 8. Responsive Design

### Desktop (>1200px)
- Grid: 4 colonne
- Card width: ~280px
- Modal cropper: 900px max-width

### Tablet (768px - 1200px)
- Grid: 3 colonne
- Card width: auto
- Modal cropper: 100% - 40px padding

### Mobile (<768px)
- Grid: 2 colonne
- Card width: auto
- Modal cropper: fullscreen
- Form fields: stack verticalmente

## 9. Accessibilità

- ✅ Keyboard navigation: Tab tra le card
- ✅ ARIA labels: Stati delle card
- ✅ Focus visible: Outline su card selezionata
- ✅ Screen reader: Annunci di stato
- ✅ Color contrast: WCAG AA compliant

## 10. Performance

### Ottimizzazioni
- Thumbnail generate client-side (FileReader)
- Lazy rendering: Solo card visibili
- Debounce su AI calls
- Batch save opzionale (future)

### Limiti Consigliati
- Max 20 immagini per sessione
- Max 30MB per file
- Timeout OCR: 30 secondi
- Timeout AI: 15 secondi

## Conclusione

La **Gallery Mode** rappresenta un miglioramento significativo rispetto alla Queue Mode iniziale, offrendo un'esperienza utente più intuitiva, flessibile e visualmente chiara. L'implementazione mantiene la compatibilità con il backend esistente e introduce pattern di design familiari agli utenti (simile a Google Photos, Dropbox, ecc.).
