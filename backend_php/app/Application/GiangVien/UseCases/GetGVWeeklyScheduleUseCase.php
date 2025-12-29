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
                // Calculate ngay_hoc based on thu (day of week) - use current week as example
                $ngayHoc = $this->calculateNgayHoc($lich->thu, $lhp->ngay_bat_dau);

                $data[] = [
                    'thu' => $lich->thu,
                    'tiet_bat_dau' => $lich->tiet_bat_dau,
                    'tiet_ket_thuc' => $lich->tiet_ket_thuc,
                    'phong' => [
                        'id' => $lich->phong_id ?? '',
                        'ma_phong' => $lich->phong?->ma_phong ?? 'TBA',
                    ],
                    'lop_hoc_phan' => [
                        'id' => $lhp->id,
                        'ma_lop' => $lhp->ma_lop,
                    ],
                    'mon_hoc' => [
                        'ma_mon' => $monHoc?->ma_mon ?? '',
                        'ten_mon' => $monHoc?->ten_mon ?? '',
                    ],
                    'ngay_hoc' => $ngayHoc,
                ];
            }
        }

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => 'Lấy thời khóa biểu thành công'
        ];
    }

    /**
     * Calculate ngay_hoc from thu (day of week) for the current week
     */
    private function calculateNgayHoc(int $thu, $ngayBatDau): string
    {
        // Get the start of current week (Monday)
        $today = now();
        $dayOfWeek = $today->dayOfWeekIso; // 1 = Monday, 7 = Sunday
        $monday = $today->copy()->subDays($dayOfWeek - 1);

        // Calculate the date for this thu
        // thu: 2 = Monday, 3 = Tuesday, ..., 8 = Sunday
        $daysFromMonday = $thu - 2; // Convert thu to 0-indexed (Mon=0)
        if ($daysFromMonday < 0)
            $daysFromMonday = 6; // Sunday

        $targetDate = $monday->copy()->addDays($daysFromMonday);

        return $targetDate->format('Y-m-d');
    }
}
