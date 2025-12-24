<?php

namespace App\Domain\Auth\Entities;

use App\Domain\Auth\ValueObjects\Email;
use App\Domain\Auth\ValueObjects\Username;

/**
 * Domain Entity for TaiKhoan (Account)
 * 
 * This is a domain object - independent of database/ORM.
 * Use Mapper to convert between this Entity and Eloquent Model.
 */
class TaiKhoanEntity
{
    public function __construct(
        public readonly string $id,
        public readonly Username $tenDangNhap,
        public readonly string $loaiTaiKhoan,
        public readonly bool $trangThaiHoatDong,
        public readonly ?\DateTimeImmutable $ngayTao = null,
    ) {
    }

    /**
     * Check if account is active
     */
    public function isActive(): bool
    {
        return $this->trangThaiHoatDong;
    }

    /**
     * Check if account has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->loaiTaiKhoan === $role;
    }

    /**
     * Check if this is a student account
     */
    public function isSinhVien(): bool
    {
        return $this->hasRole('sinh_vien');
    }

    /**
     * Check if this is a teacher account
     */
    public function isGiangVien(): bool
    {
        return $this->hasRole('giang_vien');
    }

    /**
     * Check if this account can access PDT (Phòng Đào Tạo) features
     */
    public function canAccessPDT(): bool
    {
        return $this->hasRole('pdt');
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenDangNhap' => $this->tenDangNhap->value(),
            'loaiTaiKhoan' => $this->loaiTaiKhoan,
            'trangThaiHoatDong' => $this->trangThaiHoatDong,
            'ngayTao' => $this->ngayTao?->format('Y-m-d H:i:s'),
        ];
    }
}
