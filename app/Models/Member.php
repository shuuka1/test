<?php

// app/Models/Member.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Member extends Authenticatable
{
    use HasFactory;  // 使用 HasApiTokens 特性

    // 禁用時間戳
    public $timestamps = false;

    // 明確指定資料表名稱為 `member`（如果不使用 Laravel 預設的複數形式）
    protected $table = 'member';

    protected $fillable = [
        'UserPWD',
        'Gender',
        'Avatar',
        'UserIntro',
    ];

    protected $hidden = [
        'password',
    ];

    protected $guarded = [
        'Email',
        'UserName',
    ];
}
