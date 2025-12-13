<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\DeXuatHocPhanRepositoryInterface;

/**
 * UseCase: Lấy danh sách đề xuất học phần chờ duyệt PDT
 */
class GetDeXuatHocPhanListUseCase
{
    public function __construct(
        private DeXuatHocPhanRepositoryInterface $repository
    ) {
    }

    public function execute(): array
    {
        $deXuats = $this->repository->getPendingForPdt();

        $data = $deXuats->map(function ($dx) {
            $monHoc = $dx->monHoc;
            $gvDeXuat = $dx->giangVienDeXuat;

            return [
                'id' => $dx->id,
                'maHocPhan' => $monHoc?->ma_mon ?? '',
                'tenHocPhan' => $monHoc?->ten_mon ?? '',
                'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                'giangVien' => $gvDeXuat?->user?->ho_ten ?? '',
                'trangThai' => $dx->trang_thai ?? '',
                'soLopDuKien' => $dx->so_lop_du_kien ?? 0,
                'khoa' => $dx->khoa?->ten_khoa ?? '',
                'ngayTao' => $dx->created_at?->toISOString(),
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} đề xuất chờ duyệt PDT"
        ];
    }
}
