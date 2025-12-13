<?php

namespace App\Application\TLK\UseCases;

use App\Domain\TLK\Repositories\TLKRepositoryInterface;

/**
 * UseCase: Lấy danh sách phòng học của khoa
 */
class GetPhongHocByKhoaUseCase
{
    public function __construct(
        private TLKRepositoryInterface $repository
    ) {
    }

    public function execute(string $khoaId, bool $onlyAvailable = false): array
    {
        $phongs = $onlyAvailable
            ? $this->repository->getAvailablePhongHocByKhoa($khoaId)
            : $this->repository->getPhongHocByKhoa($khoaId);

        $data = $phongs->map(function ($p) {
            return [
                'id' => $p->id,
                'maPhong' => $p->ma_phong,
                'sucChua' => $p->suc_chua ?? 0,
                'daSuDung' => $p->da_dc_su_dung ?? false,
            ];
        });

        $message = $onlyAvailable
            ? "Lấy thành công {$data->count()} phòng trống"
            : "Lấy thành công {$data->count()} phòng học";

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => $message
        ];
    }
}
