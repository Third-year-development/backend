<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// ==========================================
// 認証不要ルート（誰でもアクセス可）
// ==========================================
// ユーザー登録用API
Route::post('/register', [AuthController::class, 'register']);

// ログイン用API
Route::post('/login', [AuthController::class, 'login']);

// ==========================================
// 認証必要ルート（ログイン済みのみアクセス可）
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // ログイン中のユーザー情報を取得
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
