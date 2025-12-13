<?php

namespace App\Infrastructure\GiangVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DiemSinhVien extends Model
{
    use HasUuids;

    protected $table = 'diem_sinh_vien';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'sinh_vien_id',
        'lop_hoc_phan_id',
        'diem_chuyen_can',
        'diem_giua_ky',
        'diem_cuoi_ky',
        'diem_tong_ket',
        'ghi_chu',
    ];

    protected $casts = [
        'diem_chuyen_can' => 'decimal:2',
        'diem_giua_ky' => 'decimal:2',
        'diem_cuoi_ky' => 'decimal:2',
        'diem_tong_ket' => 'decimal:2',
    ];

    public function sinhVien()
    {
        return $this->belongsTo(\App\Infrastructure\SinhVien\Persistence\Models\SinhVien::class, 'sinh_vien_id');
    }

    public function lopHocPhan()
    {
        return $this->belongsTo(\App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan::class, 'lop_hoc_phan_id');
    }
}
