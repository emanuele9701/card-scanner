# Piano di Integrazione - Sistema Prezzi di Mercato

**Data:** 31 Dicembre 2025  
**Versione:** 1.0

## 1. Panoramica

### Obiettivo
Integrare sistema di tracciamento prezzi di mercato per carte Pokemon con storicizzazione e correlazione alla collezione personale.

### Approccio
Sviluppo incrementale in 4 sprint (8 settimane)

## 2. Sprint 1 - Database Foundation (Week 1-2)

### 2.1 Creazione Tabelle

#### Migration 1: `card_sets`
```php
Schema::create('card_sets', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('abbreviation')->unique();
    $table->date('release_date')->nullable();
    $table->integer('total_cards')->nullable();
    $table->timestamps();
    
    $table->index('abbreviation');
});
```

#### Migration 2: `market_cards`
```php
Schema::create('market_cards', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('product_id')->unique();
    $table->string('product_name');
    $table->string('card_number');
    $table->string('set_name');
    $table->string('set_abbreviation');
    $table->string('rarity');
    $table->string('type');
    $table->string('game')->default('Pokemon');
    $table->boolean('is_supplemental')->default(false);
    $table->timestamps();
    
    $table->index(['card_number', 'set_abbreviation']);
    $table->index('product_name');
});
```

#### Migration 3: `market_prices`
```php
Schema::create('market_prices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('market_card_id')->constrained()->onDelete('cascade');
    $table->enum('condition', ['Damaged', 'Heavily Played', 'Moderately Played', 'Lightly Played', 'Near Mint']);
    $table->enum('printing', ['Normal', 'Reverse Holofoil', 'Holofoil']);
    $table->decimal('low_price', 10, 2);
    $table->decimal('market_price', 10, 2);
    $table->integer('sales_count')->default(0);
    $table->date('import_date');
    $table->timestamp('created_at');
    
    $table->index(['market_card_id', 'import_date']);
    $table->index('import_date');
});
```

#### Migration 4: Update `pokemon_cards`
```php
Schema::table('pokemon_cards', function (Blueprint $table) {
    $table->foreignId('card_set_id')->nullable()->after('id')->constrained()->onDelete('set null');
    $table->foreignId('market_card_id')->nullable()->after('card_set_id')->constrained()->onDelete('set null');
    $table->enum('condition', ['Damaged', 'Heavily Played', 'Moderately Played', 'Lightly Played', 'Near Mint'])
          ->nullable()->after('market_card_id');
    $table->enum('printing', ['Normal', 'Reverse Holofoil', 'Holofoil'])
          ->default('Normal')->after('condition');
    $table->decimal('acquisition_price', 10, 2)->nullable()->after('printing');
    $table->date('acquisition_date')->nullable()->after('acquisition_price');
    
    $table->index('card_set_id');
    $table->index('market_card_id');
});
```

### 2.2 Modelli Eloquent

#### `app/Models/CardSet.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardSet extends Model
{
    protected $fillable = [
        'name',
        'abbreviation',
        'release_date',
        'total_cards',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];

    public function pokemonCards(): HasMany
    {
        return $this->hasMany(PokemonCard::class);
    }

    public function marketCards(): HasMany
    {
        return $this->hasMany(MarketCard::class, 'set_abbreviation', 'abbreviation');
    }
}
```

#### `app/Models/MarketCard.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketCard extends Model
{
    protected $fillable = [
        'product_id',
        'product_name',
        'card_number',
        'set_name',
        'set_abbreviation',
        'rarity',
        'type',
        'game',
        'is_supplemental',
    ];

    protected $casts = [
        'is_supplemental' => 'boolean',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(MarketPrice::class);
    }

    public function pokemonCards(): HasMany
    {
        return $this->hasMany(PokemonCard::class);
    }

    public function latestPrice()
    {
        return $this->hasOne(MarketPrice::class)->latestOfMany('import_date');
    }
}
```

#### `app/Models/MarketPrice.php`
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketPrice extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'market_card_id',
        'condition',
        'printing',
        'low_price',
        'market_price',
        'sales_count',
        'import_date',
    ];

    protected $casts = [
        'low_price' => 'decimal:2',
        'market_price' => 'decimal:2',
        'import_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function marketCard(): BelongsTo
    {
        return $this->belongsTo(MarketCard::class);
    }
}
```

### 2.3 Service per Import

#### `app/Services/MarketDataImportService.php`
```php
<?php

namespace App\Services;

use App\Models\CardSet;
use App\Models\MarketCard;
use App\Models\MarketPrice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketDataImportService
{
    public function importFromJson(array $jsonData): array
    {
        $stats = [
            'total' => count($jsonData),
            'sets_created' => 0,
            'cards_created' => 0,
            'cards_updated' => 0,
            'prices_created' => 0,
            'errors' => [],
        ];

        $importDate = now()->toDateString();

        DB::beginTransaction();
        try {
            $groupedByProduct = collect($jsonData)->groupBy('productID');

            foreach ($groupedByProduct as $productId => $variants) {
                $firstVariant = $variants->first();

                // Ensure set exists
                $set = $this->ensureSetExists($firstVariant);
                if ($set) {
                    $stats['sets_created']++;
                }

                // Create or update market card
                $marketCard = MarketCard::updateOrCreate(
                    ['product_id' => $productId],
                    [
                        'product_name' => $firstVariant['productName'],
                        'card_number' => $firstVariant['number'],
                        'set_name' => $firstVariant['set'],
                        'set_abbreviation' => $firstVariant['setAbbrv'],
                        'rarity' => $firstVariant['rarity'],
                        'type' => $firstVariant['type'],
                        'game' => $firstVariant['game'],
                        'is_supplemental' => $firstVariant['isSupplemental'],
                    ]
                );

                $marketCard->wasRecentlyCreated 
                    ? $stats['cards_created']++ 
                    : $stats['cards_updated']++;

                // Create price records for each condition/printing variant
                foreach ($variants as $variant) {
                    MarketPrice::create([
                        'market_card_id' => $marketCard->id,
                        'condition' => $this->normalizeCondition($variant['condition']),
                        'printing' => $this->normalizePrinting($variant['printing']),
                        'low_price' => $variant['lowPrice'],
                        'market_price' => $variant['marketPrice'],
                        'sales_count' => $variant['sales'],
                        'import_date' => $importDate,
                        'created_at' => now(),
                    ]);
                    $stats['prices_created']++;
                }
            }

            DB::commit();
            Log::info('Market data import successful', $stats);
        } catch (\Exception $e) {
            DB::rollBack();
            $stats['errors'][] = $e->getMessage();
            Log::error('Market data import failed', ['error' => $e->getMessage()]);
            throw $e;
        }

        return $stats;
    }

    private function ensureSetExists(array $cardData): ?CardSet
    {
        return CardSet::firstOrCreate(
            ['abbreviation' => $cardData['setAbbrv']],
            ['name' => $cardData['set']]
        );
    }

    private function normalizeCondition(string $condition): string
    {
        $normalized = str_replace([' Holofoil', ' Reverse Holofoil'], '', $condition);
        return trim($normalized);
    }

    private function normalizePrinting(string $printing): string
    {
        return $printing;
    }
}
```

### 2.4 Command CLI per Import

#### `app/Console/Commands/ImportMarketData.php`
```php
<?php

namespace App\Console\Commands;

use App\Services\MarketDataImportService;
use Illuminate\Console\Command;

class ImportMarketData extends Command
{
    protected $signature = 'market:import {file}';
    protected $description = 'Import market data from JSON file';

    public function handle(MarketDataImportService $service): int
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info('Reading JSON file...');
        $jsonData = json_decode(file_get_contents($filePath), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: ' . json_last_error_msg());
            return 1;
        }

        if (!isset($jsonData['result']) || !is_array($jsonData['result'])) {
            $this->error('Invalid JSON structure. Expected "result" array.');
            return 1;
        }

        $this->info("Processing {$jsonData['count']} records...");
        
        $bar = $this->output->createProgressBar($jsonData['count']);
        $bar->start();

        try {
            $stats = $service->importFromJson($jsonData['result']);
            $bar->finish();
            $this->newLine(2);

            $this->info('Import completed successfully!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Records', $stats['total']],
                    ['Sets Created', $stats['sets_created']],
                    ['Cards Created', $stats['cards_created']],
                    ['Cards Updated', $stats['cards_updated']],
                    ['Prices Created', $stats['prices_created']],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error('Import failed: ' . $e->getMessage());
            return 1;
        }
    }
}
```

## 3. Sprint 2 - Matching System (Week 3-4)

### 3.1 Auto-Matching Service

#### `app/Services/CardMatchingService.php`
```php
<?php

namespace App\Services;

use App\Models\PokemonCard;
use App\Models\MarketCard;
use Illuminate\Support\Facades\Log;

class CardMatchingService
{
    public function matchCard(PokemonCard $card): ?MarketCard
    {
        // Try exact match by number and set
        if ($card->set_number && $card->cardSet) {
            $match = MarketCard::where('card_number', $card->set_number)
                ->where('set_abbreviation', $card->cardSet->abbreviation)
                ->first();
            
            if ($match) {
                Log::info("Exact match found", [
                    'pokemon_card_id' => $card->id,
                    'market_card_id' => $match->id
                ]);
                return $match;
            }
        }

        // Fallback: fuzzy match by name
        if ($card->card_name) {
            $match = MarketCard::where('product_name', 'LIKE', "%{$card->card_name}%")
                ->first();
            
            if ($match) {
                Log::info("Fuzzy match found", [
                    'pokemon_card_id' => $card->id,
                    'market_card_id' => $match->id
                ]);
                return $match;
            }
        }

        Log::warning("No match found", ['pokemon_card_id' => $card->id]);
        return null;
    }

    public function matchAllUnmatched(): array
    {
        $stats = [
            'processed' => 0,
            'matched' => 0,
            'unmatched' => 0,
        ];

        $unmatchedCards = PokemonCard::whereNull('market_card_id')->get();
        $stats['processed'] = $unmatchedCards->count();

        foreach ($unmatchedCards as $card) {
            $marketCard = $this->matchCard($card);
            
            if ($marketCard) {
                $card->update(['market_card_id' => $marketCard->id]);
                $stats['matched']++;
            } else {
                $stats['unmatched']++;
            }
        }

        return $stats;
    }
}
```

### 3.2 Migration Dati Esistenti

#### Command: `app/Console/Commands/MatchExistingCards.php`
```php
<?php

namespace App\Console\Commands;

use App\Services\CardMatchingService;
use Illuminate\Console\Command;

class MatchExistingCards extends Command
{
    protected $signature = 'cards:match-existing';
    protected $description = 'Match existing Pokemon cards to market data';

    public function handle(CardMatchingService $service): int
    {
        $this->info('Matching existing cards to market data...');
        
        $stats = $service->matchAllUnmatched();
        
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $stats['processed']],
                ['Matched', $stats['matched']],
                ['Unmatched', $stats['unmatched']],
            ]
        );

        if ($stats['unmatched'] > 0) {
            $this->warn("{$stats['unmatched']} cards could not be matched automatically.");
        }

        return 0;
    }
}
```

### 3.3 Update PokemonCard Model

```php
// Add to app/Models/PokemonCard.php

use Illuminate\Database\Eloquent\Relations\BelongsTo;

public function cardSet(): BelongsTo
{
    return $this->belongsTo(CardSet::class);
}

public function marketCard(): BelongsTo
{
    return $this->belongsTo(MarketCard::class);
}

public function getEstimatedValue(): ?float
{
    if (!$this->marketCard || !$this->condition) {
        return null;
    }

    $price = $this->marketCard->prices()
        ->where('condition', $this->condition)
        ->where('printing', $this->printing ?? 'Normal')
        ->orderBy('import_date', 'desc')
        ->first();

    return $price?->market_price;
}
```

## 4. Sprint 3 - UI Implementation (Week 5-6)

### 4.1 Backend - MarketDataController

#### `app/Http/Controllers/MarketDataController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Services\MarketDataImportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MarketDataController extends Controller
{
    public function __construct(
        private MarketDataImportService $importService
    ) {}

    public function index()
    {
        return Inertia::render('MarketData/Index');
    }

    public function import(Request $request)
    {
        $request->validate([
            'json_file' => 'required|file|mimes:json|max:10240', // Max 10MB
        ]);

        $file = $request->file('json_file');
        $jsonData = json_decode($file->get(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['json_file' => 'Invalid JSON file']);
        }

        try {
            $stats = $this->importService->importFromJson($jsonData['result'] ?? []);
            
            return back()->with('success', 'Import completed successfully!')
                        ->with('stats', $stats);
        } catch (\Exception $e) {
            return back()->withErrors(['import' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}
```

### 4.2 Update PokemonCardController

```php
// Add to store() method in PokemonCardController

$validated = $request->validate([
    // ... existing validations
    'card_set_id' => 'required|exists:card_sets,id',
    'condition' => 'nullable|in:Damaged,Heavily Played,Moderately Played,Lightly Played,Near Mint',
    'printing' => 'nullable|in:Normal,Reverse Holofoil,Holofoil',
    'acquisition_price' => 'nullable|numeric|min:0',
    'acquisition_date' => 'nullable|date',
]);

// After creating card, try auto-matching
if ($card) {
    $matchingService = app(CardMatchingService::class);
    $marketCard = $matchingService->matchCard($card);
    if ($marketCard) {
        $card->update(['market_card_id' => $marketCard->id]);
    }
}
```

### 4.3 Frontend Components

#### Vue Component: `resources/js/Pages/MarketData/Index.vue`
```vue
<template>
  <AppLayout title="Market Data">
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
          <h2 class="text-2xl font-bold mb-6">Import Market Data</h2>
          
          <form @submit.prevent="submitImport" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">
                JSON File
                </label>
              <input 
                type="file" 
                @change="handleFileChange"
                accept=".json"
                class="mt-1 block w-full"
                required
              />
              <p class="mt-1 text-sm text-gray-500">
                Upload a JSON file containing market card data
              </p>
            </div>

            <button 
              type="submit"
              :disabled="importing"
              class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 disabled:opacity-50"
            >
              {{ importing ? 'Importing...' : 'Import Data' }}
            </button>
          </form>

          <!-- Stats Display -->
          <div v-if="importStats" class="mt-6">
            <h3 class="text-lg font-semibold mb-2">Import Results</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
              <div class="bg-gray-100 p-4 rounded">
                <div class="text-2xl font-bold">{{ importStats.total }}</div>
                <div class="text-sm text-gray-600">Total Records</div>
              </div>
              <div class="bg-green-100 p-4 rounded">
                <div class="text-2xl font-bold">{{ importStats.cards_created }}</div>
                <div class="text-sm text-gray-600">Cards Created</div>
              </div>
              <div class="bg-blue-100 p-4 rounded">
                <div class="text-2xl font-bold">{{ importStats.cards_updated }}</div>
                <div class="text-sm text-gray-600">Cards Updated</div>
              </div>
              <div class="bg-purple-100 p-4 rounded">
                <div class="text-2xl font-bold">{{ importStats.prices_created }}</div>
                <div class="text-sm text-gray-600">Prices Added</div>
              </div>
              <div class="bg-yellow-100 p-4 rounded">
                <div class="text-2xl font-bold">{{ importStats.sets_created }}</div>
                <div class="text-sm text-gray-600">Sets Created</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const importing = ref(false);
const importStats = ref(null);
const form = useForm({
  json_file: null,
});

function handleFileChange(event) {
  form.json_file = event.target.files[0];
}

function submitImport() {
  importing.value = true;
  
  form.post(route('market-data.import'), {
    onSuccess: (page) => {
      importStats.value = page.props.flash.stats;
      form.reset();
      importing.value = false;
    },
    onError: () => {
      importing.value = false;
    },
  });
}
</script>
```

#### Update Scan Form: Add Set Selection
```vue
<!-- Add to card scanning form -->
<div>
  <label class="block text-sm font-medium text-gray-700">Set/Collection *</label>
  <select v-model="form.card_set_id" required class="mt-1 block w-full">
    <option value="">Select a set...</option>
    <option v-for="set in cardSets" :key="set.id" :value="set.id">
      {{ set.name }} ({{ set.abbreviation }})
    </option>
  </select>
</div>

<div>
  <label class="block text-sm font-medium text-gray-700">Condition</label>
  <select v-model="form.condition" class="mt-1 block w-full">
    <option value="">Unknown</option>
    <option value="Near Mint">Near Mint</option>
    <option value="Lightly Played">Lightly Played</option>
    <option value="Moderately Played">Moderately Played</option>
    <option value="Heavily Played">Heavily Played</option>
    <option value="Damaged">Damaged</option>
  </select>
</div>

<div>
  <label class="block text-sm font-medium text-gray-700">Printing</label>
  <select v-model="form.printing" class="mt-1 block w-full">
    <option value="Normal">Normal</option>
    <option value="Reverse Holofoil">Reverse Holofoil</option>
    <option value="Holofoil">Holofoil</option>
  </select>
</div>
```

## 5. Sprint 4 - Dashboard & Optimization (Week 7-8)

### 5.1 Collection Value Dashboard

#### Controller Method
```php
// Add to PokemonCardController

public function collectionValue()
{
    $cards = PokemonCard::with([
        'cardSet',
        'marketCard.latestPrice'
    ])->where('user_id', auth()->id())->get();

    $totalValue = 0;
    $totalCards = $cards->count();
    $cardsWithValue = 0;

    foreach ($cards as $card) {
        $value = $card->getEstimatedValue();
        if ($value !== null) {
            $totalValue += $value;
            $cardsWithValue++;
        }
    }

    return Inertia::render('Collection/Value', [
        'stats' => [
            'total_cards' => $totalCards,
            'total_value' => $totalValue,
            'cards_with_value' => $cardsWithValue,
            'cards_without_value' => $totalCards - $cardsWithValue,
            'average_value' => $cardsWithValue > 0 ? $totalValue / $cardsWithValue : 0,
        ],
        'cards' => $cards->map(fn($card) => [
            'id' => $card->id,
            'name' => $card->card_name,
            'set' => $card->cardSet?->name,
            'condition' => $card->condition,
            'estimated_value' => $card->getEstimatedValue(),
        ]),
    ]);
}
```

### 5.2 Routes

```php
// routes/web.php

use App\Http\Controllers\MarketDataController;

Route::middleware(['auth'])->group(function () {
    // Market Data
    Route::get('/market-data', [MarketDataController::class, 'index'])->name('market-data.index');
    Route::post('/market-data/import', [MarketDataController::class, 'import'])->name('market-data.import');
    
    // Collection Value
    Route::get('/collection/value', [PokemonCardController::class, 'collectionValue'])->name('collection.value');
});
```

### 5.3 Database Optimization

#### Add Indexes
```php
// Migration: add_indexes_for_performance.php

public function up()
{
    Schema::table('market_prices', function (Blueprint $table) {
        $table->index(['market_card_id', 'condition', 'printing']);
    });
    
    Schema::table('pokemon_cards', function (Blueprint $table) {
        $table->index(['user_id', 'card_set_id']);
    });
}
```

## 6. Testing Checklist

- [ ] Import JSON con dati corretti
- [ ] Import JSON con dati malformati (gestione errori)
- [ ] Creazione automatica set mancanti
- [ ] Matching automatico carte esistenti
- [ ] Calcolo valore collezione
- [ ] Performance con 1000+ record
- [ ] UI responsive import
- [ ] Validazione form scansione
- [ ] Filtri per set nella collezione

## 7. Deployment

### 7.1 Comandi Sequenziali
```bash
# 1. Backup database
php artisan db:backup

# 2. Run migrations
php artisan migrate

# 3. Import market data
php artisan market:import data/market_cards.json

# 4. Match existing cards
php artisan cards:match-existing

# 5. Clear cache
php artisan cache:clear
php artisan config:clear
```

### 7.2 Rollback Plan
- Backup database pre-migrazione
- Script rollback migrations disponibile
- Export dati critici prima di import

---

**Status:** ✅ Piano completato  
**Pronto per:** Revisione e approvazione
