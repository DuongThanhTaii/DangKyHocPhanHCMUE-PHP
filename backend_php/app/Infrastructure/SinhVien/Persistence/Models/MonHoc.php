<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Infrastructure\Common\Persistence\Models\Khoa;

class MonHoc extends Model
{
    use HasUuids;

    protected $table = 'mon_hoc';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'id',
        'ma_mon',
        'ten_mon',
        'so_tin_chi',
        'khoa_id',
        'la_mon_chung',
        'thu_tu_hoc',
    ];

    protected $casts = [
        'la_mon_chung' => 'boolean',
    ];

    public function khoa()
    {
        return $this->belongsTo(Khoa::class, 'khoa_id');
    }

    public function hocPhans()
    {
        return $this->hasMany(HocPhan::class, 'mon_hoc_id');
    }
}
