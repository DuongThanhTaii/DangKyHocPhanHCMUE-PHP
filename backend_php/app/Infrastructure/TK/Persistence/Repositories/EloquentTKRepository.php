<?php

namespace App\Infrastructure\TK\Persistence\Repositories;

use App\Domain\TK\Repositories\TKRepositoryInterface;
use App\Infrastructure\TLK\Persistence\Models\DeXuatHocPhan;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation của TKRepositoryInterface
 */
class EloquentTKRepository implements TKRepositoryInterface
{
    /**
     * Lấy danh sách đề xuất cần TK duyệt
     */
    public function getDeXuatPendingForTK(string $khoaId, ?string $hocKyId = null): Collection
    {
        $query = DeXuatHocPhan::with(['monHoc', 'giangVienDeXuat.user', 'nguoiTao'])
            ->where('khoa_id', $khoaId)
            ->where('cap_duyet_hien_tai', 'truong_khoa');

        if ($hocKyId) {
            $query->where('hoc_ky_id', $hocKyId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Tìm đề xuất theo ID và khoa mà TK có quyền duyệt
     */
    public function findDeXuatForTK(string $id, string $khoaId): ?object
    {
        return DeXuatHocPhan::where('khoa_id', $khoaId)
            ->where('cap_duyet_hien_tai', 'truong_khoa')
            ->find($id);
    }

    /**
     * Duyệt đề xuất (chuyển sang PDT)
     */
    public function approveDeXuat(object $deXuat): void
    {
        $deXuat->trang_thai = 'da_duyet_tk';
        $deXuat->cap_duyet_hien_tai = 'pdt';
        $deXuat->updated_at = now();
        $deXuat->save();
    }

    /**
     * Từ chối đề xuất
     */
    public function rejectDeXuat(object $deXuat, string $lyDo): void
    {
        $deXuat->trang_thai = 'tu_choi';
        $deXuat->cap_duyet_hien_tai = 'truong_khoa';
        $deXuat->ghi_chu = $lyDo ? "Lý do từ chối: {$lyDo}" : 'Bị từ chối bởi Trưởng Khoa';
        $deXuat->updated_at = now();
        $deXuat->save();
    }
}
