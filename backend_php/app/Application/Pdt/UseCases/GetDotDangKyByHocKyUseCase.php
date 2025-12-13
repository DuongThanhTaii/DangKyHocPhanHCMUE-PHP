<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\DotDangKyRepositoryInterface;

/**
 * UseCase: Lấy đợt đăng ký theo học kỳ
 */
class GetDotDangKyByHocKyUseCase
{
    public function __construct(
        private DotDangKyRepositoryInterface $repository
    ) {
    }

    /**
     * Execute use case
     *
     * @param string $hocKyId ID học kỳ
     * @return array Response data
     * @throws \InvalidArgumentException Khi thiếu hocKyId
     */
    public function execute(string $hocKyId): array
    {
        if (empty($hocKyId)) {
            throw new \InvalidArgumentException('Thiếu hocKyId');
        }

        $dotDangKys = $this->repository->getByHocKyId($hocKyId);

        $data = $dotDangKys->map(function ($d) {
            return [
                'id' => $d->id,
                'hocKyId' => $d->hoc_ky_id,
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
