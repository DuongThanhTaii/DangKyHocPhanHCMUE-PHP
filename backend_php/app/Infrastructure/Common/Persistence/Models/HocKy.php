<?php

namespace App\Infrastructure\Common\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class HocKy extends Model
{
    use HasUuids;

    protected $table = 'hoc_ky';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'ten_hoc_ky',
        'ma_hoc_ky',
        'id_nien_khoa',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai_hien_tai',
    ];

    protected $casts = [
        'ngay_bat_dau' => 'date',
        'ngay_ket_thuc' => 'date',
        'trang_thai_hien_tai' => 'boolean',
    ];

    /**
     * Relationship to NienKhoa (academic year)
     */
    public function nienKhoa()
    {
        return $this->belongsTo(NienKhoa::class, 'id_nien_khoa');
    }
}
