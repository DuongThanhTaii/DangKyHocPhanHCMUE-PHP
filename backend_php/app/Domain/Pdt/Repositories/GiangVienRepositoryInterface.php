<?php

namespace App\Domain\Pdt\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Giảng viên
 */
interface GiangVienRepositoryInterface
{
    /**
     * Lấy danh sách giảng viên có phân trang
     */
    public function getAll(int $page = 1, int $pageSize = 10000): Collection;

    /**
     * Tìm giảng viên theo ID
     */
    public function findById(string $id): ?object;

    /**
     * Tạo giảng viên mới (bao gồm TaiKhoan, UserProfile, GiangVien)
     */
    public function create(array $data): object;

    /**
     * Cập nhật giảng viên
     */
    public function update(string $id, array $data): ?object;

    /**
     * Xóa giảng viên
     */
    public function delete(string $id): bool;
}
