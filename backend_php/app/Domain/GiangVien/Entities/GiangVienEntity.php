<?php

namespace App\Domain\GiangVien\Entities;

/**
 * Domain Entity for GiangVien (Teacher/Lecturer)
 * 
 * Represents a teacher in the system
 */
class GiangVienEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $maGiangVien,
        public readonly ?string $hoTen = null,
        public readonly ?string $email = null,
        public readonly ?string $khoaId = null,
        public readonly ?string $chucDanh = null, // Chức danh: ThS, TS, PGS, GS
        public readonly ?string $hocVi = null,    // Học vị
        public readonly bool $isActive = true,
    ) {
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return $this->hoTen ?? $this->maGiangVien;
    }

    /**
     * Get full title with academic rank
     */
    public function getFullTitle(): string
    {
        if ($this->chucDanh) {
            return "{$this->chucDanh}. {$this->getDisplayName()}";
        }
        return $this->getDisplayName();
    }

    /**
     * Check if belongs to a department
     */
    public function belongsToKhoa(string $khoaId): bool
    {
        return $this->khoaId === $khoaId;
    }

    /**
     * Check if teacher is active
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'maGiangVien' => $this->maGiangVien,
            'hoTen' => $this->hoTen,
            'email' => $this->email,
            'khoaId' => $this->khoaId,
            'chucDanh' => $this->chucDanh,
            'hocVi' => $this->hocVi,
            'isActive' => $this->isActive,
            'displayName' => $this->getDisplayName(),
            'fullTitle' => $this->getFullTitle(),
        ];
    }
}
