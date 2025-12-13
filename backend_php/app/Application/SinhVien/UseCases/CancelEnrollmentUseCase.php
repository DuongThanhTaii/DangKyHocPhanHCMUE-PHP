<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\GhiDanhRepositoryInterface;

/**
 * UseCase: Hủy ghi danh môn học
 */
class CancelEnrollmentUseCase
{
    public function __construct(
        private GhiDanhRepositoryInterface $repository
    ) {
    }

    public function execute(string $ghiDanhId, string $sinhVienId): array
    {
        if (!$ghiDanhId) {
            throw new \InvalidArgumentException('Thiếu ID ghi danh');
        }

        $ghiDanh = $this->repository->findGhiDanh($ghiDanhId, $sinhVienId);

        if (!$ghiDanh) {
            throw new \RuntimeException('Không tìm thấy ghi danh hoặc không có quyền');
        }

        $this->repository->deleteGhiDanh($ghiDanh);

        return [
            'isSuccess' => true,
            'data' => null,
            'message' => 'Hủy ghi danh thành công'
        ];
    }

    public function executeBatch(array $ghiDanhIds, string $sinhVienId): array
    {
        if (empty($ghiDanhIds)) {
            throw new \InvalidArgumentException('Danh sách ghi danh rỗng');
        }

        $cancelledCount = 0;

        foreach ($ghiDanhIds as $ghiDanhId) {
            $ghiDanh = $this->repository->findGhiDanh($ghiDanhId, $sinhVienId);
            if ($ghiDanh) {
                $this->repository->deleteGhiDanh($ghiDanh);
                $cancelledCount++;
            }
        }

        return [
            'isSuccess' => true,
            'data' => ['cancelledCount' => $cancelledCount],
            'message' => "Đã hủy {$cancelledCount} ghi danh"
        ];
    }
}
