<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Middleware\PostmanTestUser;
use App\Http\Controllers\LobbyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    //Independent Endpoints
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);

    //Auth Endpoints
    Route::match(['get', 'post'], '/apps-login', [AuthController::class, 'redirectToIee']);
    Route::get('/apps-callback', [AuthController::class, 'handleIeeCallback']);
    Route::post('/dev-login', [AuthController::class, 'devLogin']);

    Route::middleware('auth.token')->group(function () {
        //Profile Endpoints
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/logout', [AuthController::class, 'logout']);

        //Lobby Endpoints
        Route::get('/lobbies', [LobbyController::class, 'index']);
        Route::post('/lobbies', [LobbyController::class, 'store']);
        Route::post('/lobbies/{id}/join', [LobbyController::class, 'join']);
        Route::delete('/lobbies', [LobbyController::class, 'cancel']);
    });


    // Game Endpoints
    Route::middleware('auth.token')->group(function () {
        Route::get('/game', [GameController::class, 'show']);
        Route::post('/game/start', [GameController::class, 'start']);
        Route::post('/game/move', [GameController::class, 'move']);
    });
});
