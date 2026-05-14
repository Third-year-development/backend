<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'follow_user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function followUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follow_user_id');
    }
}
