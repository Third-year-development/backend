<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'profile',
        'icon_file_name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
