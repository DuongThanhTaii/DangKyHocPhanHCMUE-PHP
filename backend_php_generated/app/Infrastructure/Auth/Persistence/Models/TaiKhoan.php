<?php

namespace App\Infrastructure\Auth\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class TaiKhoan extends Model
{
    protected $table = 'tai_khoan';
    public $timestamps = false; // Assuming usage of created_at/updated_at might differ or be handled manually

    protected $fillable = [
        'ten_dang_nhap',
        'mat_khau',
        'loai_tai_khoan',
        'trang_thai_hoat_dong'
    ];

    protected $casts = [
        'trang_thai_hoat_dong' => 'boolean',
    ];
}
