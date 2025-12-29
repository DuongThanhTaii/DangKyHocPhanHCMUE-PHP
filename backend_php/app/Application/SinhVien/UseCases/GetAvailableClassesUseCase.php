<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\DangKyHocPhanRepositoryInterface;

/**
 * UseCase: Lấy danh sách lớp học phần có thể đăng ký
 */
class GetAvailableClassesUseCase
{
    public function __construct(
        private DangKyHocPhanRepositoryInterface $repository
    ) {
    }

    public function execute(string $sinhVienId, string $hocKyId): array
    {
        $lopHocPhans = $this->repository->getAvailableClasses($hocKyId);
        $registeredIds = $this->repository->getRegisteredClassIds($sinhVienId, $hocKyId);

        // Group by MonHoc
        $monHocMap = [];

        foreach ($lopHocPhans as $lhp) {
            // Skip if already registered
            if (in_array($lhp->id, $registeredIds)) {
                continue;
            }

            $hocPhan = $lhp->hocPhan;
            $monHoc = $hocPhan?->monHoc;
            $maMon = $monHoc?->ma_mon ?? 'UNKNOWN';

            if (!isset($monHocMap[$maMon])) {
                $monHocMap[$maMon] = [
                    'monHocId' => $monHoc?->id ?? '',
                    'maMon' => $maMon,
                    'tenMon' => $monHoc?->ten_mon ?? '',
                    'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                    'laMonChung' => $monHoc?->la_mon_chung ?? false,
                    'loaiMon' => $monHoc?->loai_mon ?? '',
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
                'tenLop' => $lhp->ma_lop,
                'soLuongHienTai' => $lhp->so_luong_hien_tai ?? 0,
                'soLuongToiDa' => $lhp->so_luong_toi_da ?? 50,
                'giangVien' => $lhp->giangVien?->ho_ten ?? '',
                'tkb' => $tkbList,
            ];
        }

        // Categorize into monChung, batBuoc, tuChon
        $monChung = [];
        $batBuoc = [];
        $tuChon = [];

        foreach ($monHocMap as $dto) {
            $laMonChung = $dto['laMonChung'] ?? false;
            $loaiMon = $dto['loaiMon'] ?? '';

            unset($dto['laMonChung']);
            unset($dto['loaiMon']);

            if ($laMonChung) {
                $monChung[] = $dto;
            } elseif ($loaiMon === 'chuyen_nganh') {
                $batBuoc[] = $dto;
            } else {
                $tuChon[] = $dto;
            }
        }

        return [
            'isSuccess' => true,
            'data' => [
                'monChung' => $monChung,
                'batBuoc' => $batBuoc,
                'tuChon' => $tuChon,
            ],
            'message' => "Lấy thành công " . count($monHocMap) . " môn học"
        ];
    }
}
