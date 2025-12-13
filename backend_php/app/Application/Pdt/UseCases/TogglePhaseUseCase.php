<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\KyPhaseRepositoryInterface;
use App\Application\Pdt\DTOs\TogglePhaseDTO;
use Carbon\Carbon;

/**
 * UseCase: Toggle phase trong học kỳ
 */
class TogglePhaseUseCase
{
    public function __construct(
        private KyPhaseRepositoryInterface $repository
    ) {
    }

    public function execute(TogglePhaseDTO $dto): array
    {
        $hocKyId = $dto->hocKyId;

        // If hocKyId not provided, get current semester
        if (!$hocKyId) {
            $currentHocKy = $this->repository->getCurrentHocKy();
            if (!$currentHocKy) {
                throw new \RuntimeException('Không tìm thấy học kỳ hiện hành');
            }
            $hocKyId = $currentHocKy->id;
        }

        // Disable all phases for this semester
        $this->repository->disableAllPhases($hocKyId);

        // Find and enable the selected phase
        $kyPhase = $this->repository->findByHocKyAndPhase($hocKyId, $dto->phase);

        if (!$kyPhase) {
            throw new \RuntimeException("Không tìm thấy phase '{$dto->phase}' trong học kỳ này (dữ liệu chưa được khởi tạo)");
        }

        $now = Carbon::now();
        $startAt = $now->copy()->subHour();
        $endAt = $now->copy()->addDays(30);

        $this->repository->enablePhase($kyPhase, $startAt, $endAt);

        return [
            'isSuccess' => true,
            'data' => [
                'message' => "Đã chuyển sang giai đoạn: {$dto->phase}",
                'active_phase' => $dto->phase,
                'start_at' => $startAt->toIso8601String(),
                'end_at' => $endAt->toIso8601String(),
            ],
            'message' => "Đã chuyển sang giai đoạn: {$dto->phase}"
        ];
    }
}
