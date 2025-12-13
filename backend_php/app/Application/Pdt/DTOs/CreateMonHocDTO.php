<?php

namespace App\Application\Pdt\DTOs;

/**
 * DTO cho việc tạo Môn học mới
 */
class CreateMonHocDTO
{
    public function __construct(
        public readonly string $maMon,
        public readonly string $tenMon,
        public readonly int $soTinChi = 0,
        public readonly ?string $khoaId = null,
        public readonly ?string $loaiMon = null,
        public readonly bool $laMonChung = false,
        public readonly int $thuTuHoc = 1,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        $maMon = $data['maMon'] ?? $data['ma_mon'] ?? null;
        $tenMon = $data['tenMon'] ?? $data['ten_mon'] ?? null;

        if (!$maMon || !$tenMon) {
            throw new \InvalidArgumentException('Thiếu thông tin bắt buộc (maMon, tenMon)');
        }

        return new self(
            maMon: $maMon,
            tenMon: $tenMon,
            soTinChi: (int) ($data['soTinChi'] ?? $data['so_tin_chi'] ?? 0),
            khoaId: $data['khoaId'] ?? $data['khoa_id'] ?? null,
            loaiMon: $data['loaiMon'] ?? $data['loai_mon'] ?? null,
            laMonChung: filter_var($data['laMonChung'] ?? $data['la_mon_chung'] ?? false, FILTER_VALIDATE_BOOLEAN),
            thuTuHoc: (int) ($data['thuTuHoc'] ?? $data['thu_tu_hoc'] ?? 1),
        );
    }

    public function toArray(): array
    {
        return [
            'ma_mon' => $this->maMon,
            'ten_mon' => $this->tenMon,
            'so_tin_chi' => $this->soTinChi,
            'khoa_id' => $this->khoaId,
            'loai_mon' => $this->loaiMon,
            'la_mon_chung' => $this->laMonChung,
            'thu_tu_hoc' => $this->thuTuHoc,
        ];
    }
}
