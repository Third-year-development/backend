<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 複数代入可能な属性（DBに保存・更新できるカラム）
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * シリアライズ（配列やJSONに変換）する際に隠蔽する属性。
     * パスワードなどがAPIのレスポンスに含まれないようにする。
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * キャスト（データ型の変換）を行う属性。
     * 取得時や保存時に自動で指定の型に変換。
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * user_profilesテーブルとのリレーション 自分のプロフィールを取得するためのリレーション（1対1）
     */
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * 自分の投稿（Whisper）一覧のリレーショナル（1対n）
     */
    public function whispers()
    {
        return $this->hasMany(Whisper::class);
    }
    /**
     * 自分がフォローしているユーザー一覧のリレーショナル(n対n)
     */
    public function follows()
    {
        return $this->belongsToMany(User::class, 'follow_users', 'user_id', 'follow_user_id');
    }

    /**
     * 自分をフォローしているユーザー一覧（フォロワー一覧）のリレーショナル(n対n)
     */
    public function followers()
    {
        return $this->belongsToMany(User::class, 'follow_users', 'follow_user_id', 'user_id');
    }

    /**
     * 自分が引数のユーザーをすでにフォローしているか判定する
     */
public function isFollowing($userId)
    {
        return $this->follows()->where('follow_user_id', $userId)->exists();
    }

    /**
     * ユーザーが「いいね」した投稿一覧のリレーショナル（n対n）
     */
    public function likedWhispers()
    {
        return $this->belongsToMany(Whisper::class, 'likes')->withTimestamps();
    }
}
