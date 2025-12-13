<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\PhongHocRepositoryInterface;

/**
 * UseCase: Lấy danh sách phòng học trống
 */
class GetAvailablePhongHocUseCase
{
    public function __construct(
        private PhongHocRepositoryInterface $repository
    ) {
    }

    public function execute(): array
    {
        $phongs = $this->repository->getAvailable();

        $data = $phongs->map(function ($p) {
            return [
                'id' => $p->id,
                'maPhong' => $p->ma_phong ?? '',
                'sucChua' => $p->suc_chua ?? 0,
                'daSuDung' => $p->da_dc_su_dung ?? false,
                'khoaId' => $p->khoa_id,
                'coSoId' => $p->co_so_id,
                'tenCoSo' => $p->coSo?->ten_co_so ?? '',
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} phòng trống"
        ];
    }
}
