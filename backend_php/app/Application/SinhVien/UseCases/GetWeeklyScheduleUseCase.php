<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\DangKyHocPhanRepositoryInterface;

/**
 * UseCase: Lấy thời khóa biểu tuần
 */
class GetWeeklyScheduleUseCase
{
    public function __construct(
        private DangKyHocPhanRepositoryInterface $repository
    ) {
    }

    public function execute(string $sinhVienId, string $hocKyId, string $dateStart, string $dateEnd): array
    {
        $dangKys = $this->repository->getWeeklySchedule($sinhVienId, $hocKyId);

        $startDate = new \DateTime($dateStart);
        $endDate = new \DateTime($dateEnd);

        $data = [];

        foreach ($dangKys as $dk) {
            $lhp = $dk->lopHocPhan;
            $monHoc = $lhp?->hocPhan?->monHoc;
            $giangVien = $lhp?->giangVien;

            foreach ($lhp->lichHocDinhKys as $lich) {
                $thu = $lich->thu;
                $phpDayOfWeek = ($thu == 8) ? 0 : ($thu - 1);

                $currentDate = clone $startDate;
                while ($currentDate <= $endDate) {
                    if ((int) $currentDate->format('w') == $phpDayOfWeek) {
                        $data[] = [
                            'ngay_hoc' => $currentDate->format('Y-m-d'),
                            'thu' => $thu,
                            'tiet_bat_dau' => $lich->tiet_bat_dau,
                            'tiet_ket_thuc' => $lich->tiet_ket_thuc,
                            'phong' => [
                                'id' => $lich->phong?->id ?? '',
                                'ma_phong' => $lich->phong?->ma_phong ?? 'TBA',
                            ],
                            'mon_hoc' => [
                                'ma_mon' => $monHoc?->ma_mon ?? '',
                                'ten_mon' => $monHoc?->ten_mon ?? '',
                            ],
                            'giang_vien' => $giangVien?->ho_ten ?? 'Chưa phân công',
                            'ma_lop' => $lhp->ma_lop ?? '',
                        ];
                    }
                    $currentDate->modify('+1 day');
                }
            }
        }

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => 'Lấy thời khóa biểu thành công'
        ];
    }
}
