<?php

namespace App\Domain\SinhVien\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for SinhVien (Student)
 * 
 * Represents a student in the system with academic information
 */
class SinhVienEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $maSoSinhVien,
        public readonly ?string $lop = null,
        public readonly ?string $khoaId = null,
        public readonly ?string $khoaHoc = null,
        public readonly ?string $nganhId = null,
        public readonly ?DateTimeImmutable $ngayNhapHoc = null,
        // Profile info (from users table)
        public readonly ?string $hoTen = null,
        public readonly ?string $email = null,
    ) {
    }

    /**
     * Get display name (hoTen or MSSV)
     */
    public function getDisplayName(): string
    {
        return $this->hoTen ?? $this->maSoSinhVien;
    }

    /**
     * Get academic year from MSSV (e.g., "49.01.104.123" -> "49")
     */
    public function getKhoaHocFromMSSV(): ?string
    {
        $parts = explode('.', $this->maSoSinhVien);
        return $parts[0] ?? null;
    }

    /**
     * Check if student is in a specific department
     */
    public function belongsToKhoa(string $khoaId): bool
    {
        return $this->khoaId === $khoaId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'maSoSinhVien' => $this->maSoSinhVien,
            'lop' => $this->lop,
            'khoaId' => $this->khoaId,
            'khoaHoc' => $this->khoaHoc,
            'nganhId' => $this->nganhId,
            'ngayNhapHoc' => $this->ngayNhapHoc?->format('Y-m-d'),
            'hoTen' => $this->hoTen,
            'email' => $this->email,
        ];
    }
}
