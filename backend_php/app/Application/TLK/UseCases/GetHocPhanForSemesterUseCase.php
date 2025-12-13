<?php

namespace App\Application\TLK\UseCases;

use App\Domain\TLK\Repositories\TLKRepositoryInterface;
use App\Infrastructure\TLK\Persistence\Models\DeXuatHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\GhiDanhHocPhan;

/**
 * UseCase: Lấy danh sách học phần trong học kỳ
 */
class GetHocPhanForSemesterUseCase
{
    public function __construct(
        private TLKRepositoryInterface $repository
    ) {
    }

    public function execute(string $hocKyId, string $khoaId): array
    {
        $hocPhans = $this->repository->getHocPhanForSemester($hocKyId, $khoaId);

        $data = $hocPhans->map(function ($hp) use ($hocKyId) {
            $monHoc = $hp->monHoc;

            // Count enrolled students
            $soSinhVienGhiDanh = GhiDanhHocPhan::where('hoc_phan_id', $hp->id)->count();

            // Get giangVien from DeXuatHocPhan
            $tenGiangVien = 'Chưa có giảng viên';
            $giangVienId = '';

            if ($monHoc) {
                $deXuat = DeXuatHocPhan::with('giangVienDeXuat.user')
                    ->where('mon_hoc_id', $monHoc->id)
                    ->where('hoc_ky_id', $hocKyId)
                    ->whereIn('trang_thai', ['cho_duyet', 'da_duyet_tk', 'da_duyet_pdt'])
                    ->first();

                if ($deXuat && $deXuat->giangVienDeXuat && $deXuat->giangVienDeXuat->user) {
                    $tenGiangVien = $deXuat->giangVienDeXuat->user->ho_ten;
                    $giangVienId = $deXuat->giang_vien_de_xuat;
                }
            }

            return [
                'id' => $hp->id,
                'hocPhanId' => $hp->id,
                'maHocPhan' => $monHoc?->ma_mon ?? '',
                'tenHocPhan' => $hp->ten_hoc_phan ?? $monHoc?->ten_mon ?? '',
                'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                'soSinhVienGhiDanh' => $soSinhVienGhiDanh,
                'tenGiangVien' => $tenGiangVien,
                'giangVienId' => $giangVienId,
                'trangThaiMo' => $hp->trang_thai_mo ?? false,
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} học phần"
        ];
    }
}
