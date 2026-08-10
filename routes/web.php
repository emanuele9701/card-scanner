<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\CollezioniController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/test-variants', function() {
    $card = \App\Models\TCGCard::whereNotNull('variants')->first();
    return response()->json($card->variants);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Collezioni
    Route::prefix('collezioni')->name('collezioni.')->group(function () {
        Route::get('/mie', [CollezioniController::class, 'index'])->name('mie');
        Route::get('/mie/export', [\App\Http\Controllers\CollectionExportController::class, 'export'])->name('mie.export');
        Route::get('/mancanti', [CollezioniController::class, 'missingGlobal'])->name('mancanti');
        Route::get('/mie/set/{set}', [CollezioniController::class, 'showMySet'])->name('mie.set');
        Route::get('/mie/set/{set}/export-excel', [\App\Http\Controllers\CollectionExportController::class, 'exportSetExcel'])->name('mie.set.exportExcel');
        Route::get('/disponibili', [CollezioniController::class, 'disponibili'])->name('disponibili');
        Route::get('/set/{set}', [CollezioniController::class, 'showSet'])->name('set');
        Route::get('/set/{set}/missing-cards', [CollezioniController::class, 'missingCards'])->name('set.missing');

        // Incoming cards (in arrivo)
        Route::post('/incoming/add', [\App\Http\Controllers\IncomingCardController::class, 'addIncoming'])->name('incoming.add');
        Route::post('/incoming/arrived', [\App\Http\Controllers\IncomingCardController::class, 'arrivedIncoming'])->name('incoming.arrived');
        Route::post('/incoming/remove', [\App\Http\Controllers\IncomingCardController::class, 'removeIncoming'])->name('incoming.remove');
        Route::post('/incoming/list', [\App\Http\Controllers\IncomingCardController::class, 'getIncomingCards'])->name('incoming.list');

        // Mass actions — MUST be before /cards/{card} to avoid wildcard capture
        Route::delete('/cards/mass-remove', [\App\Http\Controllers\MassActionController::class, 'massRemoveCards'])->name('cards.massRemove');
        Route::post('/cards/mass-add', [\App\Http\Controllers\MassActionController::class, 'massAddCopies'])->name('cards.massAdd');
        Route::post('/cards/mass-copies', [\App\Http\Controllers\MassActionController::class, 'getMassCardCopies'])->name('cards.massCopies');
        Route::put('/copies/mass-update', [\App\Http\Controllers\MassActionController::class, 'massUpdateQuantities'])->name('copies.massUpdate');
        Route::post('/set/{set}/bulk-add', [\App\Http\Controllers\MassActionController::class, 'bulkAddMissingCards'])->name('set.bulkAdd');

        // Single card routes
        Route::post('/cards/{card}', [\App\Http\Controllers\CardCopyController::class, 'addCardToCollection'])->name('cards.addToCollection');
        Route::get('/cards/{card}/copies', [\App\Http\Controllers\CardCopyController::class, 'getCardCopies'])->name('cards.getCopies');
        Route::post('/cards/{card}/copies', [\App\Http\Controllers\CardCopyController::class, 'addCardCopy'])->name('cards.addCopy');
        Route::put('/copies/{copy}', [\App\Http\Controllers\CardCopyController::class, 'updateCardCopy'])->name('copies.update');
        Route::delete('/copies/{copy}', [\App\Http\Controllers\CardCopyController::class, 'deleteCardCopy'])->name('copies.delete');
        Route::delete('/cards/{card}/collection', [\App\Http\Controllers\CardCopyController::class, 'removeCardFromCollection'])->name('cards.removeFromCollection');
    });

    Route::prefix('cards')->name('cards.')->group(function () {
        Route::get('/search', [CardController::class, 'search'])->name('search');
        Route::get('/autocomplete', [CardController::class, 'autocomplete'])->name('autocomplete');
        Route::get('/{card}', [CardController::class, 'show'])->name('show');
    });

    // Profilo utente
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Impostazioni utente
    Route::get('/settings', [UserSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [UserSettingsController::class, 'update'])->name('settings.update');

    // Watchlist & Notifiche (web session auth)
    Route::get('/watchlist', [\App\Http\Controllers\Api\WatchlistController::class, 'index'])->name('watchlist.index');
    Route::post('/watchlist/card/mass-toggle', [\App\Http\Controllers\Api\WatchlistController::class, 'massToggle'])->name('watchlist.massToggle');
    Route::post('/watchlist/card/{id}', [\App\Http\Controllers\Api\WatchlistController::class, 'toggleCard'])->name('watchlist.toggleCard');
    Route::post('/watchlist/set/{id}', [\App\Http\Controllers\Api\WatchlistController::class, 'toggleSet'])->name('watchlist.toggleSet');
});

require __DIR__.'/auth.php';
