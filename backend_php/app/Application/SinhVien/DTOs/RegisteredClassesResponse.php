<?php

namespace App\Application\SinhVien\DTOs;

use App\Domain\SinhVien\Entities\DangKyHocPhanEntity;

/**
 * Response DTO for GetRegisteredClasses UseCase
 * 
 * Groups registrations by MonHoc (subject)
 */
class RegisteredClassesResponse
{
    /**
     * @param MonHocGroupDTO[] $monHocGroups
     */
    public function __construct(
        public readonly array $monHocGroups,
        public readonly int $totalCredits = 0,
        public readonly int $totalClasses = 0,
    ) {
    }

    public static function fromDangKys(array $dangKys): self
    {
        $monHocMap = [];
        $totalCredits = 0;
        $totalClasses = 0;

        foreach ($dangKys as $dk) {
            $maMonHoc = $dk->maMonHoc ?? 'UNKNOWN';
            
            if (!isset($monHocMap[$maMonHoc])) {
                $monHocMap[$maMonHoc] = new MonHocGroupDTO(
                    maMonHoc: $maMonHoc,
                    tenMonHoc: $dk->tenMonHoc ?? '',
                    soTinChi: $dk->soTinChi ?? 0,
                    lopHocPhans: [],
                );
                $totalCredits += $dk->soTinChi ?? 0;
            }

            $monHocMap[$maMonHoc]->lopHocPhans[] = new LopHocPhanSummaryDTO(
                id: $dk->lopHocPhanId,
                maLop: $dk->maLop ?? '',
                trangThai: $dk->trangThai->value(),
                trangThaiLabel: $dk->trangThai->label(),
            );
            $totalClasses++;
        }

        return new self(
            monHocGroups: array_values($monHocMap),
            totalCredits: $totalCredits,
            totalClasses: $totalClasses,
        );
    }

    public function toArray(): array
    {
        return [
            'monHocGroups' => array_map(fn($g) => $g->toArray(), $this->monHocGroups),
            'totalCredits' => $this->totalCredits,
            'totalClasses' => $this->totalClasses,
        ];
    }
}

/**
 * DTO for MonHoc group in registration list
 */
class MonHocGroupDTO
{
    /**
     * @param LopHocPhanSummaryDTO[] $lopHocPhans
     */
    public function __construct(
        public readonly string $maMonHoc,
        public readonly string $tenMonHoc,
        public readonly int $soTinChi,
        public array $lopHocPhans = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'maMonHoc' => $this->maMonHoc,
            'tenMonHoc' => $this->tenMonHoc,
            'soTinChi' => $this->soTinChi,
            'lopHocPhans' => array_map(fn($l) => $l->toArray(), $this->lopHocPhans),
        ];
    }
}

/**
 * DTO for LopHocPhan summary in registration list
 */
class LopHocPhanSummaryDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $maLop,
        public readonly string $trangThai,
        public readonly string $trangThaiLabel,
        public readonly ?array $lichHoc = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'maLop' => $this->maLop,
            'trangThai' => $this->trangThai,
            'trangThaiLabel' => $this->trangThaiLabel,
            'lichHoc' => $this->lichHoc,
        ];
    }
}
