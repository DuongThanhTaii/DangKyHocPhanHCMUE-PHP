<?php

namespace App\Infrastructure\RBAC\Persistence\Repositories;

use App\Domain\RBAC\Entities\RoleEntity;
use App\Domain\RBAC\Entities\ApiPermissionEntity;
use App\Domain\RBAC\Repositories\RBACRepositoryInterface;
use App\Infrastructure\RBAC\Persistence\Models\Role;
use App\Infrastructure\RBAC\Persistence\Models\ApiPermission;
use App\Infrastructure\RBAC\Persistence\Models\ApiRolePermission;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use DateTimeImmutable;

class EloquentRBACRepository implements RBACRepositoryInterface
{
    // ==================== ROLES ====================

    public function getAllRoles(): array
    {
        return Role::all()->map(fn($m) => $this->toRoleEntity($m))->toArray();
    }

    public function findRoleByCode(string $code): ?RoleEntity
    {
        $model = Role::where('code', $code)->first();
        return $model ? $this->toRoleEntity($model) : null;
    }

    public function findRoleById(string $id): ?RoleEntity
    {
        $model = Role::find($id);
        return $model ? $this->toRoleEntity($model) : null;
    }

    public function createRole(string $code, string $name, ?string $description = null, bool $isSystem = false): RoleEntity
    {
        $model = Role::create([
            'id' => (string) Str::uuid(),
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'is_system' => $isSystem,
        ]);
        return $this->toRoleEntity($model);
    }

    public function deleteRole(string $id): bool
    {
        $model = Role::find($id);
        if (!$model || $model->is_system) {
            return false;
        }
        return (bool) $model->delete();
    }

    // ==================== PERMISSIONS ====================

    public function getAllPermissions(): array
    {
        return ApiPermission::all()->map(fn($m) => $this->toPermissionEntity($m))->toArray();
    }

    public function findPermissionByRoute(string $routePath, string $method): ?ApiPermissionEntity
    {
        $model = ApiPermission::where('route_path', $routePath)
            ->where('method', strtoupper($method))
            ->first();
        return $model ? $this->toPermissionEntity($model) : null;
    }

    public function upsertPermission(
        string $routePath,
        string $method,
        ?string $routeName = null,
        ?string $description = null,
        ?string $module = null,
        bool $isPublic = false
    ): ApiPermissionEntity {
        $model = ApiPermission::updateOrCreate(
            ['route_path' => $routePath, 'method' => strtoupper($method)],
            [
                'route_name' => $routeName,
                'description' => $description,
                'module' => $module,
                'is_public' => $isPublic,
            ]
        );
        return $this->toPermissionEntity($model);
    }

    public function syncRoutesFromRouter(): int
    {
        $routes = Route::getRoutes();
        $count = 0;

        foreach ($routes as $route) {
            $uri = $route->uri();
            
            // Only sync /api routes
            if (!str_starts_with($uri, 'api/')) {
                continue;
            }

            $methods = $route->methods();
            $routeName = $route->getName();
            $action = $route->getActionName();
            
            // Determine module from route prefix or controller namespace
            $module = $this->extractModuleFromRoute($uri, $action);

            foreach ($methods as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'])) continue;
                
                $this->upsertPermission(
                    routePath: '/' . $uri,
                    method: $method,
                    routeName: $routeName,
                    module: $module,
                    isPublic: $this->isPublicRoute($route)
                );
                $count++;
            }
        }

        return $count;
    }

    // ==================== ROLE-PERMISSION MAPPING ====================

    public function hasPermission(string $roleCode, string $routePath, string $method): bool
    {
        // admin_system always has full access (fallback safety)
        if ($roleCode === 'admin_system') {
            return true;
        }

        $permission = ApiPermission::where('route_path', $routePath)
            ->where('method', strtoupper($method))
            ->first();

        if (!$permission) {
            return false; // Unknown route = no access
        }

        // Check if public
        if ($permission->is_public) {
            return true;
        }

        // Check role mapping
        $role = Role::where('code', $roleCode)->first();
        if (!$role) {
            return false;
        }

        return ApiRolePermission::where('api_permission_id', $permission->id)
            ->where('role_id', $role->id)
            ->where('is_enabled', true)
            ->exists();
    }

    public function getRolesForPermission(string $permissionId): array
    {
        $permission = ApiPermission::find($permissionId);
        if (!$permission) return [];

        return $permission->enabledRoles
            ->map(fn($r) => $this->toRoleEntity($r))
            ->toArray();
    }

    public function getPermissionsForRole(string $roleId): array
    {
        $role = Role::find($roleId);
        if (!$role) return [];

        return $role->enabledPermissions
            ->map(fn($p) => $this->toPermissionEntity($p))
            ->toArray();
    }

    public function grantPermission(string $roleId, string $permissionId, ?string $grantedBy = null): bool
    {
        ApiRolePermission::updateOrCreate(
            ['role_id' => $roleId, 'api_permission_id' => $permissionId],
            [
                'is_enabled' => true,
                'granted_by' => $grantedBy,
                'granted_at' => now(),
            ]
        );
        return true;
    }

    public function revokePermission(string $roleId, string $permissionId): bool
    {
        return (bool) ApiRolePermission::where('role_id', $roleId)
            ->where('api_permission_id', $permissionId)
            ->delete();
    }

    public function togglePermission(string $roleId, string $permissionId): bool
    {
        $mapping = ApiRolePermission::where('role_id', $roleId)
            ->where('api_permission_id', $permissionId)
            ->first();

        if (!$mapping) return false;

        $mapping->is_enabled = !$mapping->is_enabled;
        $mapping->save();
        return $mapping->is_enabled;
    }

    public function setPermissionEnabled(string $roleId, string $permissionId, bool $enabled): bool
    {
        return (bool) ApiRolePermission::where('role_id', $roleId)
            ->where('api_permission_id', $permissionId)
            ->update(['is_enabled' => $enabled]);
    }

    // ==================== HELPERS ====================

    private function toRoleEntity(Role $model): RoleEntity
    {
        return new RoleEntity(
            id: $model->id,
            code: $model->code,
            name: $model->name,
            description: $model->description,
            isSystem: $model->is_system,
            createdAt: $model->created_at 
                ? new DateTimeImmutable($model->created_at) 
                : null,
        );
    }

    private function toPermissionEntity(ApiPermission $model): ApiPermissionEntity
    {
        return new ApiPermissionEntity(
            id: $model->id,
            routePath: $model->route_path,
            method: $model->method,
            routeName: $model->route_name,
            description: $model->description,
            module: $model->module,
            isPublic: $model->is_public,
            createdAt: $model->created_at 
                ? new DateTimeImmutable($model->created_at) 
                : null,
        );
    }

    private function extractModuleFromRoute(string $uri, string $action): ?string
    {
        // Extract from URI: api/sv/... -> SinhVien, api/pdt/... -> PDT
        $parts = explode('/', $uri);
        if (count($parts) >= 2) {
            $prefix = $parts[1];
            return match($prefix) {
                'sv' => 'SinhVien',
                'pdt' => 'PDT',
                'gv' => 'GiangVien', 
                'tlk' => 'TLK',
                'tk' => 'TK',
                'auth' => 'Auth',
                'payment' => 'Payment',
                'admin' => 'Admin',
                default => ucfirst($prefix),
            };
        }
        return null;
    }

    private function isPublicRoute($route): bool
    {
        $middlewares = $route->middleware();
        // If no auth middleware, it's public
        return !in_array('auth:api', $middlewares) && !in_array('auth', $middlewares);
    }
}
