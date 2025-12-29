<?php

namespace App\Infrastructure\GiangVien\Persistence\Repositories;

use App\Domain\GiangVien\Repositories\GVRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\TaiLieu;
use App\Infrastructure\GiangVien\Persistence\Models\DiemSinhVien;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Eloquent implementation của GVRepositoryInterface
 */
class EloquentGVRepository implements GVRepositoryInterface
{
    public function getLopHocPhanByGV(string $giangVienId, ?string $hocKyId = null): Collection
    {
        $query = LopHocPhan::with(['hocPhan.monHoc'])
            ->where('giang_vien_id', $giangVienId);

        if ($hocKyId) {
            $query->whereHas('hocPhan', function ($q) use ($hocKyId) {
                $q->where('id_hoc_ky', $hocKyId);
            });
        }

        return $query->get();
    }

    public function findLopHocPhanByGV(string $lopId, string $giangVienId): ?object
    {
        return LopHocPhan::with(['hocPhan.monHoc.khoa', 'lichHocDinhKys.phong'])
            ->where('giang_vien_id', $giangVienId)
            ->find($lopId);
    }

    public function getStudentsInClass(string $lopId): Collection
    {
        return DangKyHocPhan::with(['sinhVien.user'])
            ->where('lop_hoc_phan_id', $lopId)
            ->where('trang_thai', 'da_dang_ky')
            ->get();
    }

    public function getGradesForClass(string $lopId): Collection
    {
        return DangKyHocPhan::with(['sinhVien.user'])
            ->where('lop_hoc_phan_id', $lopId)
            ->where('trang_thai', 'da_dang_ky')
            ->get();
    }

    public function updateGrade(string $sinhVienId, string $lopId, array $gradeData): void
    {
        DiemSinhVien::updateOrCreate(
            [
                'sinh_vien_id' => $sinhVienId,
                'lop_hoc_phan_id' => $lopId,
            ],
            $gradeData
        );
    }

    public function getDocumentsForClass(string $lopId): Collection
    {
        return TaiLieu::where('lop_hoc_phan_id', $lopId)->get();
    }

    public function findDocument(string $lopId, string $docId): ?object
    {
        return TaiLieu::where('lop_hoc_phan_id', $lopId)->find($docId);
    }

    public function createDocument(array $data): object
    {
        return TaiLieu::create([
            'id' => Str::uuid()->toString(),
            'lop_hoc_phan_id' => $data['lop_hoc_phan_id'],
            'uploaded_by' => $data['uploaded_by'],
            'ten_tai_lieu' => $data['ten_tai_lieu'],
            'file_type' => $data['file_type'] ?? null,
            'file_path' => $data['file_path'] ?? '',
        ]);
    }

    public function getWeeklySchedule(string $giangVienId, string $hocKyId): Collection
    {
        return LopHocPhan::with(['lichHocDinhKys.phong', 'hocPhan.monHoc'])
            ->where('giang_vien_id', $giangVienId)
            ->whereHas('hocPhan', function ($q) use ($hocKyId) {
                $q->where('id_hoc_ky', $hocKyId);
            })
            ->get();
    }
}
