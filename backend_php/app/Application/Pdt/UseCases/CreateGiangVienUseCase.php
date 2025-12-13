<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\GiangVienRepositoryInterface;
use App\Application\Pdt\DTOs\CreateGiangVienDTO;

/**
 * UseCase: Tạo giảng viên mới
 */
class CreateGiangVienUseCase
{
    public function __construct(
        private GiangVienRepositoryInterface $repository
    ) {
    }

    public function execute(CreateGiangVienDTO $dto): array
    {
        $giangVien = $this->repository->create($dto->toArray());

        return [
            'isSuccess' => true,
            'data' => ['id' => $giangVien->id],
            'message' => 'Tạo giảng viên thành công'
        ];
    }
}
