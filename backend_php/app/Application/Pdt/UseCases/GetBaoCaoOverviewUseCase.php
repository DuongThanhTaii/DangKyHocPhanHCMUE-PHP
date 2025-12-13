<?php

namespace App\Application\Pdt\UseCases;

use App\Application\Pdt\DTOs\BaoCaoFilterDTO;
use App\Domain\Pdt\Repositories\BaoCaoRepositoryInterface;

/**
 * UseCase: Lấy thống kê tổng quan
 * 
 * Business logic cho API GET /bao-cao/overview
 */
class GetBaoCaoOverviewUseCase
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
        $stats = $this->baoCaoRepository->getOverviewStats(
            $dto->hocKyId,
            $dto->khoaId,
            $dto->nganhId
        );

        // 3. Build and return result
        return [
            'isSuccess' => true,
            'data' => [
                'svUnique' => $stats['svUnique'],
                'soDangKy' => $stats['soDangKy'],
                'soLopHocPhan' => $stats['soLopHocPhan'],
                'taiChinh' => [
                    'thuc_thu' => (float) $stats['thucThu'],
                    'ky_vong' => (float) $stats['kyVong'],
                ],
                'ketLuan' => "Tổng quan: {$stats['svUnique']} sinh viên đã đăng ký {$stats['soDangKy']} lượt học phần.",
            ],
        ];
    }
}
