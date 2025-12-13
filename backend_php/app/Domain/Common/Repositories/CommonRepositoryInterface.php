<?php

namespace App\Domain\Common\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Common operations
 */
interface CommonRepositoryInterface
{
    /**
     * Lấy học kỳ hiện hành
     */
    public function getCurrentHocKy(): ?object;

    /**
     * Lấy tất cả học kỳ grouped by niên khóa
     */
    public function getAllHocKyGroupedByNienKhoa(): Collection;

    /**
     * Lấy danh sách khoa
     */
    public function getAllKhoa(): Collection;

    /**
     * Lấy danh sách ngành, optional filter by khoa
     */
    public function getNganhByKhoa(?string $khoaId = null): Collection;

    /**
     * Lấy danh sách ngành chưa có chính sách trong học kỳ
     */
    public function getNganhWithoutPolicy(string $hocKyId, string $khoaId): Collection;
}
