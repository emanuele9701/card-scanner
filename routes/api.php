<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CollectionApiController;
use App\Http\Controllers\Api\CardSetApiController;
use App\Http\Controllers\ImageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthApiController::class, 'register']);
    Route::post('/login', [AuthApiController::class, 'login']);
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::get('/user', [AuthApiController::class, 'user']);
    });

    // Collection routes
    Route::prefix('collection')->group(function () {
        Route::get('/cards', [CollectionApiController::class, 'cards']);
        Route::get('/games', [CollectionApiController::class, 'games']);

        // Individual Card management (nested under collection)
        Route::prefix('cards')->group(function () {
            Route::post('/{card}', [\App\Http\Controllers\Api\CardApiController::class, 'update']);
            Route::delete('/{card}', [\App\Http\Controllers\Api\CardApiController::class, 'destroy']);
            Route::get('/{card}/conditions', [\App\Http\Controllers\Api\CardApiController::class, 'getConditions']);
            Route::post('/{card}/condition', [\App\Http\Controllers\Api\CardApiController::class, 'updateCondition']);
            Route::delete('/{card}/set', [\App\Http\Controllers\Api\CardApiController::class, 'removeSet']);
            Route::post('/{card}/set', [\App\Http\Controllers\Api\CardApiController::class, 'updateSet']);
        });
    });

    // Card Sets routes
    Route::prefix('sets')->group(function () {
        Route::get('/', [CardSetApiController::class, 'index']);
        Route::get('/{id}', [CardSetApiController::class, 'show']);
    });


    // Image route
    Route::get('/image/card/{card}', [ImageController::class, 'showCardImage'])->name('api.image.card');

    // Card Analysis & Confirmation
    Route::post('/card/analyze', [\App\Http\Controllers\Api\CardAnalysisController::class, 'analyze']);
    Route::post('/card/confirm', [\App\Http\Controllers\Api\CardAnalysisController::class, 'confirm']);
    Route::delete('/card/delete', [\App\Http\Controllers\Api\CardAnalysisController::class, 'delete']);

    // Card Matching routes
    Route::prefix('matching')->group(function () {
        Route::get('/cards/{card}/suggestions', [\App\Http\Controllers\Api\MatchingApiController::class, 'suggestions']);
        Route::post('/cards/{card}/match', [\App\Http\Controllers\Api\MatchingApiController::class, 'match']);
        Route::post('/auto-match', [\App\Http\Controllers\Api\MatchingApiController::class, 'autoMatch']);
    });

    // Market Data routes
    Route::post('/market-data/import', [\App\Http\Controllers\Api\MarketDataApiController::class, 'import']);
});
