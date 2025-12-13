<?php

namespace App\Infrastructure\TLK\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DeXuatHocPhan extends Model
{
    use HasUuids;

    protected $table = 'de_xuat_hoc_phan';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'khoa_id',
        'nguoi_tao_id', // FK to Users (not nguoi_de_xuat_id)
        'hoc_ky_id',
        'mon_hoc_id', // FK to MonHoc (not hoc_phan_id)
        'so_lop_du_kien',
        'giang_vien_de_xuat',
        'trang_thai',
        'cap_duyet_hien_tai',
        'ghi_chu',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'so_lop_du_kien' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function monHoc()
    {
        return $this->belongsTo(\App\Infrastructure\SinhVien\Persistence\Models\MonHoc::class, 'mon_hoc_id');
    }

    public function khoa()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\Khoa::class, 'khoa_id');
    }

    public function hocKy()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\HocKy::class, 'hoc_ky_id');
    }

    public function nguoiTao()
    {
        return $this->belongsTo(\App\Infrastructure\Auth\Persistence\Models\UserProfile::class, 'nguoi_tao_id');
    }

    public function giangVienDeXuat()
    {
        return $this->belongsTo(\App\Infrastructure\GiangVien\Persistence\Models\GiangVien::class, 'giang_vien_de_xuat');
    }
}

