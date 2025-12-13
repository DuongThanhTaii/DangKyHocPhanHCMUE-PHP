<?php

namespace App\Infrastructure\Pdt\Persistence\Repositories;

use App\Domain\Pdt\Repositories\DotDangKyRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\DotDangKy;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation của DotDangKyRepositoryInterface
 */
class EloquentDotDangKyRepository implements DotDangKyRepositoryInterface
{
    /**
     * Lấy tất cả đợt đăng ký (có thể filter theo học kỳ)
     */
    public function getAll(?string $hocKyId = null): Collection
    {
        $query = DotDangKy::with(['hocKy', 'khoa']);

        if ($hocKyId) {
            $query->where('hoc_ky_id', $hocKyId);
        }

        return $query->orderBy('thoi_gian_bat_dau', 'desc')->get();
    }

    /**
     * Lấy đợt đăng ký theo ID học kỳ
     */
    public function getByHocKyId(string $hocKyId): Collection
    {
        return DotDangKy::with(['hocKy', 'khoa'])
            ->where('hoc_ky_id', $hocKyId)
            ->orderBy('thoi_gian_bat_dau', 'asc')
            ->get();
    }

    /**
     * Tìm đợt đăng ký theo ID
     */
    public function findById(string $id): ?object
    {
        return DotDangKy::find($id);
    }

    /**
     * Cập nhật đợt đăng ký
     */
    public function update(string $id, array $data): object
    {
        $dotDangKy = DotDangKy::findOrFail($id);

        foreach ($data as $key => $value) {
            $dotDangKy->{$key} = $value;
        }

        $dotDangKy->updated_at = now();
        $dotDangKy->save();

        return $dotDangKy;
    }
}
