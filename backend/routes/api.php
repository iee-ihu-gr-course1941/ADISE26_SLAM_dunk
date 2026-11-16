<?php

use App\Http\Controllers\GameController;
use App\Http\Middleware\PostmanTestUser;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(PostmanTestUser::class)
    ->group(function () {
        Route::get('/game', [GameController::class, 'show']);
        Route::post('/game/start', [GameController::class, 'start']);
        Route::post('/game/move', [GameController::class, 'move']);
    });
