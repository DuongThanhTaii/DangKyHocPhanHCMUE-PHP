<?php

namespace App\Infrastructure\Common\Persistence\Repositories;

use App\Domain\Common\Repositories\CommonRepositoryInterface;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use App\Infrastructure\Common\Persistence\Models\NganhHoc;
use App\Infrastructure\SinhVien\Persistence\Models\ChinhSachTinChi;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation của CommonRepositoryInterface
 */
class EloquentCommonRepository implements CommonRepositoryInterface
{
    /**
     * Lấy học kỳ hiện hành
     */
    public function getCurrentHocKy(): ?object
    {
        return HocKy::with('nienKhoa')
            ->where('trang_thai_hien_tai', true)
            ->first();
    }

    /**
     * Lấy tất cả học kỳ grouped by niên khóa
     */
    public function getAllHocKyGroupedByNienKhoa(): Collection
    {
        return HocKy::with('nienKhoa')
            ->orderBy('ngay_bat_dau', 'desc')
            ->get();
    }

    /**
     * Lấy danh sách khoa
     */
    public function getAllKhoa(): Collection
    {
        return Khoa::orderBy('ten_khoa')->get();
    }

    /**
     * Lấy danh sách ngành, optional filter by khoa
     */
    public function getNganhByKhoa(?string $khoaId = null): Collection
    {
        $query = NganhHoc::with('khoa')->orderBy('ten_nganh');

        if ($khoaId) {
            $query->where('khoa_id', $khoaId);
        }

        return $query->get();
    }

    /**
     * Lấy danh sách ngành chưa có chính sách trong học kỳ
     */
    public function getNganhWithoutPolicy(string $hocKyId, string $khoaId): Collection
    {
        $existingPolicyNganhIds = ChinhSachTinChi::where('hoc_ky_id', $hocKyId)
            ->pluck('nganh_id')
            ->toArray();

        return NganhHoc::where('khoa_id', $khoaId)
            ->whereNotIn('id', $existingPolicyNganhIds)
            ->orderBy('ten_nganh')
            ->get();
    }
}
