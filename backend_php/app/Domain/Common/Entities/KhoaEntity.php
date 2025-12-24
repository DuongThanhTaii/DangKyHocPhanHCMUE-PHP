<?php

namespace App\Domain\Common\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for Khoa (Department/Faculty)
 */
class KhoaEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $maKhoa,
        public readonly string $tenKhoa,
        public readonly ?string $moTa = null,
        public readonly bool $isActive = true,
    ) {
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return $this->tenKhoa;
    }

    /**
     * Get short code
     */
    public function getCode(): string
    {
        return $this->maKhoa;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'maKhoa' => $this->maKhoa,
            'tenKhoa' => $this->tenKhoa,
            'moTa' => $this->moTa,
            'isActive' => $this->isActive,
            'displayName' => $this->getDisplayName(),
        ];
    }
}
