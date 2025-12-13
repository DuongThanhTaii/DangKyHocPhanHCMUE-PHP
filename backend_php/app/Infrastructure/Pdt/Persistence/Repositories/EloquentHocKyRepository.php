<?php

namespace App\Infrastructure\Pdt\Persistence\Repositories;

use App\Domain\Pdt\Repositories\HocKyRepositoryInterface;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\SinhVien\Persistence\Models\KyPhase;
use App\Infrastructure\SinhVien\Persistence\Models\DotDangKy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation của HocKyRepositoryInterface
 */
class EloquentHocKyRepository implements HocKyRepositoryInterface
{
    /**
     * Tìm học kỳ theo ID
     */
    public function findById(string $id): ?object
    {
        return HocKy::find($id);
    }

    /**
     * Đặt học kỳ hiện hành
     */
    public function setCurrentSemester(string $hocKyId): void
    {
        DB::transaction(function () use ($hocKyId) {
            HocKy::where('trang_thai_hien_tai', true)->update(['trang_thai_hien_tai' => false]);
            HocKy::where('id', $hocKyId)->update(['trang_thai_hien_tai' => true]);
        });
    }

    /**
     * Cập nhật thời gian học kỳ
     */
    public function updateDates(string $hocKyId, string $startDate, string $endDate): void
    {
        HocKy::where('id', $hocKyId)->update([
            'ngay_bat_dau' => $startDate,
            'ngay_ket_thuc' => $endDate,
            'updated_at' => now(),
        ]);
    }

    /**
     * Xóa tất cả phases của học kỳ
     */
    public function deletePhasesByHocKy(string $hocKyId): void
    {
        KyPhase::where('hoc_ky_id', $hocKyId)->delete();
    }

    /**
     * Xóa tất cả đợt đăng ký của học kỳ
     */
    public function deleteDotDangKyByHocKy(string $hocKyId): void
    {
        DotDangKy::where('hoc_ky_id', $hocKyId)->delete();
    }

    /**
     * Tạo phase mới
     */
    public function createPhase(array $data): object
    {
        return KyPhase::create($data);
    }

    /**
     * Tạo đợt đăng ký
     */
    public function createDotDangKy(array $data): object
    {
        return DotDangKy::create($data);
    }

    /**
     * Lấy phases theo học kỳ
     */
    public function getPhasesByHocKy(string $hocKyId): Collection
    {
        return KyPhase::where('hoc_ky_id', $hocKyId)
            ->orderBy('start_at', 'asc')
            ->get();
    }
}
