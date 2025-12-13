<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\DangKyHocPhanRepositoryInterface;

/**
 * UseCase: Tra cứu học phần mở
 */
class SearchOpenCoursesUseCase
{
    public function __construct(
        private DangKyHocPhanRepositoryInterface $repository
    ) {
    }

    public function execute(string $hocKyId): array
    {
        $hocPhans = $this->repository->searchOpenCourses($hocKyId);

        $stt = 0;
        $data = $hocPhans->map(function ($hp) use (&$stt) {
            $stt++;
            $monHoc = $hp->monHoc;

            $loaiMon = 'dai_cuong';

            $danhSachLop = ($hp->lopHocPhans ?? collect())->map(function ($lhp) {
                $tkbLines = [];
                foreach ($lhp->lichHocDinhKys ?? [] as $lich) {
                    $thuText = "Thứ " . $lich->thu;
                    $tietText = "Tiết " . $lich->tiet_bat_dau . "-" . $lich->tiet_ket_thuc;
                    $phongText = $lich->phong?->ma_phong ?? 'TBA';
                    $tkbLines[] = "{$thuText}, {$tietText}, {$phongText}";
                }

                return [
                    'id' => $lhp->id,
                    'maLop' => $lhp->ma_lop,
                    'giangVien' => $lhp->giangVien?->ho_ten ?? 'Chưa phân công',
                    'soLuongToiDa' => $lhp->so_luong_toi_da ?? 50,
                    'soLuongHienTai' => $lhp->so_luong_hien_tai ?? 0,
                    'conSlot' => ($lhp->so_luong_toi_da ?? 50) - ($lhp->so_luong_hien_tai ?? 0),
                    'thoiKhoaBieu' => implode("\n", $tkbLines) ?: 'Chưa xếp TKB',
                ];
            });

            return [
                'stt' => $stt,
                'maMon' => $monHoc?->ma_mon ?? '',
                'tenMon' => $monHoc?->ten_mon ?? '',
                'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                'loaiMon' => $loaiMon,
                'danhSachLop' => $danhSachLop,
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Tìm thấy {$data->count()} học phần"
        ];
    }
}
