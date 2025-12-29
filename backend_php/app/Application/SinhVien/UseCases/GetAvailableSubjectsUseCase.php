<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\GhiDanhRepositoryInterface;

/**
 * UseCase: Lấy danh sách môn học có thể ghi danh
 */
class GetAvailableSubjectsUseCase
{
    public function __construct(
        private GhiDanhRepositoryInterface $repository
    ) {
    }

    public function execute(string $hocKyId, string $khoaId): array
    {
        $hocPhans = $this->repository->getAvailableSubjectsForEnrollment($hocKyId, $khoaId);

        $data = $hocPhans->map(function ($hp) {
            $monHoc = $hp->monHoc;
            return [
                'hocPhanId' => $hp->id,
                'monHocId' => $monHoc?->id ?? '',
                'maMon' => $monHoc?->ma_mon ?? '',
                'tenMon' => $monHoc?->ten_mon ?? '',
                'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                'laMonChung' => $monHoc?->la_mon_chung ?? false,
                'tenKhoa' => $monHoc?->khoa?->ten_khoa ?? '',
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} môn học"
        ];
    }
}
