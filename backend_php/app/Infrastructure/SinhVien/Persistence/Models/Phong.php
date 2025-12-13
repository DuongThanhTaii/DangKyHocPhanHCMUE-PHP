<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Phong extends Model
{
    use HasUuids;

    protected $table = 'phong';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'ma_phong',
        'ten_phong',
        'suc_chua',
        'khoa_id',
        'co_so_id',
        'da_dc_su_dung',
    ];

    protected $casts = [
        'da_dc_su_dung' => 'boolean',
    ];

    public function khoa()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\Khoa::class, 'khoa_id');
    }

    public function coSo()
    {
        return $this->belongsTo(CoSo::class, 'co_so_id');
    }
}
