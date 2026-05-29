# 🗄️ Database — Schema Tabelle

Tutte le tabelle usano **auto-increment bigint** come primary key. Gli ID originali delle API TCGdex sono salvati in campi separati (es. `serie_id`, `set_id`, `card_id`).

Tutte le tabelle TCG contengono un campo `language` per supportare dati multi-lingua (it, en).

---

## `tcg_series`

Serie di carte (es. "Base", "Mega Evolution").

| Colonna | Tipo | Note |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `serie_id` | string | ID TCGdex (es. "base") |
| `name` | string | Nome della serie |
| `logo` | string, null | URL logo |
| `language` | string | Default `'it'` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Vincoli:** `UNIQUE(serie_id, language)`

---

## `tcg_sets`

Set di carte all'interno di una serie (es. "Set Base", "Flamme Spettrali").

| Colonna | Tipo | Note |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `set_id` | string | ID TCGdex (es. "base1") |
| `serie_id` | bigint FK → `tcg_series.id` | Cascade on delete |
| `name` | string | Nome del set |
| `logo` | string, null | URL logo |
| `symbol` | string, null | URL simbolo |
| `card_total` | uint, null | Totale carte nel set |
| `card_official` | uint, null | Carte ufficiali |
| `card_normal` | uint, null | Carte normal |
| `card_reverse` | uint, null | Carte reverse holo |
| `card_holo` | uint, null | Carte holo |
| `card_first_edition` | uint, null | Carte prima edizione |
| `release_date` | date, null | Data di uscita |
| `variants` | json, null | Varianti disponibili |
| `abbreviation` | json, null | Abbreviazioni ufficiali |
| `language` | string | Default `'it'` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Vincoli:** `UNIQUE(set_id, language)`

---

## `tcg_cards`

Singole carte all'interno di un set.

| Colonna | Tipo | Note |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `card_id` | string | ID TCGdex (es. "base1-1") |
| `set_id` | bigint FK → `tcg_sets.id` | Cascade on delete |
| `name` | string | Nome della carta |
| `url_image` | string, null | URL immagine |
| `illustrator` | string, null | Illustratore |
| `rarity` | string, null | Rarità |
| `variants` | json, null | Varianti disponibili (normal, holo, reverse, ecc.) |
| `dexId` | string, null | Numero della carta nel set |
| `types` | json, null | Tipo/i della carta (cast ad array) |
| `evolve_from` | string, null | Carta da cui si evolve |
| `level_stage` | string, null | Stato di evoluzione (Base, Livello 1, Livello 2) |
| `language` | string | Default `'it'` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Vincoli:** `UNIQUE(card_id, set_id)`

---

## `tcg_card_abilities`

Abilità e attacchi di una carta.

| Colonna | Tipo | Note |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `card_id` | bigint FK → `tcg_cards.id` | Cascade on delete |
| `type` | string, null | Tipo (es. "Pokemon Power", "Attack") |
| `cost` | json, null | Costi energia (cast ad array) |
| `name` | string, null | Nome dell'abilità |
| `effect` | text, null | Descrizione effetto |
| `damage` | string, null | Danno inflitto |
| `language` | string | Default `'it'` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

## `tcg_card_prices`

Prezzi da Cardmarket per una carta.

| Colonna | Tipo | Note |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `card_id` | bigint FK → `tcg_cards.id` | Cascade on delete |
| `card_id_product` | string, null | ID prodotto Cardmarket |
| `unit` | string, null | Valuta (es. "EUR") |
| `avg` | decimal(10,2), null | Prezzo medio |
| `low` | decimal(10,2), null | Prezzo più basso |
| `trend` | decimal(10,2), null | Trend di prezzo |
| `avg_1d` | decimal(10,2), null | Media 1 giorno |
| `avg_7d` | decimal(10,2), null | Media 7 giorni |
| `avg_30d` | decimal(10,2), null | Media 30 giorni |
| `avg_holo` | decimal(10,2), null | Media holo |
| `low_holo` | decimal(10,2), null | Prezzo basso holo |
| `trend_holo` | decimal(10,2), null | Trend holo |
| `avg_1d_holo` | decimal(10,2), null | Media 1g holo |
| `avg_7d_holo` | decimal(10,2), null | Media 7g holo |
| `avg_30d_holo` | decimal(10,2), null | Media 30g holo |
| `language` | string | Default `'it'` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

## `user_card_collections`

Associazione tra utenti e carte possedute.

| Colonna | Tipo | Note |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `user_id` | bigint FK → `users.id` | Cascade on delete |
| `set_id` | bigint FK → `tcg_sets.id` | Cascade on delete |
| `serie_id` | bigint FK → `tcg_series.id` | Cascade on delete |
| `card_id` | bigint FK → `tcg_cards.id` | Cascade on delete |
| `quantity` | uint | Default 1, minimo 1 |
| `variants` | json, null | Varianti possedute (es. `["holo", "reverse"]`) |
| `condition` | string | Default `'NM'`. Valori: NM, LP, MP, HP, DMG |
| `notes` | text, null | Note personali dell'utente |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

> Lo stesso utente può avere la stessa carta più volte con condizioni o varianti diverse, il vincolo UNIQUE è stato rimosso per permettere la gestione capillare delle copie.

---

## `user_settings`

Impostazioni personalizzate dell'utente.

| Colonna | Tipo | Note |
|---|---|---|
| `id` | bigint PK | Auto-increment |
| `user_id` | bigint FK → `users.id` | Cascade on delete |
| `key` | string | Chiave impostazione (es. `language`) |
| `value` | string | Valore |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

## `media` (Spatie Media Library)

Tabella gestita automaticamente da Spatie. Collegata a `user_card_collections` tramite relazione polimorfica (`model_type`, `model_id`).

Conversioni automatiche generate:
- **thumb**: 200×200px
- **preview**: 600×800px

Formati accettati: JPEG, PNG, WebP. Max 5MB per file.

---


## Ordine di Migrazione

```
1. create_users_table            (Laravel default)
2. create_cache_table            (Laravel default)
3. create_jobs_table             (Laravel default)
4. create_tcg_series_table
5. create_tcg_sets_table         (FK → tcg_series)
6. create_tcg_cards_table        (FK → tcg_sets)
7. create_tcg_card_abilities     (FK → tcg_cards)
8. create_tcg_card_prices        (FK → tcg_cards)
9. create_user_card_collections  (FK → users, tcg_cards)
10. create_user_settings           (FK → users)
11. create_media_table               (Spatie Media Library)
12. create_personal_access_tokens    (Sanctum)
```
