<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\MonHocRepositoryInterface;
use App\Application\Pdt\DTOs\CreateMonHocDTO;

/**
 * UseCase: Tạo môn học mới
 */
class CreateMonHocUseCase
{
    public function __construct(
        private MonHocRepositoryInterface $repository
    ) {
    }

    public function execute(CreateMonHocDTO $dto): array
    {
        $monHoc = $this->repository->create($dto->toArray());

        return [
            'isSuccess' => true,
            'data' => ['id' => $monHoc->id],
            'message' => 'Tạo môn học thành công'
        ];
    }
}
