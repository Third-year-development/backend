<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Whisper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    public function followRegister(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'follow_user_id' => 'required|integer|exists:users,id',
            'following' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $followUserId = (int) $request->input('follow_user_id');

        if ($user->id === $followUserId) {
            return response()->json([
                'message' => 'You cannot follow yourself.',
            ], 422);
        }

        $target = User::findOrFail($followUserId);
        $isFollowing = $user->isFollowing($target->id);
        $desiredState = $request->has('following')
            ? $request->boolean('following')
            : !$isFollowing;

        if ($desiredState) {
            $user->follows()->syncWithoutDetaching([$target->id]);
            $following = true;
            $message = $isFollowing ? 'Already following.' : 'Follow added.';
        } else {
            $user->follows()->detach($target->id);
            $following = false;
            $message = $isFollowing ? 'Follow removed.' : 'Already not following.';
        }

        return response()->json([
            'message' => $message,
            'following' => $following,
        ]);
    }

    /**
     * イイね登録・解除処理
     *
     * whisper_idを受け取り、そのidのささやきをログインユーザーが
     * イイねしていればイイねを外し、イイねされていなければイイねとする。
     *
     * エンドポイント: POST v1/likecheck
     * リクエストパラメータ: whisper_id（必須）, liked（任意）
     * レスポンス: message, liked, liked_by_count, likes_count
     */
    public function likeRegister(Request $request): JsonResponse
    {
        // バリデーションルールを定義
        // whisper_id: 必須・整数・whispersテーブルに存在するIDのみ許可
        // liked: 任意・真偽値（明示的にいいね状態を指定する場合に使用）
        $validator = Validator::make($request->all(), [
            'whisper_id' => 'required|integer|exists:whispers,id',
            'liked' => 'sometimes|boolean',
        ]);

        // バリデーションエラー時は422エラーで返却
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // ログイン中のユーザーを取得
        $user = $request->user();

        // リクエストからwhisper_idを取得し、該当するささやきをDBから検索
        $whisper = Whisper::findOrFail((int) $request->input('whisper_id'));

        // 現在のいいね状態を確認（likesテーブルにレコードが存在するかで判定）
        $liked = $user->likedWhispers()
            ->where('whispers.id', $whisper->id)
            ->exists();

        // 変更前のいいね状態を保持（レスポンスメッセージの出し分けに使用）
        $wasLiked = $liked;

        // いいねの目標状態を決定
        // likedパラメータが送られていれば、その値を使用
        // 送られていなければ、現在の状態を反転（トグル動作）で処理
        $desiredState = $request->has('liked')
            ? $request->boolean('liked')
            : !$liked;

        if ($desiredState) {
            // いいね追加：syncWithoutDetachingで重複登録を防止しつつlikesテーブルに1行追加
            $user->likedWhispers()->syncWithoutDetaching([$whisper->id]);
            $liked = true;
            $message = $wasLiked ? 'Already liked.' : 'Like added.';
        } else {
            // いいね解除：detachでlikesテーブルから該当の1行を削除
            $user->likedWhispers()->detach($whisper->id);
            $liked = false;
            $message = $wasLiked ? 'Like removed.' : 'Already not liked.';
        }

        // このささやきの最新のいいね数をカウントで取得
        $likesCount = $whisper->likedBy()->count();

        // レスポンスをJSON形式で返却
        return response()->json([
            'message' => $message,           // 処理結果メッセージ
            'liked' => $liked,               // 現在のいいね状態（true/false）
            'liked_by_count' => $likesCount,  // いいね数
            'likes_count' => $likesCount,     // いいね数（別名）
        ]);
    }
}
