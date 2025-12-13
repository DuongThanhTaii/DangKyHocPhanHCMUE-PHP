<?php

namespace App\Application\Pdt\DTOs;

/**
 * DTO cho tạo đề xuất học phần
 */
class CreateDeXuatHocPhanDTO
{
    public function __construct(
        public readonly string $monHocId,
        public readonly string $hocKyId,
        public readonly int $soLopDuKien = 1,
        public readonly ?string $giangVienDeXuat = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            monHocId: $data['monHocId'] ?? $data['mon_hoc_id'] ?? '',
            hocKyId: $data['hocKyId'] ?? $data['hoc_ky_id'] ?? '',
            soLopDuKien: (int) ($data['soLopDuKien'] ?? $data['so_lop_du_kien'] ?? 1),
            giangVienDeXuat: $data['giangVienDeXuat'] ?? $data['giang_vien_de_xuat'] ?? null,
        );
    }

    public function validate(): void
    {
        if (empty($this->monHocId) || empty($this->hocKyId)) {
            throw new \InvalidArgumentException('Thiếu thông tin (monHocId, hocKyId)');
        }
    }
}
