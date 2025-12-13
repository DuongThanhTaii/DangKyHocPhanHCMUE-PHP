<?php

namespace App\Application\TLK\UseCases;

use App\Domain\TLK\Repositories\TLKRepositoryInterface;

/**
 * UseCase: Tạo đề xuất học phần
 */
class CreateDeXuatUseCase
{
    public function __construct(
        private TLKRepositoryInterface $repository
    ) {
    }

    public function execute(string $monHocId, string $khoaId, string $nguoiTaoId, ?string $giangVienId = null): array
    {
        if (!$monHocId) {
            throw new \InvalidArgumentException('maHocPhan (môn học ID) là bắt buộc');
        }

        // Get current semester
        $currentHocKy = $this->repository->getCurrentHocKy();
        if (!$currentHocKy) {
            throw new \RuntimeException('Không tìm thấy học kỳ hiện hành');
        }

        // Verify MonHoc exists and belongs to TLK's khoa
        $monHoc = $this->repository->findMonHocByKhoa($monHocId, $khoaId);
        if (!$monHoc) {
            throw new \RuntimeException('Không tìm thấy môn học hoặc môn học không thuộc khoa của bạn');
        }

        // Check if already exists
        if ($this->repository->existsDeXuat($monHocId, $currentHocKy->id, $khoaId)) {
            throw new \RuntimeException('Đề xuất cho môn học này trong học kỳ hiện tại đã tồn tại');
        }

        // Create the proposal
        $deXuat = $this->repository->createDeXuat([
            'mon_hoc_id' => $monHocId,
            'hoc_ky_id' => $currentHocKy->id,
            'khoa_id' => $khoaId,
            'nguoi_tao_id' => $nguoiTaoId,
            'giang_vien_de_xuat' => $giangVienId,
            'so_lop_du_kien' => 1,
        ]);

        return [
            'isSuccess' => true,
            'data' => [
                'id' => $deXuat->id,
                'monHocId' => $monHocId,
                'trangThai' => 'cho_duyet',
            ],
            'message' => "Đã tạo đề xuất cho môn: {$monHoc->ten_mon}"
        ];
    }
}
