<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\DangKyHocPhanRepositoryInterface;

/**
 * UseCase: Lấy lịch sử đăng ký
 */
class GetRegistrationHistoryUseCase
{
    public function __construct(
        private DangKyHocPhanRepositoryInterface $repository
    ) {
    }

    public function execute(string $sinhVienId, string $hocKyId): array
    {
        $chiTiets = $this->repository->getRegistrationHistory($sinhVienId, $hocKyId);

        if ($chiTiets->isEmpty()) {
            return [
                'isSuccess' => true,
                'data' => [],
                'message' => 'Không có lịch sử đăng ký'
            ];
        }

        $lichSuItems = $chiTiets->map(function ($ct) {
            // Try to get lopHocPhan directly first (if lop_hoc_phan_id is set)
            $lhp = $ct->lopHocPhan;

            // Fallback to getting from dangKyHocPhan if direct relationship is not available
            if (!$lhp && $ct->dangKyHocPhan) {
                $lhp = $ct->dangKyHocPhan->lopHocPhan;
            }

            $monHoc = $lhp?->hocPhan?->monHoc;

            return [
                'id' => $ct->id,
                'hanhDong' => $ct->hanh_dong,
                'thoiGian' => $ct->thoi_gian?->toISOString(),
                'monHoc' => [
                    'maMon' => $monHoc?->ma_mon ?? '',
                    'tenMon' => $monHoc?->ten_mon ?? '',
                    'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                ],
                'lopHocPhan' => [
                    'id' => $lhp?->id ?? '',
                    'maLop' => $lhp?->ma_lop ?? '',
                ],
            ];
        });

        return [
            'isSuccess' => true,
            'data' => ['lichSu' => $lichSuItems],
            'message' => "Lấy thành công {$lichSuItems->count()} lịch sử"
        ];
    }
}
