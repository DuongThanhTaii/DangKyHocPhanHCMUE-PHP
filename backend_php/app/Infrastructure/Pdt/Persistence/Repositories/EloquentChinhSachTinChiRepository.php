<?php

namespace App\Infrastructure\Pdt\Persistence\Repositories;

use App\Domain\Pdt\Repositories\ChinhSachTinChiRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\ChinhSachTinChi;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Illuminate\Support\Collection;

/**
 * Eloquent implementation của ChinhSachTinChiRepositoryInterface
 */
class EloquentChinhSachTinChiRepository implements ChinhSachTinChiRepositoryInterface
{
    public function getAll(): Collection
    {
        return ChinhSachTinChi::with(['hocKy', 'khoa', 'nganh'])
            ->orderBy('ngay_hieu_luc', 'desc')
            ->get();
    }

    public function findById(string $id): ?object
    {
        return ChinhSachTinChi::with(['hocKy', 'khoa', 'nganh'])->find($id);
    }

    public function create(array $data): object
    {
        return ChinhSachTinChi::create($data);
    }

    public function update(string $id, array $data): object
    {
        $policy = ChinhSachTinChi::findOrFail($id);
        foreach ($data as $key => $value) {
            $policy->{$key} = $value;
        }
        $policy->save();
        return $policy;
    }

    public function delete(string $id): bool
    {
        $policy = ChinhSachTinChi::find($id);
        return $policy ? $policy->delete() : false;
    }

    public function getHocKy(string $hocKyId): ?object
    {
        return HocKy::find($hocKyId);
    }
}
