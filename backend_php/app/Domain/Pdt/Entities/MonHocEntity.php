<?php

namespace App\Domain\Pdt\Entities;

/**
 * Domain Entity for MonHoc (Subject/Course)
 * 
 * Represents an academic subject that students can enroll in
 */
class MonHocEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $maMonHoc,
        public readonly string $tenMonHoc,
        public readonly int $soTinChi = 0,
        public readonly ?string $moTa = null,
        public readonly ?string $khoaId = null,
        public readonly bool $coThucHanh = false,
        public readonly bool $isActive = true,
    ) {
    }

    /**
     * Check if subject has practical component
     */
    public function hasPractical(): bool
    {
        return $this->coThucHanh;
    }

    /**
     * Check if subject is currently offered
     */
    public function isOffered(): bool
    {
        return $this->isActive;
    }

    /**
     * Get credit display (e.g., "3 TC")
     */
    public function getCreditDisplay(): string
    {
        return "{$this->soTinChi} TC";
    }

    /**
     * Get full display name with code
     */
    public function getFullName(): string
    {
        return "{$this->maMonHoc} - {$this->tenMonHoc}";
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'maMonHoc' => $this->maMonHoc,
            'tenMonHoc' => $this->tenMonHoc,
            'soTinChi' => $this->soTinChi,
            'moTa' => $this->moTa,
            'khoaId' => $this->khoaId,
            'coThucHanh' => $this->coThucHanh,
            'isActive' => $this->isActive,
            'creditDisplay' => $this->getCreditDisplay(),
        ];
    }
}
