<?php

namespace App\Domain\Pdt\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for DotDangKy (Registration Period)
 * 
 * Represents a registration period within a semester
 */
class DotDangKyEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $hocKyId,
        public readonly string $tenDot,
        public readonly ?DateTimeImmutable $ngayBatDau = null,
        public readonly ?DateTimeImmutable $ngayKetThuc = null,
        public readonly ?string $moTa = null,
        public readonly bool $daKetThuc = false,
    ) {
    }

    /**
     * Check if registration is currently open
     */
    public function isOpen(?DateTimeImmutable $now = null): bool
    {
        if ($this->daKetThuc) {
            return false;
        }

        $now = $now ?? new DateTimeImmutable();
        
        if ($this->ngayBatDau && $now < $this->ngayBatDau) {
            return false; // Not started
        }
        
        if ($this->ngayKetThuc && $now > $this->ngayKetThuc) {
            return false; // Already ended
        }
        
        return true;
    }

    /**
     * Check if registration period has ended
     */
    public function hasEnded(?DateTimeImmutable $now = null): bool
    {
        if ($this->daKetThuc) return true;
        
        $now = $now ?? new DateTimeImmutable();
        return $this->ngayKetThuc && $now > $this->ngayKetThuc;
    }

    /**
     * Check if registration period hasn't started yet
     */
    public function isUpcoming(?DateTimeImmutable $now = null): bool
    {
        $now = $now ?? new DateTimeImmutable();
        return $this->ngayBatDau && $now < $this->ngayBatDau;
    }

    /**
     * Get status string
     */
    public function getStatus(?DateTimeImmutable $now = null): string
    {
        if ($this->isOpen($now)) return 'Đang mở';
        if ($this->isUpcoming($now)) return 'Sắp mở';
        return 'Đã đóng';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hocKyId' => $this->hocKyId,
            'tenDot' => $this->tenDot,
            'ngayBatDau' => $this->ngayBatDau?->format('Y-m-d H:i:s'),
            'ngayKetThuc' => $this->ngayKetThuc?->format('Y-m-d H:i:s'),
            'moTa' => $this->moTa,
            'daKetThuc' => $this->daKetThuc,
            'isOpen' => $this->isOpen(),
            'status' => $this->getStatus(),
        ];
    }
}
