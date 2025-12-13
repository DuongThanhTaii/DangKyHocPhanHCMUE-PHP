<?php

namespace App\Infrastructure\GiangVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Common\Persistence\Models\Khoa;

class GiangVien extends Model
{
    protected $table = 'giang_vien';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'khoa_id',
        'chuyen_mon',
        'trinh_do',
        'kinh_nghiem_giang_day',
    ];

    /**
     * User profile (same ID)
     */
    public function user()
    {
        return $this->belongsTo(UserProfile::class, 'id');
    }

    /**
     * Department
     */
    public function khoa()
    {
        return $this->belongsTo(Khoa::class, 'khoa_id');
    }
}
