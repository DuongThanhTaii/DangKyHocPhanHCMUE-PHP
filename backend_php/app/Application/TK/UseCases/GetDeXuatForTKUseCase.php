<?php

namespace App\Application\TK\UseCases;

use App\Domain\TK\Repositories\TKRepositoryInterface;

/**
 * UseCase: Lấy danh sách đề xuất chờ TK duyệt
 */
class GetDeXuatForTKUseCase
{
    public function __construct(
        private TKRepositoryInterface $repository
    ) {
    }

    public function execute(string $khoaId, ?string $hocKyId = null): array
    {
        $deXuats = $this->repository->getDeXuatPendingForTK($khoaId, $hocKyId);

        $data = $deXuats->map(function ($dx) {
            $monHoc = $dx->monHoc;
            $gvDeXuat = $dx->giangVienDeXuat;

            return [
                'id' => $dx->id,
                'trangThai' => $dx->trang_thai ?? 'cho_duyet',
                'capDuyetHienTai' => $dx->cap_duyet_hien_tai ?? '',
                'soLopDuKien' => $dx->so_lop_du_kien ?? 0,
                'ngayTao' => $dx->created_at?->toISOString(),
                'ghiChu' => $dx->ghi_chu ?? '',
                'monHoc' => [
                    'id' => $monHoc?->id ?? '',
                    'maMon' => $monHoc?->ma_mon ?? '',
                    'tenMon' => $monHoc?->ten_mon ?? '',
                    'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                ],
                'giangVienDeXuat' => [
                    'id' => $dx->giang_vien_de_xuat ?? '',
                    'hoTen' => $gvDeXuat?->user?->ho_ten ?? '',
                ],
                'nguoiTao' => [
                    'id' => $dx->nguoi_tao ?? '',
                    'hoTen' => $dx->nguoiTao?->ho_ten ?? '',
                ],
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} đề xuất chờ duyệt"
        ];
    }
}
