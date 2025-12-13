<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\PhongHocRepositoryInterface;

/**
 * UseCase: Lấy danh sách phòng học theo khoa
 */
class GetPhongHocByKhoaUseCase
{
    public function __construct(
        private PhongHocRepositoryInterface $repository
    ) {
    }

    public function execute(string $khoaId): array
    {
        if (!$khoaId) {
            throw new \InvalidArgumentException('Thiếu khoaId');
        }

        $phongs = $this->repository->getByKhoa($khoaId);

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
            'message' => "Lấy thành công {$data->count()} phòng của khoa"
        ];
    }
}
