<?php

namespace App\Infrastructure\TLK\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Common\Persistence\Models\Khoa;

class TroLyKhoa extends Model
{
    protected $table = 'tro_ly_khoa';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'khoa_id',
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
