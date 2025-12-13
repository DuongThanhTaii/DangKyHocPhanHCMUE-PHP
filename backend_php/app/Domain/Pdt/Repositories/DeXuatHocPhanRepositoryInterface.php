<?php

namespace App\Domain\Pdt\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Đề xuất học phần
 */
interface DeXuatHocPhanRepositoryInterface
{
    /**
     * Lấy danh sách đề xuất đang chờ duyệt PDT
     */
    public function getPendingForPdt(): Collection;

    /**
     * Tìm đề xuất theo ID
     */
    public function findById(string $id): ?object;

    /**
     * Tìm đề xuất đang chờ duyệt PDT theo ID
     */
    public function findPendingById(string $id): ?object;

    /**
     * Tạo đề xuất mới
     */
    public function create(array $data): object;

    /**
     * Cập nhật đề xuất
     */
    public function update(string $id, array $data): object;

    /**
     * Kiểm tra môn học tồn tại
     */
    public function monHocExists(string $monHocId): bool;

    /**
     * Lấy môn học theo ID
     */
    public function getMonHoc(string $monHocId): ?object;

    /**
     * Kiểm tra học kỳ tồn tại
     */
    public function hocKyExists(string $hocKyId): bool;

    /**
     * Tìm học phần theo môn học và học kỳ
     */
    public function findHocPhan(string $monHocId, string $hocKyId): ?object;

    /**
     * Tạo hoặc cập nhật học phần
     */
    public function createOrUpdateHocPhan(array $data): object;
}
