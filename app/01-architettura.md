# 🏗️ Architettura del Progetto

## Stack Tecnologico

| Componente | Tecnologia |
|---|---|
| **Framework** | Laravel 13.x (PHP 8.3+) |
| **Frontend** | Blade Templates + Tailwind CSS v4 |
| **Database** | MariaDB |
| **Dati TCG** | [TCGdex PHP SDK](https://github.com/tcgdex/php-sdk) |
| **Auth API** | Laravel Sanctum (Bearer Token) |
| **Auth Web** | Session-based (Laravel default) |
| **Media/Foto** | Spatie Laravel Media Library |
| **Queue** | Laravel Queue (per jobs asincroni) |

## Struttura Cartelle

```
app/
├── Console/Commands/
│   └── FetchPokemonCommand.php       # Import dati da TCGdex
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php        # API Register/Login/Logout
│   │   ├── AuthWebController.php     # Web Login/Logout
│   │   ├── CollezioniController.php  # Web views per collezioni
│   │   ├── UserSettingsController.php# Web views per impostazioni utente
│   │   └── UserCardCollectionController.php  # CRUD collezione (API)
│   └── Requests/
│       ├── StoreUserCardRequest.php   # Validazione creazione
│       └── UpdateUserCardRequest.php  # Validazione aggiornamento
├── Models/
│   ├── User.php                      # Utente (+ HasApiTokens)
│   ├── TCGSeries.php                 # Serie (es. "Base")
│   ├── TCGSet.php                    # Set (es. "Set Base")
│   ├── TCGCard.php                   # Carta singola
│   ├── TCGCardAbility.php            # Abilità/attacco carta
│   ├── TCGCardPrice.php              # Prezzi Cardmarket
│   ├── UserCardCollection.php        # Collezione utente (+ Spatie Media)
│   └── UrlMapping.php                # Mappatura URL da scrapare
├── Enums/
│   └── UrlMappingStatus.php          # Enum: pending|active|scraping|done|failed
bootstrap/
│   └── app.php                       # Routing (web + api)
routes/
│   ├── web.php
│   ├── api.php                       # API REST
│   └── console.php
database/migrations/
│   ├── 000001_create_tcg_series_table.php
│   ├── 000002_create_tcg_sets_table.php
│   ├── 000003_create_tcg_cards_table.php
│   ├── 000004_create_tcg_card_abilities_table.php
│   ├── 000005_create_tcg_card_prices_table.php
│   ├── 230000_create_user_card_collections_table.php
│   └── *_create_media_table.php      # Spatie Media Library
```

## Schema ER

```mermaid
erDiagram
    users ||--o{ user_card_collections : "possiede"
    users ||--o{ personal_access_tokens : "ha tokens"
    users ||--o{ user_settings : "ha impostazioni"
    
    tcg_series ||--o{ tcg_sets : "contiene"
    tcg_sets ||--o{ tcg_cards : "contiene"
    tcg_cards ||--o{ tcg_card_abilities : "ha"
    tcg_cards ||--o{ tcg_card_prices : "ha"
    tcg_cards ||--o{ user_card_collections : "collezionata da"
    
    user_card_collections ||--o{ media : "foto"

    user_settings {
        bigint id PK
        bigint user_id FK
        string key
        string value
    }

    tcg_series {
        bigint id PK
        string serie_id "ID TCGdex"
        string name
        string logo
        string language
    }

    tcg_sets {
        bigint id PK
        string set_id "ID TCGdex"
        bigint serie_id FK
        string name
        string logo
        string symbol
        int card_total
        int card_official
        int card_normal
        int card_reverse
        int card_holo
        int card_first_edition
        date release_date
        json variants
        json abbreviation
        string language
    }

    tcg_cards {
        bigint id PK
        string card_id "ID TCGdex"
        bigint set_id FK
        string name
        string url_image
        string illustrator
        string rarity
        json variants
        string dexId
        json types
        string evolve_from
        string level_stage
        string language
    }

    tcg_card_abilities {
        bigint id PK
        bigint card_id FK
        string type
        json cost
        string name
        text effect
        string damage
        string language
    }

    tcg_card_prices {
        bigint id PK
        bigint card_id FK
        string card_id_product "ID Cardmarket"
        string unit "Valuta"
        decimal avg
        decimal low
        decimal trend
        decimal avg_1d
        decimal avg_7d
        decimal avg_30d
        decimal avg_holo
        decimal low_holo
        decimal trend_holo
        decimal avg_1d_holo
        decimal avg_7d_holo
        decimal avg_30d_holo
        string language
    }

    user_card_collections {
        bigint id PK
        bigint user_id FK
        bigint card_id FK
        int quantity
        json variants
        string condition "NM LP MP HP DMG"
        text notes
    }

    url_mappings {
        bigint id PK
        string site_name
        string url_path
        enum status "pending|active|scraping|done|failed"
        timestamp last_scraped_at
        uint attempts_ok
        uint attempts_failed
    }
```

## Flusso Dati

```
TCGdex API ──► FetchPokemonCommand ──► Database
                                          │
                  ┌───────────────────────┴───────────────────────┐
                  ▼                                               ▼
          API REST (Sanctum Auth)                       Web Routes (Session Auth)
                  │                                               │
                  ▼                                               ▼
      Frontend Mobile / App Esterna                    Blade Views + Tailwind CSS
```

## Dipendenze Composer

| Pacchetto | Versione | Scopo |
|---|---|---|
| `tcgdex/sdk` | ^2.3 | Client PHP per TCGdex API |
| `laravel/sanctum` | ^4.3 | Autenticazione API token |
| `spatie/laravel-medialibrary` | ^11.21 | Upload e conversione foto |
| `kriswallsmith/buzz` | ^1.3 | HTTP client (usato da TCGdex SDK) |
| `nyholm/psr7` | ^1.8 | PSR-7 HTTP messages |
| `symfony/cache` | ^7.4 | Cache layer |
