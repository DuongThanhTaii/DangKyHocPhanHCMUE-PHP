<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\MonHocRepositoryInterface;

/**
 * UseCase: Xóa môn học
 */
class DeleteMonHocUseCase
{
    public function __construct(
        private MonHocRepositoryInterface $repository
    ) {
    }

    public function execute(string $id): array
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new \RuntimeException('Không tìm thấy môn học');
        }

        return [
            'isSuccess' => true,
            'data' => null,
            'message' => 'Xóa môn học thành công'
        ];
    }
}
