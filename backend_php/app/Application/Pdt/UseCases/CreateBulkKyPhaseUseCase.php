<?php

namespace App\Application\Pdt\UseCases;

use App\Application\Pdt\DTOs\CreateBulkKyPhaseDTO;
use App\Domain\Pdt\Repositories\HocKyRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * UseCase: Tạo bulk phases cho học kỳ
 */
class CreateBulkKyPhaseUseCase
{
    public function __construct(
        private HocKyRepositoryInterface $repository
    ) {
    }

    /**
     * Execute use case
     *
     * @param CreateBulkKyPhaseDTO $dto
     * @return array Response data
     */
    public function execute(CreateBulkKyPhaseDTO $dto): array
    {
        $createdPhases = [];

        DB::transaction(function () use ($dto, &$createdPhases) {
            // 1. Update HocKy dates
            $this->repository->updateDates(
                $dto->hocKyId,
                $dto->hocKyStartAt->toDateString(),
                $dto->hocKyEndAt->toDateString()
            );

            // 2. Delete old phases and dot_dang_ky for this semester
            $this->repository->deletePhasesByHocKy($dto->hocKyId);
            $this->repository->deleteDotDangKyByHocKy($dto->hocKyId);

            // 3. Create new phases
            $ghiDanhPhase = null;
            $dangKyPhase = null;

            foreach ($dto->phases as $phase) {
                $phaseName = $phase['phase'] ?? $phase['tenPhase'] ?? '';
                $phaseStart = $phase['startAt'] ?? $phase['ngayBatDau'] ?? null;
                $phaseEnd = $phase['endAt'] ?? $phase['ngayKetThuc'] ?? null;

                if (!$phaseName || !$phaseStart || !$phaseEnd) {
                    continue;
                }

                $newPhase = $this->repository->createPhase([
                    'id' => Str::uuid()->toString(),
                    'hoc_ky_id' => $dto->hocKyId,
                    'phase' => $phaseName,
                    'start_at' => Carbon::parse($phaseStart),
                    'end_at' => Carbon::parse($phaseEnd),
                    'is_enabled' => $phase['isEnabled'] ?? true,
                ]);

                $createdPhases[] = [
                    'id' => $newPhase->id,
                    'phase' => $newPhase->phase,
                    'startAt' => $newPhase->start_at?->toISOString(),
                    'endAt' => $newPhase->end_at?->toISOString(),
                    'isEnabled' => $newPhase->is_enabled,
                ];

                if ($phaseName === 'ghi_danh') {
                    $ghiDanhPhase = $newPhase;
                } elseif ($phaseName === 'dang_ky_hoc_phan') {
                    $dangKyPhase = $newPhase;
                }
            }

            // 4. Create default DotDangKy for ghi_danh and dang_ky phases
            if ($ghiDanhPhase) {
                $this->repository->createDotDangKy([
                    'id' => Str::uuid()->toString(),
                    'hoc_ky_id' => $dto->hocKyId,
                    'loai_dot' => 'ghi_danh',
                    'is_check_toan_truong' => true,
                    'thoi_gian_bat_dau' => $ghiDanhPhase->start_at,
                    'thoi_gian_ket_thuc' => $ghiDanhPhase->end_at,
                    'gioi_han_tin_chi' => 50,
                    'khoa_id' => null,
                ]);
            }

            if ($dangKyPhase) {
                $this->repository->createDotDangKy([
                    'id' => Str::uuid()->toString(),
                    'hoc_ky_id' => $dto->hocKyId,
                    'loai_dot' => 'dang_ky',
                    'is_check_toan_truong' => true,
                    'thoi_gian_bat_dau' => $dangKyPhase->start_at,
                    'thoi_gian_ket_thuc' => $dangKyPhase->end_at,
                    'gioi_han_tin_chi' => 9999,
                    'khoa_id' => null,
                ]);
            }
        });

        return [
            'isSuccess' => true,
            'data' => $createdPhases,
            'message' => 'Tạo phases thành công'
        ];
    }
}
