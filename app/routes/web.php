<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\AuthWebController;
use App\Http\Controllers\CollezioniController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Guest (non autenticati) ─────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthWebController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthWebController::class, 'login']);
    Route::get('/register', [AuthWebController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthWebController::class, 'register']);
});

// ── Authenticated ───────────────────────────────────────────────────────
Route::get('/test-variants', function() {
    $card = \App\Models\TCGCard::whereNotNull('variants')->first();
    return response()->json($card->variants);
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

    // Collezioni
    Route::prefix('collezioni')->name('collezioni.')->group(function () {
        Route::get('/mie', [CollezioniController::class, 'index'])->name('mie');
        Route::get('/mancanti', [CollezioniController::class, 'missingGlobal'])->name('mancanti');
        Route::get('/mie/set/{set}', [CollezioniController::class, 'showMySet'])->name('mie.set');
        Route::get('/disponibili', [CollezioniController::class, 'disponibili'])->name('disponibili');
        Route::get('/set/{set}', [CollezioniController::class, 'showSet'])->name('set');
        Route::get('/set/{set}/missing-cards', [CollezioniController::class, 'missingCards'])->name('set.missing');
        Route::post('/cards/{card}', [CollezioniController::class, 'addCardToCollection'])
            ->name('cards.addToCollection');
            
        Route::get('/cards/{card}/copies', [CollezioniController::class, 'getCardCopies'])->name('cards.getCopies');
        Route::post('/cards/{card}/copies', [CollezioniController::class, 'addCardCopy'])->name('cards.addCopy');
        Route::put('/copies/{copy}', [CollezioniController::class, 'updateCardCopy'])->name('copies.update');
        Route::delete('/copies/{copy}', [CollezioniController::class, 'deleteCardCopy'])->name('copies.delete');
        Route::delete('/cards/mass-remove', [CollezioniController::class, 'massRemoveCards'])->name('cards.massRemove');
        Route::delete('/cards/{card}/collection', [CollezioniController::class, 'removeCardFromCollection'])->name('cards.removeFromCollection');
    });

    Route::prefix('cards')->name('cards.')->group(function () {
        Route::get('/search', [CardController::class, 'search'])->name('search');
        Route::get('/{card}', [CardController::class, 'show'])->name('show');
    });

    // Profilo utente
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile/username', [ProfileController::class, 'updateUsername'])->name('profile.updateUsername');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');

    // Impostazioni utente
    Route::get('/settings', [UserSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [UserSettingsController::class, 'update'])->name('settings.update');
});
