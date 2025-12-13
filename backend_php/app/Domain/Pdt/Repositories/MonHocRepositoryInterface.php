<?php

namespace App\Domain\Pdt\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Môn học
 */
interface MonHocRepositoryInterface
{
    /**
     * Lấy danh sách môn học có phân trang
     */
    public function getAll(int $page = 1, int $pageSize = 10000): Collection;

    /**
     * Tìm môn học theo ID
     */
    public function findById(string $id): ?object;

    /**
     * Tạo môn học mới
     */
    public function create(array $data): object;

    /**
     * Cập nhật môn học
     */
    public function update(string $id, array $data): ?object;

    /**
     * Xóa môn học
     */
    public function delete(string $id): bool;
}
