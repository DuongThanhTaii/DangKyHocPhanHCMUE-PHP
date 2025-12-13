<?php

namespace App\Domain\TK\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Trưởng Khoa operations
 */
interface TKRepositoryInterface
{
    /**
     * Lấy danh sách đề xuất cần TK duyệt
     */
    public function getDeXuatPendingForTK(string $khoaId, ?string $hocKyId = null): Collection;

    /**
     * Tìm đề xuất theo ID và khoa
     */
    public function findDeXuatForTK(string $id, string $khoaId): ?object;

    /**
     * Duyệt đề xuất (chuyển sang PDT)
     */
    public function approveDeXuat(object $deXuat): void;

    /**
     * Từ chối đề xuất
     */
    public function rejectDeXuat(object $deXuat, string $lyDo): void;
}
