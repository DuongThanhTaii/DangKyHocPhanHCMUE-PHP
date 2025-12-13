<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\GhiDanhRepositoryInterface;

/**
 * UseCase: Kiểm tra điều kiện ghi danh
 */
class CheckGhiDanhConditionUseCase
{
    public function __construct(
        private GhiDanhRepositoryInterface $repository
    ) {
    }

    public function execute(string $sinhVienId): array
    {
        // Get current semester
        $hocKy = $this->repository->getCurrentHocKy();

        if (!$hocKy) {
            return [
                'canEnroll' => false,
                'reason' => 'Không tìm thấy học kỳ hiện hành',
            ];
        }

        // Check current phase
        $phase = $this->repository->getCurrentPhase($hocKy->id);

        if (!$phase) {
            return [
                'canEnroll' => false,
                'reason' => 'Không trong giai đoạn ghi danh',
                'hocKyId' => $hocKy->id,
                'tenHocKy' => $hocKy->ten_hoc_ky,
            ];
        }

        if ($phase->phase !== 'ghi_danh') {
            return [
                'canEnroll' => false,
                'reason' => "Đang trong giai đoạn: {$phase->phase}",
                'hocKyId' => $hocKy->id,
                'tenHocKy' => $hocKy->ten_hoc_ky,
            ];
        }

        return [
            'canEnroll' => true,
            'hocKyId' => $hocKy->id,
            'tenHocKy' => $hocKy->ten_hoc_ky,
            'phase' => $phase->phase,
            'startAt' => $phase->start_at->toISOString(),
            'endAt' => $phase->end_at->toISOString(),
        ];
    }
}
