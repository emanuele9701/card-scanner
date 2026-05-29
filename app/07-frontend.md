# 🎨 Frontend — Architettura e Strumenti

L'applicazione espone un'interfaccia utente web per la navigazione del catalogo e la gestione della collezione personale. L'architettura frontend è progettata per essere responsiva, moderna e dinamica.

## Stack Tecnologico

- **Motore di Template:** [Blade](https://laravel.com/docs/blade) (integrato in Laravel)
- **Styling:** [Tailwind CSS v4](https://tailwindcss.com/)
- **Routing:** Laravel Web Routes (`routes/web.php`)
- **Autenticazione:** Session-based (via `AuthWebController`)

---

## Struttura e Componenti

### Layouts e View (`resources/views/`)

Il frontend è strutturato in una gerarchia di view Blade:

- `layouts/app.blade.php`: Il layout principale che contiene la navbar top e la struttura base della pagina. Include gli script essenziali (es. FontAwesome).
- `auth/`: View per l'autenticazione.
  - `login.blade.php`: Pagina di login.
- `collezioni/`: View relative alle carte e ai set.
  - `mie.blade.php`: La dashboard "Le mie collezioni" che mostra i set e le carte possedute dall'utente.
  - `mie-set-detail.blade.php`: Dettaglio avanzato delle carte di un set, organizzato a **Tab** (Possedute, Mancanti, Venditori). Include calcolo percentuale di completamento e una **Mass Action Bar** dinamica (permette l'eliminazione multipla delle carte possedute, o la ricerca massiva dei venditori ottimali per le carte mancanti).
  - `disponibili.blade.php`: "Collezioni disponibili", mostra tutte le serie e i set esplorabili, con supporto a filtri per anno, ricerca testuale, ordinamento e paginazione.
  - `set-detail.blade.php`: Dettaglio di un set specifico con griglia delle carte, paginazione e filtri asincroni.
  - `partials/cards-grid.blade.php`: Componente parziale per la griglia generale delle carte, utile per il caricamento via AJAX.
  - `partials/my-cards-grid.blade.php`: Componente parziale per la griglia delle carte possedute, ottimizzato per AJAX.

### Stili Custom e Tailwind (`resources/css/app.css`)

L'applicazione sfrutta **Tailwind CSS v4** importato in `app.css`. Oltre alle utility standard di Tailwind, sono state definite configurazioni custom e varianti CSS:

- **Theming:** Sono state aggiunte variabili CSS (es. `--color-vault-bg`, `--color-vault-surface`) per definire una palette di colori coerente orientata ai toni scuri ("Vault").
- **Animazioni:** Animazioni personalizzate (es. `blob`, `dropdown`) e delay (`animation-delay-2000`).
- **Componenti CSS:** Classi utility ad-hoc per le card della collezione (`.coll-card`, `.coll-card-logo-panel`, `.coll-card-info`), che implementano transizioni morbide su hover, effetti di elevazione (box-shadow) e gradienti.

---

## Controller e Flusso Web

I controller Web (nella cartella `app/Http/Controllers/`) gestiscono le richieste frontend e restituiscono le view Blade. A differenza delle API, utilizzano la sessione per mantenere lo stato e l'autenticazione.

### `AuthWebController`

Gestisce l'accesso e l'uscita degli utenti web.
- `showLogin()`: Mostra la view `auth.login`.
- `login()`: Valida le credenziali e avvia la sessione (`Auth::attempt`).
- `logout()`: Invalida la sessione e disconnette l'utente.

### `CollezioniController`

Il core della navigazione del frontend.
- `index()`: Mostra le collezioni possedute dall'utente (view `collezioni.mie`).
- `disponibili(Request $request)`: Mostra tutti i set disponibili raggruppati, con supporto a filtri per ricerca testuale, anno d'uscita, ordinamento personalizzabile e paginazione (view `collezioni.disponibili`).
- `showSet(TCGSet $set)`: Mostra il dettaglio delle carte contenute in un set. Implementa:
  - Filtraggio avanzato (per tipo di pokemon, stadio evolutivo).
  - Ordinamento (alfabetico, per rarità, per numero nel set).
  - Supporto AJAX (ritorna JSON con HTML parziale) per evitare il reload della pagina durante paginazione o filtri.
- `showMySet(TCGSet $set)`: Gestisce la visualizzazione delle carte per un set tramite 3 Tab principali:
  - **owned (Possedute):** Recupera carte univoche possedute con copie in eager loading e percentuale di completamento in tempo reale.
  - **missing (Mancanti):** Mostra le carte del set non ancora possedute.
  - **sellers (Venditori):** Incrocia le carte mancanti (o una selezione specifica tramite il parametro URL `selected_cards`) con la tabella `tcg_card_offers`. Suggerisce i venditori che dispongono della maggior quantità di carte mancanti, ottimizzando così i costi di spedizione.
- **Gestione Copie e Mass Actions (AJAX)**: Endpoint dedicati per le manipolazioni della collezione tramite frontend:
  - `getCardCopies(TCGCard $card)`: Ritorna tutte le condizioni/varianti possedute per una data carta.
  - `addCardCopy(Request, TCGCard)`: Aggiunge una o più copie specificando condizione, varianti e quantità.
  - `updateCardCopy(Request, UserCardCollection)`: Aggiorna la quantità di una copia.
  - `deleteCardCopy(UserCardCollection)`: Rimuove un record specifico di copie.
  - `removeCardFromCollection(TCGCard)`: Elimina la carta e tutte le sue copie dalla collezione.
  - `massRemoveCards(Request)`: Supporta la selezione multipla per rimuovere massivamente N carte.
  - `missingCards(TCGSet)`: Ritorna in JSON le carte del set che non sono ancora possedute dall'utente.
- `addCardToCollection(TCGCard $card)`: Permette all'utente di aggiungere rapidamente una carta base alla collezione via frontend.

### `DashboardController`

Gestisce la landing page dell'area riservata.
- `index()`: Carica la view principale della dashboard.

### `UserSettingsController`

Gestisce la configurazione del profilo dell'utente.
- `index()`: Mostra la pagina delle impostazioni (view `settings.blade.php`).
- `update()`: Salva la lingua preferita dell'utente tramite i metodi di helper nel model `User` (`setSetting`).

---

## Rotte Web (`routes/web.php`)

Le rotte sono divise in due gruppi principali, gestiti dai middleware di Laravel:

1. **Guest Middleware (`guest`)**
   - Rotte accessibili solo agli utenti non loggati: `/login` (GET, POST).

2. **Auth Middleware (`auth`)**
   - Rotte riservate agli utenti loggati, che compongono l'applicazione vera e propria.
   - Redirect sulla root `/` verso `/dashboard` (gestita dal `DashboardController`).
   - Gruppo prefisso `collezioni/`:
     - `/mie`
     - `/mie/set/{set}` (Dettaglio carte possedute nel set)
     - `/disponibili`
     - `/set/{set}` (Dettaglio generale carte del set)
     - `/cards/{card}` (Aggiunta rapida)
   - Impostazioni utente: `/settings`.

Ogni richiesta all'interno del gruppo `auth` ha automaticamente accesso ai dati dell'utente correntemente loggato tramite `Auth::user()`.
