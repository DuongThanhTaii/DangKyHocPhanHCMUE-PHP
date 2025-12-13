<?php

namespace App\Application\Pdt\UseCases;

use App\Application\Pdt\DTOs\BaoCaoFilterDTO;
use App\Domain\Pdt\Repositories\BaoCaoRepositoryInterface;

/**
 * UseCase: Lấy thống kê tải giảng viên
 * 
 * Business logic cho API GET /bao-cao/tai-giang-vien
 */
class GetTaiGiangVienUseCase
{
    public function __construct(
        private BaoCaoRepositoryInterface $baoCaoRepository
    ) {
    }

    /**
     * Execute use case
     *
     * @param BaoCaoFilterDTO $dto Filter parameters
     * @return array Response data
     * @throws \InvalidArgumentException Khi thiếu hoc_ky_id
     */
    public function execute(BaoCaoFilterDTO $dto): array
    {
        // 1. Validate input
        $dto->validate();

        // 2. Get data from repository
        $stats = $this->baoCaoRepository->getTaiGiangVien($dto->hocKyId, $dto->khoaId);

        // 3. Transform to response format
        $data = $stats->map(function ($item) {
            return [
                'ho_ten' => $item->ho_ten,
                'so_lop' => (int) $item->so_lop,
            ];
        })->toArray();

        // 4. Return result
        return [
            'isSuccess' => true,
            'data' => [
                'data' => $data,
                'ketLuan' => 'Thống kê số lượng lớp học phần theo giảng viên.',
            ],
        ];
    }
}
