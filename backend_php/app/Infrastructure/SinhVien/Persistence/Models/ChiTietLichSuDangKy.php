<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ChiTietLichSuDangKy extends Model
{
    use HasUuids;

    protected $table = 'chi_tiet_lich_su_dang_ky';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'lich_su_dang_ky_id',
        'dang_ky_hoc_phan_id',
        'hanh_dong',
        'thoi_gian',
    ];

    protected $casts = [
        'thoi_gian' => 'datetime',
    ];

    public function lichSuDangKy()
    {
        return $this->belongsTo(LichSuDangKy::class, 'lich_su_dang_ky_id');
    }

    public function dangKyHocPhan()
    {
        return $this->belongsTo(DangKyHocPhan::class, 'dang_ky_hoc_phan_id');
    }
}
