<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LichSuDangKy extends Model
{
    use HasUuids;

    protected $table = 'lich_su_dang_ky';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'sinh_vien_id',
        'hoc_ky_id',
        'ngay_tao',
    ];

    protected $casts = [
        'ngay_tao' => 'datetime',
    ];

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'sinh_vien_id');
    }

    public function hocKy()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\HocKy::class, 'hoc_ky_id');
    }

    /**
     * Details of this history record
     */
    public function chiTiets()
    {
        return $this->hasMany(ChiTietLichSuDangKy::class, 'lich_su_dang_ky_id');
    }
}
