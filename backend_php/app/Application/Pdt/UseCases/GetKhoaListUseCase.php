<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\KhoaRepositoryInterface;

/**
 * UseCase: Lấy danh sách khoa
 */
class GetKhoaListUseCase
{
    public function __construct(
        private KhoaRepositoryInterface $repository
    ) {
    }

    public function execute(): array
    {
        $khoas = $this->repository->getAll();

        $data = $khoas->map(function ($k) {
            return [
                'id' => $k->id,
                'maKhoa' => $k->ma_khoa ?? '',
                'tenKhoa' => $k->ten_khoa ?? '',
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} khoa"
        ];
    }
}
