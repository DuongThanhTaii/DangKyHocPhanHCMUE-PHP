<?php

namespace App\Infrastructure\Auth\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    protected $fillable = [
        'ho_ten',
        'email',
        'tai_khoan_id',
        'ma_nhan_vien'
    ];

    public function taiKhoan()
    {
        return $this->belongsTo(TaiKhoan::class, 'tai_khoan_id');
    }
}
