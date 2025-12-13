<?php

namespace App\Infrastructure\Pdt\Persistence\Repositories;

use App\Domain\Pdt\Repositories\KhoaRepositoryInterface;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation của KhoaRepositoryInterface
 */
class EloquentKhoaRepository implements KhoaRepositoryInterface
{
    /**
     * Lấy danh sách tất cả khoa
     */
    public function getAll(): Collection
    {
        return Khoa::orderBy('ten_khoa', 'asc')->get();
    }
}
