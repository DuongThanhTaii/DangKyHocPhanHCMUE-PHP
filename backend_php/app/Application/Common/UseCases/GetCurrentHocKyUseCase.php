<?php

namespace App\Application\Common\UseCases;

use App\Domain\Common\Repositories\CommonRepositoryInterface;

/**
 * UseCase: Lấy học kỳ hiện hành
 */
class GetCurrentHocKyUseCase
{
    public function __construct(
        private CommonRepositoryInterface $repository
    ) {
    }

    public function execute(): array
    {
        $hocKy = $this->repository->getCurrentHocKy();

        if (!$hocKy) {
            return [
                'isSuccess' => true,
                'data' => null,
                'message' => 'Không có học kỳ hiện hành'
            ];
        }

        $data = [
            'id' => $hocKy->id,
            'tenHocKy' => $hocKy->ten_hoc_ky,
            'maHocKy' => $hocKy->ma_hoc_ky,
            'nienKhoa' => [
                'id' => $hocKy->nienKhoa?->id ?? $hocKy->id_nien_khoa,
                'tenNienKhoa' => $hocKy->nienKhoa?->ten_nien_khoa ?? ''
            ],
            'ngayBatDau' => $hocKy->ngay_bat_dau?->toDateString(),
            'ngayKetThuc' => $hocKy->ngay_ket_thuc?->toDateString()
        ];

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => 'Lấy học kỳ hiện hành thành công'
        ];
    }
}
