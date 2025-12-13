<?php

namespace App\Domain\GiangVien\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho GiangVien portal operations
 */
interface GVRepositoryInterface
{
    /**
     * Lấy danh sách lớp học phần của giảng viên
     */
    public function getLopHocPhanByGV(string $giangVienId, ?string $hocKyId = null): Collection;

    /**
     * Tìm lớp học phần của giảng viên
     */
    public function findLopHocPhanByGV(string $lopId, string $giangVienId): ?object;

    /**
     * Lấy danh sách sinh viên trong lớp
     */
    public function getStudentsInClass(string $lopId): Collection;

    /**
     * Lấy điểm sinh viên trong lớp
     */
    public function getGradesForClass(string $lopId): Collection;

    /**
     * Cập nhật điểm sinh viên
     */
    public function updateGrade(string $sinhVienId, string $lopId, array $gradeData): void;

    /**
     * Lấy danh sách tài liệu của lớp
     */
    public function getDocumentsForClass(string $lopId): Collection;

    /**
     * Tìm tài liệu
     */
    public function findDocument(string $lopId, string $docId): ?object;

    /**
     * Tạo tài liệu
     */
    public function createDocument(array $data): object;

    /**
     * Lấy lịch dạy tuần của giảng viên
     */
    public function getWeeklySchedule(string $giangVienId, string $hocKyId): Collection;
}
