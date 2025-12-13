<?php

namespace App\Application\TLK\UseCases;

use App\Domain\TLK\Repositories\TLKRepositoryInterface;

/**
 * UseCase: Lấy danh sách giảng viên của khoa
 */
class GetGiangVienByKhoaUseCase
{
    public function __construct(
        private TLKRepositoryInterface $repository
    ) {
    }

    public function execute(string $khoaId): array
    {
        $giangViens = $this->repository->getGiangVienByKhoa($khoaId);

        $data = $giangViens->map(function ($gv) {
            $user = $gv->user;
            return [
                'id' => $gv->id,
                'hoTen' => $user?->ho_ten ?? '',
                'email' => $user?->email ?? '',
                'trinhDo' => $gv->trinh_do ?? '',
                'chuyenMon' => $gv->chuyen_mon ?? '',
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} giảng viên"
        ];
    }
}
