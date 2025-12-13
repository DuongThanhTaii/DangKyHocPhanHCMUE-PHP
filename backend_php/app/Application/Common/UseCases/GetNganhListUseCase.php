<?php

namespace App\Application\Common\UseCases;

use App\Domain\Common\Repositories\CommonRepositoryInterface;

/**
 * UseCase: Lấy danh sách ngành
 */
class GetNganhListUseCase
{
    public function __construct(
        private CommonRepositoryInterface $repository
    ) {
    }

    public function execute(?string $khoaId = null): array
    {
        $nganhs = $this->repository->getNganhByKhoa($khoaId);

        $data = $nganhs->map(function ($n) {
            return [
                'id' => $n->id,
                'maNganh' => $n->ma_nganh,
                'tenNganh' => $n->ten_nganh,
                'khoaId' => $n->khoa_id,
                'tenKhoa' => $n->khoa?->ten_khoa
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} ngành"
        ];
    }
}
