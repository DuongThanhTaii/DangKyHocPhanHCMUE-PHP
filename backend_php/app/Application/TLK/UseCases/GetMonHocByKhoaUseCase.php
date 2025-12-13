<?php

namespace App\Application\TLK\UseCases;

use App\Domain\TLK\Repositories\TLKRepositoryInterface;

/**
 * UseCase: Lấy danh sách môn học của khoa
 */
class GetMonHocByKhoaUseCase
{
    public function __construct(
        private TLKRepositoryInterface $repository
    ) {
    }

    public function execute(string $khoaId): array
    {
        $monHocs = $this->repository->getMonHocByKhoa($khoaId);

        $data = $monHocs->map(function ($mh) {
            return [
                'id' => $mh->id,
                'maMon' => $mh->ma_mon,
                'tenMon' => $mh->ten_mon,
                'soTinChi' => $mh->so_tin_chi ?? 0,
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} môn học"
        ];
    }
}
