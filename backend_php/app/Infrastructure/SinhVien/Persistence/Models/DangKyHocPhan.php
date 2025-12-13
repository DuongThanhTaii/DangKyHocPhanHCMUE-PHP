<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DangKyHocPhan extends Model
{
    use HasUuids;

    protected $table = 'dang_ky_hoc_phan';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'sinh_vien_id',
        'lop_hoc_phan_id',
        'ngay_dang_ky',
        'trang_thai',
        'co_xung_dot',
    ];

    protected $casts = [
        'ngay_dang_ky' => 'datetime',
        'co_xung_dot' => 'boolean',
    ];

    /**
     * Student who registered
     */
    public function sinhVien()
    {
        return $this->belongsTo(SinhVien::class, 'sinh_vien_id');
    }

    /**
     * Class section
     */
    public function lopHocPhan()
    {
        return $this->belongsTo(LopHocPhan::class, 'lop_hoc_phan_id');
    }

    /**
     * Check if enrollment is active
     */
    public function isActive(): bool
    {
        return in_array($this->trang_thai, [
            'da_dang_ky',
            'da_duyet',
            'cho_thanh_toan',
            'da_thanh_toan',
            'completed'
        ]);
    }
}
