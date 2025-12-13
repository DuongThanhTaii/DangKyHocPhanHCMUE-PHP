<?php

namespace App\Infrastructure\Payment\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class HocPhi extends Model
{
    use HasUuids;

    protected $table = 'hoc_phi';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'sinh_vien_id',
        'hoc_ky_id',
        'tong_hoc_phi',
        'trang_thai_thanh_toan',
        'ngay_tinh_toan',
        'ngay_thanh_toan',
        'chinh_sach_id',
        'ghi_chu',
    ];

    protected $casts = [
        'tong_hoc_phi' => 'decimal:2',
        'ngay_tinh_toan' => 'datetime',
        'ngay_thanh_toan' => 'datetime',
    ];

    public function sinhVien()
    {
        return $this->belongsTo(\App\Infrastructure\SinhVien\Persistence\Models\SinhVien::class, 'sinh_vien_id');
    }

    public function hocKy()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\HocKy::class, 'hoc_ky_id');
    }
}
