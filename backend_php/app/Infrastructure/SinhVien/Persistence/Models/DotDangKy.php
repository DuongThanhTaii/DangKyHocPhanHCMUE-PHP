<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DotDangKy extends Model
{
    use HasUuids;

    protected $table = 'dot_dang_ky';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'hoc_ky_id',
        'loai_dot',
        'gioi_han_tin_chi',
        'thoi_gian_bat_dau',
        'thoi_gian_ket_thuc',
        'is_check_toan_truong',
        'khoa_id',
    ];

    protected $casts = [
        'thoi_gian_bat_dau' => 'datetime',
        'thoi_gian_ket_thuc' => 'datetime',
        'is_check_toan_truong' => 'boolean',
    ];

    public function hocKy()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\HocKy::class, 'hoc_ky_id');
    }

    public function khoa()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\Khoa::class, 'khoa_id');
    }

    /**
     * Check if this period is currently active
     */
    public function isActive(): bool
    {
        $now = now();
        return $this->thoi_gian_bat_dau <= $now && $this->thoi_gian_ket_thuc >= $now;
    }
}
