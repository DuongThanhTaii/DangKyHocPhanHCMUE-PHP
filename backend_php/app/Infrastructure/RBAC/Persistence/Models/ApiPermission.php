<?php

namespace App\Infrastructure\RBAC\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ApiPermission extends Model
{
    use HasUuids;

    protected $table = 'api_permissions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'route_name',
        'route_path',
        'method',
        'description',
        'module',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * Permission belongs to many roles through the pivot table
     */
    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'api_role_permissions',
            'api_permission_id',
            'role_id'
        )->withPivot(['is_enabled', 'granted_by', 'granted_at'])
         ->withTimestamps();
    }

    /**
     * Get only enabled roles for this permission
     */
    public function enabledRoles()
    {
        return $this->roles()->wherePivot('is_enabled', true);
    }

    /**
     * Scope: filter by module
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
