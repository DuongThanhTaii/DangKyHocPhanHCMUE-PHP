<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\MonHocRepositoryInterface;
use App\Application\Pdt\DTOs\UpdateMonHocDTO;

/**
 * UseCase: Cập nhật môn học
 */
class UpdateMonHocUseCase
{
    public function __construct(
        private MonHocRepositoryInterface $repository
    ) {
    }

    public function execute(string $id, UpdateMonHocDTO $dto): array
    {
        $monHoc = $this->repository->update($id, $dto->toArray());

        if (!$monHoc) {
            throw new \RuntimeException('Không tìm thấy môn học');
        }

        return [
            'isSuccess' => true,
            'data' => ['id' => $monHoc->id],
            'message' => 'Cập nhật môn học thành công'
        ];
    }
}
