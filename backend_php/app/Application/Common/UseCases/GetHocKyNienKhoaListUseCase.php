<?php

namespace App\Application\Common\UseCases;

use App\Domain\Common\Repositories\CommonRepositoryInterface;

/**
 * UseCase: Lấy danh sách học kỳ theo niên khóa
 */
class GetHocKyNienKhoaListUseCase
{
    public function __construct(
        private CommonRepositoryInterface $repository
    ) {
    }

    public function execute(): array
    {
        $hocKys = $this->repository->getAllHocKyGroupedByNienKhoa();

        // Group by nienKhoa
        $grouped = [];
        foreach ($hocKys as $hocKy) {
            $nienKhoaId = $hocKy->nienKhoa?->id ?? $hocKy->id_nien_khoa;
            $tenNienKhoa = $hocKy->nienKhoa?->ten_nien_khoa ?? '';

            if (!isset($grouped[$nienKhoaId])) {
                $grouped[$nienKhoaId] = [
                    'nienKhoaId' => $nienKhoaId,
                    'tenNienKhoa' => $tenNienKhoa,
                    'hocKy' => []
                ];
            }

            $grouped[$nienKhoaId]['hocKy'][] = [
                'id' => $hocKy->id,
                'tenHocKy' => $hocKy->ten_hoc_ky,
                'maHocKy' => $hocKy->ma_hoc_ky,
                'ngayBatDau' => $hocKy->ngay_bat_dau?->toDateString(),
                'ngayKetThuc' => $hocKy->ngay_ket_thuc?->toDateString(),
                'trangThaiHienTai' => $hocKy->trang_thai_hien_tai,
            ];
        }

        $data = array_values($grouped);

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công " . count($data) . " niên khóa"
        ];
    }
}
