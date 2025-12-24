<?php

namespace App\Domain\Auth\Entities;

use App\Domain\Auth\ValueObjects\Email;

/**
 * Domain Entity for UserProfile
 * 
 * Contains personal information linked to a TaiKhoan.
 */
class UserProfileEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $taiKhoanId,
        public readonly ?string $maNhanVien,
        public readonly ?string $hoTen,
        public readonly ?Email $email,
    ) {
    }

    /**
     * Get display name (fallback to mã nhân viên if no name)
     */
    public function getDisplayName(): string
    {
        return $this->hoTen ?? $this->maNhanVien ?? 'Unknown';
    }

    /**
     * Check if profile has email
     */
    public function hasEmail(): bool
    {
        return $this->email !== null;
    }

    /**
     * Convert to array for serialization  
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'taiKhoanId' => $this->taiKhoanId,
            'maNhanVien' => $this->maNhanVien,
            'hoTen' => $this->hoTen,
            'email' => $this->email?->value(),
        ];
    }
}
