<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Whisper;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    /**
     * ユーザー名検索
     * 引数のkeywordの内容を持つユーザー名を検索してその結果のユーザー一覧を返す
     *
     * @param string $keyword
     * @return JsonResponse
     */
    public function usernameSearch(string $keyword): JsonResponse
    {
        // ユーザー名にキーワードが含まれる(LIKE検索)ユーザーを取得
        $users = User::where('name', 'like', "%{$keyword}%")->get();

        return response()->json([
            'users' => $users
        ]);
    }

    /**
     * ささやき本文検索
     * 引数のkeywordの内容を持つささやきの本文を検索してその結果のささやき一覧を返す（日付の新しい順）
     *
     * @param string $keyword
     * @return JsonResponse
     */
    public function whisperSearch(string $keyword): JsonResponse
    {
        // ささやきの本文にキーワードが含まれるものを検索し、作成日時の新しい順(降順)で取得
        $whispers = Whisper::where('content', 'like', "%{$keyword}%")
            ->orderBy('created_at', 'desc') // 日付の新しい順
            ->get();

        return response()->json([
            'whispers' => $whispers
        ]);
    }
}
