<?php

namespace App\Infrastructure\Admin\Persistence\Repositories;

use App\Domain\Admin\Repositories\AdminRepositoryInterface;
use App\Infrastructure\RBAC\Persistence\Models\Role;
use App\Infrastructure\RBAC\Persistence\Models\ApiPermission;
use App\Infrastructure\RBAC\Persistence\Models\ApiRolePermission;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Admin Repository Implementation for RBAC Management
 */
class AdminRepository implements AdminRepositoryInterface
{
    public function getAllRoles(): Collection
    {
        return Role::orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();
    }

    public function getRoleById(string $id): ?object
    {
        return Role::find($id);
    }

    public function getAllPermissions(): Collection
    {
        return ApiPermission::orderBy('module')
            ->orderBy('route_path')
            ->get();
    }

    public function getRolePermissions(string $roleId): Collection
    {
        // Get all permissions with their status for this role
        return ApiPermission::select([
                'api_permissions.*',
                'api_role_permissions.is_enabled',
                'api_role_permissions.id as mapping_id'
            ])
            ->leftJoin('api_role_permissions', function ($join) use ($roleId) {
                $join->on('api_permissions.id', '=', 'api_role_permissions.api_permission_id')
                    ->where('api_role_permissions.role_id', '=', $roleId);
            })
            ->orderBy('api_permissions.module')
            ->orderBy('api_permissions.route_path')
            ->get()
            ->map(function ($permission) {
                return (object) [
                    'id' => $permission->id,
                    'route_name' => $permission->route_name,
                    'route_path' => $permission->route_path,
                    'method' => $permission->method,
                    'description' => $permission->description,
                    'module' => $permission->module,
                    'is_public' => $permission->is_public,
                    'is_enabled' => $permission->is_enabled ?? false,
                    'has_mapping' => $permission->mapping_id !== null,
                ];
            });
    }

    public function toggleRolePermission(string $roleId, string $permissionId, bool $isEnabled): bool
    {
        $mapping = ApiRolePermission::where('role_id', $roleId)
            ->where('api_permission_id', $permissionId)
            ->first();

        if ($mapping) {
            $mapping->is_enabled = $isEnabled;
            $mapping->save();
            return true;
        }

        // Create new mapping if doesn't exist
        $this->createRolePermission($roleId, $permissionId, $isEnabled);
        return true;
    }

    public function hasRolePermission(string $roleId, string $permissionId): bool
    {
        return ApiRolePermission::where('role_id', $roleId)
            ->where('api_permission_id', $permissionId)
            ->exists();
    }

    public function createRolePermission(string $roleId, string $permissionId, bool $isEnabled = true): void
    {
        ApiRolePermission::create([
            'id' => (string) Str::uuid(),
            'role_id' => $roleId,
            'api_permission_id' => $permissionId,
            'is_enabled' => $isEnabled,
            'granted_at' => now(),
        ]);
    }
}
