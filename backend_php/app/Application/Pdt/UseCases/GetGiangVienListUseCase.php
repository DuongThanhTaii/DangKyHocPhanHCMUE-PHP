<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\GiangVienRepositoryInterface;

/**
 * UseCase: Lấy danh sách giảng viên
 */
class GetGiangVienListUseCase
{
    public function __construct(
        private GiangVienRepositoryInterface $repository
    ) {
    }

    public function execute(int $page = 1, int $pageSize = 10000): array
    {
        $giangViens = $this->repository->getAll($page, $pageSize);

        $data = $giangViens->map(function ($gv) {
            return [
                'id' => $gv->id,
                'khoa_id' => $gv->khoa_id,
                'trinh_do' => $gv->trinh_do ?? '',
                'chuyen_mon' => $gv->chuyen_mon ?? '',
                'kinh_nghiem_giang_day' => $gv->kinh_nghiem_giang_day ?? 0,
                'users' => [
                    'id' => $gv->user?->id ?? '',
                    'ho_ten' => $gv->user?->ho_ten ?? '',
                    'ma_nhan_vien' => $gv->user?->ma_nhan_vien ?: ($gv->ma_giang_vien ?? ''),
                    'tai_khoan' => null,
                ],
                'khoa' => [
                    'id' => $gv->khoa?->id ?? '',
                    'ten_khoa' => $gv->khoa?->ten_khoa ?? '',
                ],
            ];
        });

        return [
            'isSuccess' => true,
            'data' => [
                'items' => $data,
                'total' => $data->count(),
            ],
            'message' => "Lấy thành công {$data->count()} giảng viên"
        ];
    }
}
