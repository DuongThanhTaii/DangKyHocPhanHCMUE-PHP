<?php

namespace App\Domain\RBAC\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for API Permission
 * 
 * Represents an API endpoint that can be protected
 */
class ApiPermissionEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $routePath,
        public readonly string $method,
        public readonly ?string $routeName = null,
        public readonly ?string $description = null,
        public readonly ?string $module = null,
        public readonly bool $isPublic = false,
        public readonly ?DateTimeImmutable $createdAt = null,
    ) {
    }

    /**
     * Check if this is a public endpoint (no auth required)
     */
    public function isPublicEndpoint(): bool
    {
        return $this->isPublic;
    }

    /**
     * Check if this permission matches a route
     */
    public function matchesRoute(string $path, string $method): bool
    {
        return $this->routePath === $path && $this->method === strtoupper($method);
    }

    /**
     * Get unique identifier for this endpoint
     */
    public function getEndpointKey(): string
    {
        return strtoupper($this->method) . ':' . $this->routePath;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'routePath' => $this->routePath,
            'method' => $this->method,
            'routeName' => $this->routeName,
            'description' => $this->description,
            'module' => $this->module,
            'isPublic' => $this->isPublic,
            'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
        ];
    }
}
