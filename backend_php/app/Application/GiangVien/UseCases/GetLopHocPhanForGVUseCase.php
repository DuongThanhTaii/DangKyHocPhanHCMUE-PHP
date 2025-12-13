<?php

namespace App\Application\GiangVien\UseCases;

use App\Domain\GiangVien\Repositories\GVRepositoryInterface;

/**
 * UseCase: Lấy danh sách lớp học phần của giảng viên
 */
class GetLopHocPhanForGVUseCase
{
    public function __construct(
        private GVRepositoryInterface $repository
    ) {
    }

    public function execute(string $giangVienId, ?string $hocKyId = null): array
    {
        $lopHocPhans = $this->repository->getLopHocPhanByGV($giangVienId, $hocKyId);

        $data = $lopHocPhans->map(function ($lhp) {
            $hocPhan = $lhp->hocPhan;
            $monHoc = $hocPhan?->monHoc;

            return [
                'id' => $lhp->id,
                'ma_lop' => $lhp->ma_lop,
                'so_luong_hien_tai' => $lhp->so_luong_hien_tai ?? 0,
                'so_luong_toi_da' => $lhp->so_luong_toi_da ?? 50,
                'hoc_phan' => [
                    'ten_hoc_phan' => $hocPhan?->ten_hoc_phan ?? '',
                    'mon_hoc' => [
                        'ma_mon' => $monHoc?->ma_mon ?? '',
                        'ten_mon' => $monHoc?->ten_mon ?? '',
                        'so_tin_chi' => $monHoc?->so_tin_chi ?? 0,
                    ],
                ],
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} lớp học phần"
        ];
    }
}
