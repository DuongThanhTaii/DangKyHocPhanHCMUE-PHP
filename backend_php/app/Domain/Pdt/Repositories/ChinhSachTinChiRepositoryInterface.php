<?php

namespace App\Domain\Pdt\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Chính sách tín chỉ
 */
interface ChinhSachTinChiRepositoryInterface
{
    public function getAll(): Collection;
    public function findById(string $id): ?object;
    public function create(array $data): object;
    public function update(string $id, array $data): object;
    public function delete(string $id): bool;
    public function getHocKy(string $hocKyId): ?object;
}
