<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\SinhVienPortalRepositoryInterface;

/**
 * UseCase: Lấy profile sinh viên
 */
class GetStudentProfileUseCase
{
    public function __construct(
        private SinhVienPortalRepositoryInterface $repository
    ) {
    }

    public function execute(string $userProfileId): array
    {
        $sinhVien = $this->repository->findSinhVienByUserProfileId($userProfileId);

        if (!$sinhVien) {
            throw new \RuntimeException('Không tìm thấy thông tin sinh viên');
        }

        $data = [
            'id' => $sinhVien->id,
            'maSoSinhVien' => $sinhVien->ma_so_sinh_vien,
            'hoTen' => $sinhVien->user?->ho_ten,
            'email' => $sinhVien->user?->email,
            'khoaId' => $sinhVien->khoa_id,
            'tenKhoa' => $sinhVien->khoa?->ten_khoa,
            'nganhId' => $sinhVien->nganh_id,
            'tenNganh' => $sinhVien->nganh?->ten_nganh,
            'lop' => $sinhVien->lop,
            'khoaHoc' => $sinhVien->khoa_hoc,
            'ngayNhapHoc' => $sinhVien->ngay_nhap_hoc?->toDateString(),
        ];

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => 'Lấy thông tin sinh viên thành công'
        ];
    }
}
