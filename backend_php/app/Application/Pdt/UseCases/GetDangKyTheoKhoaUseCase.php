<?php

namespace App\Application\Pdt\UseCases;

use App\Application\Pdt\DTOs\BaoCaoFilterDTO;
use App\Domain\Pdt\Repositories\BaoCaoRepositoryInterface;

/**
 * UseCase: Lấy thống kê đăng ký theo khoa
 * 
 * Business logic cho API GET /bao-cao/dk-theo-khoa
 */
class GetDangKyTheoKhoaUseCase
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
        $stats = $this->baoCaoRepository->getDangKyTheoKhoa($dto->hocKyId);

        // 3. Transform to response format
        $data = $stats->map(function ($item) {
            return [
                'ten_khoa' => $item->ten_khoa,
                'so_dang_ky' => (int) $item->so_dang_ky,
            ];
        })->toArray();

        // 4. Return result
        return [
            'isSuccess' => true,
            'data' => [
                'data' => $data,
                'ketLuan' => 'Thống kê số lượng đăng ký theo từng khoa.',
            ],
        ];
    }
}
