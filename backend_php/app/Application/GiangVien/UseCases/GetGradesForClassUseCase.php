<?php

namespace App\Application\GiangVien\UseCases;

use App\Domain\GiangVien\Repositories\GVRepositoryInterface;
use App\Infrastructure\GiangVien\Persistence\Models\DiemSinhVien;

/**
 * UseCase: Lấy điểm sinh viên trong lớp
 */
class GetGradesForClassUseCase
{
    public function __construct(
        private GVRepositoryInterface $repository
    ) {
    }

    public function execute(string $lopId, string $giangVienId): array
    {
        // Verify class belongs to instructor
        $lhp = $this->repository->findLopHocPhanByGV($lopId, $giangVienId);
        if (!$lhp) {
            throw new \RuntimeException('Không tìm thấy lớp học phần hoặc không có quyền truy cập');
        }

        $dangKys = $this->repository->getGradesForClass($lopId);

        $data = $dangKys->map(function ($dk) use ($lopId) {
            $sv = $dk->sinhVien;
            $user = $sv?->user;

            // Get grade if exists
            $diem = DiemSinhVien::where('sinh_vien_id', $sv?->id)
                ->where('lop_hoc_phan_id', $lopId)
                ->first();

            return [
                'sinhVienId' => $sv?->id ?? '',
                'maSoSinhVien' => $sv?->ma_so_sinh_vien ?? '',
                'hoTen' => $user?->ho_ten ?? '',
                'diemChuyenCan' => $diem?->diem_chuyen_can ?? null,
                'diemGiuaKy' => $diem?->diem_giua_ky ?? null,
                'diemCuoiKy' => $diem?->diem_cuoi_ky ?? null,
                'diemTongKet' => $diem?->diem_tong_ket ?? null,
                'ghiChu' => $diem?->ghi_chu ?? '',
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy điểm thành công"
        ];
    }
}
