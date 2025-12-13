<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class GhiDanhHocPhan extends Model
{
    use HasUuids;

    protected $table = 'ghi_danh_hoc_phan';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'sinh_vien_id',
        'hoc_phan_id',
        'ngay_ghi_danh',
        'trang_thai',
    ];

    protected $casts = [
        'ngay_ghi_danh' => 'datetime',
    ];

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'sinh_vien_id');
    }

    public function hocPhan()
    {
        return $this->belongsTo(HocPhan::class, 'hoc_phan_id');
    }
}
