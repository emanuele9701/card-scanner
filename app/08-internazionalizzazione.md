# 📚 Internazionalizzazione (i18n) – IT/EN

## Obiettivo
Implementare il supporto multilingua (Italiano/English) per tutta l'interfaccia frontend, basato sulle preferenze dell'utente memorizzate in `user_settings.language`.

## Componenti principali

1. **Middleware `SetLocale`** (`app/Http/Middleware/SetLocale.php`)
   - Legge il valore `language` dall'utente autenticato (`Auth::user()->settings->where('key', 'language')->first()?->value`).
   - Se presente, imposta la locale con `App::setLocale($locale)`, altrimenti usa la locale di fallback (`config('app.fallback_locale')`).
   - Registrato nel gruppo `web` del file `bootstrap/app.php`.

2. **File di traduzione** (`lang/it.json` & `lang/en.json`)
   - `it.json` è vuoto perché le chiavi sono già in italiano (Laravel utilizza la chiave stessa). 
   - `en.json` contiene le traduzioni inglesi per tutte le stringhe UI (≈ 120 voci). Esempio:
   ```json
   {
       "Le mie collezioni": "My Collections",
       "Visualizza e gestisci le tue collezioni di carte.": "View and manage your card collections.",
       "VALORE TOTALE": "TOTAL VALUE",
       "CARTE TOTALI": "TOTAL CARDS",
       "Caricamento...": "Loading...",
       "Invio...": "Sending...",
       "Aggiunta!": "Added!",
       "Errore": "Error",
       "Accedi": "Login",
       "Impostazioni": "Settings",
       "Lingua preferita aggiornata.": "Preferred language updated."
   }
   ```

3. **Blade Views**
   - Tutti i testi hard‑coded sono stati avvolti con la funzione di traduzione Blade `{{ __('...') }}`.
   - Aggiornate ~15 view (`app.blade.php`, `dashboard.blade.php`, `collezioni/*.blade.php`, `partials/*.blade.php`, `auth/login.blade.php`, ecc.).
   - I titoli delle pagine e i meta description ora usano `__('...')`.

4. **Traduzioni JavaScript**
   - Inserito nello `layouts/app.blade.php` l'oggetto globale `window.__trans` con le stringhe tradotte tramite `@json(__('...'))`.
   - I modali, i `confirm()` e gli `alert()` in Blade ora usano `window.__trans.<key>`.

5. **Configurazione locale di default**
   - `config/app.php` modificato: `locale` e `fallback_locale` impostati a `it`.

## Come testare
1. Accedi a **Impostazioni** → seleziona **English**.
2. Tutte le pagine (dashboard, collezioni, modali, login) mostrano i testi in inglese.
3. Cambia nuovamente a **Italiano** per verificare il ritorno alla lingua originale.

## Estensioni future
- Aggiungere nuove chiavi in `lang/en.json` quando vengono introdotti nuovi testi.
- Supportare ulteriori lingue creando nuovi file `lang/<locale>.json`.
- Considerare l'uso di `Accept-Language` header per gli utenti non autenticati (login page).
