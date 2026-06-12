<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Whisper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * 検索コントローラー
 *
 * ユーザー名検索とささやき検索を処理する。
 */
class SearchController extends Controller
{
    /**
     * ユーザー名検索処理
     *
     * キーワードでユーザー名を部分一致検索し、ユーザー一覧を返す。
     *
     * エンドポイント: GET v1/search/users/{keyword}
     * レスポンス: user_line[]
     */
    public function usernameSearch(Request $request, string $keyword): JsonResponse
    {
        // キーワードが2文字未満の場合は422エラーで返却（検索精度を保つため）
        if (mb_strlen($keyword) < 2) {
            return response()->json([
                'message' => 'Keyword must be at least 2 characters.',
            ], 422);
        }

        // ============================
        // ユーザー名で部分一致検索
        // ============================
        // User::with('profile')                → プロフィール情報も一緒に取得
        // ->withCount(['follows', 'followers']) → フォロー数・フォロワー数をカウントで取得
        // ->where('name', 'like', '%'.$keyword.'%')
        //   → nameカラムにキーワードが含まれるレコードを検索
        //     例: keyword="田中" → "%田中%" → 「田中太郎」「山田中村」などがヒット
        // ->orderBy('name')                    → ユーザー名の昇順（あいうえお順）で並べる
        // ->limit(...)                         → 取得件数の上限を設定
        $users = User::with('profile')
            ->withCount(['follows', 'followers'])
            ->where('name', 'like', '%'.$keyword.'%')
            ->orderBy('name')
            ->limit($this->limit($request))
            ->get();

        // 検索結果をJSON形式で返却
        return response()->json([
            'user_line' => $users,
        ]);
    }

    /**
     * ささやき検索処理
     *
     * キーワードでささやきの本文を部分一致検索し、ささやき一覧を返す。
     *
     * エンドポイント: GET v1/search/whispers/{keyword}
     * レスポンス: whisper[]
     */
    public function whisperSearch(Request $request, string $keyword): JsonResponse
    {
        // キーワードが2文字未満の場合は422エラーで返却
        if (mb_strlen($keyword) < 2) {
            return response()->json([
                'message' => 'Keyword must be at least 2 characters.',
            ], 422);
        }

        // ============================
        // ささやき本文で部分一致検索
        // ============================
        // ->where('content', 'like', '%'.$keyword.'%')
        //   → contentカラムにキーワードが含まれるささやきを検索
        // ->orderByDesc('created_at')  → 作成日の新しい順に並べる
        $whispers = Whisper::with(['user.profile'])
            ->withCount('likedBy')
            ->where('content', 'like', '%'.$keyword.'%')
            ->orderByDesc('created_at')
            ->limit($this->limit($request))
            ->get();

        // 各ささやきに「自分がいいね済みか」の情報を付加して返却
        return response()->json([
            'whisper' => $this->withViewerStates($whispers, $request->user()->id),
        ]);
    }

    /**
     * 複数のささやきに「自分がいいね済みか」の情報を一括付加するヘルパー
     *
     * WhisperControllerと同じロジック。
     * 各ささやきの配列データに liked_by_me（true/false）を追加して返す。
     */
    private function withViewerStates(Collection $whispers, int $viewerId): Collection
    {
        // ログインユーザーがいいね済みのささやきIDを一括取得
        // ->flip() で値をキーに変換し、has() での検索を高速化
        $likedIds = User::findOrFail($viewerId)
            ->likedWhispers()
            ->whereIn('whispers.id', $whispers->pluck('id'))
            ->pluck('whispers.id')
            ->flip();

        // 各ささやきに liked_by_me を付加して返却
        return $whispers->map(function (Whisper $whisper) use ($likedIds): array {
            $data = $whisper->toArray();
            $data['liked_by_me'] = $likedIds->has($whisper->id);  // true: いいね済み / false: 未いいね

            return $data;
        });
    }

    /**
     * 取得件数の上限を決定するヘルパー
     *
     * クエリパラメータ limit の値を取得し、1〜100の範囲に制限する。
     * 指定なしの場合はデフォルト50件で処理。
     */
    private function limit(Request $request): int
    {
        return min(max((int) $request->query('limit', 50), 1), 100);
    }
}
