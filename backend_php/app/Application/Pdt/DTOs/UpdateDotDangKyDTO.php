<?php

namespace App\Application\Pdt\DTOs;

/**
 * DTO cho cập nhật đợt đăng ký
 */
class UpdateDotDangKyDTO
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $thoiGianBatDau = null,
        public readonly ?string $thoiGianKetThuc = null,
        public readonly ?int $gioiHanTinChi = null,
        public readonly ?bool $isCheckToanTruong = null,
    ) {
    }

    /**
     * Factory method từ Request array
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            id: $data['id'] ?? '',
            thoiGianBatDau: $data['thoiGianBatDau'] ?? $data['thoi_gian_bat_dau'] ?? null,
            thoiGianKetThuc: $data['thoiGianKetThuc'] ?? $data['thoi_gian_ket_thuc'] ?? null,
            gioiHanTinChi: isset($data['gioiHanTinChi']) ? (int) $data['gioiHanTinChi'] 
                          : (isset($data['gioi_han_tin_chi']) ? (int) $data['gioi_han_tin_chi'] : null),
            isCheckToanTruong: $data['isCheckToanTruong'] ?? $data['is_check_toan_truong'] ?? null,
        );
    }

    /**
     * Validate DTO
     * 
     * @throws \InvalidArgumentException
     */
    public function validate(): void
    {
        if (empty($this->id)) {
            throw new \InvalidArgumentException('Thiếu ID đợt đăng ký');
        }
    }

    /**
     * Convert to array for update (only non-null values)
     */
    public function toUpdateArray(): array
    {
        $data = [];

        if ($this->thoiGianBatDau !== null) {
            $data['thoi_gian_bat_dau'] = $this->thoiGianBatDau;
        }
        if ($this->thoiGianKetThuc !== null) {
            $data['thoi_gian_ket_thuc'] = $this->thoiGianKetThuc;
        }
        if ($this->gioiHanTinChi !== null) {
            $data['gioi_han_tin_chi'] = $this->gioiHanTinChi;
        }
        if ($this->isCheckToanTruong !== null) {
            $data['is_check_toan_truong'] = $this->isCheckToanTruong;
        }

        return $data;
    }
}
