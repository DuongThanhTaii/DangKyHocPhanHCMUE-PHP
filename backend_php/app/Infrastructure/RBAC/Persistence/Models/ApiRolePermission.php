<?php

namespace App\Infrastructure\RBAC\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ApiRolePermission extends Model
{
    use HasUuids;

    protected $table = 'api_role_permissions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'api_permission_id',
        'role_id',
        'is_enabled',
        'granted_by',
        'granted_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'granted_at' => 'datetime',
    ];

    /**
     * Get the role
     */
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Get the permission
     */
    public function permission()
    {
        return $this->belongsTo(ApiPermission::class, 'api_permission_id');
    }

    /**
     * Scope: only enabled
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
