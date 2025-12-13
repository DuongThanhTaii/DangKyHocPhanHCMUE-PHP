<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\DeXuatHocPhanRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * UseCase: Duyệt đề xuất học phần
 */
class ApproveDeXuatHocPhanUseCase
{
    public function __construct(
        private DeXuatHocPhanRepositoryInterface $repository
    ) {
    }

    public function execute(string $deXuatId): array
    {
        if (empty($deXuatId)) {
            throw new \InvalidArgumentException('ID đề xuất không được rỗng');
        }

        // Find proposal pending PDT approval
        $deXuat = $this->repository->findPendingById($deXuatId);
        if (!$deXuat) {
            throw new \RuntimeException('Không tìm thấy đề xuất hoặc đề xuất chưa được TK duyệt');
        }

        DB::transaction(function () use ($deXuat) {
            // 1. Update status
            $this->repository->update($deXuat->id, [
                'trang_thai' => 'da_duyet_pdt',
                'cap_duyet_hien_tai' => 'pdt',
                'updated_at' => now(),
            ]);

            // 2. Create or update HocPhan
            $existingHocPhan = $this->repository->findHocPhan($deXuat->mon_hoc_id, $deXuat->hoc_ky_id);

            if ($existingHocPhan) {
                $this->repository->createOrUpdateHocPhan([
                    'id' => $existingHocPhan->id,
                    'so_lop' => ($existingHocPhan->so_lop ?? 0) + $deXuat->so_lop_du_kien,
                    'updated_at' => now(),
                ]);
            } else {
                $this->repository->createOrUpdateHocPhan([
                    'id' => Str::uuid()->toString(),
                    'mon_hoc_id' => $deXuat->mon_hoc_id,
                    'ten_hoc_phan' => $deXuat->monHoc?->ten_mon ?? 'Học phần mới',
                    'so_lop' => $deXuat->so_lop_du_kien,
                    'trang_thai_mo' => true,
                    'id_hoc_ky' => $deXuat->hoc_ky_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return [
            'isSuccess' => true,
            'data' => [
                'id' => $deXuat->id,
                'trangThai' => 'da_duyet_pdt',
            ],
            'message' => 'Duyệt đề xuất thành công'
        ];
    }
}
