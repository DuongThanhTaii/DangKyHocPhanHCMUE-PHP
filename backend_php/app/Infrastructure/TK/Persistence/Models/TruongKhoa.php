<?php

namespace App\Infrastructure\TK\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Common\Persistence\Models\Khoa;

class TruongKhoa extends Model
{
    protected $table = 'truong_khoa';
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
     * Department (OneToOne)
     */
    public function khoa()
    {
        return $this->belongsTo(Khoa::class, 'khoa_id');
    }
}
