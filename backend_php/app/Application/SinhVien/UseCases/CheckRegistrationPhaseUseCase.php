<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\DangKyHocPhanRepositoryInterface;

/**
 * UseCase: Kiểm tra giai đoạn đăng ký
 */
class CheckRegistrationPhaseUseCase
{
    public function __construct(
        private DangKyHocPhanRepositoryInterface $repository
    ) {
    }

    public function execute(string $hocKyId): array
    {
        if (!$hocKyId) {
            throw new \InvalidArgumentException('Thiếu học kỳ ID');
        }

        $currentPhase = $this->repository->getCurrentPhase($hocKyId);

        if (!$currentPhase) {
            return [
                'isValid' => false,
                'phase' => null,
                'message' => 'Chưa có giai đoạn hiện hành'
            ];
        }

        if ($currentPhase->phase !== 'dang_ky_hoc_phan') {
            return [
                'isValid' => false,
                'phase' => $currentPhase->phase,
                'message' => 'Không trong giai đoạn đăng ký học phần'
            ];
        }

        return [
            'isValid' => true,
            'phase' => $currentPhase->phase,
            'startAt' => $currentPhase->start_at->toISOString(),
            'endAt' => $currentPhase->end_at->toISOString(),
            'message' => 'Đang trong giai đoạn đăng ký học phần'
        ];
    }
}
