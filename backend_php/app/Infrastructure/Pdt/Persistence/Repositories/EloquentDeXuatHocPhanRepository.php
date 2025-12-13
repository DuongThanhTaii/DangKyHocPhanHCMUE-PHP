<?php

namespace App\Infrastructure\Pdt\Persistence\Repositories;

use App\Domain\Pdt\Repositories\DeXuatHocPhanRepositoryInterface;
use App\Infrastructure\TLK\Persistence\Models\DeXuatHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\HocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\MonHoc;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation của DeXuatHocPhanRepositoryInterface
 */
class EloquentDeXuatHocPhanRepository implements DeXuatHocPhanRepositoryInterface
{
    /**
     * Lấy danh sách đề xuất đang chờ duyệt PDT
     */
    public function getPendingForPdt(): Collection
    {
        return DeXuatHocPhan::with(['monHoc', 'giangVienDeXuat.user', 'nguoiTao', 'khoa'])
            ->where('trang_thai', 'da_duyet_tk')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Tìm đề xuất theo ID
     */
    public function findById(string $id): ?object
    {
        return DeXuatHocPhan::find($id);
    }

    /**
     * Tìm đề xuất đang chờ duyệt PDT theo ID
     */
    public function findPendingById(string $id): ?object
    {
        return DeXuatHocPhan::with(['monHoc', 'hocKy'])
            ->where('trang_thai', 'da_duyet_tk')
            ->find($id);
    }

    /**
     * Tạo đề xuất mới
     */
    public function create(array $data): object
    {
        return DeXuatHocPhan::create($data);
    }

    /**
     * Cập nhật đề xuất
     */
    public function update(string $id, array $data): object
    {
        $deXuat = DeXuatHocPhan::findOrFail($id);
        foreach ($data as $key => $value) {
            $deXuat->{$key} = $value;
        }
        $deXuat->save();
        return $deXuat;
    }

    /**
     * Kiểm tra môn học tồn tại
     */
    public function monHocExists(string $monHocId): bool
    {
        return MonHoc::where('id', $monHocId)->exists();
    }

    /**
     * Lấy môn học theo ID
     */
    public function getMonHoc(string $monHocId): ?object
    {
        return MonHoc::find($monHocId);
    }

    /**
     * Kiểm tra học kỳ tồn tại
     */
    public function hocKyExists(string $hocKyId): bool
    {
        return HocKy::where('id', $hocKyId)->exists();
    }

    /**
     * Tìm học phần theo môn học và học kỳ
     */
    public function findHocPhan(string $monHocId, string $hocKyId): ?object
    {
        return HocPhan::where('mon_hoc_id', $monHocId)
            ->where('id_hoc_ky', $hocKyId)
            ->first();
    }

    /**
     * Tạo hoặc cập nhật học phần
     */
    public function createOrUpdateHocPhan(array $data): object
    {
        if (isset($data['id']) && HocPhan::where('id', $data['id'])->exists()) {
            $hocPhan = HocPhan::find($data['id']);
            foreach ($data as $key => $value) {
                $hocPhan->{$key} = $value;
            }
            $hocPhan->save();
            return $hocPhan;
        }

        return HocPhan::create($data);
    }
}
