<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\AuthWebController;
use App\Http\Controllers\CollezioniController;
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
});

// ── Authenticated ───────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::post('/logout', [AuthWebController::class, 'logout'])->name('logout');

    // Collezioni
    Route::prefix('collezioni')->name('collezioni.')->group(function () {
        Route::get('/mie', [CollezioniController::class, 'index'])->name('mie');
        Route::get('/disponibili', [CollezioniController::class, 'disponibili'])->name('disponibili');
        Route::get('/set/{set}', [CollezioniController::class, 'showSet'])->name('set');
        Route::post('/cards/{card}', [CollezioniController::class, 'addCardToCollection'])
            ->name('cards.addToCollection');
    });

    Route::prefix('cards')->name('cards.')->group(function () {
        Route::get('/search', [CardController::class, 'search'])->name('search');
        Route::get('/{card}', [CardController::class, 'show'])->name('show');
    });

    // Impostazioni utente
    Route::get('/settings', [UserSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [UserSettingsController::class, 'update'])->name('settings.update');
});
