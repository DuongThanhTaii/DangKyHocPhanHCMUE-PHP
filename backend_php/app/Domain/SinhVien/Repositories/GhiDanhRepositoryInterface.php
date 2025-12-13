<?php

namespace App\Domain\SinhVien\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Ghi Danh (Subject Enrollment) operations
 */
interface GhiDanhRepositoryInterface
{
    /**
     * Lấy danh sách môn học có thể ghi danh
     */
    public function getAvailableSubjectsForEnrollment(string $hocKyId, string $khoaId): Collection;

    /**
     * Kiểm tra sinh viên đã ghi danh môn học chưa
     */
    public function hasEnrolled(string $sinhVienId, string $monHocId, string $hocKyId): bool;

    /**
     * Tạo ghi danh mới
     */
    public function createGhiDanh(array $data): object;

    /**
     * Lấy danh sách môn đã ghi danh của sinh viên
     */
    public function getEnrolledSubjects(string $sinhVienId, string $hocKyId): Collection;

    /**
     * Tìm ghi danh theo ID và sinh viên
     */
    public function findGhiDanh(string $ghiDanhId, string $sinhVienId): ?object;

    /**
     * Xóa ghi danh
     */
    public function deleteGhiDanh(object $ghiDanh): void;

    /**
     * Lấy học kỳ hiện tại
     */
    public function getCurrentHocKy(): ?object;

    /**
     * Lấy phase đăng ký hiện tại
     */
    public function getCurrentPhase(string $hocKyId): ?object;
}
