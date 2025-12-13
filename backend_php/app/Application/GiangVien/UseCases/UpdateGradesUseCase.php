<?php

namespace App\Application\GiangVien\UseCases;

use App\Domain\GiangVien\Repositories\GVRepositoryInterface;

/**
 * UseCase: Cập nhật điểm sinh viên
 */
class UpdateGradesUseCase
{
    public function __construct(
        private GVRepositoryInterface $repository
    ) {
    }

    public function execute(string $lopId, string $giangVienId, array $items): array
    {
        // Verify class belongs to instructor
        $lhp = $this->repository->findLopHocPhanByGV($lopId, $giangVienId);
        if (!$lhp) {
            throw new \RuntimeException('Không tìm thấy lớp học phần hoặc không có quyền truy cập');
        }

        if (empty($items)) {
            throw new \InvalidArgumentException('Không có dữ liệu điểm để cập nhật');
        }

        $updatedCount = 0;

        foreach ($items as $item) {
            $sinhVienId = $item['sinhVienId'] ?? $item['sinh_vien_id'] ?? null;

            if (!$sinhVienId)
                continue;

            $this->repository->updateGrade($sinhVienId, $lopId, [
                'diem_chuyen_can' => $item['diemChuyenCan'] ?? $item['diem_chuyen_can'] ?? null,
                'diem_giua_ky' => $item['diemGiuaKy'] ?? $item['diem_giua_ky'] ?? null,
                'diem_cuoi_ky' => $item['diemCuoiKy'] ?? $item['diem_cuoi_ky'] ?? null,
                'diem_tong_ket' => $item['diemTongKet'] ?? $item['diem_tong_ket'] ?? null,
                'ghi_chu' => $item['ghiChu'] ?? $item['ghi_chu'] ?? null,
            ]);

            $updatedCount++;
        }

        return [
            'isSuccess' => true,
            'data' => ['updatedCount' => $updatedCount],
            'message' => "Cập nhật điểm thành công ({$updatedCount} sinh viên)"
        ];
    }
}
