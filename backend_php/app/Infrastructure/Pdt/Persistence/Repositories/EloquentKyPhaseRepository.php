<?php

namespace App\Infrastructure\Pdt\Persistence\Repositories;

use App\Domain\Pdt\Repositories\KyPhaseRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\KyPhase;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Carbon\Carbon;

/**
 * Eloquent implementation của KyPhaseRepositoryInterface
 */
class EloquentKyPhaseRepository implements KyPhaseRepositoryInterface
{
    /**
     * Lấy học kỳ hiện tại
     */
    public function getCurrentHocKy(): ?object
    {
        return HocKy::where('trang_thai_hien_tai', true)->first();
    }

    /**
     * Tìm phase theo học kỳ và tên phase
     */
    public function findByHocKyAndPhase(string $hocKyId, string $phaseName): ?object
    {
        return KyPhase::where('hoc_ky_id', $hocKyId)
            ->where('phase', $phaseName)
            ->first();
    }

    /**
     * Vô hiệu hóa tất cả phases trong học kỳ
     */
    public function disableAllPhases(string $hocKyId): void
    {
        $now = Carbon::now();
        KyPhase::where('hoc_ky_id', $hocKyId)->update([
            'is_enabled' => false,
            'start_at' => $now->copy()->subYear(),
            'end_at' => $now->copy()->subYear()->addDay(),
        ]);
    }

    /**
     * Kích hoạt phase
     */
    public function enablePhase(object $kyPhase, \DateTime $startAt, \DateTime $endAt): void
    {
        $kyPhase->is_enabled = true;
        $kyPhase->start_at = $startAt;
        $kyPhase->end_at = $endAt;
        $kyPhase->save();
    }
}
