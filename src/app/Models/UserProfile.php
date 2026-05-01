<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;
    // 主キーの関連付け
    protected $primaryKey = 'user_id';

    // createメソッドで一括代入できるように配列を作成
    protected $fillable = [
        'user_id',
        'profile',
        'icon_file_name',
    ];
}
