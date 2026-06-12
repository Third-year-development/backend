<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FollwerController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WhisperController;
use Illuminate\Support\Facades\Route;

// ============================
// すべてのAPIは /api/v1 から始まる
// ============================
Route::prefix('v1')->group(function () {

    // --- 1. 認証処理 ---
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);       // ログイン処理
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum'); // ログアウト処理（要ログイン）
    });

    // --- 2. ユーザー登録・更新・削除処理 ---
    Route::prefix('users')->group(function () {
        Route::post('/register', [UserController::class, 'register']);                              // ユーザー新規登録
        Route::post('/profile/{id}', [UserController::class, 'update'])->middleware('auth:sanctum'); // プロフィール更新（要ログイン）
        Route::post('/delete/{id}', [UserController::class, 'destroy'])->middleware('auth:sanctum'); // ユーザー削除（要ログイン）
    });

    // --- ログインユーザー情報取得 ---
    Route::get('/user', [UserController::class, 'show'])->middleware('auth:sanctum');

    // ============================
    // 以下はすべてログイン必須（auth:sanctum）
    // ============================
    Route::middleware('auth:sanctum')->group(function () {

        // --- 5.2 タイムライン（ささやき）取得 ---
        Route::get('/whispers', [WhisperController::class, 'index']);            // フォロー中＋自分のささやき一覧を取得

        // --- 3.1 ささやき登録 ---
        Route::post('/whispers', [WhisperController::class, 'store']);           // 新しいささやきを投稿

        // --- ユーザー別ささやき一覧取得 ---
        Route::get('/user/whispers/{id}', [WhisperController::class, 'show']);   // 指定ユーザーのささやき一覧を取得

        // --- 3.2 ささやき削除 ---
        Route::post('/whispers/{id}', [WhisperController::class, 'destroy']);    // ささやきを削除（本人のみ可）

        // --- 4.1 ユーザー名検索 ---
        Route::get('/search/users/{keyword}', [SearchController::class, 'usernameSearch']);   // ユーザー名で部分一致検索

        // --- 4.2 ささやき検索 ---
        Route::get('/search/whispers/{keyword}', [SearchController::class, 'whisperSearch']); // ささやき本文で部分一致検索

        // --- フォロー一覧取得 ---
        Route::get('/following', [FollwerController::class, 'following']);       // フォロー中のユーザー一覧を取得

        // --- フォロワー一覧取得 ---
        Route::get('/followers', [FollwerController::class, 'followers']);       // フォロワーのユーザー一覧を取得

        // --- フォロー登録・解除 ---
        Route::post('/followcheck', [RegistrationController::class, 'followRegister']); // フォロー状態をトグル

        // --- イイね登録・解除 ---
        Route::post('/likecheck', [RegistrationController::class, 'likeRegister']);     // いいね状態をトグル
    });
});
