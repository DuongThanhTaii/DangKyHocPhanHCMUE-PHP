<?php

namespace App\Domain\Pdt\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Học kỳ và Phases
 */
interface HocKyRepositoryInterface
{
    /**
     * Tìm học kỳ theo ID
     */
    public function findById(string $id): ?object;

    /**
     * Đặt học kỳ hiện hành
     * 
     * @param string $hocKyId
     * @return void
     */
    public function setCurrentSemester(string $hocKyId): void;

    /**
     * Cập nhật thời gian học kỳ
     */
    public function updateDates(string $hocKyId, string $startDate, string $endDate): void;

    /**
     * Xóa tất cả phases của học kỳ
     */
    public function deletePhasesByHocKy(string $hocKyId): void;

    /**
     * Xóa tất cả đợt đăng ký của học kỳ
     */
    public function deleteDotDangKyByHocKy(string $hocKyId): void;

    /**
     * Tạo phase mới
     * 
     * @param array $data
     * @return object Created phase
     */
    public function createPhase(array $data): object;

    /**
     * Tạo đợt đăng ký
     */
    public function createDotDangKy(array $data): object;

    /**
     * Lấy phases theo học kỳ
     */
    public function getPhasesByHocKy(string $hocKyId): Collection;
}
