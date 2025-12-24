<?php

namespace App\Domain\Common\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for Nganh (Major/Program)
 */
class NganhEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $maNganh,
        public readonly string $tenNganh,
        public readonly ?string $khoaId = null,
        public readonly ?int $soNamHoc = 4,
        public readonly bool $isActive = true,
    ) {
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return $this->tenNganh;
    }

    /**
     * Get program code
     */
    public function getCode(): string
    {
        return $this->maNganh;
    }

    /**
     * Get duration in years
     */
    public function getDurationYears(): int
    {
        return $this->soNamHoc ?? 4;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'maNganh' => $this->maNganh,
            'tenNganh' => $this->tenNganh,
            'khoaId' => $this->khoaId,
            'soNamHoc' => $this->soNamHoc,
            'isActive' => $this->isActive,
            'displayName' => $this->getDisplayName(),
        ];
    }
}
