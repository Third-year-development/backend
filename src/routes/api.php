<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

// ==========================================
// API v1
// ==========================================
Route::prefix('v1')->group(function () {

    // ------------------------------------------
    // 認証不要ルート（誰でもアクセス可）
    // ------------------------------------------
    
    // [ auth 関連 ]
    Route::prefix('auth')->group(function () {
        // ログイン用API ( POST v1/auth/login )
        Route::post('/login', [AuthController::class, 'login']);
    });

    // [ users 関連 ]
    Route::prefix('users')->group(function () {
        // ユーザー登録用API ( POST v1/users/register )
        Route::post('/register', [UserController::class, 'register']);
    });

    // ------------------------------------------
    // 認証必要ルート（ログイン済みのみアクセス可）
    // ------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        
        // [ auth 関連 ]
        Route::prefix('auth')->group(function () {
            // ログアウト用API ( POST v1/auth/logout )
            Route::post('/logout', [AuthController::class, 'logout']);
        });

        // ログイン中のユーザー情報を取得 ( GET v1/user )
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
    });

});
