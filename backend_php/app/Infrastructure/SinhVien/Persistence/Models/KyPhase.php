<?php

namespace App\Infrastructure\SinhVien\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KyPhase extends Model
{
    use HasUuids;

    protected $table = 'ky_phase';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'hoc_ky_id',
        'phase',
        'start_at',
        'end_at',
        'is_enabled',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_enabled' => 'boolean',
    ];

    public function hocKy()
    {
        return $this->belongsTo(\App\Infrastructure\Common\Persistence\Models\HocKy::class, 'hoc_ky_id');
    }

    /**
     * Check if this phase is currently active
     */
    public function isActive(): bool
    {
        $now = now();
        return $this->is_enabled && $this->start_at <= $now && $this->end_at >= $now;
    }
}
