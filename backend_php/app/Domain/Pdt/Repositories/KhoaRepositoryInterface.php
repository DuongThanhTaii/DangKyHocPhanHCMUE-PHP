<?php

namespace App\Domain\Pdt\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Khoa
 */
interface KhoaRepositoryInterface
{
    /**
     * Lấy danh sách tất cả khoa
     */
    public function getAll(): Collection;
}
