<?php

namespace App\Application\Pdt\UseCases;

use App\Application\Pdt\DTOs\CreateDeXuatHocPhanDTO;
use App\Domain\Pdt\Repositories\DeXuatHocPhanRepositoryInterface;
use Illuminate\Support\Str;

/**
 * UseCase: Tạo đề xuất học phần
 */
class CreateDeXuatHocPhanUseCase
{
    public function __construct(
        private DeXuatHocPhanRepositoryInterface $repository
    ) {
    }

    public function execute(CreateDeXuatHocPhanDTO $dto): array
    {
        // 1. Validate
        $dto->validate();

        // 2. Check môn học tồn tại
        $monHoc = $this->repository->getMonHoc($dto->monHocId);
        if (!$monHoc) {
            throw new \RuntimeException('Môn học không tồn tại');
        }

        // 3. Check học kỳ tồn tại
        if (!$this->repository->hocKyExists($dto->hocKyId)) {
            throw new \RuntimeException('Học kỳ không tồn tại');
        }

        // 4. Tạo đề xuất
        $deXuat = $this->repository->create([
            'id' => Str::uuid()->toString(),
            'khoa_id' => $monHoc->khoa_id,
            'nguoi_tao' => null,
            'hoc_ky_id' => $dto->hocKyId,
            'mon_hoc_id' => $dto->monHocId,
            'so_lop_du_kien' => $dto->soLopDuKien,
            'giang_vien_de_xuat' => $dto->giangVienDeXuat,
            'trang_thai' => 'da_duyet_tk',
            'cap_duyet_hien_tai' => 'pdt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'isSuccess' => true,
            'data' => [
                'id' => $deXuat->id,
                'monHocId' => $deXuat->mon_hoc_id,
                'hocKyId' => $deXuat->hoc_ky_id,
                'trangThai' => $deXuat->trang_thai,
            ],
            'message' => 'Tạo đề xuất thành công'
        ];
    }
}
