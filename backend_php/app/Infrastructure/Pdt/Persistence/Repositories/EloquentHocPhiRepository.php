<?php

namespace App\Infrastructure\Pdt\Persistence\Repositories;

use App\Domain\Pdt\Repositories\HocPhiRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\ChinhSachTinChi;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use App\Infrastructure\Payment\Persistence\Models\HocPhi;
use App\Infrastructure\Pdt\Persistence\Models\SinhVien;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Eloquent implementation của HocPhiRepositoryInterface
 */
class EloquentHocPhiRepository implements HocPhiRepositoryInterface
{
    /**
     * Lấy danh sách sinh viên đã đăng ký trong học kỳ
     */
    public function getStudentIdsWithRegistrations(string $hocKyId): Collection
    {
        return DangKyHocPhan::join('lop_hoc_phan', 'dang_ky_hoc_phan.lop_hoc_phan_id', '=', 'lop_hoc_phan.id')
            ->join('hoc_phan', 'lop_hoc_phan.hoc_phan_id', '=', 'hoc_phan.id')
            ->where('hoc_phan.id_hoc_ky', $hocKyId)
            ->whereIn('dang_ky_hoc_phan.trang_thai', ['da_dang_ky', 'dang_ky'])
            ->distinct()
            ->pluck('dang_ky_hoc_phan.sinh_vien_id');
    }

    /**
     * Lấy tổng số tín chỉ của sinh viên trong học kỳ
     */
    public function getTotalCredits(string $sinhVienId, string $hocKyId): float
    {
        return DangKyHocPhan::join('lop_hoc_phan', 'dang_ky_hoc_phan.lop_hoc_phan_id', '=', 'lop_hoc_phan.id')
            ->join('hoc_phan', 'lop_hoc_phan.hoc_phan_id', '=', 'hoc_phan.id')
            ->join('mon_hoc', 'hoc_phan.mon_hoc_id', '=', 'mon_hoc.id')
            ->where('dang_ky_hoc_phan.sinh_vien_id', $sinhVienId)
            ->where('hoc_phan.id_hoc_ky', $hocKyId)
            ->whereIn('dang_ky_hoc_phan.trang_thai', ['da_dang_ky', 'dang_ky'])
            ->sum('mon_hoc.so_tin_chi');
    }

    /**
     * Tìm sinh viên theo ID
     */
    public function findSinhVien(string $sinhVienId): ?object
    {
        return SinhVien::find($sinhVienId);
    }

    /**
     * Tìm chính sách tín chỉ phù hợp cho sinh viên
     * Priority: Nganh > Khoa > General
     */
    public function findPolicyForStudent(object $sinhVien, string $hocKyId): ?object
    {
        return ChinhSachTinChi::where('hoc_ky_id', $hocKyId)
            ->where(function ($q) use ($sinhVien) {
                $q->where('nganh_id', $sinhVien->nganh_id)
                    ->orWhere(function ($q2) use ($sinhVien) {
                        $q2->where('khoa_id', $sinhVien->khoa_id)
                            ->whereNull('nganh_id');
                    })
                    ->orWhere(function ($q3) {
                        $q3->whereNull('khoa_id')
                            ->whereNull('nganh_id');
                    });
            })
            ->orderByRaw('CASE WHEN nganh_id IS NOT NULL THEN 1 WHEN khoa_id IS NOT NULL THEN 2 ELSE 3 END')
            ->first();
    }

    /**
     * Lưu hoặc cập nhật học phí
     */
    public function saveHocPhi(array $data): void
    {
        HocPhi::updateOrCreate(
            [
                'sinh_vien_id' => $data['sinh_vien_id'],
                'hoc_ky_id' => $data['hoc_ky_id'],
            ],
            [
                'id' => Str::uuid()->toString(),
                'tong_hoc_phi' => $data['tong_hoc_phi'],
                'chinh_sach_id' => $data['chinh_sach_id'],
                'ngay_tinh_toan' => now(),
                'trang_thai_thanh_toan' => 'chua_thanh_toan',
            ]
        );
    }
}
