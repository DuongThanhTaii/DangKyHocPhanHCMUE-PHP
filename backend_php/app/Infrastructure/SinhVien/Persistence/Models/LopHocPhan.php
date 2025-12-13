<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;

class LopHocPhan extends Model
{
    use HasUuids;

    protected $table = 'lop_hoc_phan';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'hoc_phan_id',
        'ma_lop',
        'giang_vien_id',
        'so_luong_toi_da',
        'so_luong_hien_tai',
        'phong_mac_dinh_id',
        'ngay_bat_dau',
        'ngay_ket_thuc',
    ];

    protected $casts = [
        'ngay_bat_dau' => 'date',
        'ngay_ket_thuc' => 'date',
    ];

    /**
     * HocPhan (course section)
     */
    public function hocPhan()
    {
        return $this->belongsTo(HocPhan::class, 'hoc_phan_id');
    }

    /**
     * Weekly schedules
     */
    public function lichHocDinhKys()
    {
        return $this->hasMany(LichHocDinhKy::class, 'lop_hoc_phan_id');
    }

    /**
     * Teacher (giangVien.id = users.id)
     */
    public function giangVien()
    {
        return $this->belongsTo(UserProfile::class, 'giang_vien_id');
    }

    /**
     * Enrollments for this class
     */
    public function dangKyHocPhans()
    {
        return $this->hasMany(DangKyHocPhan::class, 'lop_hoc_phan_id');
    }

    /**
     * Documents for this class
     */
    public function taiLieus()
    {
        return $this->hasMany(TaiLieu::class, 'lop_hoc_phan_id');
    }
}
