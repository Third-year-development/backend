<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Whisper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * ささやき管理コントローラー
 *
 * ささやきの一覧取得・登録・ユーザー別表示・削除を処理する。
 */
class WhisperController extends Controller
{
    /**
     * ささやき一覧取得処理
     *
     * ログインユーザーがフォローしているユーザーと自分のささやきを
     * 作成日の降順で取得し、その値を返す。
     *
     * エンドポイント: GET v1/whispers
     */
    public function index(Request $request): JsonResponse
    {
        // ログイン中のユーザーを取得
        $user = $request->user();

        // ============================
        // フォロー中ユーザーのID一覧を作成
        // ============================
        // $user->follows()         → フォロー中のユーザー一覧を取得するリレーション
        // ->pluck('users.id')      → その中からIDだけを配列で抜き出す
        // ->push($user->id)        → 自分自身のIDも配列に追加（自分の投稿も表示するため）
        // ->unique()               → 重複を除去
        // ->values()               → 配列のキーを0から振り直す
        $followingIds = $user->follows()->pluck('users.id')->push($user->id)->unique()->values();

        // ============================
        // ささやきをDBから取得
        // ============================
        // Whisper::with(['user.profile'])  → ささやきと一緒に投稿者情報＋プロフィールも取得（N+1問題を防止）
        // ->withCount('likedBy')           → いいね数を liked_by_count として取得
        // ->whereIn('user_id', ...)        → user_idが上で作ったID一覧に含まれるものだけに絞り込む
        // ->orderByDesc('created_at')      → 作成日の新しい順（降順）に並べる
        // ->limit(...)                     → 取得件数の上限を設定
        // ->get()                          → 実際にSQLを実行して結果を取得
        $whispers = Whisper::with(['user.profile'])
            ->withCount('likedBy')
            ->whereIn('user_id', $followingIds)
            ->orderByDesc('created_at')
            ->limit($this->limit($request))
            ->get();

        // JSON形式でレスポンスを返却
        // withViewerStates() で各ささやきに「自分がいいね済みか」の情報を付加
        return response()->json([
            'whisper' => $this->withViewerStates($whispers, $user->id),
        ]);
    }

    /**
     * ささやき登録処理
     *
     * textデータを受け取り、ささやき情報として登録する。
     * メッセージを返す。
     *
     * エンドポイント: POST v1/whispers
     * リクエストパラメータ: text（必須）
     */
    public function store(Request $request): JsonResponse
    {
        // バリデーションルールを定義
        // text: 必須・文字列
        $validator = Validator::make($request->all(), [
            'text' => 'required|string',
        ]);

        // バリデーションエラー時は422エラーで返却
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // ============================
        // ささやきをDBに登録
        // ============================
        // Whisper::create([...])           → whispersテーブルに新しい1行を追加
        //   'user_id'   → ログインユーザーのID
        //   'content'   → リクエストから受け取ったテキスト
        //   'whisper_id' → 返信先（今回はnull＝通常投稿）
        // ->load(['user.profile'])         → 登録後に投稿者情報＋プロフィールを読み込む
        // ->loadCount('likedBy')           → 登録後にいいね数を読み込む
        $whisper = Whisper::create([
            'user_id' => $request->user()->id,
            'content' => $request->string('text')->toString(),
            'whisper_id' => null,
        ])->load(['user.profile'])
            ->loadCount('likedBy');

        // 201（Created）ステータスで登録結果を返却
        return response()->json([
            'message' => 'Whisper created.',
            'whisper' => $this->withViewerState($whisper, $request->user()->id),
        ], 201);
    }

    /**
     * ユーザー別ささやき一覧取得処理
     *
     * 引数のidのユーザのユーザー情報とささやきの一覧を取得して返す。
     *
     * エンドポイント: GET v1/user/whispers/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        // ============================
        // 指定IDのユーザー情報を取得
        // ============================
        // User::with('profile')                → プロフィール情報も一緒に取得
        // ->withCount(['follows', 'followers']) → フォロー数・フォロワー数をカウントで取得
        // ->findOrFail($id)                    → IDで検索（見つからなければ404エラー）
        $user = User::with('profile')
            ->withCount(['follows', 'followers'])
            ->findOrFail($id);

        // ============================
        // そのユーザーのささやき一覧を取得
        // ============================
        // ->where('user_id', $user->id)   → 指定ユーザーの投稿だけに絞り込む
        // ->orderByDesc('created_at')     → 作成日の新しい順に並べる
        $whispers = Whisper::with(['user.profile'])
            ->withCount('likedBy')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($this->limit($request))
            ->get();

        // ユーザー情報とささやき一覧をJSON形式で返却
        return response()->json([
            'user_line' => $user,       // ユーザー情報（フォロー数・フォロワー数付き）
            'userprofile' => $user,     // ユーザープロフィール（互換性のため同じデータ）
            'whisper' => $this->withViewerStates($whispers, $request->user()->id),
        ]);
    }

    /**
     * ささやき削除処理
     *
     * 引数のidのささやき情報を削除する。
     * ただし、このささやきは作成したユーザーでないと削除できない。
     *
     * エンドポイント: POST v1/whispers/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        // 指定IDのささやきをDBから検索（見つからなければ404エラー）
        $whisper = Whisper::findOrFail($id);

        // ============================
        // 権限チェック
        // ============================
        // ささやきの投稿者IDとログインユーザーのIDが一致しなければ403エラーで拒否
        // → 自分が投稿したささやきのみ削除可能
        if ($whisper->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only delete your own whisper.',
            ], 403);
        }

        // ささやきをDBから削除
        $whisper->delete();

        // 削除成功メッセージを返却
        return response()->json([
            'message' => 'Whisper deleted.',
        ]);
    }

    /**
     * 単一のささやきに「自分がいいね済みか」の情報を付加するヘルパー
     *
     * 1件だけのささやきをCollectionに変換してから withViewerStates() に渡し、
     * 結果の1件目を取り出して返す。
     */
    private function withViewerState(Whisper $whisper, int $viewerId): array
    {
        return $this->withViewerStates(collect([$whisper]), $viewerId)->first();
    }

    /**
     * 複数のささやきに「自分がいいね済みか」の情報を一括付加するヘルパー
     *
     * 各ささやきの配列データに liked_by_me（true/false）を追加して返す。
     * N+1問題を防ぐため、いいね済みIDを一括取得してから照合する。
     */
    private function withViewerStates(Collection $whispers, int $viewerId): Collection
    {
        // ============================
        // ログインユーザーがいいね済みのささやきIDを一括取得
        // ============================
        // $whispers->pluck('id')             → 表示対象のささやきIDを配列で取得
        // ->whereIn('whispers.id', $ids)     → その中で自分がいいねしたものだけを抽出
        // ->pluck('whispers.id')             → IDだけを取り出す
        // ->flip()                           → 値をキーに変換（高速な検索用）
        //   例: [3, 7, 12] → [3 => 0, 7 => 1, 12 => 2]
        $ids = $whispers->pluck('id');
        $likedIds = User::findOrFail($viewerId)
            ->likedWhispers()
            ->whereIn('whispers.id', $ids)
            ->pluck('whispers.id')
            ->flip();

        // ============================
        // 各ささやきに liked_by_me を付加
        // ============================
        // ->map() で各ささやきを1つずつ処理
        // ->toArray() でモデルを配列に変換
        // $likedIds->has() でそのIDがいいね済みリストに含まれるか判定
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
