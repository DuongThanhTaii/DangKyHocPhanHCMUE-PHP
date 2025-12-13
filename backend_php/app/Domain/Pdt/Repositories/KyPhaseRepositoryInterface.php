<?php

namespace App\Domain\Pdt\Repositories;

use Illuminate\Support\Collection;

/**
 * Repository Interface cho Ky Phase (giai đoạn học kỳ)
 */
interface KyPhaseRepositoryInterface
{
    /**
     * Lấy học kỳ hiện tại
     */
    public function getCurrentHocKy(): ?object;

    /**
     * Tìm phase theo học kỳ và tên phase
     */
    public function findByHocKyAndPhase(string $hocKyId, string $phaseName): ?object;

    /**
     * Vô hiệu hóa tất cả phases trong học kỳ
     */
    public function disableAllPhases(string $hocKyId): void;

    /**
     * Kích hoạt phase
     */
    public function enablePhase(object $kyPhase, \DateTime $startAt, \DateTime $endAt): void;
}
