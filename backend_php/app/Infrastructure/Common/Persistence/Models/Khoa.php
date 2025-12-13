<?php

namespace App\Infrastructure\Common\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Khoa extends Model
{
    use HasUuids;

    protected $table = 'khoa';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'ma_khoa',
        'ten_khoa',
        'ngay_thanh_lap',
        'trang_thai_hoat_dong',
    ];

    protected $casts = [
        'ngay_thanh_lap' => 'date',
        'trang_thai_hoat_dong' => 'boolean',
    ];

    public function nganhHocs()
    {
        return $this->hasMany(NganhHoc::class, 'khoa_id');
    }
}
