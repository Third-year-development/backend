<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Whisper;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class WhisperController extends Controller
{
    /**
     * フォローしているユーザーと自分のささやき一覧を取得して返す。
     */
    public function index()
    {
        // ログインユーザーを取得
        $user = Auth::user();
        
        // 自分がフォローしているユーザーのID一覧を取得
        $followingIds = $user->follows()->pluck('users.id');
        
        // 自分のIDも一覧に追加（自分のささやきも表示するため）
        $followingIds->push($user->id);

        // フォローしているユーザーと自分のささやきを作成日の降順で取得
        $whispers = Whisper::with('user') // ユーザー情報も一緒に取得
            ->whereIn('user_id', $followingIds)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($whispers);
    }

    /**
     * 受け取ったデータをささやき情報として登録する。
     */
    public function store(Request $request)
    {
        // リクエストのバリデーション
        $request->validate([
            'text' => 'required|string|max:255',
        ]);

        // ささやき情報として登録
        $whisper = Whisper::create([
            'user_id' => Auth::id(),
            'text' => $request->text,
        ]);

        return response()->json([
            'message' => 'ささやきを投稿しました。',
            'whisper' => $whisper
        ], 201); // 201 Created
    }

    /**
     * 指定されたIDのユーザー情報とささやき一覧を取得して返す。
     */
    public function show(string $id)
    {
        // 引数のidのユーザ情報を取得（存在しない場合は404エラー）
        $user = User::findOrFail($id);

        // そのユーザーのささやき一覧を作成日の降順で取得
        $whispers = Whisper::where('user_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'user' => $user,
            'whispers' => $whispers
        ]);
    }

    /**
     * 指定されたささやき情報を更新する。
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * 指定されたIDのささやき情報を削除する。
     */
    public function destroy(string $id)
    {
        // 削除対象のささやきを取得
        $whisper = Whisper::findOrFail($id);

        // 作成したユーザでないと削除できないようにチェック
        if (Auth::id() !== $whisper->user_id) {
            return response()->json(['message' => '削除する権限がありません。'], 403);
        }

        // 削除実行
        $whisper->delete();

        return response()->json(['message' => 'ささやきを削除しました。']);
    }
}
