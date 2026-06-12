<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * ささやき管理モデル
 *
 * テーブル名: whispers
 * ユーザーが投稿する「ささやき」を管理するテーブル。
 */
class Whisper extends Model
{
    use HasFactory;

    /**
     * 一括代入を許可するカラムを定義
     * - content: ささやきの本文
     * - whisper_id: 返信先のささやきID（リプライ機能用）
     * - user_id: 投稿したユーザーのID
     */
    protected $fillable = [
        'content',
        'whisper_id',
        'user_id',
    ];

    /**
     * Whisperを投稿したユーザーを取得するリレーション（従属関係）
     * whispers.user_id → users.id で紐づけ
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 返信先の親ささやきを取得するリレーション（従属関係）
     * whispers.whisper_id → whispers.id で自己参照
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'whisper_id');
    }

    /**
     * この投稿を「いいね」したユーザー一覧のリレーション（n対n）
     *
     * likesテーブルを中間テーブルとして、whispers と users を多対多で紐づけ。
     * withTimestamps() で中間テーブルのcreated_at/updated_atも自動管理。
     */
    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'likes')
            ->withTimestamps();
    }

    /**
     * この投稿の「いいね」数を取得するアクセサ（なくてもできます）
     *
     * $whisper->likes_count でいいね数を取得可能にするアクセサ。
     * Laravelの命名規則: getXxxAttribute() → $model->xxx で呼び出し可能。
     */
    public function getLikesCountAttribute(): int
    {
        return $this->likedBy()->count();
    }
}
