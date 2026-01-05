# 📚 Guida Completa - Pokemon Card Scanner

**Data di creazione:** 2 Gennaio 2026  
**Versione:** 1.1  
**Ultimo aggiornamento:** 5 Gennaio 2026

---

## 📝 Changelog e Aggiornamenti

### Versione 1.1 (5 Gennaio 2026)

#### 🔐 Sistema Multi-Utente e Ownership

**Implementato user ownership su tutte le carte:**
- Ogni carta è ora associata all'utente che l'ha creata tramite il campo `user_id`
- Tutti i controller sono stati aggiornati per filtrare le carte in base all'utente autenticato:
  - `CardUploadController`: Upload, modifiche ed eliminazioni limitate alle proprie carte
  - `CollectionController`: Visualizzazione collezione e calcolo valore solo per carte proprie
  - `CardMatchingController`: Matching e suggerimenti filtrati per utente
  - `PokemonCardController`: Aggiornamento condizioni riservato al proprietario
  - `ImageController`: Streaming immagini protetto da ownership check
- Protezione completa contro accessi non autorizzati (HTTP 403 per tentativi di accesso a carte altrui)

**Vantaggi:**
- Ogni utente vede e gestisce solo le proprie carte
- Privacy e sicurezza dei dati garantite
- Supporto multi-utente senza conflitti tra collezioni

#### 🎨 Migrazione Completa a Vue 3 + Inertia.js

**Tutte le views Blade sono state convertite a componenti Vue:**
- ✅ `Auth/Login.vue` - Pagina login con animazioni
- ✅ `Auth/Register.vue` - Pagina registrazione
- ✅ `Cards/Upload.vue` - Sistema upload carte con pipeline visiva
- ✅ `Cards/Index.vue` - Collezione carte organizzata per set
- ✅ `Collection/Index.vue` - Dashboard statistiche collezione
- ✅ `Collection/Value.vue` - Calcolo valore totale collezione
- ✅ `Matching/Index.vue` - Sistema matching carte/prezzi
- ✅ `Profile/Show.vue` - Visualizzazione profilo utente
- ✅ `Profile/Edit.vue` - Modifica profilo utente

**Navbar Unificata:**
- Nuovo layout `AppLayout.vue` condiviso da tutte le pagine
- Menu utente con dropdown nell'angolo in alto a destra:
  - Avatar con iniziale del nome
  - Link al profilo (`/profile`)
  - Pulsante logout funzionante
- Navigazione responsive con menu hamburger su mobile
- Banner "DEMO MODE" sempre visibile per ambienti dimostrativi

**Miglioramenti UX:**
- Notifiche toast al posto degli `alert()` JavaScript
- Animazioni fluide e transizioni moderne
- Componenti riutilizzabili e manutenibili
- Gestione errori di validazione integrata con Inertia

#### 🔧 Miglioramenti Tecnici

**Backend:**
- Middleware Inertia configurato per condividere dati utente autenticato (`auth.user`)
- Migration per aggiungere `user_id` alla tabella `pokemon_cards`
- Relazione `belongsTo` tra `PokemonCard` e `User`
- Query scoping automatico per ownership in tutti i controller

**Frontend:**
- Setup Vue 3 con Composition API (`<script setup>`)
- Gestione stato reattiva con `ref` e `computed`
- Routing client-side con Inertia.js Router
- Import Bootstrap CSS/JS in `app.js` per styling consistente

---

## 🎯 Introduzione e Scopo della Piattaforma

Pokemon Card Scanner è una piattaforma progettata per **tenere traccia delle collezioni di carte Pokemon divise per set** che si possiedono. 

> ⚠️ **Nota Importante:** Questa piattaforma **non è pensata per catalogare tutte le carte esistenti nel mondo Pokemon TCG**, ma per gestire e monitorare il valore delle **proprie collezioni personali**.

L'idea è semplice: quando acquisti un nuovo set di carte Pokemon, importi i relativi dati di mercato e poi scansioni/aggiungi le carte che possiedi. In questo modo puoi:

- 📊 Monitorare il valore della tua collezione nel tempo
- 💰 Calcolare profitti e perdite (P&L) sui tuoi investimenti
- 🎴 Organizzare le carte per set e condizione
- 🤖 Identificare automaticamente le carte con l'AI

---

## 🚀 Flusso di Lavoro Consigliato

### Per ogni nuovo set che acquisti:

```
1. Ottieni i dati di mercato dal TCGPlayer Price Guide
2. Importa il JSON nella piattaforma (operazione manuale)
3. Scansiona/aggiungi le carte che possiedi
4. Collega le carte ai dati di mercato (Matching)
5. Monitora il valore nel tempo
```

---

## 📖 Guida Passo-Passo

### 1️⃣ Accesso alla Piattaforma

1. Apri il browser e naviga all'indirizzo dell'applicazione
2. Se non hai un account:
   - Clicca su **"Registrati"**
   - Inserisci Nome, Email e Password
   - Conferma la registrazione
3. Effettua il login con le tue credenziali

---

### 2️⃣ Importare i Dati di Mercato

> ⚠️ **IMPORTANTE:** L'importazione dei prezzi di mercato è un'operazione **puramente manuale**. Non esistono comandi automatici o job schedulati che eseguono questa operazione in modo massivo.

#### Fonte dei Dati

I dati di mercato vengono prelevati dall'API **TCGPlayer Infinite**:
```
https://infinite-api.tcgplayer.com
```

Specificamente, si utilizza l'endpoint del **Price Guide** che restituisce i prezzi delle carte per ogni set.

#### Struttura del JSON Richiesto

Il JSON deve essere strutturato nel seguente formato:

```json
{
  "count": 544,
  "result": [
    {
      "productID": 662125,
      "productConditionID": 0,
      "condition": "Near Mint",
      "game": "Pokemon",
      "lowPrice": 0.01,
      "marketPrice": 0.07,
      "number": "063/094",
      "printing": "Normal",
      "productName": "Absol",
      "rarity": "Common",
      "sales": 0,
      "set": "ME02: Phantasmal Flames",
      "setAbbrv": "PFL",
      "type": "Cards",
      "isSupplemental": false
    },
    // ... altri record
  ]
}
```

#### Spiegazione dei Campi

| Campo | Tipo | Descrizione |
|-------|------|-------------|
| `productID` | Integer | ID univoco del prodotto su TCGPlayer |
| `productConditionID` | Integer | ID della condizione |
| `condition` | String | Condizione della carta (Near Mint, Lightly Played, etc.) |
| `game` | String | Gioco di appartenenza (sempre "Pokemon") |
| `lowPrice` | Decimal | Prezzo minimo sul mercato |
| `marketPrice` | Decimal | Prezzo medio di mercato |
| `number` | String | Numero della carta nel set (es. "063/094") |
| `printing` | String | Tipo di stampa: Normal, Reverse Holofoil, Holofoil |
| `productName` | String | Nome della carta/Pokemon |
| `rarity` | String | Rarità (Common, Uncommon, Rare, etc.) |
| `sales` | Integer | Numero di vendite recenti |
| `set` | String | Nome completo del set |
| `setAbbrv` | String | Abbreviazione del set (es. "PFL") |
| `type` | String | Tipo di prodotto (Cards, Sealed, etc.) |
| `isSupplemental` | Boolean | Se è un prodotto supplementare |

#### Condizioni Supportate

- **Near Mint** (NM) - Condizione perfetta o quasi
- **Lightly Played** (LP) - Leggeri segni di usura
- **Moderately Played** (MP) - Segni moderati di usura
- **Heavily Played** (HP) - Segni evidenti di usura
- **Damaged** (DMG) - Carta danneggiata

#### Tipi di Stampa Supportati

- **Normal** - Stampa standard
- **Reverse Holofoil** - Olografica inversa
- **Holofoil** - Olografica standard

#### Come Importare

1. Naviga a **"Market Data"** dal menu principale
2. Clicca sul pulsante **"Importa"** o nell'area di upload
3. Seleziona il file JSON contenente i dati del Price Guide
4. Clicca **"Import Data"**
5. Attendi il completamento dell'importazione
6. Verifica le statistiche:
   - **Total Records**: numero totale di record processati
   - **Cards Created**: nuove carte aggiunte al catalogo
   - **Cards Updated**: carte già esistenti aggiornate
   - **Prices Created**: nuovi prezzi registrati
   - **Sets Created**: nuovi set creati

> 📌 **Nota sull'Aggiornamento Automatico dei Prezzi**
> 
> In questa versione della piattaforma **non è stato implementato** un sistema di recupero automatico dei prezzi (es. job schedulati che aggiornano i dati ogni X ore/giorni). Questa scelta è stata fatta intenzionalmente per **rispettare le policy e i termini di servizio di TCGPlayer**, che potrebbero non consentire lo scraping automatizzato o l'accesso frequente alle loro API senza autorizzazione.
> 
> Tuttavia, l'architettura della piattaforma è già predisposta per supportare questa funzionalità: con **piccole modifiche** al codice (ad esempio, creando un Laravel Command o un Job schedulato che richiami il servizio di import), è possibile automatizzare il processo di aggiornamento. **L'implementazione di tale automatismo è a discrezione e responsabilità dell'utente**, che dovrà assicurarsi di rispettare i termini di servizio della fonte dati utilizzata.

---

### 3️⃣ Scansione e Aggiunta Carte

#### Il Flusso di Upload a Pipeline

Il processo di aggiunta carte è organizzato in una **pipeline visiva** con tre schede:

##### 📥 Tab 1: "Da Ritagliare" (Pending)
Le carte appena caricate che necessitano di essere ritagliate per isolare l'immagine della carta.

**Azioni disponibili:**
- **Ritaglia**: Apre l'editor di ritaglio per perfezionare l'inquadratura
- **Salta Ritaglio**: Usa l'immagine originale senza modifiche

##### 🤖 Tab 2: "Da Analizzare" (Ready for AI)
Carte pronte per l'analisi AI o l'inserimento manuale dei dati.

**Azioni disponibili:**
- **Analisi AI**: Invia l'immagine a Google Gemini per riconoscimento automatico
- **Inserimento Manuale**: Compila i campi manualmente

##### ✅ Tab 3: "Completate" (Completed)
Carte salvate con tutti i metadati confermati.

#### Procedura Step-by-Step

1. **Vai su "Scansiona"** (icona fotocamera nel menu)

2. **Carica le immagini**:
   - Trascina le immagini nell'area di upload, oppure
   - Clicca per selezionare file dal computer
   - Puoi caricare **multiple immagini** contemporaneamente

3. **Ritaglia le carte** (Tab "Da Ritagliare"):
   - Clicca **"Ritaglia"** su ogni carta
   - Usa il cropper per inquadrare solo la carta
   - Conferma il ritaglio
   - Oppure clicca **"Salta Ritaglio"** per usare l'immagine originale

4. **Analizza con AI** (Tab "Da Analizzare"):
   - Clicca **"Riconosci con AI"** (icona bacchetta magica)
   - L'AI estrarrà automaticamente:
     - Nome del Pokemon
     - HP (Punti Vita)
     - Tipo (Fuoco, Acqua, Erba, etc.)
     - Stadio evoluzione
     - Attacchi e abilità
     - Debolezza, Resistenza, Costo ritirata
     - Numero set e rarità

5. **Rivedi e Salva**:
   - Verifica i dati estratti dall'AI
   - Modifica eventuali errori
   - Seleziona il **Set** di appartenenza
   - Indica la **Condizione** della carta
   - Specifica il tipo di **Printing** (Normal, Holofoil, etc.)
   - Clicca **"Salva"**

#### Azioni Massive (Bulk Actions)

Per velocizzare il processo con molte carte:

1. Seleziona le carte tramite le checkbox
2. Usa la **barra azioni flottante** che appare
3. Clicca **"Analizza Selezionati"** per lanciare l'AI su tutte
4. Oppure **"Conferma Selezionati"** per salvare multiple carte

---

### 4️⃣ Matching: Collegare Carte ai Prezzi di Mercato

Dopo aver importato i dati di mercato e aggiunto le tue carte, devi **collegare** le carte della collezione ai dati di prezzo.

1. Vai su **"Matching"** dal menu

2. **Auto-Match**: 
   - Clicca il pulsante per tentare il matching automatico
   - Il sistema cerca corrispondenze basate su:
     - Numero carta + Abbreviazione set
     - Nome della carta (fallback fuzzy)

3. **Match Manuale** (per carte non riconosciute):
   - Clicca sulla carta non matchata
   - Visualizza i suggerimenti
   - Seleziona la corrispondenza corretta
   - Conferma il match

4. **Unmatch** (in caso di errore):
   - Clicca sulla carta matchata erroneamente
   - Clicca **"Rimuovi Match"**

---

### 5️⃣ Visualizzare e Gestire la Collezione

Naviga a **"Collezione"** per vedere tutte le tue carte organizzate per set.

#### Organizzazione per Set

Le carte sono automaticamente raggruppate in **sezioni collassabili** per ogni set:
- Ogni sezione mostra il nome del set e il conteggio delle carte
- Clicca sull'intestazione del set per **espandere/comprimere** la sezione
- Le carte senza set assegnato sono raggruppate nella sezione **"Senza Set"** (evidenziata in rosso)

#### Visualizzazione Dettagli Carta

1. Clicca sull'icona **�️ (occhio)** per aprire il modal di visualizzazione
2. Il modal mostra:
   - Immagine della carta (cliccabile per zoom fullscreen)
   - Tutti i dettagli: Nome, HP, Tipo, Stadio Evoluzione
   - Debolezza, Resistenza, Costo Ritirata
   - Set e Numero Set
   - Rarità e Illustratore
   - **💰 Valore Stimato** (se disponibile dal matching)
   - Prezzo di Acquisto e Condizione

#### Modifica Singola Carta

1. Clicca sull'icona **✏️ (matita)** per aprire il modal in modalità modifica
2. Puoi modificare tutti i campi della carta:
   - Nome, HP, Tipo, Stadio Evoluzione
   - Debolezza, Resistenza, Costo Ritirata
   - **Set** (dropdown con tutti i set disponibili)
   - Numero Set, Rarità, Illustratore
3. Clicca **"Salva"** per confermare le modifiche

> 💡 **Tip**: Per assegnare un set a una carta senza set, aprila in modifica e seleziona il set dal dropdown!

#### Selezione Massiva e Assegnazione Set

Per gestire più carte contemporaneamente:

1. **Seleziona le carte** spuntando le checkbox in alto a sinistra di ogni carta
2. Una **barra flottante** apparirà in basso mostrando il numero di carte selezionate
3. Clicca **"Assegna Set"** per aprire il modal di assegnazione massiva
4. Seleziona il set da assegnare (o "Nessun Set" per rimuoverlo)
5. Clicca **"Assegna Set"** per confermare

> ⚡ **Uso rapido**: Questa funzione è ideale per assegnare rapidamente un set a tutte le carte appena caricate che sono nella sezione "Senza Set"!

#### Altre Funzionalità

- 🔍 **Zoom Fullscreen**: Clicca su qualsiasi immagine carta per vederla a schermo intero
- 🗑️ **Elimina Carta**: Clicca sull'icona cestino per rimuovere definitivamente una carta

---

## 🔄 Procedura per Nuovo Set

Quando acquisti un nuovo set di carte Pokemon:

### Step 1: Ottieni i Dati di Mercato
```
1. Vai su https://infinite-api.tcgplayer.com
2. Accedi al Price Guide del set desiderato
3. Esporta/Copia i dati JSON
4. Salva il file con estensione .json
```

### Step 2: Importa nella Piattaforma
```
1. Naviga a Market Data
2. Carica il file JSON
3. Verifica il successo dell'import
```

### Step 3: Aggiungi le Tue Carte
```
1. Vai su Scansiona
2. Fotografa/Carica le carte del set
3. Ritaglia e analizza con AI
4. Salva nella collezione
```

### Step 4: Collega ai Prezzi
```
1. Vai su Matching
2. Esegui Auto-Match
3. Completa manualmente i match mancanti
```

### Step 5: Monitora
```
1. Controlla il valore in "Valore"
2. Ripeti l'import dei prezzi periodicamente
   per avere valutazioni aggiornate
```

---

## 💡 Consigli per Migliori Risultati

### Per l'AI Recognition:
- ✅ Usa **buona illuminazione** senza riflessi
- ✅ Sfondo **uniforme** e pulito
- ✅ Inquadra **solo la carta** nel ritaglio
- ✅ Le carte in **inglese** funzionano meglio per il matching globale

### Per il Matching:
- ✅ Importa sempre i dati **prima** di aggiungere le carte
- ✅ Usa il formato numero carta **esatto** (es. "063/094")
- ✅ Seleziona il **set corretto** durante il salvataggio

### Per la Valutazione:
- ✅ Indica sempre la **condizione** corretta della carta
- ✅ Specifica il tipo di **printing** (Normal/Holo/Reverse)
- ✅ Aggiorna i prezzi **periodicamente** per valutazioni accurate

---

## ⚠️ Note Importanti

1. **Operazione Manuale**: L'importazione dei dati di mercato è **completamente manuale**. Non esistono job automatici o scheduled task che aggiornano i prezzi.

2. **Colleziona ciò che possiedi**: La piattaforma è pensata per tracciare le **TUE** carte, non per essere un database completo di tutte le carte Pokemon esistenti.

3. **Set per Set**: Per ogni nuovo set che acquisti, dovrai importare i relativi dati di mercato separatamente.

4. **Fonte Dati**: I prezzi provengono da TCGPlayer (https://infinite-api.tcgplayer.com). Assicurati di rispettare i loro termini di servizio.

5. **Precisione**: I valori sono stime basate sui prezzi di mercato. Il valore reale può variare.

---

## 🔧 Risoluzione Problemi

### L'AI non riconosce la carta
- Verifica la qualità dell'immagine
- Riprova con illuminazione migliore
- Usa l'inserimento manuale come fallback

### Il matching non trova corrispondenze
- Verifica che i dati del set siano stati importati
- Controlla il numero carta (formato corretto)
- Usa il match manuale

### Import JSON fallisce
- Verifica la struttura del JSON
- Controlla che contenga `result` come array
- Assicurati che il file non superi 10MB

---

## 📞 Supporto

Per problemi o suggerimenti, apri una issue su GitHub o contatta il supporto.

---

**Buon collezionismo! 🎴**
