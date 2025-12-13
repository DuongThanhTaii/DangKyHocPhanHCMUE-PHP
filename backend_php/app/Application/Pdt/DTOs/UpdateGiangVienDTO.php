<?php

namespace App\Application\Pdt\DTOs;

/**
 * DTO cho việc cập nhật Giảng viên
 */
class UpdateGiangVienDTO
{
    public function __construct(
        public readonly ?string $maGiangVien = null,
        public readonly ?string $khoaId = null,
        public readonly ?string $hocVi = null,
        public readonly ?string $hoTen = null,
        public readonly ?string $email = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            maGiangVien: $data['maGiangVien'] ?? $data['ma_giang_vien'] ?? null,
            khoaId: $data['khoaId'] ?? $data['khoa_id'] ?? null,
            hocVi: $data['hocVi'] ?? $data['hoc_vi'] ?? null,
            hoTen: $data['hoTen'] ?? $data['ho_ten'] ?? null,
            email: $data['email'] ?? null,
        );
    }

    public function toArray(): array
    {
        $result = [];

        if ($this->maGiangVien !== null) {
            $result['ma_giang_vien'] = $this->maGiangVien;
        }
        if ($this->khoaId !== null) {
            $result['khoa_id'] = $this->khoaId;
        }
        if ($this->hocVi !== null) {
            $result['hoc_vi'] = $this->hocVi;
        }
        if ($this->hoTen !== null) {
            $result['ho_ten'] = $this->hoTen;
        }
        if ($this->email !== null) {
            $result['email'] = $this->email;
        }

        return $result;
    }
}
