<?php

namespace App\Domain\Pdt\Entities;

use DateTimeImmutable;

/**
 * Domain Entity for KyPhase (Semester Phase)
 * 
 * Represents a phase within a semester (e.g., midterm, final, makeup)
 */
class KyPhaseEntity
{
    // Phase type constants
    public const PHASE_GIUA_KY = 'giua_ky';
    public const PHASE_CUOI_KY = 'cuoi_ky';
    public const PHASE_BO_SUNG = 'bo_sung';
    public const PHASE_DANG_KY = 'dang_ky';

    public function __construct(
        public readonly string $id,
        public readonly string $hocKyId,
        public readonly string $tenPhase,
        public readonly ?string $loaiPhase = null,
        public readonly ?DateTimeImmutable $ngayBatDau = null,
        public readonly ?DateTimeImmutable $ngayKetThuc = null,
        public readonly int $thuTu = 0, // Order/priority
    ) {
    }

    /**
     * Check if phase is active now
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
     * Check if this is a registration phase
     */
    public function isRegistrationPhase(): bool
    {
        return $this->loaiPhase === self::PHASE_DANG_KY;
    }

    /**
     * Check if this is midterm phase
     */
    public function isMidterm(): bool
    {
        return $this->loaiPhase === self::PHASE_GIUA_KY;
    }

    /**
     * Check if this is final phase
     */
    public function isFinal(): bool
    {
        return $this->loaiPhase === self::PHASE_CUOI_KY;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hocKyId' => $this->hocKyId,
            'tenPhase' => $this->tenPhase,
            'loaiPhase' => $this->loaiPhase,
            'ngayBatDau' => $this->ngayBatDau?->format('Y-m-d'),
            'ngayKetThuc' => $this->ngayKetThuc?->format('Y-m-d'),
            'thuTu' => $this->thuTu,
            'isActive' => $this->isActive(),
        ];
    }
}
