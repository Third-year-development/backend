<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Whisper extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'whisper_id',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'whisper_id');
    }

    public function likedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'likes')
            ->withTimestamps();
    }

    public function getLikesCountAttribute(): int
    {
        return $this->likedBy()->count();
    }
}
