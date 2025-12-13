<?php

namespace App\Domain\TLK\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Trợ Lý Khoa operations
 */
interface TLKRepositoryInterface
{
    /**
     * Lấy danh sách môn học của khoa
     */
    public function getMonHocByKhoa(string $khoaId): Collection;

    /**
     * Lấy danh sách giảng viên của khoa
     */
    public function getGiangVienByKhoa(string $khoaId): Collection;

    /**
     * Lấy danh sách phòng học của khoa
     */
    public function getPhongHocByKhoa(string $khoaId): Collection;

    /**
     * Lấy danh sách phòng học trống của khoa
     */
    public function getAvailablePhongHocByKhoa(string $khoaId): Collection;

    /**
     * Lấy danh sách đề xuất của khoa
     */
    public function getDeXuatByKhoa(string $khoaId, ?string $hocKyId = null): Collection;

    /**
     * Kiểm tra môn học thuộc khoa
     */
    public function findMonHocByKhoa(string $monHocId, string $khoaId): ?object;

    /**
     * Kiểm tra đề xuất đã tồn tại
     */
    public function existsDeXuat(string $monHocId, string $hocKyId, string $khoaId): bool;

    /**
     * Tạo đề xuất học phần
     */
    public function createDeXuat(array $data): object;

    /**
     * Lấy học kỳ hiện tại
     */
    public function getCurrentHocKy(): ?object;

    /**
     * Lấy danh sách học phần của học kỳ và khoa
     */
    public function getHocPhanForSemester(string $hocKyId, string $khoaId): Collection;

    /**
     * Tìm học phần theo mã môn và học kỳ
     */
    public function findHocPhanByMaMon(string $maMon, string $hocKyId): ?object;

    /**
     * Tạo lớp học phần
     */
    public function createLopHocPhan(array $data): object;

    /**
     * Tạo lịch học định kỳ
     */
    public function createLichHocDinhKy(array $data): object;
}
