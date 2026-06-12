<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FollwerController;
use App\Http\Controllers\RegistrationController;
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
    Route::get('/users/{id}', [UserController::class, 'showById'])->middleware('auth:sanctum');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/whispers', [WhisperController::class, 'index']);
        Route::get('/whispers/all', [WhisperController::class, 'all']);
        Route::post('/whispers', [WhisperController::class, 'store']);
        Route::get('/user/whispers/{id}', [WhisperController::class, 'show']);
        Route::post('/whispers/{id}', [WhisperController::class, 'destroy']);
        Route::get('/search/users/{keyword}', [SearchController::class, 'usernameSearch']);
        Route::get('/search/whispers/{keyword}', [SearchController::class, 'whisperSearch']);
        Route::get('/following', [FollwerController::class, 'following']);
        Route::get('/followers', [FollwerController::class, 'followers']);
        Route::get('/user/following/{id}', [FollwerController::class, 'followingById']);
        Route::get('/user/followers/{id}', [FollwerController::class, 'followersById']);
        Route::post('/followcheck', [RegistrationController::class, 'followRegister']);
        Route::post('/likecheck', [RegistrationController::class, 'likeRegister']);
    });
});
