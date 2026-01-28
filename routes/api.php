<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CollectionApiController;
use App\Http\Controllers\Api\CardSetApiController;

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
    });

    // Card Sets routes
    Route::prefix('sets')->group(function () {
        Route::get('/', [CardSetApiController::class, 'index']);
        Route::get('/{id}', [CardSetApiController::class, 'show']);
    });
});
