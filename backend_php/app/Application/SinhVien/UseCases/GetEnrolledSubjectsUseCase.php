<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\GhiDanhRepositoryInterface;

/**
 * UseCase: Lấy danh sách môn đã ghi danh
 */
class GetEnrolledSubjectsUseCase
{
    public function __construct(
        private GhiDanhRepositoryInterface $repository
    ) {
    }

    public function execute(string $sinhVienId, string $hocKyId): array
    {
        $ghiDanhs = $this->repository->getEnrolledSubjects($sinhVienId, $hocKyId);

        $data = $ghiDanhs->map(function ($gd) {
            $hocPhan = $gd->hocPhan;
            $monHoc = $hocPhan?->monHoc;

            return [
                'ghiDanhId' => $gd->id,
                'hocPhanId' => $hocPhan?->id ?? '',
                'monHocId' => $monHoc?->id ?? '',
                'maMon' => $monHoc?->ma_mon ?? '',
                'tenMon' => $monHoc?->ten_mon ?? '',
                'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                'ngayGhiDanh' => $gd->ngay_ghi_danh?->toDateString(),
                'trangThai' => $gd->trang_thai,
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} môn đã ghi danh"
        ];
    }
}
