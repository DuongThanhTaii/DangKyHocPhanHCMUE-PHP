<?php

namespace App\Infrastructure\SinhVien\Persistence\Repositories;

use App\Domain\SinhVien\Repositories\GhiDanhRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\GhiDanhHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\HocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\KyPhase;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Eloquent implementation của GhiDanhRepositoryInterface
 */
class EloquentGhiDanhRepository implements GhiDanhRepositoryInterface
{
    public function getAvailableSubjectsForEnrollment(string $hocKyId, string $khoaId): Collection
    {
        return HocPhan::with(['monHoc.khoa'])
            ->where('id_hoc_ky', $hocKyId)
            ->where('trang_thai_mo', true)
            ->whereHas('monHoc', function ($q) use ($khoaId) {
                $q->where(function ($inner) use ($khoaId) {
                    $inner->where('khoa_id', $khoaId)
                        ->orWhere('la_mon_chung', true);
                });
            })
            ->get();
    }

    public function hasEnrolled(string $sinhVienId, string $monHocId, string $hocKyId): bool
    {
        return GhiDanhHocPhan::where('sinh_vien_id', $sinhVienId)
            ->whereHas('hocPhan', function ($q) use ($monHocId, $hocKyId) {
                $q->where('mon_hoc_id', $monHocId)
                    ->where('id_hoc_ky', $hocKyId);
            })
            ->exists();
    }

    public function createGhiDanh(array $data): object
    {
        return GhiDanhHocPhan::create([
            'id' => Str::uuid()->toString(),
            'sinh_vien_id' => $data['sinh_vien_id'],
            'hoc_phan_id' => $data['hoc_phan_id'],
            'ngay_ghi_danh' => now(),
            'trang_thai' => 'da_ghi_danh',
        ]);
    }

    public function getEnrolledSubjects(string $sinhVienId, string $hocKyId): Collection
    {
        return GhiDanhHocPhan::with(['hocPhan.monHoc'])
            ->where('sinh_vien_id', $sinhVienId)
            ->whereHas('hocPhan', function ($q) use ($hocKyId) {
                $q->where('id_hoc_ky', $hocKyId);
            })
            ->get();
    }

    public function findGhiDanh(string $ghiDanhId, string $sinhVienId): ?object
    {
        return GhiDanhHocPhan::where('id', $ghiDanhId)
            ->where('sinh_vien_id', $sinhVienId)
            ->first();
    }

    public function deleteGhiDanh(object $ghiDanh): void
    {
        $ghiDanh->delete();
    }

    public function getCurrentHocKy(): ?object
    {
        return HocKy::with('nienKhoa')
            ->where('trang_thai_hien_tai', true)
            ->first();
    }

    public function getCurrentPhase(string $hocKyId): ?object
    {
        $now = now();
        return KyPhase::where('hoc_ky_id', $hocKyId)
            ->where('is_enabled', true)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->first();
    }
}
