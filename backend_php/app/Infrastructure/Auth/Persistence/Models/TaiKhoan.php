<?php

namespace App\Infrastructure\Auth\Persistence\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Tymon\JWTAuth\Contracts\JWTSubject;

class TaiKhoan extends Authenticatable implements JWTSubject
{
    use HasUuids;

    protected $table = 'tai_khoan';
    protected $primaryKey = 'id';

    // QUAN TRỌNG: uuid => không auto increment, kiểu string
    public $incrementing = false;
    protected $keyType = 'string';

    // Bảng này không có created_at, updated_at chuẩn
    public $timestamps = false;

    protected $fillable = [
        'ten_dang_nhap',
        'mat_khau',
        'loai_tai_khoan',
        'trang_thai_hoat_dong',
        'ngay_tao',
        'updated_at',
    ];

    protected $hidden = ['mat_khau'];

    public function getAuthPassword()
    {
        return $this->mat_khau;
    }

    // JWTSubject
    public function getJWTIdentifier()
    {
        // sub trong token sẽ là uuid string, VD: "4d5c-..."
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Quan hệ với bảng users (cán bộ, giảng viên, phòng đào tạo...)
    public function userProfile()
    {
        return $this->hasOne(UserProfile::class, 'tai_khoan_id');
    }
}
