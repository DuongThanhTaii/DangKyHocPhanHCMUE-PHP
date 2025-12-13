<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\PhongHocRepositoryInterface;
use App\Application\Pdt\DTOs\AssignPhongHocDTO;

/**
 * UseCase: Gán phòng học cho khoa
 */
class AssignPhongHocUseCase
{
    public function __construct(
        private PhongHocRepositoryInterface $repository
    ) {
    }

    public function execute(AssignPhongHocDTO $dto): array
    {
        $updated = $this->repository->assignToKhoa($dto->phongIds, $dto->khoaId);

        return [
            'isSuccess' => true,
            'data' => ['updatedCount' => $updated],
            'message' => "Đã gán {$updated} phòng cho khoa"
        ];
    }
}
