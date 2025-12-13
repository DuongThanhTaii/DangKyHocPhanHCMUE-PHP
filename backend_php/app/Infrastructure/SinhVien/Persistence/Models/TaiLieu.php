<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;

class TaiLieu extends Model
{
    use HasUuids;

    protected $table = 'tai_lieu';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = [
        'id',
        'lop_hoc_phan_id',
        'ten_tai_lieu',
        'file_path',
        'file_type',
        'uploaded_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Uploader user
     */
    public function uploadedBy()
    {
        return $this->belongsTo(UserProfile::class, 'uploaded_by');
    }

    /**
     * LopHocPhan
     */
    public function lopHocPhan()
    {
        return $this->belongsTo(LopHocPhan::class, 'lop_hoc_phan_id');
    }
}
