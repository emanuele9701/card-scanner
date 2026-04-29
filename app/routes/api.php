<?php

use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserCardCollectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Auth (pubbliche) ────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// ── Protette con Sanctum ────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Collezione carte
    Route::prefix('collection')->group(function () {
        Route::get('/', [UserCardCollectionController::class, 'index']);
        Route::get('/stats', [UserCardCollectionController::class, 'stats']);
        Route::post('/', [UserCardCollectionController::class, 'store']);
        Route::get('/{id}', [UserCardCollectionController::class, 'show']);
        Route::put('/{id}', [UserCardCollectionController::class, 'update']);
        Route::delete('/{id}', [UserCardCollectionController::class, 'destroy']);
        Route::delete('/{id}/photos/{mediaId}', [UserCardCollectionController::class, 'deletePhoto']);
    });
});
