<?php

namespace App\Application\Pdt\UseCases;

use App\Application\Pdt\DTOs\UpdateDotDangKyDTO;
use App\Domain\Pdt\Repositories\DotDangKyRepositoryInterface;

/**
 * UseCase: Cập nhật đợt đăng ký
 */
class UpdateDotDangKyUseCase
{
    public function __construct(
        private DotDangKyRepositoryInterface $repository
    ) {
    }

    /**
     * Execute use case
     *
     * @param UpdateDotDangKyDTO $dto Update data
     * @return array Response data
     * @throws \InvalidArgumentException Khi validation fail
     * @throws \RuntimeException Khi không tìm thấy đợt đăng ký
     */
    public function execute(UpdateDotDangKyDTO $dto): array
    {
        // 1. Validate
        $dto->validate();

        // 2. Check if exists
        $dotDangKy = $this->repository->findById($dto->id);
        if (!$dotDangKy) {
            throw new \RuntimeException('Không tìm thấy đợt đăng ký');
        }

        // 3. Update
        $updated = $this->repository->update($dto->id, $dto->toUpdateArray());

        // 4. Return result
        return [
            'isSuccess' => true,
            'data' => [
                'id' => $updated->id,
                'loaiDot' => $updated->loai_dot,
                'thoiGianBatDau' => $updated->thoi_gian_bat_dau?->toISOString(),
                'thoiGianKetThuc' => $updated->thoi_gian_ket_thuc?->toISOString(),
            ],
            'message' => 'Cập nhật đợt đăng ký thành công'
        ];
    }
}
