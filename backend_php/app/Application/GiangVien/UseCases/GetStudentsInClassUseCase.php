<?php

namespace App\Application\GiangVien\UseCases;

use App\Domain\GiangVien\Repositories\GVRepositoryInterface;

/**
 * UseCase: Lấy danh sách sinh viên trong lớp
 */
class GetStudentsInClassUseCase
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

        $dangKys = $this->repository->getStudentsInClass($lopId);

        $data = $dangKys->map(function ($dk) {
            $sv = $dk->sinhVien;
            $user = $sv?->user;

            return [
                'id' => $sv?->id ?? '',
                'maSoSinhVien' => $sv?->ma_so_sinh_vien ?? '',
                'hoTen' => $user?->ho_ten ?? '',
                'email' => $user?->email ?? '',
                'lop' => $sv?->lop ?? '',
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} sinh viên"
        ];
    }
}
