<?php

namespace App\Domain\Pdt\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Đợt đăng ký
 * 
 * Định nghĩa các method CRUD cho đợt đăng ký học phần
 */
interface DotDangKyRepositoryInterface
{
    /**
     * Lấy tất cả đợt đăng ký (có thể filter theo học kỳ)
     *
     * @param string|null $hocKyId ID học kỳ (optional filter)
     * @return Collection
     */
    public function getAll(?string $hocKyId = null): Collection;

    /**
     * Lấy đợt đăng ký theo ID học kỳ
     *
     * @param string $hocKyId ID học kỳ
     * @return Collection
     */
    public function getByHocKyId(string $hocKyId): Collection;

    /**
     * Tìm đợt đăng ký theo ID
     *
     * @param string $id
     * @return object|null
     */
    public function findById(string $id): ?object;

    /**
     * Cập nhật đợt đăng ký
     *
     * @param string $id
     * @param array $data
     * @return object Updated model
     */
    public function update(string $id, array $data): object;
}
