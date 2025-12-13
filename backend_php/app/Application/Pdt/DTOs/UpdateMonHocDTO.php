<?php

namespace App\Application\Pdt\DTOs;

/**
 * DTO cho việc cập nhật Môn học
 */
class UpdateMonHocDTO
{
    public function __construct(
        public readonly ?string $maMon = null,
        public readonly ?string $tenMon = null,
        public readonly ?int $soTinChi = null,
        public readonly ?string $khoaId = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            maMon: $data['maMon'] ?? $data['ma_mon'] ?? null,
            tenMon: $data['tenMon'] ?? $data['ten_mon'] ?? null,
            soTinChi: isset($data['soTinChi']) || isset($data['so_tin_chi'])
            ? (int) ($data['soTinChi'] ?? $data['so_tin_chi'])
            : null,
            khoaId: $data['khoaId'] ?? $data['khoa_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        $result = [];

        if ($this->maMon !== null) {
            $result['ma_mon'] = $this->maMon;
        }
        if ($this->tenMon !== null) {
            $result['ten_mon'] = $this->tenMon;
        }
        if ($this->soTinChi !== null) {
            $result['so_tin_chi'] = $this->soTinChi;
        }
        if ($this->khoaId !== null) {
            $result['khoa_id'] = $this->khoaId;
        }

        return $result;
    }
}
