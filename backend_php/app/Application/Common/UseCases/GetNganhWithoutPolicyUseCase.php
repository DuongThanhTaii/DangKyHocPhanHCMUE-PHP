<?php

namespace App\Application\Common\UseCases;

use App\Domain\Common\Repositories\CommonRepositoryInterface;

/**
 * UseCase: Lấy danh sách ngành chưa có chính sách tín chỉ
 */
class GetNganhWithoutPolicyUseCase
{
    public function __construct(
        private CommonRepositoryInterface $repository
    ) {
    }

    public function execute(string $hocKyId, string $khoaId): array
    {
        if (!$hocKyId) {
            throw new \InvalidArgumentException('Thiếu học kỳ ID');
        }

        if (!$khoaId) {
            throw new \InvalidArgumentException('Thiếu khoa ID');
        }

        $nganhs = $this->repository->getNganhWithoutPolicy($hocKyId, $khoaId);

        $data = $nganhs->map(function ($n) {
            return [
                'id' => $n->id,
                'ma_nganh' => $n->ma_nganh,
                'ten_nganh' => $n->ten_nganh,
                'khoa_id' => $n->khoa_id
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} ngành chưa có chính sách"
        ];
    }
}
