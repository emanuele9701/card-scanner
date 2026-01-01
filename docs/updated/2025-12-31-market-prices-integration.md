# Analisi di Impatto - Integrazione Prezzi di Mercato

**Data:** 31 Dicembre 2025  
**Autore:** System Analysis  
**Versione:** 1.0

## Sommario Esecutivo

Questa analisi valuta l'impatto dell'introduzione di un sistema di tracciamento prezzi di mercato per le carte Pokemon, con storicizzazione dei prezzi e correlazione con la collezione personale dell'utente.

## 1. Panoramica della Modifica

### 1.1 Obiettivi
- Salvare i dati di mercato delle carte Pokemon da fonti esterne (es. TCGPlayer)
- Mantenere uno storico dei prezzi per ogni carta
- Collegare i prezzi di mercato alle carte della collezione personale
- Aggiungere la selezione del set/collezione durante la scansione delle carte

### 1.2 Fonte Dati
Il JSON fornito contiene dati di mercato con la seguente struttura:
```json
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
  "type": "Cards"
}
```

## 2. Analisi di Impatto sul Database

### 2.1 Nuove Tabelle Richieste

#### A) `market_cards` - Catalogo Carte di Mercato
**Scopo:** Memorizzare tutte le carte disponibili sul mercato con i loro dettagli

**Impatto:**
- ✅ Nessun conflitto con tabelle esistenti
- ⚠️ Potenzialmente grande volume di dati (migliaia di carte)
- ✅ Normalizzazione dei dati di mercato

**Campi:**
```
- id (PK)
- product_id (UNIQUE)
- product_name
- card_number
- set_name
- set_abbreviation
- rarity
- type
- game
- is_supplemental
- created_at
- updated_at
```

#### B) `market_prices` - Storico Prezzi di Mercato
**Scopo:** Tracciare l'evoluzione dei prezzi nel tempo per ogni carta e condizione

**Impatto:**
- ⚠️ Crescita rapida dei dati (ogni import crea nuovi record)
- ✅ Permette analisi di trend
- 📊 Necessità di strategia di archiviazione/pulizia

**Campi:**
```
- id (PK)
- market_card_id (FK → market_cards)
- condition
- printing
- low_price
- market_price
- sales_count
- import_date
- created_at
```

#### C) `card_sets` - Collezioni/Set di Carte
**Scopo:** Normalizzare le informazioni sui set di carte

**Impatto:**
- ✅ Migliora l'organizzazione dei dati
- ✅ Facilita il filtraggio e la ricerca
- ⚠️ Richiede refactoring della tabella `pokemon_cards`

**Campi:**
```
- id (PK)
- name
- abbreviation
- release_date (nullable)
- total_cards (nullable)
- created_at
- updated_at
```

### 2.2 Modifiche a Tabelle Esistenti

#### `pokemon_cards` (Collezione Personale)
**Modifiche necessarie:**

1. **Aggiunte:**
   - `card_set_id` (FK → card_sets) - Riferimento al set della carta
   - `market_card_id` (FK → market_cards, nullable) - Link ai dati di mercato
   - `condition` (enum: Near Mint, Lightly Played, Moderately Played, Heavily Played, Damaged) - Condizione della carta
   - `printing` (enum: Normal, Reverse Holofoil, Holofoil) - Tipo di stampa
   - `acquisition_price` (decimal, nullable) - Prezzo di acquisto
   - `acquisition_date` (date, nullable) - Data di acquisto

2. **Impatto:**
   - ✅ Arricchisce i dati della collezione personale
   - ⚠️ Richiede migrazione dati esistenti
   - ✅ Permette valutazione automatica della collezione

## 3. Analisi di Impatto sul Codice

### 3.1 Nuovi Modelli Eloquent

#### A) `MarketCard`
**Scopo:** Gestione carte di mercato

**Relazioni:**
- `hasMany` → MarketPrice
- `hasMany` → PokemonCard (collezione personale)

#### B) `MarketPrice`
**Scopo:** Gestione storico prezzi

**Relazioni:**
- `belongsTo` → MarketCard

#### C) `CardSet`
**Scopo:** Gestione set di carte

**Relazioni:**
- `hasMany` → PokemonCard
- `hasMany` → MarketCard (tramite nome set)

### 3.2 Modifiche a Modelli Esistenti

#### `PokemonCard`
**Modifiche:**
```php
// Nuove relazioni
public function marketCard(): BelongsTo
public function cardSet(): BelongsTo
public function currentMarketPrice(): HasOneThrough

// Nuovi metodi
public function getEstimatedValue(): float
public function getMarketPriceByCondition(string $condition): ?float
```

**Impatto:**
- ✅ Mantiene retrocompatibilità
- ✅ Aggiunge funzionalità di valutazione
- ⚠️ Necessità di gestire carte senza prezzo di mercato

### 3.3 Nuovi Controller

#### A) `MarketDataController`
**Responsabilità:**
- Import dati JSON di mercato
- Aggiornamento prezzi
- Visualizzazione storico prezzi

**Impatto:**
- ✅ Separazione delle responsabilità
- ⚠️ Necessità di gestione errori import
- 📊 Necessità di validazione dati JSON

#### B) `CardSetController`
**Responsabilità:**
- Gestione CRUD dei set
- Ricerca e filtri per set

**Impatto:**
- ✅ Nuova funzionalità organizzativa
- ⚠️ Necessità di interfaccia UI dedicata

### 3.4 Modifiche a Controller Esistenti

#### `PokemonCardController`
**Modifiche necessarie:**
```php
// Metodo store() - aggiungere selezione set
public function store(Request $request)
{
    // + Validazione card_set_id
    // + Validazione condition
    // + Validazione printing
    // + Link opzionale a market_card_id
}

// Nuovo metodo per valutazione
public function getCollectionValue()
{
    // Calcola valore totale della collezione
}
```

**Impatto:**
- ⚠️ Modifiche ai form di inserimento
- ⚠️ Aggiornamento validazione
- ✅ Nuove funzionalità di reportistica

## 4. Analisi di Impatto sull'Interfaccia Utente

### 4.1 Modifiche Necessarie

#### A) Form di Scansione Carte
**Cambiamenti:**
- ➕ Dropdown selezione Set/Collezione (obbligatorio)
- ➕ Dropdown selezione Condizione (es. Near Mint)
- ➕ Dropdown selezione Printing (es. Normal, Holofoil)
- 🔄 Ricerca automatica carta su database mercato

**Impatto:**
- ⚠️ Form più complesso
- ✅ Maggiore accuratezza dati
- ⚠️ Necessità di UX intuitiva

#### B) Visualizzazione Collezione
**Cambiamenti:**
- ➕ Colonna "Valore Stimato" per ogni carta
- ➕ Badge con condizione della carta
- ➕ Indicatore tipo stampa
- ➕ Filtri per set/collezione
- 🔄 Dashboard con valore totale collezione

**Impatto:**
- ✅ Informazioni più ricche
- ⚠️ Necessità di ottimizzazione query
- ✅ Maggiore utilità per l'utente

#### C) Nuove Sezioni
**Da creare:**
1. **Market Prices Dashboard**
   - Importazione dati JSON
   - Visualizzazione trend prezzi
   - Comparazione prezzi collezione vs mercato

2. **Set Management**
   - Lista set disponibili
   - Filtri e ricerche

**Impatto:**
- ✅ Nuove funzionalità di valore
- ⚠️ Aumento complessità navigazione
- 📊 Necessità di design coherente

## 5. Analisi delle Dipendenze

### 5.1 Dipendenze del Sistema

#### Import Dati
**Dipendenze:**
- Formato JSON stabile da fonte esterna
- Periodicità aggiornamenti (giornaliero/settimanale?)
- Dimensione file JSON (gestione memoria)

**Rischi:**
- ⚠️ Cambio formato JSON fonte
- ⚠️ Disponibilità fonte dati
- ⚠️ Performance import con grandi volumi

#### Matching Carte
**Sfide:**
- Matching automatico collezione ↔ mercato
  - Basato su: numero carta + set + nome
- Gestione carte non presenti nel catalogo mercato
- Gestione varianti (stampe diverse)

**Rischi:**
- ⚠️ False matching
- ⚠️ Carte orfane (senza prezzo)
- ⚠️ Gestione edizioni speciali

### 5.2 Performance

#### Query Database
**Impatto previsto:**
```sql
-- Esempio query complessa
SELECT 
    pc.*,
    cs.name as set_name,
    mp.market_price,
    mp.condition
FROM pokemon_cards pc
LEFT JOIN card_sets cs ON pc.card_set_id = cs.id
LEFT JOIN market_cards mc ON pc.market_card_id = mc.id
LEFT JOIN market_prices mp ON mc.id = mp.market_card_id
    AND mp.import_date = (
        SELECT MAX(import_date) 
        FROM market_prices 
        WHERE market_card_id = mc.id
    )
WHERE pc.user_id = ?
```

**Considerazioni:**
- ⚠️ Necessità di indici appropriati
- ⚠️ Possibile lentezza con collezioni grandi
- 📊 Necessità di cache per dashboard

#### Storage
**Proiezioni:**
- `market_cards`: ~5000-10000 record (stabile)
- `market_prices`: Crescita continua
  - Per import: ~5000 record x condizioni (4-7 per carta) = ~25000-35000 record/import
  - Annuale: ~9M-12M record/anno (import settimanale)

**Raccomandazioni:**
- 📊 Implementare strategia di archiviazione
- 📊 Mantenere solo ultimi N mesi di prezzi in tabella principale
- 📊 Archiviare storico in tabella separata

## 6. Rischi e Mitigazioni

### 6.1 Rischi Tecnici

| Rischio | Probabilità | Impatto | Mitigazione |
|---------|-------------|---------|-------------|
| Dati JSON corrotti durante import | Media | Alto | Validazione completa, rollback automatico |
| Performance deteriorata con storico grande | Alta | Medio | Indici, cache, archiviazione |
| Matching carte errato | Media | Alto | Algoritmo matching robusto, review manuale |
| Fonte dati non disponibile | Bassa | Alto | Multiple fonti, fallback, notifiche |

### 6.2 Rischi Funzionali

| Rischio | Probabilità | Impatto | Mitigazione |
|---------|-------------|---------|-------------|
| UX complessa per utente | Media | Medio | Design intuitivo, wizard, tooltips |
| Dati obsoleti | Media | Basso | Notifiche ultimo aggiornamento |
| Valutazioni imprecise | Media | Medio | Disclaimer, fonti multiple |

## 7. Impatto sulle Performance

### 7.1 Import Dati
**Stima:**
- JSON 544 record: ~2-3 secondi
- Batch insert ottimizzato
- ⚠️ Rischio timeout con file molto grandi

**Raccomandazione:**
- Implementare import asincrono (Job Queue)
- Progress bar per feedback utente
- Chunking dati per import grandi

### 7.2 Calcolo Valore Collezione
**Stima:**
- Query singola carta con prezzo: ~5-10ms
- Collezione 100 carte: ~500ms-1s
- ⚠️ Troppo lento per risposta real-time

**Raccomandazione:**
- Cache valore totale (aggiornamento nocturn)
- Query ottimizzate con JOIN
- Eager loading relazioni

## 8. Strategia di Migrazione Dati

### 8.1 Dati Esistenti

**Carte Esistenti nella Collezione:**
1. Estrarre numero carta e nome da `set_number` e `card_name`
2. Cercare match in `market_cards` dopo primo import
3. Linking automatico dove possibile
4. Flag carte non matchate per review manuale

**Impatto:**
- ⚠️ Possibile necessità di intervento manuale
- 📊 Report carte non matchate
- ✅ Preservazione dati esistenti

### 8.2 Timeline Migrazione
```
Fase 1: Creazione nuove tabelle (0 downtime)
Fase 2: Import dati mercato iniziale (background)
Fase 3: Aggiornamento schema pokemon_cards (breve downtime)
Fase 4: Matching automatico carte esistenti (background)
Fase 5: Deploy nuova UI (0 downtime)
```

## 9. Metriche di Successo

### 9.1 KPI Tecnici
- ✅ Tempo import < 5 secondi per 1000 record
- ✅ Tempo calcolo valore collezione < 1 secondo
- ✅ Matching automatico > 85% successo
- ✅ Zero perdita dati durante migrazione

### 9.2 KPI Funzionali
- ✅ Utente riesce a importare prezzi con < 3 click
- ✅ Valore collezione visibile in dashboard
- ✅ Storico prezzi visualizzabile per ogni carta
- ✅ < 5% carte richiedono matching manuale

## 10. Conclusioni e Raccomandazioni

### 10.1 Impatto Complessivo
**Magnitudine:** 🔴 **ALTO**

La modifica richiede:
- ✅ 3 nuove tabelle
- ⚠️ Modifiche significative a tabella esistente
- ✅ 3 nuovi modelli
- ✅ 2 nuovi controller
- ⚠️ Modifiche sostanziali a UI
- 📊 Sistema import e matching complesso

### 10.2 Raccomandazioni Prioritarie

1. **CRITICO:** Implementare validazione robusta import JSON
2. **CRITICO:** Strategia di gestione storage storico prezzi
3. **ALTO:** Algoritmo matching carte intelligente
4. **ALTO:** Sistema cache per performance
5. **MEDIO:** UI intuitiva per selezione set
6. **MEDIO:** Dashboard analitico prezzi e trend

### 10.3 Approccio Consigliato

**Sviluppo Incrementale:**
```
Sprint 1 (Week 1-2):
- Creazione tabelle database
- Modelli Eloquent base
- Import dati mercato (CLI)

Sprint 2 (Week 3-4):
- Sistema matching automatico
- Aggiornamento pokemon_cards
- Migrazione dati esistenti

Sprint 3 (Week 5-6):
- UI import dati
- Modifica form scansione
- Dashboard valore collezione

Sprint 4 (Week 7-8):
- Storico prezzi UI
- Ottimizzazioni performance
- Testing e bug fixing
```

### 10.4 Considerazioni Finali

Questa modifica trasforma significativamente l'applicazione da semplice catalogatore a **sistema completo di portfolio management** per carte Pokemon. L'impatto è sostanziale ma il valore aggiunto è molto elevato.

Il rischio principale è la **complessità del sistema di matching** e la **gestione dello storico prezzi** nel lungo termine. Entrambi richiedono particolare attenzione in fase di design e implementazione.

---

**Status:** ✅ Analisi completata  
**Prossimo Step:** Creazione Integration Plan dettagliato
