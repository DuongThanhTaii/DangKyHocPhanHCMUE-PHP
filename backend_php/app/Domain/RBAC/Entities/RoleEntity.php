<?php

namespace App\Domain\RBAC\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for Role
 * 
 * Represents a system role (e.g., sinh_vien, admin_system)
 */
class RoleEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly bool $isSystem = false,
        public readonly ?DateTimeImmutable $createdAt = null,
    ) {
    }

    /**
     * Check if this is a system role (cannot be deleted)
     */
    public function isSystemRole(): bool
    {
        return $this->isSystem;
    }

    /**
     * Check if this role matches a code
     */
    public function hasCode(string $code): bool
    {
        return $this->code === $code;
    }

    /**
     * Check if this is admin_system role
     */
    public function isAdminSystem(): bool
    {
        return $this->code === 'admin_system';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'isSystem' => $this->isSystem,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}
