<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\GhiDanhRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\HocPhan;

/**
 * UseCase: Ghi danh môn học
 */
class EnrollSubjectUseCase
{
    public function __construct(
        private GhiDanhRepositoryInterface $repository
    ) {
    }

    public function execute(string $sinhVienId, string $monHocId, string $hocKyId): array
    {
        if (!$monHocId) {
            throw new \InvalidArgumentException('Thiếu ID môn học');
        }

        // Check if already enrolled
        if ($this->repository->hasEnrolled($sinhVienId, $monHocId, $hocKyId)) {
            throw new \RuntimeException('Đã ghi danh môn học này rồi');
        }

        // Find HocPhan
        $hocPhan = HocPhan::where('mon_hoc_id', $monHocId)
            ->where('id_hoc_ky', $hocKyId)
            ->first();

        if (!$hocPhan) {
            throw new \RuntimeException('Không tìm thấy học phần cho môn học này trong học kỳ');
        }

        // Create enrollment
        $ghiDanh = $this->repository->createGhiDanh([
            'sinh_vien_id' => $sinhVienId,
            'hoc_phan_id' => $hocPhan->id,
        ]);

        return [
            'isSuccess' => true,
            'data' => [
                'id' => $ghiDanh->id,
                'hocPhanId' => $hocPhan->id,
            ],
            'message' => 'Ghi danh thành công'
        ];
    }
}
