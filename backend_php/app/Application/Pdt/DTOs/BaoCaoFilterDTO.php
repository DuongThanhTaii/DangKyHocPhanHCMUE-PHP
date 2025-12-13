<?php

namespace App\Application\Pdt\DTOs;

/**
 * DTO cho request lọc báo cáo
 * 
 * Chứa các filter parameters cho các API thống kê
 */
class BaoCaoFilterDTO
{
    public function __construct(
        public readonly string $hocKyId,
        public readonly ?string $khoaId = null,
        public readonly ?string $nganhId = null,
    ) {
    }

    /**
     * Factory method từ Request array
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            hocKyId: $data['hoc_ky_id'] ?? $data['hocKyId'] ?? '',
            khoaId: $data['khoa_id'] ?? $data['khoaId'] ?? null,
            nganhId: $data['nganh_id'] ?? $data['nganhId'] ?? null,
        );
    }

    /**
     * Validate DTO
     * 
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (empty($this->hocKyId)) {
            throw new \InvalidArgumentException('hoc_ky_id is required');
        }
    }
}
