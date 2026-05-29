# Card Scanner — Piano di Implementazione Core MVP

Questo documento definisce il piano di implementazione per risolvere le lacune critiche lato utente evidenziate nella **Business Analysis**, concentrandosi sul **Core MVP (Sprint 1-3)**.

L'obiettivo è rendere l'applicazione fruibile, accattivante e pronta per il lancio, migliorando drasticamente la User Experience (UX) e introducendo le feature base di ritenzione.

## User Review Required

> [!CAUTION]
> **Il Rischio Esistenziale: Il Nome "Card Scanner"**
> Come evidenziato nell'analisi, il nome promette uno scanner fisico. Se lanciamo così, l'utente cercherà il bottone "Scannerizza" e, non trovandolo, disinstallerà l'app.
> **Decisione richiesta:** Vuoi che manteniamo il nome e pianifichiamo lo sviluppo di uno scanner (es. via fotocamera/OCR) nella v1.1, oppure preferisci fare un re-branding del progetto con un nome diverso (es. "PokeVault", "DexCollector")?

## Open Questions

> [!WARNING]
> **Scelta del flusso di Autenticazione per Web**
> L'app usa `AuthWebController` personalizzato. Per il **Password Reset e l'Email Verification**, preferisci che installiamo Laravel Breeze (che sovrascriverà alcune view e controller standardizzando tutto) oppure che integriamo manualmente il flusso `Illuminate\Foundation\Auth\ResetsPasswords` nel tuo controller custom per non alterare l'attuale struttura?

> [!NOTE]
> **Esportazione PDF**
> Per l'export PDF servirà un pacchetto come `barryvdh/laravel-dompdf`. Lo ritieni necessario subito, o possiamo partire solo con l'export CSV che è molto più rapido e manipolabile dall'utente?

---

## Proposed Changes

Di seguito l'elenco delle modifiche raggruppate per funzionalità. Tutte le modifiche al codice verranno effettuate nella directory `c:\Users\EmanueleLucchese\Downloads\card-scanner\app`.

---

### 1. Ricerca Globale Carte

Aggiungeremo una barra di ricerca globale accessibile dalla navbar, per cercare direttamente le carte per nome bypassando la navigazione Serie → Set.

#### [NEW] `app/Http/Controllers/SearchController.php`
- Creazione di un endpoint AJAX per l'autocomplete.
- Endpoint per la pagina dei risultati completi.

#### [MODIFY] `routes/web.php`
- Aggiunta delle rotte `GET /search/autocomplete` e `GET /search`.

#### [MODIFY] `resources/views/layouts/app.blade.php`
- Inserimento dell'input box per la ricerca globale nella navbar, con design "Vault" scuro e micro-animazioni Tailwind.

#### [NEW] `resources/views/search/results.blade.php`
- Pagina dei risultati di ricerca con griglia carte riutilizzando il partial esistente `partials/cards-grid.blade.php`.

---

### 2. Dashboard Valore Collezione

Calcoleremo il valore totale della collezione incrociando `user_card_collections` e `tcg_card_prices` e mostreremo le carte di maggior valore ("Top Value").

#### [MODIFY] `app/Http/Controllers/DashboardController.php`
- Aggiornamento del metodo `index()` per aggregare i prezzi correnti delle carte possedute dall'utente.
- Calcolo del "Top 5" delle carte per valore.

#### [MODIFY] `resources/views/dashboard.blade.php` (o `collezioni/mie.blade.php`)
- Creazione di Widget per il Valore Totale, Trend di mercato e la griglia "Top Cards".
- Aggiunta di UI premium con grafiche accattivanti e colori a contrasto per enfatizzare il valore.

---

### 3. Onboarding ed Empty State

Implementazione di un "Empty State" guidato per i nuovi utenti che non hanno ancora inserito carte.

#### [MODIFY] `resources/views/collezioni/mie.blade.php`
- Modifica della view per intercettare l'assenza di carte.
- Sostituzione della schermata vuota con un "Wizard" o un banner "Inizia la tua collezione" che guida l'utente a esplorare i set o cercare la prima carta.

---

### 4. Password Reset & Email Verification

Aggiunta delle rotte e controller mancanti per garantire la sicurezza base.

#### [MODIFY] `app/Models/User.php`
- Implementazione dell'interfaccia `MustVerifyEmail`.

#### [NEW] `resources/views/auth/passwords/email.blade.php` e `reset.blade.php`
- Pagine di dimenticanza e reset password, stilizzate con Tailwind.

#### [MODIFY] `routes/web.php`
- Aggiunta delle rotte `password.request`, `password.email`, `password.reset`, e `password.update`.

---

### 5. Storicizzazione Valori (Price History)

Attualmente i prezzi vengono sovrascritti. Creeremo una nuova tabella per mantenere lo storico, utile per i futuri grafici temporali.

#### [NEW] `database/migrations/xxxx_xx_xx_xxxxxx_create_tcg_card_price_history_table.php`
- Creazione della tabella `tcg_card_price_history` associata al `card_id` con timestamp per salvare i valori storici.

#### [MODIFY] `app/Models/TcgCard.php`
- Aggiunta della relazione `priceHistory()`.

#### [MODIFY] L'Import dei dati (`FetchPokemonCards` o simile descritto nel doc 04)
- Modifica per fare insert nella nuova tabella storica anziché fare un semplice `updateOrCreate` che sovrascrive, oppure l'aggiunta di un trigger in fase di import per creare la riga storica.

---

### 6. Export Collezione (CSV)

Permettere all'utente di scaricare la propria collezione.

#### [NEW] `app/Http/Controllers/ExportController.php`
- Creazione di `exportCsv()` che preleva le carte dell'utente autenticato e ritorna un download in formato `.csv`.

#### [MODIFY] `routes/web.php`
- Rotta `GET /collection/export`.

#### [MODIFY] `resources/views/collezioni/mie.blade.php`
- Aggiunta di un bottone "Esporta in CSV" (con icona download e stile in linea con il tema Vault).

---

## Verification Plan

### Automated Tests
- Non ci sono test automatizzati indicati nella documentazione, ma possiamo verificare con l'ambiente locale la generazione del CSV e le rotte protette di autenticazione.

### Manual Verification
- **Ricerca**: Ricercherò "Charizard" dalla navbar e verificherò che escano tutte le varianti in database.
- **Onboarding**: Registrerò un nuovo utente e verificherò che appaia il wizard. Aggiungerò una carta e controllerò che il wizard sparisca.
- **Dashboard Valore**: Aggiungerò una carta con prezzo noto e verificherò che la dashboard mostri l'ammontare corretto.
- **Storico Prezzi**: Simulerò l'esecuzione del comando di import per una singola carta in due momenti diversi e verificherò che esistano due record in `tcg_card_price_history`.
- **Export**: Scaricherò il CSV e ne controllerò l'integrità aprendolo.
