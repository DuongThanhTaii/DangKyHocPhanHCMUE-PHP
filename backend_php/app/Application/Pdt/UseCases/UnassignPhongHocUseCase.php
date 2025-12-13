<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\PhongHocRepositoryInterface;
use App\Application\Pdt\DTOs\UnassignPhongHocDTO;

/**
 * UseCase: Hủy gán phòng học khỏi khoa
 */
class UnassignPhongHocUseCase
{
    public function __construct(
        private PhongHocRepositoryInterface $repository
    ) {
    }

    public function execute(UnassignPhongHocDTO $dto): array
    {
        $updated = $this->repository->unassignFromKhoa($dto->phongIds);

        return [
            'isSuccess' => true,
            'data' => ['updatedCount' => $updated],
            'message' => "Đã gỡ {$updated} phòng khỏi khoa"
        ];
    }
}
