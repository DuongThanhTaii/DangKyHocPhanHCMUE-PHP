<?php

namespace App\Infrastructure\SinhVien\Persistence\Repositories;

use App\Domain\SinhVien\Repositories\DangKyHocPhanRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\KyPhase;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyTkb;
use App\Infrastructure\SinhVien\Persistence\Models\LichSuDangKy;
use App\Infrastructure\SinhVien\Persistence\Models\ChiTietLichSuDangKy;
use App\Infrastructure\SinhVien\Persistence\Models\HocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\TaiLieu;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Eloquent implementation của DangKyHocPhanRepositoryInterface
 */
class EloquentDangKyHocPhanRepository implements DangKyHocPhanRepositoryInterface
{
    public function getCurrentPhase(string $hocKyId): ?object
    {
        $now = now();
        return KyPhase::where('hoc_ky_id', $hocKyId)
            ->where('is_enabled', true)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();
    }

    public function getAvailableClasses(string $hocKyId): Collection
    {
        return LopHocPhan::with(['hocPhan.monHoc.khoa', 'lichHocDinhKys.phong', 'giangVien'])
            ->whereHas('hocPhan', function ($q) use ($hocKyId) {
                $q->where('id_hoc_ky', $hocKyId);
            })
            ->get();
    }

    public function findLopHocPhan(string $lopId): ?object
    {
        return LopHocPhan::with(['hocPhan.monHoc', 'lichHocDinhKys'])->find($lopId);
    }

    public function getClassesByMonHoc(string $monHocId, string $hocKyId, array $excludeIds): Collection
    {
        return LopHocPhan::with(['lichHocDinhKys.phong'])
            ->whereHas('hocPhan', function ($q) use ($monHocId, $hocKyId) {
                $q->where('mon_hoc_id', $monHocId)
                    ->where('id_hoc_ky', $hocKyId);
            })
            ->whereNotIn('id', $excludeIds)
            ->get();
    }

    public function getRegisteredClassIds(string $sinhVienId, string $hocKyId): array
    {
        return DangKyHocPhan::where('sinh_vien_id', $sinhVienId)
            ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                $q->where('id_hoc_ky', $hocKyId);
            })
            ->pluck('lop_hoc_phan_id')
            ->toArray();
    }

    public function getRegisteredClasses(string $sinhVienId, string $hocKyId): Collection
    {
        return DangKyHocPhan::with(['lopHocPhan.hocPhan.monHoc.khoa', 'lopHocPhan.lichHocDinhKys.phong'])
            ->where('sinh_vien_id', $sinhVienId)
            ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                $q->where('id_hoc_ky', $hocKyId);
            })
            ->get();
    }

    public function hasRegisteredForSubject(string $sinhVienId, string $monHocId, string $hocKyId): bool
    {
        return DangKyHocPhan::where('sinh_vien_id', $sinhVienId)
            ->whereHas('lopHocPhan.hocPhan', function ($q) use ($monHocId, $hocKyId) {
                $q->where('mon_hoc_id', $monHocId)
                    ->where('id_hoc_ky', $hocKyId);
            })
            ->exists();
    }

    public function hasRegisteredForClass(string $sinhVienId, string $lopId): bool
    {
        return DangKyHocPhan::where('sinh_vien_id', $sinhVienId)
            ->where('lop_hoc_phan_id', $lopId)
            ->exists();
    }

    public function findRegistration(string $sinhVienId, string $lopId): ?object
    {
        return DangKyHocPhan::where('sinh_vien_id', $sinhVienId)
            ->where('lop_hoc_phan_id', $lopId)
            ->first();
    }

    public function createRegistration(string $sinhVienId, string $lopId): object
    {
        $dangKy = DangKyHocPhan::create([
            'id' => Str::uuid()->toString(),
            'sinh_vien_id' => $sinhVienId,
            'lop_hoc_phan_id' => $lopId,
            'ngay_dang_ky' => now(),
            'trang_thai' => 'da_dang_ky',
        ]);

        // Create DangKyTkb
        DangKyTkb::create([
            'id' => Str::uuid()->toString(),
            'dang_ky_id' => $dangKy->id,
            'sinh_vien_id' => $sinhVienId,
            'lop_hoc_phan_id' => $lopId,
        ]);

        return $dangKy;
    }

    public function deleteRegistration(object $dangKy): void
    {
        DangKyTkb::where('dang_ky_id', $dangKy->id)->delete();
        $dangKy->delete();
    }

    public function transferRegistration(object $dangKy, string $newLopId): void
    {
        $dangKy->lop_hoc_phan_id = $newLopId;
        $dangKy->ngay_dang_ky = now();
        $dangKy->save();

        DangKyTkb::where('dang_ky_id', $dangKy->id)
            ->update(['lop_hoc_phan_id' => $newLopId]);
    }

    public function getRegistrationHistory(string $sinhVienId, string $hocKyId): Collection
    {
        $lichSu = LichSuDangKy::where('sinh_vien_id', $sinhVienId)
            ->where('hoc_ky_id', $hocKyId)
            ->first();

        if (!$lichSu) {
            return collect();
        }

        return ChiTietLichSuDangKy::with(['dangKyHocPhan.lopHocPhan.hocPhan.monHoc', 'lopHocPhan.hocPhan.monHoc'])
            ->where('lich_su_dang_ky_id', $lichSu->id)
            ->orderBy('thoi_gian', 'desc')
            ->get();
    }

    public function logRegistrationAction(string $sinhVienId, string $hocKyId, string $dangKyId, string $lopHocPhanId, string $action): void
    {
        $lichSu = LichSuDangKy::firstOrCreate(
            [
                'sinh_vien_id' => $sinhVienId,
                'hoc_ky_id' => $hocKyId,
            ],
            [
                'id' => Str::uuid()->toString(),
                'ngay_tao' => now(),
            ]
        );

        ChiTietLichSuDangKy::create([
            'id' => Str::uuid()->toString(),
            'lich_su_dang_ky_id' => $lichSu->id,
            'dang_ky_hoc_phan_id' => $dangKyId,
            'lop_hoc_phan_id' => $lopHocPhanId,
            'hanh_dong' => $action,
            'thoi_gian' => now(),
        ]);
    }

    public function getWeeklySchedule(string $sinhVienId, string $hocKyId): Collection
    {
        return DangKyHocPhan::with(['lopHocPhan.lichHocDinhKys.phong', 'lopHocPhan.hocPhan.monHoc', 'lopHocPhan.giangVien'])
            ->where('sinh_vien_id', $sinhVienId)
            ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                $q->where('id_hoc_ky', $hocKyId);
            })
            ->get();
    }

    public function searchOpenCourses(string $hocKyId): Collection
    {
        return HocPhan::with(['monHoc.khoa', 'lopHocPhans.lichHocDinhKys.phong', 'lopHocPhans.giangVien'])
            ->where('id_hoc_ky', $hocKyId)
            ->where('trang_thai_mo', true)
            ->get();
    }

    public function getTuitionInfo(string $sinhVienId, string $hocKyId): ?array
    {
        $dangKys = DangKyHocPhan::with(['lopHocPhan.hocPhan.monHoc'])
            ->where('sinh_vien_id', $sinhVienId)
            ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                $q->where('id_hoc_ky', $hocKyId);
            })
            ->get();

        if ($dangKys->isEmpty()) {
            return null;
        }

        $totalCredits = 0;
        $chiTiet = [];
        $pricePerCredit = 800000;

        foreach ($dangKys as $dk) {
            $lhp = $dk->lopHocPhan;
            $monHoc = $lhp?->hocPhan?->monHoc;
            $credits = $monHoc?->so_tin_chi ?? 0;
            $totalCredits += $credits;
            $thanhTien = $credits * $pricePerCredit;

            $chiTiet[] = [
                'tenMon' => $monHoc?->ten_mon ?? '',
                'maMon' => $monHoc?->ma_mon ?? '',
                'maLop' => $lhp?->ma_lop ?? '',
                'soTinChi' => $credits,
                'donGia' => $pricePerCredit,
                'thanhTien' => $thanhTien,
            ];
        }

        // Check actual payment status from hoc_phi table
        $hocPhi = \App\Infrastructure\Payment\Persistence\Models\HocPhi::where('sinh_vien_id', $sinhVienId)
            ->where('hoc_ky_id', $hocKyId)
            ->first();
        
        $trangThaiThanhToan = $hocPhi?->trang_thai_thanh_toan ?? 'chua_thanh_toan';

        return [
            'soTinChiDangKy' => $totalCredits,
            'donGiaTinChi' => $pricePerCredit,
            'tongHocPhi' => $totalCredits * $pricePerCredit,
            'chiTiet' => $chiTiet,
            'trangThaiThanhToan' => $trangThaiThanhToan,
        ];
    }

    public function getDocumentsForRegisteredClasses(string $sinhVienId, string $hocKyId): Collection
    {
        $dangKys = DangKyHocPhan::with(['lopHocPhan.hocPhan.monHoc', 'lopHocPhan.giangVien'])
            ->where('sinh_vien_id', $sinhVienId)
            ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                $q->where('id_hoc_ky', $hocKyId);
            })
            ->get();

        $result = collect();

        foreach ($dangKys as $dk) {
            $lhp = $dk->lopHocPhan;
            $monHoc = $lhp?->hocPhan?->monHoc;

            $taiLieus = TaiLieu::where('lop_hoc_phan_id', $lhp->id)->get();

            $result->push([
                'lopHocPhanId' => $lhp->id,
                'maLop' => $lhp->ma_lop,
                'maMon' => $monHoc?->ma_mon ?? '',
                'tenMon' => $monHoc?->ten_mon ?? '',
                'soTinChi' => $monHoc?->so_tin_chi ?? 0,
                'giangVien' => $lhp->giangVien?->ho_ten ?? '',
                'taiLieu' => $taiLieus->map(function ($tl) {
                    return [
                        'id' => $tl->id,
                        'tenTaiLieu' => $tl->ten_tai_lieu,
                        'fileType' => $tl->file_type,
                    ];
                }),
            ]);
        }

        return $result;
    }

    public function incrementClassCount(string $lopId): void
    {
        LopHocPhan::where('id', $lopId)->increment('so_luong_hien_tai');
    }

    public function decrementClassCount(string $lopId): void
    {
        LopHocPhan::where('id', $lopId)->decrement('so_luong_hien_tai');
    }
}
