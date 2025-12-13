<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\DotDangKyRepositoryInterface;

/**
 * UseCase: Lấy danh sách đợt đăng ký
 */
class GetDotDangKyListUseCase
{
    public function __construct(
        private DotDangKyRepositoryInterface $repository
    ) {
    }

    /**
     * Execute use case
     *
     * @param string|null $hocKyId Filter by hoc ky
     * @return array Response data
     */
    public function execute(?string $hocKyId = null): array
    {
        $dotDangKys = $this->repository->getAll($hocKyId);

        $data = $dotDangKys->map(function ($d) {
            return [
                'id' => $d->id,
                'hocKyId' => $d->hoc_ky_id,
                'tenHocKy' => $d->hocKy?->ten_hoc_ky ?? '',
                'loaiDot' => $d->loai_dot,
                'gioiHanTinChi' => $d->gioi_han_tin_chi ?? 0,
                'thoiGianBatDau' => $d->thoi_gian_bat_dau?->toISOString(),
                'thoiGianKetThuc' => $d->thoi_gian_ket_thuc?->toISOString(),
                'isCheckToanTruong' => $d->is_check_toan_truong ?? false,
                'khoaId' => $d->khoa_id,
                'tenKhoa' => $d->khoa?->ten_khoa ?? '',
                'isActive' => $d->isActive(),
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} đợt đăng ký"
        ];
    }
}
