<?php

namespace App\Domain\Pdt\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for HocKy (Semester)
 * 
 * Represents an academic semester with start/end dates
 */
class HocKyEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $maHocKy,
        public readonly string $tenHocKy,
        public readonly ?int $namHoc = null,
        public readonly ?int $hocKySo = null, // 1, 2, or 3 (hè)
        public readonly ?DateTimeImmutable $ngayBatDau = null,
        public readonly ?DateTimeImmutable $ngayKetThuc = null,
        public readonly bool $isHienHanh = false, // Current semester
    ) {
    }

    /**
     * Check if this is the current semester
     */
    public function isCurrent(): bool
    {
        return $this->isHienHanh;
    }

    /**
     * Check if semester is active (within date range)
     */
    public function isActive(?DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new DateTimeImmutable();
        
        if ($this->ngayBatDau && $now < $this->ngayBatDau) {
            return false;
        }
        
        if ($this->ngayKetThuc && $now > $this->ngayKetThuc) {
            return false;
        }
        
        return true;
    }

    /**
     * Get display name (e.g., "HK1 2024-2025")
     */
    public function getDisplayName(): string
    {
        if ($this->hocKySo && $this->namHoc) {
            $nextYear = $this->namHoc + 1;
            return "HK{$this->hocKySo} {$this->namHoc}-{$nextYear}";
        }
        return $this->tenHocKy ?? $this->maHocKy;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'maHocKy' => $this->maHocKy,
            'tenHocKy' => $this->tenHocKy,
            'namHoc' => $this->namHoc,
            'hocKySo' => $this->hocKySo,
            'ngayBatDau' => $this->ngayBatDau?->format('Y-m-d'),
            'ngayKetThuc' => $this->ngayKetThuc?->format('Y-m-d'),
            'isHienHanh' => $this->isHienHanh,
            'displayName' => $this->getDisplayName(),
        ];
    }
}
