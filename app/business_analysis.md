# 🎯 Card Scanner — Analisi Strategica di Prodotto

> **Ruolo**: Senior Product Manager + Business Strategist (Technical PM)  
> **Data**: 29 Maggio 2026  
> **Basata su**: Documentazione tecnica completa (10 documenti)

---

## Sintesi Esecutiva

Card Scanner è un'app di gestione collezioni Pokémon TCG con dati da TCGdex, interfaccia web Blade/Tailwind e API REST. Il prodotto ha un **backend solido e ambizioso** ma presenta **lacune critiche lato utente** che ne compromettono l'adozione.

---

## 1. 🔴 COSA MANCA LATO UTENTE (Gap Analysis)

### Critici — Bloccanti per il lancio

| Gap | Impatto | Dettaglio |
|-----|---------|-----------|
| **Nessun onboarding / empty state** | 🔴 Altissimo | L'utente arriva, vede una dashboard vuota. Nessun wizard, nessun suggerimento, nessuna call-to-action per aggiungere la prima carta. Il 70% dei nuovi utenti abbandona entro 30 secondi se non capisce cosa fare. |
| **Nessuna ricerca globale di carte** | 🔴 Altissimo | Per aggiungere una carta devo navigare Serie → Set → Carta. Non c'è una barra di ricerca "cerca Charizard" che mi porti direttamente alla carta. Per un utente medio è inaccettabile. |
| **Nessuna Dashboard con il "valore della collezione"** | 🔴 Alto | Avete i dati dei prezzi nel DB, ma dalla documentazione non esiste un widget/pagina che dica all'utente: "La tua collezione vale €X". Questo è il **numero 1** che un collezionista vuole vedere. |
| **Nessun social / community** | 🟠 Medio-Alto | Nessuna wishlist pubblica, nessun marketplace P2P, nessuna possibilità di condividere la propria collezione. Il collezionismo è intrinsecamente sociale. |

### Importanti — Differenziano un prodotto buono da uno eccellente

| Gap | Impatto | Dettaglio |
|-----|---------|-----------|
| **Nessun scanner fisico (il nome è "Card Scanner")** | 🟠 Medio-Alto | Il prodotto si chiama "Card Scanner" ma **non scannerizza nulla**. Non c'è OCR, non c'è riconoscimento visuale, non c'è scan via fotocamera. L'utente deve cercare la carta manualmente. Il nome promette qualcosa che il prodotto non mantiene — un problema serio di trust e aspettativa. |
| **Export / Import collezione assente** | 🟠 Medio | Nessun CSV/PDF/Excel export. Nessun import da altre piattaforme (TCGPlayer, Pokellector, Dex, ecc.). È un lock-in percepito negativamente. |
| **Nessun grafico storico dei prezzi** | 🟠 Medio | I prezzi vengono ri-creati ad ogni import (doc 04), quindi **lo storico viene perso**. Non c'è una timeseries. Non si può fare un grafico "andamento prezzo nel tempo". Grave per chi vuole comprare/vendere al momento giusto. |
| **Nessun sistema di "Deck Building"** | 🟡 Medio | I giocatori competitivi non sono solo collezionisti. La possibilità di creare mazzi dalle carte possedute è una feature attesa nel segmento. |
| **Password reset / email verification assenti** | 🟠 Medio | Dall'auth doc: nessun endpoint per reset password, nessuna verifica email (`email_verified_at: null`). È un rischio sicurezza e una barriera per utenti reali. |
| **Nessun profilo utente** | 🟡 Basso-Medio | L'utente può solo cambiare la lingua. Nessun avatar, nessuna bio, nessuna statistica pubblica del profilo. |

### Nice-to-have — Per la v2

| Gap | Dettaglio |
|-----|-----------|
| **Supporto multi-TCG** | Attualmente solo Pokémon. Magic, Yu-Gi-Oh!, One Piece TCG sono mercati enormi. L'architettura (prefisso `tcg_*`) sembra predisposta. |
| **PWA / App nativa** | L'API REST c'è, ma nessun client mobile nativo o PWA. L'esperienza mobile è essenziale per scansioni "on the go". |
| **Gamification** | Badge, achievement per completamento set, leaderboard. I collezionisti sono competitivi per natura. |

---

## 2. 💎 IL POTENZIALE DI DIFFERENZIAZIONE

### Feature Differenzianti Chiave

| Feature | Angolo di Marketing |
|---------|---------------------|
| **Dati e statistiche TCGdex** | "Scopri tutto sulle tue carte con un catalogo costantemente aggiornato" |
| **Foto reali delle carte** (Spatie Media) | "Documenta la condizione esatta delle tue carte con foto" — utile per assicurazioni, vendita, contestazioni |
| **Multi-lingua nativa** | Vantaggio sul mercato italiano (la maggior parte dei competitor è solo in inglese) |
| **% completamento set** | Gamification naturale: "Sei al 73% del Set Base!" |

---

## 3. 📋 ROADMAP DI PRIORITÀ

### 🟢 CORE MVP — Lanciare con queste (Sprint 1-3)

> Queste feature sono il **minimo** per un lancio credibile.

| # | Feature | Effort | Perché è core |
|---|---------|--------|---------------|
| 1 | **Ricerca globale carte** (barra di ricerca con autocomplete) | Medio | Senza questo, l'app è inutilizzabile per l'utente medio |
| 2 | **Dashboard valore collezione** (widget con valore totale, trend, top carte per valore) | Medio | È il motivo #1 per cui un utente torna ogni giorno |
| 3 | **Onboarding / Empty State** (wizard "aggiungi la tua prima carta", suggerimenti set popolari) | Basso | Senza, il tasso di abbandono al primo uso sarà altissimo |
| 4 | **Password Reset + Email Verification** | Basso | Requisito base di sicurezza per qualsiasi app con account |
| 5 | **Storicizzazione valori** (non sovrascrivere, appendere con timestamp) | Medio | Senza, perdete storico prezioso |
| 6 | **Export collezione** (CSV/PDF) | Basso | Feature attesa, costo implementativo minimo |

### 🟡 VERSIONE 1.1 — Subito dopo il lancio (Sprint 4-6)

| # | Feature | Effort | Perché qui |
|---|---------|--------|------------|
| 7 | **Alert completezza** (notifica email/push per traguardi completamento) | Medio | Alto valore percepito, fidelizzazione |
| 8 | **Grafici andamento collezione** (timeseries chart per statistiche) | Medio | Dipende dalla storicizzazione (punto 5) |
| 9 | **Scanner fotocamera** (OCR/image recognition per aggiungere carte) | Alto | Il nome del prodotto lo promette. Serve per la credibilità. |
| 10 | **Wishlist** (lista carte desiderate) | Basso | Precursore della condivisione social e del marketplace |
| 11 | **Import da altre piattaforme** (CSV generico + formati TCGPlayer/Pokellector) | Medio | Riduce la barriera di migrazione |

### 🔵 VERSIONE 2.0 — Scala e monetizzazione (Sprint 7+)

| # | Feature | Effort | Note |
|---|---------|--------|------|
| 12 | **Multi-TCG** (Magic, Yu-Gi-Oh!, One Piece) | Molto Alto | Moltiplica il TAM per 5x |
| 13 | **Marketplace P2P** | Molto Alto | Monetizzazione diretta (commissioni su vendita) |
| 14 | **Collezione pubblica + Social** | Alto | Viralità organica, effetto rete |
| 15 | **PWA / App nativa** | Alto | Necessario per lo scanning mobile |
| 16 | **Deck Builder** | Medio | Espande dal collezionista al giocatore |
| 17 | **Gamification** (badge, achievement, leaderboard) | Medio | Retention a lungo termine |

### ❌ SUPERFLUO — Rimuovere o rimandare a data indefinita

| Feature attuale | Perché è superflua ora |
|-----------------|------------------------|
| **i18n completa (IT/EN)** già implementata | Prematura. Prima validate il prodotto con un mercato (IT). L'internazionalizzazione ha costo di manutenzione continuo su ogni nuova feature. **Non è sbagliata**, ma è effort speso prima del product-market fit. |
| **API REST completa** (con Sanctum, token multipli) | Se non c'è un'app mobile o un client esterno che la consuma, è over-engineering. Decidete: web-first o API-first? Entrambe in parallelo con un team piccolo è dispersione di risorse. |

---

## 4. ⚠️ RISCHI DI ADOZIONE

### 🔴 Rischi Alti

| Rischio | Descrizione | Mitigazione |
|---------|-------------|-------------|
| **Il nome promette, il prodotto non mantiene** | "Card Scanner" implica scanning fisico. L'utente scarica, cerca il bottone "Scan", non lo trova, disinstalla. | Rinominare il prodotto **oppure** implementare lo scanning come feature prioritaria. Non c'è via di mezzo. |
| **Complessità del primo utilizzo** | L'utente deve: registrarsi → navigare Serie → trovare Set → trovare Carta → aggiungerla manualmente. Sono **5+ step** per aggiungere una carta. Un competitor con scan richiede 1 step. | Ricerca globale + Quick Add + (eventualmente) Scanner. |
| **Dati non aggiornati** | L'import (`app:fetch-pokemon`) è manuale. Se non viene eseguito regolarmente, l'utente vede dati vecchi e perde fiducia. Nessun cron/scheduler documentato. | Implementare uno scheduler automatico (Laravel Task Scheduling) con aggiornamento almeno settimanale. |

### 🟠 Rischi Medi

| Rischio | Descrizione | Mitigazione |
|---------|-------------|-------------|
| **Lock-in percepito** | Nessun export → l'utente ha paura di investire tempo in una piattaforma da cui non può uscire. | Export CSV/PDF come feature day-1. |
| **Nessun feedback loop** | Nessun sistema di feedback, rating, o analytics sull'utilizzo. Non saprete mai perché gli utenti abbandonano. | Integrare analytics basici (Plausible/PostHog). Aggiungere un NPS prompt dopo 7 giorni di uso. |

### 🟡 Rischi Bassi (ma da monitorare)

| Rischio | Descrizione |
|---------|-------------|
| **Solo web, nessun mobile** | I collezionisti operano spesso "in campo" (fiere, negozi, scambi). Senza mobile, perdete il contesto d'uso principale. |
| **Solo Pokémon** | Il mercato è grande, ma limitarsi a un TCG è rischioso se un competitor generalista emerge. |
| **Blade monolitico** | L'architettura frontend è server-rendered con AJAX sprinkled. Limita la reattività e la UX moderna. Per la v2 considerare un frontend SPA (Vue/React) che consumi l'API. |

---

## Conclusione e Raccomandazione Prioritaria

> [!IMPORTANT]
> **I 2 interventi a massimo ROI prima del lancio:**
> 1. **Ricerca globale + Quick Add** → Riduce i step da 5+ a 1-2
> 2. **Dashboard Utente e Valore** → Dà il motivo per tornare ogni giorno

> [!WARNING]
> **Il rischio esistenziale:** Il nome "Card Scanner" senza uno scanner è un problema di credibilità che nessuna feature risolverà. O rinominate, o implementate lo scanner. Questa decisione va presa **prima** di qualsiasi attività di marketing.
