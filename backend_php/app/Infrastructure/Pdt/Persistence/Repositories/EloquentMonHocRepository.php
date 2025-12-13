<?php

namespace App\Infrastructure\Pdt\Persistence\Repositories;

use App\Domain\Pdt\Repositories\MonHocRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\MonHoc;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Eloquent implementation của MonHocRepositoryInterface
 */
class EloquentMonHocRepository implements MonHocRepositoryInterface
{
    /**
     * Lấy danh sách môn học có phân trang
     */
    public function getAll(int $page = 1, int $pageSize = 10000): Collection
    {
        return MonHoc::with('khoa')
            ->orderBy('ma_mon', 'asc')
            ->skip(($page - 1) * $pageSize)
            ->take($pageSize)
            ->get();
    }

    /**
     * Tìm môn học theo ID
     */
    public function findById(string $id): ?object
    {
        return MonHoc::find($id);
    }

    /**
     * Tạo môn học mới
     */
    public function create(array $data): object
    {
        return MonHoc::create([
            'id' => Str::uuid()->toString(),
            'ma_mon' => $data['ma_mon'],
            'ten_mon' => $data['ten_mon'],
            'so_tin_chi' => $data['so_tin_chi'] ?? 0,
            'khoa_id' => $data['khoa_id'] ?? null,
            'loai_mon' => $data['loai_mon'] ?? null,
            'la_mon_chung' => $data['la_mon_chung'] ?? false,
            'thu_tu_hoc' => $data['thu_tu_hoc'] ?? 1,
        ]);
    }

    /**
     * Cập nhật môn học
     */
    public function update(string $id, array $data): ?object
    {
        $monHoc = MonHoc::find($id);
        if (!$monHoc) {
            return null;
        }

        if (isset($data['ma_mon'])) {
            $monHoc->ma_mon = $data['ma_mon'];
        }
        if (isset($data['ten_mon'])) {
            $monHoc->ten_mon = $data['ten_mon'];
        }
        if (isset($data['so_tin_chi'])) {
            $monHoc->so_tin_chi = $data['so_tin_chi'];
        }
        if (isset($data['khoa_id'])) {
            $monHoc->khoa_id = $data['khoa_id'];
        }

        $monHoc->save();
        return $monHoc;
    }

    /**
     * Xóa môn học
     */
    public function delete(string $id): bool
    {
        $monHoc = MonHoc::find($id);
        if (!$monHoc) {
            return false;
        }
        $monHoc->delete();
        return true;
    }
}
