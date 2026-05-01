<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
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
}
