<?php

namespace App\Infrastructure\SinhVien\Persistence\Repositories;

use App\Domain\SinhVien\Repositories\SinhVienPortalRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\TaiLieu;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation của SinhVienPortalRepositoryInterface
 */
class EloquentSinhVienPortalRepository implements SinhVienPortalRepositoryInterface
{
    public function findSinhVienByUserProfileId(string $userProfileId): ?object
    {
        return SinhVien::with(['user', 'khoa', 'nganh'])->find($userProfileId);
    }

    public function isStudentEnrolled(string $sinhVienId, string $lopHocPhanId): bool
    {
        return DangKyHocPhan::where('sinh_vien_id', $sinhVienId)
            ->where('lop_hoc_phan_id', $lopHocPhanId)
            ->whereIn('trang_thai', [
                'da_dang_ky',
                'da_duyet',
                'cho_thanh_toan',
                'da_thanh_toan',
                'completed'
            ])
            ->exists();
    }

    public function getDocumentsForClass(string $lopHocPhanId): Collection
    {
        return TaiLieu::with('uploadedBy')
            ->where('lop_hoc_phan_id', $lopHocPhanId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findDocument(string $docId, string $lopHocPhanId): ?object
    {
        return TaiLieu::where('id', $docId)
            ->where('lop_hoc_phan_id', $lopHocPhanId)
            ->first();
    }
}
