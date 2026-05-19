<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhisperController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    Route::prefix('users')->group(function () {
        Route::post('/register', [UserController::class, 'register']);
        Route::post('/profile/{id}', [UserController::class, 'update'])->middleware('auth:sanctum');
        Route::post('/delete/{id}', [UserController::class, 'destroy'])->middleware('auth:sanctum');
    });

    Route::get('/user', [UserController::class, 'show'])->middleware('auth:sanctum');

    Route::middleware('auth:sanctum')->group(function () {
        // 検索用ルート
        Route::get('/search/users/{keyword}', [SearchController::class, 'usernameSearch']);
        Route::get('/search/whispers/{keyword}', [SearchController::class, 'whisperSearch']);

        // Whisper関連ルート
        Route::get('/whispers', [WhisperController::class, 'index']);
        Route::post('/whispers', [WhisperController::class, 'store']);
        Route::get('/user/whispers/{id}', [WhisperController::class, 'show']);
        Route::post('/whispers/{id}', [WhisperController::class, 'destroy']);
    });
});
