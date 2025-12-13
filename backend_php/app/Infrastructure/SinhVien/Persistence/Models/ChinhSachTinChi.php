<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ChinhSachTinChi extends Model
{
    use HasUuids;

    protected $table = 'chinh_sach_tin_chi';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;  // DB không có updated_at, created_at

    protected $fillable = [
        'id',
        'hoc_ky_id',
        'khoa_id',
        'nganh_id',
        'phi_moi_tin_chi',
        'ngay_hieu_luc',
        'ngay_het_hieu_luc',
    ];

    protected $casts = [
        'phi_moi_tin_chi' => 'decimal:2',
        'ngay_hieu_luc' => 'date',
        'ngay_het_hieu_luc' => 'date',
    ];

    public function hocKy()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\HocKy::class, 'hoc_ky_id');
    }

    public function khoa()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\Khoa::class, 'khoa_id');
    }

    public function nganh()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\NganhHoc::class, 'nganh_id');
    }
}
