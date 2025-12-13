<?php

namespace App\Infrastructure\Pdt\Persistence\Models;

use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SinhVien extends Model
{
    use HasUuids;

    protected $table = 'sinh_vien';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false; // Based on schema, strict mapping, check if created_at exists? Legacy schema didn't show created_at for SinhVien, only Users/TaiKhoan.

    protected $fillable = [
        'id',
        'ma_so_sinh_vien',
        'lop',
        'khoa_id',
        'khoa_hoc',
        'ngay_nhap_hoc',
        'nganh_id',
    ];

    public function user()
    {
        return $this->belongsTo(UserProfile::class, 'id', 'id');
    }

    public function khoa()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\Khoa::class, 'khoa_id', 'id');
    }

    public function nganh()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\NganhHoc::class, 'nganh_id', 'id');
    }
}
