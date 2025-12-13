<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use App\Infrastructure\Common\Persistence\Models\NganhHoc;

class SinhVien extends Model
{
    use HasUuids;

    protected $table = 'sinh_vien';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'ma_so_sinh_vien',
        'lop',
        'khoa_id',
        'khoa_hoc',
        'ngay_nhap_hoc',
        'nganh_id',
    ];

    protected $casts = [
        'ngay_nhap_hoc' => 'date',
    ];

    /**
     * The user profile (OneToOne - sinh_vien.id = users.id)
     */
    public function user()
    {
        return $this->belongsTo(UserProfile::class, 'id', 'id');
    }

    /**
     * Khoa (department)
     */
    public function khoa()
    {
        return $this->belongsTo(Khoa::class, 'khoa_id');
    }

    /**
     * Nganh (specialization)
     */
    public function nganh()
    {
        return $this->belongsTo(NganhHoc::class, 'nganh_id');
    }
}
