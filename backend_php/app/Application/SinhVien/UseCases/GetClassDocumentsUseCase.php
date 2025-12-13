<?php

namespace App\Application\SinhVien\UseCases;

use App\Domain\SinhVien\Repositories\SinhVienPortalRepositoryInterface;

/**
 * UseCase: Lấy tài liệu lớp học phần
 */
class GetClassDocumentsUseCase
{
    public function __construct(
        private SinhVienPortalRepositoryInterface $repository
    ) {
    }

    public function execute(string $sinhVienId, string $lopHocPhanId): array
    {
        // Check enrollment
        if (!$this->repository->isStudentEnrolled($sinhVienId, $lopHocPhanId)) {
            throw new \RuntimeException('Không có quyền truy cập lớp học phần này');
        }

        $documents = $this->repository->getDocumentsForClass($lopHocPhanId);

        $data = $documents->map(function ($doc) {
            return [
                'id' => $doc->id,
                'tenTaiLieu' => $doc->ten_tai_lieu,
                'fileType' => $doc->file_type,
                'fileUrl' => '',
                'uploadedAt' => $doc->created_at?->toISOString(),
                'uploadedBy' => $doc->uploadedBy?->ho_ten ?? '',
            ];
        });

        return [
            'isSuccess' => true,
            'data' => $data,
            'message' => "Lấy thành công {$data->count()} tài liệu"
        ];
    }
}
