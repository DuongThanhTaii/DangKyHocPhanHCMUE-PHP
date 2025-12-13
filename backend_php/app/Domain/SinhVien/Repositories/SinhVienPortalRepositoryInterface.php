<?php

namespace App\Domain\SinhVien\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho SinhVien profile và tài liệu operations
 */
interface SinhVienPortalRepositoryInterface
{
    /**
     * Lấy thông tin sinh viên theo userProfileId
     */
    public function findSinhVienByUserProfileId(string $userProfileId): ?object;

    /**
     * Kiểm tra sinh viên có đăng ký lớp không
     */
    public function isStudentEnrolled(string $sinhVienId, string $lopHocPhanId): bool;

    /**
     * Lấy danh sách tài liệu của lớp
     */
    public function getDocumentsForClass(string $lopHocPhanId): Collection;

    /**
     * Tìm tài liệu theo ID và lớp
     */
    public function findDocument(string $docId, string $lopHocPhanId): ?object;
}
