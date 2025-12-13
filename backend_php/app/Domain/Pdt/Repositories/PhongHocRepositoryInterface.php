<?php

namespace App\Domain\Pdt\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Phòng học
 */
interface PhongHocRepositoryInterface
{
    /**
     * Lấy danh sách phòng học trống (chưa gán cho khoa nào)
     */
    public function getAvailable(): Collection;

    /**
     * Lấy danh sách phòng học theo khoa
     */
    public function getByKhoa(string $khoaId): Collection;

    /**
     * Gán phòng học cho khoa
     */
    public function assignToKhoa(array $phongIds, string $khoaId): int;

    /**
     * Hủy gán phòng học khỏi khoa
     */
    public function unassignFromKhoa(array $phongIds): int;
}
