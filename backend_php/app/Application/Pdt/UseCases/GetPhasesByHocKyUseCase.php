<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\HocKyRepositoryInterface;

/**
 * UseCase: Lấy phases theo học kỳ
 */
class GetPhasesByHocKyUseCase
{
    public function __construct(
        private HocKyRepositoryInterface $repository
    ) {
    }

    /**
     * Execute use case
     *
     * @param string $hocKyId ID học kỳ
     * @return array Response data
     * @throws \InvalidArgumentException Khi thiếu hocKyId
     */
    public function execute(string $hocKyId): array
    {
        if (empty($hocKyId)) {
            throw new \InvalidArgumentException('Thiếu hocKyId');
        }

        $phases = $this->repository->getPhasesByHocKy($hocKyId);

        $data = $phases->map(function ($p) {
            return [
                'id' => $p->id,
                'phase' => $p->phase,
                'startAt' => $p->start_at?->toISOString(),
                'endAt' => $p->end_at?->toISOString(),
                'isEnabled' => $p->is_enabled ?? false,
                'isActive' => $p->isActive(),
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} phases"
        ];
    }
}
