<?php

namespace App\Application\Auth\DTOs;

class LoginDTO
{
    public function __construct(
        public string $tenDangNhap,
        public string $matKhau
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            tenDangNhap: $data['tenDangNhap'],
            matKhau: $data['matKhau']
        );
    }
}
