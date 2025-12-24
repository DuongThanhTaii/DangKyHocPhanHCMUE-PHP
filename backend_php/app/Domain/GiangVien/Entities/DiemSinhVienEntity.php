<?php

namespace App\Domain\GiangVien\Entities;

/**
 * Domain Entity for DiemSinhVien (Student Grade)
 * 
 * Represents a student's grade for a class
 */
class DiemSinhVienEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $sinhVienId,
        public readonly string $lopHocPhanId,
        public readonly ?float $diemQuaTrinh = null,    // Process/Midterm grade
        public readonly ?float $diemThucHanh = null,    // Practical grade
        public readonly ?float $diemCuoiKy = null,      // Final exam grade
        public readonly ?float $diemTongKet = null,     // Total grade
        public readonly ?string $diemChu = null,        // Letter grade (A, B+, etc.)
        public readonly bool $isLocked = false,         // Grade locked/finalized
        // Denormalized for display
        public readonly ?string $maSoSinhVien = null,
        public readonly ?string $hoTen = null,
    ) {
    }

    /**
     * Check if grade is passing (>= 5.0)
     */
    public function isPassing(): bool
    {
        return $this->diemTongKet !== null && $this->diemTongKet >= 5.0;
    }

    /**
     * Check if grade is complete (all components filled)
     */
    public function isComplete(): bool
    {
        return $this->diemTongKet !== null;
    }

    /**
     * Check if grade can be edited
     */
    public function canEdit(): bool
    {
        return !$this->isLocked;
    }

    /**
     * Get grade status
     */
    public function getStatus(): string
    {
        if ($this->isLocked) return 'Đã khóa';
        if ($this->isComplete()) return 'Hoàn thành';
        return 'Chưa nhập';
    }

    /**
     * Calculate weighted total (if needed)
     * Default: 30% process, 20% practical, 50% final
     */
    public function calculateTotal(
        float $quaTrinhWeight = 0.3,
        float $thucHanhWeight = 0.2,
        float $cuoiKyWeight = 0.5
    ): ?float {
        if ($this->diemQuaTrinh === null || $this->diemCuoiKy === null) {
            return null;
        }

        $total = ($this->diemQuaTrinh * $quaTrinhWeight) 
               + (($this->diemThucHanh ?? 0) * $thucHanhWeight)
               + ($this->diemCuoiKy * $cuoiKyWeight);
        
        return round($total, 2);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sinhVienId' => $this->sinhVienId,
            'lopHocPhanId' => $this->lopHocPhanId,
            'diemQuaTrinh' => $this->diemQuaTrinh,
            'diemThucHanh' => $this->diemThucHanh,
            'diemCuoiKy' => $this->diemCuoiKy,
            'diemTongKet' => $this->diemTongKet,
            'diemChu' => $this->diemChu,
            'isLocked' => $this->isLocked,
            'maSoSinhVien' => $this->maSoSinhVien,
            'hoTen' => $this->hoTen,
            'isPassing' => $this->isPassing(),
            'status' => $this->getStatus(),
        ];
    }
}
