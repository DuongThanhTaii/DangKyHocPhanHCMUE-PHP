<?php

namespace App\Infrastructure\Auth\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class UserProfile extends Model
{
    use HasUuids;

    protected $table = 'users';
    protected $primaryKey = 'id';

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    protected $fillable = [
        'ma_nhan_vien',
        'ho_ten',
        'tai_khoan_id',
        'email',
    ];

    protected $casts = [
        'email' => 'string', // type USER-DEFINED vẫn đọc như string
    ];
}
