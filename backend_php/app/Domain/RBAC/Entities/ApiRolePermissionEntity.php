<?php

namespace App\Domain\RBAC\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for API Role Permission
 * 
 * Represents the mapping between a Role and an API Permission
 * with enable/disable toggle
 */
class ApiRolePermissionEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $apiPermissionId,
        public readonly string $roleId,
        public readonly bool $isEnabled = true,
        public readonly ?string $grantedBy = null,
        public readonly ?DateTimeImmutable $grantedAt = null,
    ) {
    }

    /**
     * Check if this permission is currently enabled
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * Check if this permission is disabled
     */
    public function isDisabled(): bool
    {
        return !$this->isEnabled;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'apiPermissionId' => $this->apiPermissionId,
            'roleId' => $this->roleId,
            'isEnabled' => $this->isEnabled,
            'grantedBy' => $this->grantedBy,
            'grantedAt' => $this->grantedAt?->format('Y-m-d H:i:s'),
        ];
    }
}
