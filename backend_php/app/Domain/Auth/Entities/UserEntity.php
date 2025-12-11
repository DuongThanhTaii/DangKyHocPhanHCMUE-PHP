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
    ) {}

    public function toArray(): array
    {
        return [
            'id'             => $this->id,
            'ten_dang_nhap'  => $this->tenDangNhap,
            'ho_ten'         => $this->hoTen,
            'email'          => $this->email,
            'loai_tai_khoan' => $this->loaiTaiKhoan,
        ];
    }
}
