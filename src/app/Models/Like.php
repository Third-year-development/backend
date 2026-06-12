<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * いいね情報モデル
 *
 * テーブル名: likes（ピボットテーブル）
 * ユーザーとささやきの中間表。イイねされたら1行追加、イイねが外れたら1行削除で管理。
 */
class Like extends Model
{
    use HasFactory;

    /**
     * 一括代入を許可するカラムを定義
     * - user_id: いいねしたユーザーのID
     * - whisper_id: いいねされたささやきのID
     */
    protected $fillable = [
        'user_id',
        'whisper_id',
    ];

    /**
     * いいねしたユーザーを取得するリレーション（従属関係）
     * likes.user_id → users.id で紐づけ
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * いいねされたささやきを取得するリレーション（従属関係）
     * likes.whisper_id → whispers.id で紐づけ
     */
    public function whisper(): BelongsTo
    {
        return $this->belongsTo(Whisper::class);
    }
}
