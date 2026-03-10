<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CardUploadController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CardInventoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MarketDataController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CardMatchingController;
use App\Http\Controllers\PokemonCardController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\AdminController;
use App\Models\MarketCard;
use App\Models\MarketPrice;
use App\Models\ProviderPrice;
use App\Services\TCGdexLookupService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TCGdex\Model\Card;
use TCGdex\Query;
use TCGdex\TCGdex;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to login if guest, or upload if authenticated
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('cards.upload')
        : redirect()->route('login');
});

// Guest routes (only accessible when NOT logged in)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Profile
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Image streaming routes (for secure image access without symlinks)
    Route::get('/image/card/{card}', [ImageController::class, 'showCardImage'])->name('image.card');
    Route::get('/image', [ImageController::class, 'showImage'])->name('image.show');

    // Card Upload & Management routes
    Route::prefix('cards')->group(function () {
        // Upload flow (CardUploadController)
        Route::get('/upload', [CardUploadController::class, 'showUploadForm'])->name('cards.upload');
        Route::post('/upload-image', [CardUploadController::class, 'uploadRawImage'])->name('cards.upload-image');
        Route::post('/upload-and-enhance', [CardUploadController::class, 'uploadAndEnhance'])->name('cards.upload-and-enhance');
        Route::post('/save-crop', [CardUploadController::class, 'saveCroppedImage'])->name('cards.save-crop');
        Route::post('/skip-crop', [CardUploadController::class, 'skipCrop'])->name('cards.skip-crop');
        Route::post('/enhance', [CardUploadController::class, 'enhanceWithAI'])->name('cards.enhance');
        Route::post('/save', [CardUploadController::class, 'saveCard'])->name('cards.save');
        Route::post('/discard', [CardUploadController::class, 'discard'])->name('cards.discard');

        // Card CRUD (CardController)
        Route::get('/', [CardController::class, 'index'])->name('cards.index');
        Route::put('/{card}/update', [CardController::class, 'update'])->name('cards.update');
        Route::post('/assign-set', [CardController::class, 'assignSet'])->name('cards.assign-set');
        Route::get('/api/card-sets', [CardController::class, 'getCardSets'])->name('api.card-sets');
        Route::get('/api/available-games', [CardController::class, 'getAvailableGames'])->name('api.available-games');
        Route::get('/{card}/data', [CardController::class, 'show'])->name('cards.data');
        Route::delete('/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
        Route::post('/bulk-delete', [CardController::class, 'bulkDestroy'])->name('cards.bulk-delete');

        // Card Inventory (CardInventoryController)
        Route::get('/{card}/inventory', [CardInventoryController::class, 'index'])->name('cards.inventory.get');
        Route::post('/{card}/inventory', [CardInventoryController::class, 'store'])->name('cards.inventory.store');
        Route::put('/inventory/{inventory}', [CardInventoryController::class, 'update'])->name('cards.inventory.update');
        Route::delete('/inventory/{inventory}', [CardInventoryController::class, 'destroy'])->name('cards.inventory.destroy');
        Route::get('/api/inventory-options', [CardInventoryController::class, 'options'])->name('api.inventory-options');
    });

    // Pokemon Cards management
    Route::post('/cards/{card}/condition', [PokemonCardController::class, 'updateCondition'])->name('cards.update-condition');

    // Collection routes
    Route::prefix('collection')->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->name('collection.index');
        Route::get('/value', [CollectionController::class, 'value'])->name('collection.value');
    });


    // Market Data routes
    Route::prefix('market-data')->group(function () {
        Route::get('/', [MarketDataController::class, 'index'])->name('market-data.index');
        Route::post('/import', [MarketDataController::class, 'import'])->name('market-data.import');
    });

    // Card Matching routes
    Route::prefix('matching')->group(function () {
        Route::get('/', [CardMatchingController::class, 'index'])->name('matching.index');
        Route::post('/auto-match', [CardMatchingController::class, 'autoMatch'])->name('matching.auto');
        Route::get('/cards/{card}/suggestions', [CardMatchingController::class, 'suggestions'])->name('matching.suggestions');
        Route::post('/cards/{card}/match', [CardMatchingController::class, 'match'])->name('matching.match');
        Route::post('/cards/{card}/unmatch', [CardMatchingController::class, 'unmatch'])->name('matching.unmatch');
    });

    // API Test Page
    Route::get('/test/api', function () {
        return inertia('Test/ApiTest');
    })->name('test.api');

    // Log viewer
    Route::get('/logs', function () {
        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath)) {
            abort(404, 'File di log non trovato.');
        }

        return new StreamedResponse(function () use ($logPath) {
            $handle = fopen($logPath, 'rb');

            if ($handle === false) {
                abort(500, 'Impossibile aprire il file di log.');
            }

            echo '<html><head>';
            echo '<meta charset="UTF-8">';
            echo '<title>Laravel Log</title>';
            echo '<style>
                body { background: #1e1e1e; color: #d4d4d4; font-family: monospace; font-size: 13px; padding: 20px; margin: 0; }
                pre { white-space: pre-wrap; word-break: break-all; margin: 0; }
                .error   { color: #f48771; }
                .warning { color: #dcdcaa; }
                .info    { color: #9cdcfe; }
                .debug   { color: #888; }
            </style>';
            echo '</head><body><pre>';

            ob_flush();
            flush();

            $chunkSize = 8192; // 8 KB per chunk

            while (! feof($handle)) {
                $chunk = fread($handle, $chunkSize);

                // Escape HTML per sicurezza
                $chunk = htmlspecialchars($chunk, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                // Colorazione per livello di log
                $chunk = preg_replace('/(\[.*?\] \w+\.ERROR.*)/m',   '<span class="error">$1</span>',   $chunk);
                $chunk = preg_replace('/(\[.*?\] \w+\.WARNING.*)/m', '<span class="warning">$1</span>', $chunk);
                $chunk = preg_replace('/(\[.*?\] \w+\.INFO.*)/m',    '<span class="info">$1</span>',    $chunk);
                $chunk = preg_replace('/(\[.*?\] \w+\.DEBUG.*)/m',   '<span class="debug">$1</span>',   $chunk);

                echo $chunk;

                ob_flush();
                flush();
            }

            fclose($handle);

            echo '</pre></body></html>';
        }, 200, [
            'Content-Type'      => 'text/html; charset=UTF-8',
            'X-Accel-Buffering' => 'no', // Disabilita il buffering su Nginx
            'Cache-Control'     => 'no-store, no-cache',
        ]);
    });

    // Admin / Utility Routes
    Route::get('/admin/reset-database', [AdminController::class, 'resetDatabase'])->name('admin.reset-database');
});

Route::get('/sistemo_tutto', function () {
    // Recupero le marketcard che non hanno un prices
    $marketCards = MarketCard::doesntHave('prices')->get();
    Log::info("Trovate " . count($marketCards) . " market cards senza prezzi associati");
    $tcgdexService = app(TCGdexLookupService::class);
    foreach ($marketCards as $marketCard) {
        // Creo un nuovo record di MarketPrice con prezzo 0
        $cardGame = $marketCard->pokemonCards;

        if (!$cardGame) {
            continue; // Salta se non c'è una carta Pokemon associata
        }

        $setCard = $cardGame->cardSet;

        if (!$setCard) {
            continue; // Salta se non c'è un set associato
        }
        $totalCards = explode("/", $cardGame->set_number)[1] ?? 0;
        Log::info("Processing MarketCard ID: {$marketCard->id} - {$marketCard->product_name}, Total Cards in Set: {$totalCards}");
        // Recupero informazioni su tcgdex
        $match = $tcgdexService->searchAndMatch(
            $marketCard->card_number,
            $marketCard->product_name,
            $totalCards,
            false
        );
        Log::info("Match per card {$marketCard->id} - {$marketCard->product_name}: " . json_encode($match ?? []));
        // die;; // Rimuovi questo continue per abilitare l'importazione dei prezzi da TCGdex

        if ($match && isset($match['tcg_card'])) {
            /**
             * @var Card $card
             */
            $card = $match['tcg_card'];

            foreach (json_decode(json_encode($card->pricing), true) as $provider => $price) {

                if (!is_array($price)) continue;

                if (strtolower($provider) == 'cardmarket') {
                    TCGGenerateCardMarketsPrice($marketCard, $price);
                } else if (strtolower($provider) === 'tcgplayer') {
                    TCGGenerateTcgPlayerPrice($marketCard, $price);
                } else {
                    Log::warning("Provider non gestito: $provider per card {$marketCard->id} - {$marketCard->product_name}");
                }
            }
        }
        die;
    }
});

/**
 * From array tcg's pricing (cardmarket) array generate a MarketPrices data
 */
function TCGGenerateCardMarketsPrice(MarketCard $marketCard, array $price): void
{
    $return = [];
    $providerPrice = ProviderPrice::where('name', 'CardMarket')->first();

    if (!$providerPrice) {
        return;
    }
    $importDate = (date_create_from_format('Y-m-d', explode("T", $price['updated'])[0]))->format("Y-m-d");
    if (MarketPrice::where('market_card_id', $marketCard->id)->where('import_date', $importDate)->exists()) return;

    $return[] = [
        'external_product_id' => $price['idProduct'],
        'market_card_id' => $marketCard->id,
        'provider_id' => $providerPrice->id,
        'condition' => 'Near Mint',
        'printing' => 'Standard',
        'low_price' => $price['low'],
        'trend' => $price['trend'],
        'avg1' => $price['avg1'],
        'avg7' => $price['avg7'],
        'avg30' => $price['avg30'],
        'unit_divisa' => 'eur',
        'market_price' => $price['trend'],
        'import_date' => $importDate
    ];

    if (!empty($price['trend-holo'])) {
        $return[] = [
            'external_product_id' => $price['idProduct'],
            'market_card_id' => $marketCard->id,
            'provider_id' => $providerPrice->id,
            'condition' => 'Near Mint',
            'printing' => 'Holo',
            'low_price' => $price['low-holo'] ?? 0,
            'trend' => $price['trend-holo'],
            'avg1' => $price['avg1-holo'],
            'avg7' => $price['avg7-holo'],
            'avg30' => $price['avg30-holo'],
            'unit_divisa' => 'eur',
            'market_price' => $price['trend'],
            'import_date' => $importDate
        ];
    }
    foreach ($return as $key => $value) {
        MarketPrice::create($value);
    }
}

/**
 * From array tcg's pricing (cardmarket) array generate a MarketPrices data
 */
function TCGGenerateTcgPlayerPrice(MarketCard $marketCard, array $price): void
{
    $providerPrice = ProviderPrice::where('name', 'TCG Player')->first();

    $importDate = (date_create_from_format('Y-m-d', explode("T", $price['updated'])[0]))->format("Y-m-d");
    unset($price['updated'], $price['unit']);
    foreach ($price as $printing => $value) {
        $return = [
            'external_product_id' => $value['productId'],
            'market_card_id' => $marketCard->id,
            'provider_id' => $providerPrice->id,
            'condition' => 'Near Mint',
            'printing' => $printing,
            'low_price' => $value['lowPrice'],
            'high_price' => $value['highPrice'],
            'mid_price' => $value['midPrice'],
            'market_price' => $value['marketPrice'],
            'unit_divisa' => 'dol',
            'import_date' => $importDate
        ];
        MarketPrice::create($return);
    }
}
