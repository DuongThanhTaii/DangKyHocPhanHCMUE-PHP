<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DangKyTkb extends Model
{
    use HasUuids;

    protected $table = 'dang_ky_tkb';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'dang_ky_id',
        'sinh_vien_id',
        'lop_hoc_phan_id',
    ];

    public function dangKyHocPhan()
    {
        return $this->belongsTo(DangKyHocPhan::class, 'dang_ky_id');
    }

    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'sinh_vien_id');
    }

    public function lopHocPhan()
    {
        return $this->belongsTo(LopHocPhan::class, 'lop_hoc_phan_id');
    }
}
