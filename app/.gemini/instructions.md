# Card Scanner — Istruzioni per l'Agente AI

## Descrizione del Progetto

**Card Scanner** è un'applicazione web per la gestione di collezioni di carte Pokémon TCG (Trading Card Game). Permette agli utenti di:

- Esplorare l'intero catalogo di carte Pokémon TCG (importato dall'API TCGdex)
- Gestire la propria collezione personale (aggiungere/rimuovere carte con condizione, varianti, foto)
- Consultare i prezzi di mercato da Cardmarket tramite scraping
- Trovare i migliori venditori per completare i propri set
- Visualizzare statistiche sulla collezione (valore totale, percentuale completamento per set)

La documentazione dettagliata del progetto si trova nella cartella `docs/`.

---

## Tech Stack

| Layer | Tecnologia |
|---|---|
| **Backend** | Laravel 13.x, PHP 8.3+ |
| **Database** | MariaDB |
| **Auth API** | Laravel Sanctum (Bearer Token) |
| **Auth Web** | Session-based (Laravel default) |
| **Media/Foto** | Spatie Laravel Media Library |
| **Dati TCG** | TCGdex PHP SDK |
| **Scraping** | Puppeteer + stealth plugin (Node.js), DiDOM (PHP HTML parser) |
| **Frontend** | Blade templates, Tailwind CSS v4 |
| **Build** | Vite 8, laravel-vite-plugin |
| **Icone** | FontAwesome |
| **i18n** | Italiano (default) + Inglese |

---

## Architettura e Struttura

### Struttura delle directory principali

```
app/
├── Console/Commands/     # Comandi Artisan (import dati, scraping, best sellers)
├── Enums/                # UrlMappingStatus, UrlMappingType
├── Helpers/              # CardMarketParser (parsing HTML Cardmarket)
├── Http/
│   ├── Controllers/      # Controller API e Web separati
│   ├── Middleware/        # SetLocale per i18n
│   └── Requests/         # Form Request validation
├── Models/               # Eloquent models con relazioni
└── Providers/
resources/views/
├── layouts/              # Layout app.blade.php e guest.blade.php
├── auth/                 # Login e registrazione
├── collezioni/           # Viste collezioni (principale, set detail, mancanti)
│   ├── partials/         # Partial views per AJAX
│   └── singles/
├── partials/             # Modal globali (card detail, gestione collezione)
├── dashboard.blade.php
└── settings.blade.php
routes/
├── api.php               # REST API con Sanctum
├── web.php               # Route web con middleware auth/guest
└── console.php           # Comandi schedulati (fetch giornaliero alle 00:34)
```

### Pattern architetturali da rispettare

1. **Dual Auth** — API e Web hanno sistemi di autenticazione separati:
   - API: `AuthController` con Sanctum Bearer Token
   - Web: `AuthWebController` con sessioni Laravel
   - Non mischiare mai i due sistemi.

2. **AJAX Partial Views** — Le griglie di carte usano partial views caricate via AJAX. Non ricaricare mai la pagina intera per filtraggio/paginazione nelle viste collezione.

3. **Separazione Controller API/Web** — I controller API (es. `UserCardCollectionController`) e Web (es. `CollezioniController`) sono separati. Non unirli.

4. **Formato risposta API standard** — Tutte le risposte API devono seguire il formato:
   ```json
   { "success": true/false, "data": ..., "message": "...", "meta": ... }
   ```

5. **Spatie Media Library** — Le foto delle carte usano Spatie Media Library con conversioni automatiche:
   - `thumb`: 200×200
   - `preview`: 600×800
   - Formati accettati: JPEG, PNG, WebP (max 5MB)

---

## Convenzioni di Codice

### Database e Models

- **Primary Key**: Sempre `bigint` auto-increment. Gli ID stringa di TCGdex (es. `serie_id`, `set_id`, `card_id`) vanno in colonne dedicate, NON come chiave primaria.
- **Multi-lingua**: Tutte le tabelle TCG hanno una colonna `language` (`it`, `en`). Constraint UNIQUE combinato con l'ID + lingua.
- **JSON Cast**: Usare il cast `array` per i campi JSON (variants, types, cost, abbreviation).
- **Enum Cast**: Usare PHP Enums per i campi status/type (vedi `UrlMappingStatus`, `UrlMappingType`).
- **Relazioni**:
  ```
  User → hasMany → UserCardCollection, UserSetting
  TCGSeries → hasMany → TCGSet
  TCGSet → hasMany → TCGCard
  TCGCard → hasMany → TCGCardAbility, TCGCardPrice, TCGCardOffer, UserCardCollection
  UserCardCollection → morphMany → Media (Spatie)
  ```

### Controller e Validation

- Usare **Form Request** per la validazione (es. `StoreUserCardRequest`, `UpdateUserCardRequest`).
- Il controller più complesso è `CollezioniController` (~29KB): gestisce browsing set, griglie AJAX, gestione copie, carte mancanti, tab migliori venditori.
- Ogni nuovo endpoint API va protetto con il middleware `auth:sanctum`.

### Frontend (Blade + Tailwind)

- Il progetto usa un **tema dark personalizzato "Vault"** con CSS variables.
- Le classi CSS custom per le carte seguono la convenzione `.coll-card`.
- Le animazioni custom (blob, dropdown) sono definite nel CSS.
- I **modal** sono componenti riutilizzabili in `partials/` (card detail, gestione collezione, carte mancanti).
- **Entry points Vite**: `resources/css/app.css` + `resources/js/app.js`.

### Internazionalizzazione (i18n)

- La lingua di default è **Italiano** (`it`). Le chiavi di traduzione sono già in italiano.
- I file di traduzione sono in `lang/it.json` e `lang/en.json` (~120 voci).
- In Blade: tutto il testo hardcoded deve essere wrappato con `{{ __('testo') }}`.
- In JavaScript: usare l'oggetto globale `window.__trans` iniettato da `app.blade.php`.
- La preferenza lingua è salvata nella tabella `user_settings`.

---

## Comandi di Sviluppo

```bash
# Avviare l'ambiente di sviluppo (server + queue + vite in parallelo)
composer dev

# Setup iniziale
composer setup

# Import dati da TCGdex (eseguito anche automaticamente ogni giorno alle 00:34)
php artisan app:fetch-pokemon

# Scraping URL liste da Cardmarket
php artisan app:scraper-run

# Scraping singole carte (offerte venditori)
php artisan app:scrape-url-single-card

# Analisi migliori venditori
php artisan app:find-best-sellers

# Test
php artisan test
# oppure
./vendor/bin/phpunit
```

---

## Scraping — Note Importanti

- Il sistema di scraping usa la tabella `url_mappings` come coda di lavoro con stati: `pending → scraping → done/failed`.
- Lo scraping usa **Puppeteer con stealth plugin** (Node.js) eseguito da PHP tramite `Process::run()`.
- Il parsing HTML è fatto da `CardMarketParser` usando DiDOM.
- **Anti-bot**: sleep random 5-10 secondi tra le richieste.
- **Concorrenza sicura**: uso di `lockForUpdate()` per evitare race condition su job paralleli.
- I dati scrapati vengono salvati in `tcg_card_offers` + backup JSON in `storage/app/cards/`.

---

## Regole e Divieti

### ✅ Fai sempre
- Consulta la documentazione in `docs/` prima di modificare componenti esistenti.
- Mantieni la separazione API/Web per controller e autenticazione.
- Usa Form Request per la validazione degli input.
- Wrappa ogni nuovo testo UI con `{{ __('...') }}` e aggiungi la traduzione in `lang/en.json`.
- Segui il formato risposta API standard (`success`, `data`, `message`, `meta`).
- Usa le migrazioni Laravel per ogni modifica al database.
- Mantieni il tema dark "Vault" per le nuove UI.

### ❌ Non fare mai
- Non usare gli ID stringa di TCGdex come primary key del database.
- Non mescolare autenticazione Sanctum (API) con sessioni (Web) nello stesso controller.
- Non fare reload completo della pagina dove attualmente si usano partial views AJAX.
- Non aggiungere dipendenze npm/composer senza motivo documentato.
- Non scrivere testo hardcoded in italiano senza wrapparlo con `__()`.
- Non modificare la struttura delle tabelle TCG senza verificare l'impatto sulle relazioni multi-lingua.
- Non eseguire scraping senza i meccanismi anti-bot (sleep, stealth plugin).
