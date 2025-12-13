<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class HocPhan extends Model
{
    use HasUuids;

    protected $table = 'hoc_phan';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'mon_hoc_id',
        'ten_hoc_phan',
        'so_lop',
        'trang_thai_mo',
        'id_hoc_ky',
    ];

    protected $casts = [
        'trang_thai_mo' => 'boolean',
    ];

    public function monHoc()
    {
        return $this->belongsTo(MonHoc::class, 'mon_hoc_id');
    }

    public function hocKy()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\HocKy::class, 'id_hoc_ky');
    }

    public function ghiDanhHocPhans()
    {
        return $this->hasMany(GhiDanhHocPhan::class, 'hoc_phan_id');
    }

    public function lopHocPhans()
    {
        return $this->hasMany(LopHocPhan::class, 'hoc_phan_id');
    }
}
