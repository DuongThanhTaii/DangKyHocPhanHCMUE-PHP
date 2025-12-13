<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\GiangVienRepositoryInterface;
use App\Application\Pdt\DTOs\UpdateGiangVienDTO;

/**
 * UseCase: Cập nhật giảng viên
 */
class UpdateGiangVienUseCase
{
    public function __construct(
        private GiangVienRepositoryInterface $repository
    ) {
    }

    public function execute(string $id, UpdateGiangVienDTO $dto): array
    {
        $giangVien = $this->repository->update($id, $dto->toArray());

        if (!$giangVien) {
            throw new \RuntimeException('Không tìm thấy giảng viên');
        }

        return [
            'isSuccess' => true,
            'data' => ['id' => $giangVien->id],
            'message' => 'Cập nhật giảng viên thành công'
        ];
    }
}
