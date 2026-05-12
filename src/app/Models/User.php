<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function whispers(): HasMany
    {
        return $this->hasMany(Whisper::class);
    }

    public function follows(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'follow_users',
            'user_id',
            'follow_user_id'
        )->withTimestamps();
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'follow_users',
            'follow_user_id',
            'user_id'
        )->withTimestamps();
    }

    public function isFollowing(int $userId): bool
    {
        return $this->follows()->where('users.id', $userId)->exists();
    }

    public function likedWhispers(): BelongsToMany
    {
        return $this->belongsToMany(
            Whisper::class,
            'likes',
            'user_id',
            'whisper_id'
        )->withTimestamps();
    }
}
