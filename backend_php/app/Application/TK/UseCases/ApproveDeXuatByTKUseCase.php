<?php

namespace App\Application\TK\UseCases;

use App\Domain\TK\Repositories\TKRepositoryInterface;

/**
 * UseCase: Duyệt đề xuất học phần
 */
class ApproveDeXuatByTKUseCase
{
    public function __construct(
        private TKRepositoryInterface $repository
    ) {
    }

    public function execute(string $deXuatId, string $khoaId): array
    {
        if (!$deXuatId) {
            throw new \InvalidArgumentException('ID đề xuất học phần không được rỗng');
        }

        $deXuat = $this->repository->findDeXuatForTK($deXuatId, $khoaId);

        if (!$deXuat) {
            throw new \RuntimeException('Không tìm thấy đề xuất hoặc đề xuất không thuộc quyền duyệt của bạn');
        }

        $this->repository->approveDeXuat($deXuat);

        return [
            'isSuccess' => true,
            'data' => [
                'id' => $deXuat->id,
                'trangThai' => $deXuat->trang_thai,
                'capDuyetHienTai' => $deXuat->cap_duyet_hien_tai,
            ],
            'message' => 'Duyệt đề xuất thành công'
        ];
    }
}
