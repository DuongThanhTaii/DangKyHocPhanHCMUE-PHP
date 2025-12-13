<?php

namespace App\Application\GiangVien\UseCases;

use App\Domain\GiangVien\Repositories\GVRepositoryInterface;

/**
 * UseCase: Lấy lịch dạy tuần của giảng viên
 */
class GetGVWeeklyScheduleUseCase
{
    public function __construct(
        private GVRepositoryInterface $repository
    ) {
    }

    public function execute(string $giangVienId, string $hocKyId): array
    {
        $lopHocPhans = $this->repository->getWeeklySchedule($giangVienId, $hocKyId);

        $data = [];

        foreach ($lopHocPhans as $lhp) {
            $monHoc = $lhp->hocPhan?->monHoc;

            foreach ($lhp->lichHocDinhKys as $lich) {
                $data[] = [
                    'thu' => $lich->thu,
                    'tietBatDau' => $lich->tiet_bat_dau,
                    'tietKetThuc' => $lich->tiet_ket_thuc,
                    'phong' => $lich->phong?->ma_phong ?? 'TBA',
                    'maLop' => $lhp->ma_lop,
                    'tenMon' => $monHoc?->ten_mon ?? '',
                    'maMon' => $monHoc?->ma_mon ?? '',
                ];
            }
        }

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => 'Lấy thời khóa biểu thành công'
        ];
    }
}
