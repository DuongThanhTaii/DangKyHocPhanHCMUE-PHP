<?php

namespace App\Domain\Auth\Entities;

class UserEntity
{
    public function __construct(
        public string $id,
        public string $tenDangNhap,
        public ?string $hoTen,
        public ?string $email,
        public string $loaiTaiKhoan,
        public ?string $maNhanVien = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'tenDangNhap' => $this->tenDangNhap,
            'hoTen' => $this->hoTen,
            'email' => $this->email,
            'loaiTaiKhoan' => $this->loaiTaiKhoan,
            'maNhanVien' => $this->maNhanVien,
        ];
    }
}
