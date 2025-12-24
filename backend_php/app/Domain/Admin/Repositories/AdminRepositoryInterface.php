<?php

namespace App\Domain\Admin\Repositories;

use Illuminate\Support\Collection;

/**
 * Admin Repository Interface for RBAC Management
 */
interface AdminRepositoryInterface
{
    /**
     * Get all roles in the system
     */
    public function getAllRoles(): Collection;

    /**
     * Get role by ID
     */
    public function getRoleById(string $id): ?object;

    /**
     * Get all API permissions
     */
    public function getAllPermissions(): Collection;

    /**
     * Get permissions for a specific role
     * Returns permissions with is_enabled status
     */
    public function getRolePermissions(string $roleId): Collection;

    /**
     * Toggle permission for a role (enable/disable)
     * Returns true if operation successful
     */
    public function toggleRolePermission(string $roleId, string $permissionId, bool $isEnabled): bool;

    /**
     * Check if role-permission mapping exists
     */
    public function hasRolePermission(string $roleId, string $permissionId): bool;

    /**
     * Create role-permission mapping
     */
    public function createRolePermission(string $roleId, string $permissionId, bool $isEnabled = true): void;
}
