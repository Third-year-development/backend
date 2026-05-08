<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Whisper extends Model
{
    use HasFactory;

    /**
     * 複数代入可能な属性
     */
    protected $fillable = [
        'user_id',
        'text',
    ];

    /**
     * APIレスポンス等のためにアクセサを含める（任意）
     */
    protected $appends = ['likes_count'];

    /**
     * Whisperを投稿したユーザーを取得するリレーション（従属関係）
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この投稿を「いいね」したユーザー一覧のリレーショナル（n対n）
     */
    public function likedBy()
    {
        return $this->belongsToMany(User::class, 'likes')->withTimestamps();
    }

    /**
     * この投稿の「いいね」数を取得するアクセサ
     */
    public function getLikesCountAttribute()
    {
        return $this->likedBy()->count();
    }
}
