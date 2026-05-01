<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * ログイン処理
     * 
     * 登録済みのメールアドレスとパスワードを使用してログイン。
     * 認証に成功すると、API通信に必要なトークンを発行。
     */
    public function login(Request $request)
    {
        // バリデーション（入力チェック）
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

        // ユーザーの存在確認
        // 入力されたメールアドレスを持つユーザーをデータベースから探す。
        $user = User::where('email', $request->email)->first();

        // パスワードの確認
        // 「ユーザーが存在しない」または「パスワードが一致しない」場合はエラー。
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'メールアドレスまたはパスワードが間違っています。'
            ], 401); // 401 Unauthorized (認証失敗)
        }

        // 新しいトークンの発行
        // 次回からの通信用に新しいトークン（通行証）を発行。
        $token = $user->createToken('mobile')->plainTextToken;

        // 結果の返却
        // トークンとユーザー情報を返す。
        return response()->json([
            'message' => 'ログインに成功しました。',
            'token'   => $token,
            'user'    => $user
        ], 200); // 200 OK (成功)
    }

    /**
     * ログアウト処理
     * 
     * ログイン中のユーザのトークンを破棄し、メッセージを返す。
     */
    public function logout(Request $request)
    {
        // 現在のアクセストークンを削除してログアウト
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'ログアウトしました。'
        ], 200);
    }
}
