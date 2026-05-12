<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhisperController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::prefix('users')->group(function () {
        Route::post('/register', [UserController::class, 'register']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
        });

        Route::post('/whispers', [WhisperController::class, 'store']);

        Route::get('/user', function (Request $request) {
            return $request->user()->load('profile');
        });
    });
});
