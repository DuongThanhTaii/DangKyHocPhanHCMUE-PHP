<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\DangKyHocPhanRepositoryInterface;

/**
 * UseCase: Lấy danh sách lớp học phần đã đăng ký
 */
class GetRegisteredClassesUseCase
{
    public function __construct(
        private DangKyHocPhanRepositoryInterface $repository
    ) {
    }

    public function execute(string $sinhVienId, string $hocKyId): array
    {
        $dangKys = $this->repository->getRegisteredClasses($sinhVienId, $hocKyId);

        // Group by MonHoc
        $monHocMap = [];

        foreach ($dangKys as $dk) {
            $lhp = $dk->lopHocPhan;
            $monHoc = $lhp?->hocPhan?->monHoc;
            $maMon = $monHoc?->ma_mon ?? 'UNKNOWN';

            if (!isset($monHocMap[$maMon])) {
                $monHocMap[$maMon] = [
                    'maMon' => $maMon,
                    'tenMon' => $monHoc?->ten_mon ?? '',
                    'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                    'danhSachLop' => [],
                ];
            }

            $tkbList = $lhp->lichHocDinhKys->map(function ($lich) {
                return [
                    'thu' => $lich->thu,
                    'tietBatDau' => $lich->tiet_bat_dau,
                    'tietKetThuc' => $lich->tiet_ket_thuc,
                    'phong' => $lich->phong?->ma_phong ?? 'TBA',
                ];
            });

            $monHocMap[$maMon]['danhSachLop'][] = [
                'id' => $lhp->id,
                'maLop' => $lhp->ma_lop,
                'soLuongHienTai' => $lhp->so_luong_hien_tai ?? 0,
                'soLuongToiDa' => $lhp->so_luong_toi_da ?? 50,
                'tkb' => $tkbList,
                'trangThai' => $dk->trang_thai,
            ];
        }

        return [
            'isSuccess' => true,
            'data' => array_values($monHocMap),
            'message' => 'Lấy danh sách lớp đã đăng ký thành công'
        ];
    }
}
