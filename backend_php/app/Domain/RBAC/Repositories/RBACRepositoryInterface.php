<?php

namespace App\Domain\RBAC\Repositories;

use App\Domain\RBAC\Entities\RoleEntity;
use App\Domain\RBAC\Entities\ApiPermissionEntity;

/**
 * Repository Interface for RBAC operations
 */
interface RBACRepositoryInterface
{
    // ==================== ROLES ====================
    
    /**
     * Get all roles
     * @return RoleEntity[]
     */
    public function getAllRoles(): array;

    /**
     * Find role by code (e.g., 'sinh_vien', 'admin_system')
     */
    public function findRoleByCode(string $code): ?RoleEntity;

    /**
     * Find role by ID
     */
    public function findRoleById(string $id): ?RoleEntity;

    /**
     * Create a new role
     */
    public function createRole(string $code, string $name, ?string $description = null, bool $isSystem = false): RoleEntity;

    /**
     * Delete a role (only if not system role)
     */
    public function deleteRole(string $id): bool;

    // ==================== PERMISSIONS ====================

    /**
     * Get all API permissions
     * @return ApiPermissionEntity[]
     */
    public function getAllPermissions(): array;

    /**
     * Find permission by route path and method
     */
    public function findPermissionByRoute(string $routePath, string $method): ?ApiPermissionEntity;

    /**
     * Create or update an API permission
     */
    public function upsertPermission(
        string $routePath,
        string $method,
        ?string $routeName = null,
        ?string $description = null,
        ?string $module = null,
        bool $isPublic = false
    ): ApiPermissionEntity;

    /**
     * Sync all routes from Laravel Router
     * @return int Number of permissions synced
     */
    public function syncRoutesFromRouter(): int;

    // ==================== ROLE-PERMISSION MAPPING ====================

    /**
     * Check if a role has permission to access an API
     */
    public function hasPermission(string $roleCode, string $routePath, string $method): bool;

    /**
     * Get all roles that have permission to access an API
     * @return RoleEntity[]
     */
    public function getRolesForPermission(string $permissionId): array;

    /**
     * Get all permissions for a role
     * @return ApiPermissionEntity[]
     */
    public function getPermissionsForRole(string $roleId): array;

    /**
     * Grant a role access to an API permission
     */
    public function grantPermission(string $roleId, string $permissionId, ?string $grantedBy = null): bool;

    /**
     * Revoke a role's access to an API permission
     */
    public function revokePermission(string $roleId, string $permissionId): bool;

    /**
     * Toggle enabled/disabled status for a role-permission mapping
     */
    public function togglePermission(string $roleId, string $permissionId): bool;

    /**
     * Set enabled status for a role-permission mapping
     */
    public function setPermissionEnabled(string $roleId, string $permissionId, bool $enabled): bool;
}
