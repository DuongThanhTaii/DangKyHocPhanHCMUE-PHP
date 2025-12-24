<?php

namespace App\Domain\SinhVien\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for LopHocPhan (Class Section)
 * 
 * Represents a class section that students can register for
 */
class LopHocPhanEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $hocPhanId,
        public readonly string $maLop,
        public readonly ?string $giangVienId = null,
        public readonly int $soLuongToiDa = 50,
        public readonly int $soLuongHienTai = 0,
        public readonly ?string $phongMacDinhId = null,
        public readonly ?DateTimeImmutable $ngayBatDau = null,
        public readonly ?DateTimeImmutable $ngayKetThuc = null,
        // Related data (denormalized for display)
        public readonly ?string $tenMonHoc = null,
        public readonly ?string $maMonHoc = null,
        public readonly ?int $soTinChi = null,
        public readonly ?string $tenGiangVien = null,
    ) {
    }

    /**
     * Check if class is full
     */
    public function isFull(): bool
    {
        return $this->soLuongHienTai >= $this->soLuongToiDa;
    }

    /**
     * Get available slots
     */
    public function getAvailableSlots(): int
    {
        return max(0, $this->soLuongToiDa - $this->soLuongHienTai);
    }

    /**
     * Get capacity percentage
     */
    public function getCapacityPercentage(): float
    {
        if ($this->soLuongToiDa === 0) return 100.0;
        return round(($this->soLuongHienTai / $this->soLuongToiDa) * 100, 1);
    }

    /**
     * Check if registration is still open
     */
    public function isRegistrationOpen(): bool
    {
        return !$this->isFull();
    }

    /**
     * Check if class is active (within date range)
     */
    public function isActive(?DateTimeImmutable $currentDate = null): bool
    {
        $now = $currentDate ?? new DateTimeImmutable();
        
        if ($this->ngayBatDau && $now < $this->ngayBatDau) {
            return false; // Not started yet
        }
        
        if ($this->ngayKetThuc && $now > $this->ngayKetThuc) {
            return false; // Already ended
        }
        
        return true;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hocPhanId' => $this->hocPhanId,
            'maLop' => $this->maLop,
            'giangVienId' => $this->giangVienId,
            'soLuongToiDa' => $this->soLuongToiDa,
            'soLuongHienTai' => $this->soLuongHienTai,
            'availableSlots' => $this->getAvailableSlots(),
            'isFull' => $this->isFull(),
            'ngayBatDau' => $this->ngayBatDau?->format('Y-m-d'),
            'ngayKetThuc' => $this->ngayKetThuc?->format('Y-m-d'),
            'tenMonHoc' => $this->tenMonHoc,
            'maMonHoc' => $this->maMonHoc,
            'soTinChi' => $this->soTinChi,
            'tenGiangVien' => $this->tenGiangVien,
        ];
    }
}
