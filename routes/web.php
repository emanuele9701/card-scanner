<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CardUploadController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MarketDataController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CardMatchingController;
use App\Http\Controllers\PokemonCardController;

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

    // Card Upload & Management routes
    Route::prefix('cards')->group(function () {
        Route::get('/upload', [CardUploadController::class, 'showUploadForm'])->name('cards.upload');
        Route::post('/upload-image', [CardUploadController::class, 'uploadImage'])->name('cards.upload-image');
        Route::post('/enhance', [CardUploadController::class, 'enhanceWithAI'])->name('cards.enhance');
        Route::post('/save', [CardUploadController::class, 'saveCard'])->name('cards.save');
        Route::post('/discard', [CardUploadController::class, 'discard'])->name('cards.discard');
        Route::get('/', [CardUploadController::class, 'index'])->name('cards.index');
        Route::delete('/{card}', [CardUploadController::class, 'destroy'])->name('cards.destroy');
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
});
