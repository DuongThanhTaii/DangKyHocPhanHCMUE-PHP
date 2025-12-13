<?php

namespace App\Infrastructure\Pdt\Persistence\Repositories;

use App\Domain\Pdt\Repositories\PhongHocRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\Phong;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation của PhongHocRepositoryInterface
 */
class EloquentPhongHocRepository implements PhongHocRepositoryInterface
{
    /**
     * Lấy danh sách phòng học trống (chưa gán cho khoa nào)
     */
    public function getAvailable(): Collection
    {
        return Phong::with('coSo')
            ->whereNull('khoa_id')
            ->orderBy('ma_phong', 'asc')
            ->get();
    }

    /**
     * Lấy danh sách phòng học theo khoa
     */
    public function getByKhoa(string $khoaId): Collection
    {
        return Phong::with('coSo')
            ->where('khoa_id', $khoaId)
            ->orderBy('ma_phong', 'asc')
            ->get();
    }

    /**
     * Gán phòng học cho khoa
     */
    public function assignToKhoa(array $phongIds, string $khoaId): int
    {
        return Phong::whereIn('id', $phongIds)->update(['khoa_id' => $khoaId]);
    }

    /**
     * Hủy gán phòng học khỏi khoa
     */
    public function unassignFromKhoa(array $phongIds): int
    {
        return Phong::whereIn('id', $phongIds)->update(['khoa_id' => null]);
    }
}
