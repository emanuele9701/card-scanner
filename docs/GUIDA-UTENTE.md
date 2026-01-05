# 🎴 Card Scanner - Guida Utente

**Versione:** 1.1  
**Ultimo aggiornamento:** 5 Gennaio 2026

---

## 📖 Cos'è Card Scanner?

Card Scanner è un'**applicazione web gratuita** che ti aiuta a:

- 📊 **Tenere traccia della tua collezione** di carte da gioco collezionabili.
- 💰 **Monitorare il valore** delle tue carte nel tempo
- 🤖 **Riconoscere automaticamente** le carte con l'intelligenza artificiale
- 📈 **Calcolare profitti e perdite** sui tuoi investimenti
- 🎯 **Organizzare le carte** per set e condizione

> **Importante:** Questa app è pensata per gestire le **TUE carte personali**, non è un catalogo completo di tutte le carte da Gioco esistenti!

---

## 🚀 Installazione

### Requisiti di Sistema

Prima di iniziare, assicurati di avere:

- **Computer con Windows, macOS o Linux**
- **PHP 8.2 o superiore** ([Download PHP](https://www.php.net/downloads))
- **Composer** ([Download Composer](https://getcomposer.org/download/))
- **Node.js e NPM** ([Download Node.js](https://nodejs.org/))
- **MySQL o MariaDB** ([Download MySQL](https://dev.mysql.com/downloads/) o [MariaDB](https://mariadb.org/download/))

### Installazione Passo-Passo

#### 1️⃣ Scarica l'Applicazione

```bash
# Clona il repository o scarica il codice sorgente
git clone https://github.com/tuoutente/pokemon-card-scanner.git
cd pokemon-card-scanner
```

#### 2️⃣ Installa le Dipendenze

```bash
# Installa le dipendenze PHP
composer install

# Installa le dipendenze JavaScript
npm install
```

#### 3️⃣ Configura il Database

1. Crea un nuovo database MySQL:
   ```sql
   CREATE DATABASE pokemon_cards;
   ```

2. Copia il file di configurazione:
   ```bash
   cp .env.example .env
   ```

3. Apri il file `.env` e modifica le impostazioni del database:
   ```env
   DB_DATABASE=pokemon_cards
   DB_USERNAME=tuo_username
   DB_PASSWORD=tua_password
   ```

#### 4️⃣ Configura l'API Google Gemini (per il riconoscimento AI)

1. Vai su [Google AI Studio](https://aistudio.google.com/apikey)
2. Crea una nuova API key
3. Aggiungi la chiave nel file `.env`:
   ```env
   GEMINI_API_KEY=la_tua_chiave_api
   ```

#### 5️⃣ Inizializza l'Applicazione

```bash
# Genera la chiave dell'applicazione
php artisan key:generate

# Esegui le migrazioni del database
php artisan migrate

# Compila gli asset frontend
npm run build
```

#### 6️⃣ Avvia l'Applicazione

```bash
# Avvia il server di sviluppo
php artisan serve
```

L'applicazione sarà disponibile su: **http://localhost:8000**

---

## 👤 Primo Accesso

### Registrazione

1. Apri il browser e vai su `http://localhost:8000`
2. Clicca su **"Registrati"**
3. Inserisci:
   - **Email**: il tuo indirizzo email
   - **Password**: una password sicura (minimo 8 caratteri)
   - **Conferma Password**: ripeti la password
4. Clicca su **"Registrati"**

### Login

Dopo la registrazione, sarai automaticamente loggato. Per i successivi accessi:

1. Vai su `http://localhost:8000`
2. Inserisci email e password
3. Clicca su **"Accedi"**

---

## 🎯 Come Funziona

### Il Processo in 4 Step

```
1. IMPORTA PREZZI → 2. SCANSIONA CARTE → 3. COLLEGA PREZZI → 4. MONITORA VALORE
```

### 1️⃣ Importa i Dati di Mercato

Prima di aggiungere le tue carte, devi importare i **prezzi di mercato** delle carte:

1. Vai su **Market Data** (dal menu in alto)
2. Clicca su **"Importa"**
3. Carica un file JSON con i prezzi delle carte
   - Puoi ottenerlo da [TCGPlayer Price Guide](https://infinite-api.tcgplayer.com)
4. Attendi il completamento dell'importazione

> **Quando farlo?** Importa i dati **una volta per ogni set** che acquisti

### 🔒 I Tuoi Dati Sono Privati
La piattaforma è multi-utente, il che significa che ogni utente ha la propria collezione privata.
- **Tu vedi solo le tue carte**
- **Tu gestisci solo i tuoi dati di mercato**
- Nessun altro utente può vedere o modificare la tua collezione

---

### 2️⃣ Scansiona le Tue Carte

1. Clicca su **"Scansiona"** nel menu (icona fotocamera)
2. **Carica le foto** delle tue carte:
   - Trascina le immagini nell'area di upload, oppure
   - Clicca per selezionare i file
3. **Ritaglia** ogni carta (opzionale):
   - Clicca "Ritaglia" per inquadrare meglio la carta
   - Oppure "Salta Ritaglio" per usare l'immagine originale
   - 🗑️ **Elimina**: Se la foto non va bene, puoi eliminarla subito cliccando l'icona cestino
4. **Riconosci con AI**:
   - Clicca l'icona "bacchetta magica" 🪄
   - L'AI estrarrà automaticamente tutti i dati della carta
   - Anche in questa fase puoi eliminare la carta se necessario
5. **Verifica e Salva**:
   - Controlla che i dati siano corretti
   - Seleziona il **Set** di appartenenza
   - Indica la **Condizione** (Near Mint, Lightly Played, etc.)
   - Clicca **"Salva"**

> 🧹 **Reset Galleria**: Il pulsante "Svuota Tutto" elimina **definitivamente** tutte le carte visualizzate, rimuovendole sia dal database che dallo spazio di archiviazione fisico. Usalo con cautela!

### 3️⃣ Collega le Carte ai Prezzi

Dopo aver aggiunto le carte, devi collegarle ai dati di mercato:

1. Vai su **"Matching"** nel menu
2. Clicca **"Auto-Match"** per il matching automatico
3. Per le carte non riconosciute:
   - Clicca sulla carta
   - Seleziona la corrispondenza corretta dai suggerimenti
   - Conferma il match

### 4️⃣ Monitora il Valore

Visualizza il valore della tua collezione:

1. Vai su **"Valore"** nel menu
2. Vedrai:
   - **Valore Corrente** totale della collezione
   - **Prezzo di Acquisto** totale
   - **Profitto/Perdita** (P&L) in euro e percentuale
   - **Tabella dettagliata** con ogni carta

---

## 💡 Consigli Pratici

### Per Foto Migliori

- ✅ Usa **buona illuminazione** naturale
- ✅ Sfondo **uniforme** (bianco o nero)
- ✅ Evita **riflessi** sulla carta
- ✅ Tieni la fotocamera **parallela** alla carta

### Per Migliori Risultati AI

- ✅ Carta in **inglese** (funziona meglio)
- ✅ Foto **nitida** e non sfocata
- ✅ Inquadra **solo la carta**, non il tavolo
- ✅ Se l'AI sbaglia, puoi sempre **modificare manualmente**

### Gestione Collezione

- 📁 **Organizza per set**: assegna sempre il set corretto
- 🏷️ **Indica la condizione**: importante per il valore
- 🔄 **Aggiorna i prezzi**: importa nuovi dati periodicamente
- 🔍 **Usa la ricerca**: trova rapidamente le carte

---

## 📱 Funzionalità Principali

### Collezione

Visualizza tutte le tue carte organizzate per set:

- **Espandi/Comprimi** i set cliccando sull'intestazione
- **Visualizza dettagli**: clicca sull'icona occhio 👁️
- **Modifica carta**: clicca sull'icona matita ✏️
- **Elimina carta**: clicca sull'icona cestino 🗑️
- **Zoom schermo intero**: clicca sull'immagine

### Selezione Multipla

Per gestire più carte insieme:

1. ✅ Spunta le checkbox sulle carte
2. Apparirà una **barra in basso**
3. Clicca **"Assegna Set"** per assegnare tutte le carte a un set
4. Oppure **"Conferma Selezionati"** per salvare multiple carte
5. Oppure **"Analizza Selezionati"** per lanciare l'AI su multiple carte

#### 🛡️ Protezione Chiusura Accidentale
Se provi a chiudere la scheda o il browser mentre ci sono carte caricate ma non ancora salvate (stato "Pending" o "Ready"):
- Il browser ti mostrerà un **avviso di conferma** per evitare la perdita accidentale.
- Se confermi l'uscita, il sistema tenterà automaticamente di **eliminare le carte temporanee** dal server per non lasciare file "orfani" (immagini caricate ma non salvate).
- **Nota:** Si consiglia comunque di usare il pulsante "Svuota Tutto" o eliminare manualmente le carte prima di uscire per garantire la pulizia completa.

### Profilo Utente

- Clicca sul tuo **avatar** in alto a destra
- Vai a **"Il Mio Profilo"** per:
  - Modificare nome e dati personali
  - Cambiare password
- **"Esci"** per disconnetterti

---

## ❓ Domande Frequenti

### Come ottengo i file JSON dei prezzi?

I prezzi provengono dall'API di TCGPlayer. Puoi:
- Andare su [TCGPlayer Infinite API](https://infinite-api.tcgplayer.com)
- Cercare il set desiderato nel Price Guide
- Copiare/scaricare i dati in formato JSON

### L'AI può sbagliare?

Sì, l'intelligenza artificiale non è perfetta. Controlla sempre i dati e modifica se necessario.

### Posso usare l'app senza internet?

No, l'AI richiede connessione internet. Anche i prezzi di mercato vengono scaricati da internet.

### Quanto costa?

L'applicazione è **completamente gratuita** e open source! Devi solo pagare per:
- La tua API key di Google Gemini (ha un piano gratuito generoso)
- Eventualmente hosting se vuoi metterla online

### Posso condividere con amici?

Sì! Ogni utente ha la sua collezione privata. Puoi installare l'app su un server e condividerla con altri collezionisti.

---

## 🔧 Problemi Comuni

### "L'AI non riconosce la carta"

**Soluzione:** 
- Verifica la qualità della foto
- Riprova con illuminazione migliore
- Usa l'inserimento manuale dati

### "Il matching non trova corrispondenze"

**Soluzione:**
- Verifica di aver importato i dati del set corretto
- Controlla il numero carta (formato es. "063/094")
- Usa il match manuale

### "Errore di connessione al database"

**Soluzione:**
- Verifica che MySQL sia avviato
- Controlla le credenziali nel file `.env`
- Verifica che il database esista

### "Schermata bianca dopo l'installazione"

**Soluzione:**
```bash
# Pulisci la cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Ricompila gli asset
npm run build
```

---

## 🆘 Supporto

Se hai problemi o domande:

1. Controlla questa guida
2. Verifica i **log degli errori** in `storage/logs/laravel.log`
3. Apri una **issue** su GitHub
4. Contatta il supporto via email

---

## 🎉 Buon Collezionismo!

Ora sei pronto per gestire la tua collezione! 

**Ricorda:**
- 📸 Fai sempre foto di qualità
- 💾 Salva regolarmente i dati
- 🔄 Aggiorna i prezzi periodicamente
- 🎴 Divertiti a collezionare!

---

**Developed with ❤️ for collectors**
