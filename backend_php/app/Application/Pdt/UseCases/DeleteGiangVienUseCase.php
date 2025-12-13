<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\GiangVienRepositoryInterface;

/**
 * UseCase: Xóa giảng viên
 */
class DeleteGiangVienUseCase
{
    public function __construct(
        private GiangVienRepositoryInterface $repository
    ) {
    }

    public function execute(string $id): array
    {
        $deleted = $this->repository->delete($id);

        if (!$deleted) {
            throw new \RuntimeException('Không tìm thấy giảng viên');
        }

        return [
            'isSuccess' => true,
            'data' => null,
            'message' => 'Xóa giảng viên thành công'
        ];
    }
}
