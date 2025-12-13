<?php

namespace App\Application\Pdt\UseCases;

use App\Domain\Pdt\Repositories\HocPhiRepositoryInterface;

/**
 * UseCase: Tính toán học phí hàng loạt
 */
class TinhToanHocPhiHangLoatUseCase
{
    public function __construct(
        private HocPhiRepositoryInterface $repository
    ) {
    }

    public function execute(string $hocKyId): array
    {
        if (!$hocKyId) {
            throw new \InvalidArgumentException('Thiếu hocKyId');
        }

        $studentIds = $this->repository->getStudentIdsWithRegistrations($hocKyId);
        $count = 0;

        foreach ($studentIds as $svId) {
            $totalCredits = $this->repository->getTotalCredits($svId, $hocKyId);

            if ($totalCredits == 0) {
                continue;
            }

            $sinhVien = $this->repository->findSinhVien($svId);
            if (!$sinhVien) {
                continue;
            }

            $policy = $this->repository->findPolicyForStudent($sinhVien, $hocKyId);
            if (!$policy) {
                continue;
            }

            $totalFee = $totalCredits * $policy->phi_moi_tin_chi;

            $this->repository->saveHocPhi([
                'sinh_vien_id' => $svId,
                'hoc_ky_id' => $hocKyId,
                'tong_hoc_phi' => $totalFee,
                'chinh_sach_id' => $policy->id,
            ]);

            $count++;
        }

        return [
            'isSuccess' => true,
            'data' => ['processedCount' => $count],
            'message' => "Đã tính học phí cho {$count} sinh viên"
        ];
    }
}
