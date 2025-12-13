<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\MonHocRepositoryInterface;

/**
 * UseCase: Lấy danh sách môn học
 */
class GetMonHocListUseCase
{
    public function __construct(
        private MonHocRepositoryInterface $repository
    ) {
    }

    public function execute(int $page = 1, int $pageSize = 10000): array
    {
        $monHocs = $this->repository->getAll($page, $pageSize);

        $data = $monHocs->map(function ($m) {
            return [
                'id' => $m->id,
                'ma_mon' => $m->ma_mon ?? '',
                'ten_mon' => $m->ten_mon ?? '',
                'so_tin_chi' => $m->so_tin_chi ?? 0,
                'khoa_id' => $m->khoa_id,
                'loai_mon' => $m->loai_mon ?? null,
                'la_mon_chung' => $m->la_mon_chung ?? false,
                'thu_tu_hoc' => $m->thu_tu_hoc ?? null,
                'khoa' => $m->khoa ? [
                    'id' => $m->khoa->id,
                    'ten_khoa' => $m->khoa->ten_khoa ?? '',
                ] : null,
                'mon_hoc_nganh' => [],
            ];
        });

        return [
            'isSuccess' => true,
            'data' => [
                'items' => $data,
                'total' => $data->count(),
            ],
            'message' => "Lấy thành công {$data->count()} môn học"
        ];
    }
}
