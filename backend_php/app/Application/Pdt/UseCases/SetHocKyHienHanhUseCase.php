<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\HocKyRepositoryInterface;

/**
 * UseCase: Đặt học kỳ hiện hành
 */
class SetHocKyHienHanhUseCase
{
    public function __construct(
        private HocKyRepositoryInterface $repository
    ) {
    }

    /**
     * Execute use case
     *
     * @param string $hocKyId ID học kỳ
     * @return array Response data
     * @throws \InvalidArgumentException Khi thiếu hocKyId
     * @throws \RuntimeException Khi không tìm thấy học kỳ
     */
    public function execute(string $hocKyId): array
    {
        if (empty($hocKyId)) {
            throw new \InvalidArgumentException('Thiếu thông tin (hocKyId)');
        }

        // Check if semester exists
        $hocKy = $this->repository->findById($hocKyId);
        if (!$hocKy) {
            throw new \RuntimeException('Học kỳ không tồn tại');
        }

        // Set current semester
        $this->repository->setCurrentSemester($hocKyId);

        return [
            'isSuccess' => true,
            'data' => null,
            'message' => 'Đã đặt học kỳ hiện hành thành công'
        ];
    }
}
