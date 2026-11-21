<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Middleware\PostmanTestUser;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::get('/leaderboard', [LeaderboardController::class, 'index']);
    //Auth Endpoints
    Route::match(['get', 'post'], '/apps-login', [AuthController::class, 'redirectToIee']);
    Route::get('/apps-callback', [AuthController::class, 'handleIeeCallback']);
    Route::post('/dev-login', [AuthController::class, 'devLogin']);

    //Profile Endpoints
    Route::middleware('auth.token')->group(function () {
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Game Endpoints
    Route::middleware(PostmanTestUser::class)->group(function () {
        Route::get('/game', [GameController::class, 'show']);
        Route::post('/game/start', [GameController::class, 'start']);
        Route::post('/game/move', [GameController::class, 'move']);
    });
});
