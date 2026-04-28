<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * ユーザー登録処理（サインアップ）
     * 
     * 新規ユーザーを作成してデータベースへ保存。
     * 登録と同時に認証トークンを発行。
     */
    public function register(Request $request)
    {
        // バリデーション（入力チェック）
        // 必須項目、メール形式、既存ユーザーとの重複を検証
        $validator = Validator::make($request->all(), [
            'name'     => 'required',                     // 必須
            'email'    => 'required|email|unique:users,email', // 必須、メール形式、usersテーブル内で一意
            'password' => 'required|min:6'                // 必須、最低6文字
        ]);

        // エラー発生時の処理（HTTPステータス 422 でエラー内容を返却）
        if ($validator->fails()) {
            return response()->json([
                'message' => 'バリデーションエラー',
                'errors'  => $validator->errors()
            ], 422);
        }

        // ユーザーの作成
        // usersテーブルへ新規レコードを保存
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            // セキュリティ対策のためパスワードをハッシュ化
            'password' => Hash::make($request->password),
        ]);

        // トークンの発行
        // クライアントとの通信に使用するトークンを作成
        $token = $user->createToken('mobile')->plainTextToken;

        // 処理結果の返却
        // 作成成功（ステータス 201）とともに情報を返却
        return response()->json([
            'token' => $token,
            'user'  => $user
        ], 201);
    }

    /**
     * ログイン処理
     * 
     * 登録済みのメールアドレスとパスワードを使用してログイン。
     * 認証に成功すると、API通信に必要なトークンを発行。
     */
    public function login(Request $request)
    {
        // 1. バリデーション（入力チェック）
        // メールとパスワードが空でないか、正しい形式かチェック。
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => '入力内容に誤りがあります。',
                'errors'  => $validator->errors()
            ], 422); // 422 Unprocessable Entity (処理できないリクエスト)
        }

        // 2. ユーザーの存在確認
        // 入力されたメールアドレスを持つユーザーをデータベースから探す。
        $user = User::where('email', $request->email)->first();

        // 3. パスワードの確認
        // 「ユーザーが存在しない」または「パスワードが一致しない」場合はエラー。
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'メールアドレスまたはパスワードが間違っています。'
            ], 401); // 401 Unauthorized (認証失敗)
        }

        // 4. 新しいトークンの発行
        // 次回からの通信用に新しいトークン（通行証）を発行。
        $token = $user->createToken('mobile')->plainTextToken;

        // 5. 結果の返却
        // トークンとユーザー情報を返す。
        return response()->json([
            'message' => 'ログインに成功しました。',
            'token'   => $token,
            'user'    => $user
        ], 200); // 200 OK (成功)
    }
}
