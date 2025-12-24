<?php

namespace App\Domain\TLK\Entities;

/**
 * Domain Entity for PhongHoc (Classroom)
 * 
 * Represents a classroom/room for scheduling
 */
class PhongHocEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $maPhong,
        public readonly ?string $tenPhong = null,
        public readonly ?string $khoaId = null,
        public readonly int $sucChua = 50,
        public readonly ?string $loaiPhong = null, // Ly thuyet, Thuc hanh, Lab
        public readonly ?string $toaNha = null,
        public readonly bool $isAvailable = true,
    ) {
    }

    /**
     * Check if room is available
     */
    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    /**
     * Check if room can accommodate students
     */
    public function canAccommodate(int $students): bool
    {
        return $this->sucChua >= $students;
    }

    /**
     * Check if room is a lab
     */
    public function isLab(): bool
    {
        return $this->loaiPhong === 'Lab' || $this->loaiPhong === 'thuc_hanh';
    }

    /**
     * Get full display name
     */
    public function getDisplayName(): string
    {
        $name = $this->tenPhong ?? $this->maPhong;
        if ($this->toaNha) {
            return "{$this->toaNha} - {$name}";
        }
        return $name;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'maPhong' => $this->maPhong,
            'tenPhong' => $this->tenPhong,
            'khoaId' => $this->khoaId,
            'sucChua' => $this->sucChua,
            'loaiPhong' => $this->loaiPhong,
            'toaNha' => $this->toaNha,
            'isAvailable' => $this->isAvailable,
            'displayName' => $this->getDisplayName(),
        ];
    }
}
