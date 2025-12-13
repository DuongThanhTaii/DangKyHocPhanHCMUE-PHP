<?php

namespace App\Domain\SinhVien\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho đăng ký học phần operations
 */
interface DangKyHocPhanRepositoryInterface
{
    // ===== Phase & Semester =====
    /**
     * Kiểm tra giai đoạn đăng ký hiện tại
     */
    public function getCurrentPhase(string $hocKyId): ?object;

    // ===== LopHocPhan =====
    /**
     * Lấy danh sách lớp học phần khả dụng
     */
    public function getAvailableClasses(string $hocKyId): Collection;

    /**
     * Tìm lớp học phần theo ID
     */
    public function findLopHocPhan(string $lopId): ?object;

    /**
     * Lấy lớp học phần theo môn học
     */
    public function getClassesByMonHoc(string $monHocId, string $hocKyId, array $excludeIds): Collection;

    // ===== DangKyHocPhan =====
    /**
     * Lấy IDs lớp đã đăng ký của sinh viên trong học kỳ
     */
    public function getRegisteredClassIds(string $sinhVienId, string $hocKyId): array;

    /**
     * Lấy danh sách đăng ký của sinh viên
     */
    public function getRegisteredClasses(string $sinhVienId, string $hocKyId): Collection;

    /**
     * Kiểm tra đã đăng ký môn học này chưa
     */
    public function hasRegisteredForSubject(string $sinhVienId, string $monHocId, string $hocKyId): bool;

    /**
     * Kiểm tra đã đăng ký lớp này chưa
     */
    public function hasRegisteredForClass(string $sinhVienId, string $lopId): bool;

    /**
     * Tìm đăng ký theo sinh viên và lớp
     */
    public function findRegistration(string $sinhVienId, string $lopId): ?object;

    /**
     * Tạo đăng ký học phần mới
     */
    public function createRegistration(string $sinhVienId, string $lopId): object;

    /**
     * Xóa đăng ký
     */
    public function deleteRegistration(object $dangKy): void;

    /**
     * Chuyển lớp
     */
    public function transferRegistration(object $dangKy, string $newLopId): void;

    // ===== Lịch sử đăng ký =====
    /**
     * Lấy lịch sử đăng ký
     */
    public function getRegistrationHistory(string $sinhVienId, string $hocKyId): Collection;

    /**
     * Ghi log đăng ký
     */
    public function logRegistrationAction(string $sinhVienId, string $hocKyId, string $dangKyId, string $action): void;

    // ===== TKB =====
    /**
     * Lấy thời khóa biểu tuần
     */
    public function getWeeklySchedule(string $sinhVienId, string $hocKyId): Collection;

    // ===== Tra cứu =====
    /**
     * Tra cứu học phần mở
     */
    public function searchOpenCourses(string $hocKyId): Collection;

    // ===== Học phí =====
    /**
     * Lấy thông tin học phí sinh viên
     */
    public function getTuitionInfo(string $sinhVienId, string $hocKyId): ?array;

    // ===== Tài liệu =====
    /**
     * Lấy tài liệu của các lớp đã đăng ký
     */
    public function getDocumentsForRegisteredClasses(string $sinhVienId, string $hocKyId): Collection;

    // ===== Tiện ích =====
    /**
     * Tăng số lượng sinh viên trong lớp
     */
    public function incrementClassCount(string $lopId): void;

    /**
     * Giảm số lượng sinh viên trong lớp
     */
    public function decrementClassCount(string $lopId): void;
}
