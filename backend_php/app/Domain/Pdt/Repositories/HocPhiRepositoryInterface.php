<?php

namespace App\Domain\Pdt\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Học phí
 */
interface HocPhiRepositoryInterface
{
    /**
     * Lấy danh sách sinh viên đã đăng ký trong học kỳ
     */
    public function getStudentIdsWithRegistrations(string $hocKyId): Collection;

    /**
     * Lấy tổng số tín chỉ của sinh viên trong học kỳ
     */
    public function getTotalCredits(string $sinhVienId, string $hocKyId): float;

    /**
     * Tìm sinh viên theo ID
     */
    public function findSinhVien(string $sinhVienId): ?object;

    /**
     * Tìm chính sách tín chỉ phù hợp cho sinh viên
     */
    public function findPolicyForStudent(object $sinhVien, string $hocKyId): ?object;

    /**
     * Lưu hoặc cập nhật học phí
     */
    public function saveHocPhi(array $data): void;
}
