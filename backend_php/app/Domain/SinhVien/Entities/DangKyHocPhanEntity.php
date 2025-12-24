<?php

namespace App\Domain\SinhVien\Entities;

use DateTimeImmutable;
use App\Domain\SinhVien\ValueObjects\TrangThaiDangKy;

/**
 * Domain Entity for DangKyHocPhan (Course Registration)
 * 
 * Represents a student's registration for a class section
 */
class DangKyHocPhanEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $sinhVienId,
        public readonly string $lopHocPhanId,
        public readonly TrangThaiDangKy $trangThai,
        public readonly ?DateTimeImmutable $ngayDangKy = null,
        public readonly bool $coXungDot = false,
        // Denormalized data for display
        public readonly ?string $maLop = null,
        public readonly ?string $tenMonHoc = null,
        public readonly ?int $soTinChi = null,
    ) {
    }

    /**
     * Check if registration is active
     */
    public function isActive(): bool
    {
        return $this->trangThai->isActive();
    }

    /**
     * Check if registration is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->trangThai->isCancelled();
    }

    /**
     * Check if registration is pending payment
     */
    public function isPendingPayment(): bool
    {
        return $this->trangThai->isPendingPayment();
    }

    /**
     * Check if registration is completed (paid)
     */
    public function isCompleted(): bool
    {
        return $this->trangThai->isCompleted();
    }

    /**
     * Check if registration has schedule conflict
     */
    public function hasConflict(): bool
    {
        return $this->coXungDot;
    }

    /**
     * Check if can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return $this->isActive() && !$this->isCompleted();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sinhVienId' => $this->sinhVienId,
            'lopHocPhanId' => $this->lopHocPhanId,
            'trangThai' => $this->trangThai->value(),
            'trangThaiLabel' => $this->trangThai->label(),
            'ngayDangKy' => $this->ngayDangKy?->format('Y-m-d H:i:s'),
            'coXungDot' => $this->coXungDot,
            'maLop' => $this->maLop,
            'tenMonHoc' => $this->tenMonHoc,
            'soTinChi' => $this->soTinChi,
            'isActive' => $this->isActive(),
            'canBeCancelled' => $this->canBeCancelled(),
        ];
    }
}
