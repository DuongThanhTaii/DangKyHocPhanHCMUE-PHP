<?php

namespace App\Domain\Pdt\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho module Báo cáo - Thống kê
 * 
 * Định nghĩa các method truy xuất dữ liệu thống kê
 * mà không phụ thuộc vào implementation cụ thể (Eloquent, DB, etc.)
 */
interface BaoCaoRepositoryInterface
{
    /**
     * Lấy thống kê tổng quan theo học kỳ
     *
     * @param string $hocKyId ID học kỳ
     * @param string|null $khoaId ID khoa (optional)
     * @param string|null $nganhId ID ngành (optional)
     * @return array Chứa: svUnique, soDangKy, soLopHocPhan, taiChinh
     */
    public function getOverviewStats(string $hocKyId, ?string $khoaId = null, ?string $nganhId = null): array;

    /**
     * Thống kê đăng ký theo từng khoa
     *
     * @param string $hocKyId ID học kỳ
     * @return Collection Danh sách [ten_khoa, so_dang_ky]
     */
    public function getDangKyTheoKhoa(string $hocKyId): Collection;

    /**
     * Thống kê đăng ký theo ngành
     *
     * @param string $hocKyId ID học kỳ
     * @param string|null $khoaId ID khoa (optional filter)
     * @return Collection Danh sách [ten_nganh, so_dang_ky]
     */
    public function getDangKyTheoNganh(string $hocKyId, ?string $khoaId = null): Collection;

    /**
     * Thống kê số lớp học phần theo giảng viên
     *
     * @param string $hocKyId ID học kỳ
     * @param string|null $khoaId ID khoa (optional filter)
     * @return Collection Danh sách [ho_ten, so_lop]
     */
    public function getTaiGiangVien(string $hocKyId, ?string $khoaId = null): Collection;
}
