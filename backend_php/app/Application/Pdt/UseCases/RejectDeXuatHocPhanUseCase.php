<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\DeXuatHocPhanRepositoryInterface;

/**
 * UseCase: Từ chối đề xuất học phần
 */
class RejectDeXuatHocPhanUseCase
{
    public function __construct(
        private DeXuatHocPhanRepositoryInterface $repository
    ) {
    }

    public function execute(string $deXuatId, string $lyDo = ''): array
    {
        if (empty($deXuatId)) {
            throw new \InvalidArgumentException('ID đề xuất không được rỗng');
        }

        // Find proposal
        $deXuat = $this->repository->findById($deXuatId);
        if (!$deXuat) {
            throw new \RuntimeException('Không tìm thấy đề xuất');
        }

        // Update status
        $this->repository->update($deXuatId, [
            'trang_thai' => 'tu_choi',
            'cap_duyet_hien_tai' => 'pdt',
            'ghi_chu' => $lyDo ? "Lý do từ chối PDT: {$lyDo}" : 'Bị từ chối bởi Phòng Đào Tạo',
            'updated_at' => now(),
        ]);

        return [
            'isSuccess' => true,
            'data' => [
                'id' => $deXuat->id,
                'trangThai' => 'tu_choi',
            ],
            'message' => 'Từ chối đề xuất thành công'
        ];
    }
}
