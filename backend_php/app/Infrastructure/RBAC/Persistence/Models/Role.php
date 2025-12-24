<?php

namespace App\Infrastructure\RBAC\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Role extends Model
{
    use HasUuids;

    protected $table = 'roles';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'code',
        'name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Role has many API permissions through the pivot table
     */
    public function permissions()
    {
        return $this->belongsToMany(
            ApiPermission::class,
            'api_role_permissions',
            'role_id',
            'api_permission_id'
        )->withPivot(['is_enabled', 'granted_by', 'granted_at'])
         ->withTimestamps();
    }

    /**
     * Get only enabled permissions
     */
    public function enabledPermissions()
    {
        return $this->permissions()->wherePivot('is_enabled', true);
    }
}
