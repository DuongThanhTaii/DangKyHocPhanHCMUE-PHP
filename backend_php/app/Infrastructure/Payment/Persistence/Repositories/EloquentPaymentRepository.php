<?php

namespace App\Infrastructure\Payment\Persistence\Repositories;

use App\Domain\Payment\Repositories\PaymentRepositoryInterface;
use App\Infrastructure\Payment\Persistence\Models\PaymentTransaction;
use App\Infrastructure\Payment\Persistence\Models\HocPhi;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use Illuminate\Support\Str;

/**
 * Eloquent implementation của PaymentRepositoryInterface
 */
class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function createTransaction(array $data): object
    {
        return PaymentTransaction::create([
            'id' => Str::uuid()->toString(),
            'provider' => $data['provider'],
            'order_id' => $data['order_id'],
            'sinh_vien_id' => $data['sinh_vien_id'],
            'hoc_ky_id' => $data['hoc_ky_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'VND',
            'status' => 'pending',
            'pay_url' => $data['pay_url'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function findByOrderId(string $orderId): ?object
    {
        return PaymentTransaction::where('order_id', $orderId)->first();
    }

    public function updateTransactionStatus(object $transaction, string $status, array $data = []): void
    {
        $transaction->status = $status;
        $transaction->result_code = $data['result_code'] ?? null;
        $transaction->callback_raw = $data['callback_raw'] ?? null;
        $transaction->signature_valid = $data['signature_valid'] ?? null;
        $transaction->updated_at = now();
        $transaction->save();
    }

    public function updateHocPhiStatus(string $sinhVienId, string $hocKyId): void
    {
        // Use updateOrCreate to handle case where hoc_phi record doesn't exist yet
        HocPhi::updateOrCreate(
            [
                'sinh_vien_id' => $sinhVienId,
                'hoc_ky_id' => $hocKyId,
            ],
            [
                'trang_thai_thanh_toan' => 'da_thanh_toan',
                'ngay_thanh_toan' => now(),
            ]
        );
    }

    public function calculateTuition(string $sinhVienId, string $hocKyId): ?array
    {
        $dangKys = DangKyHocPhan::with(['lopHocPhan.hocPhan.monHoc'])
            ->where('sinh_vien_id', $sinhVienId)
            ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                $q->where('id_hoc_ky', $hocKyId);
            })
            ->get();

        if ($dangKys->isEmpty()) {
            return null;
        }

        $totalCredits = 0;
        $pricePerCredit = 800000;

        foreach ($dangKys as $dk) {
            $monHoc = $dk->lopHocPhan?->hocPhan?->monHoc;
            $totalCredits += $monHoc?->so_tin_chi ?? 0;
        }

        return [
            'totalCredits' => $totalCredits,
            'pricePerCredit' => $pricePerCredit,
            'amount' => $totalCredits * $pricePerCredit,
        ];
    }

    public function generateOrderId(string $provider): string
    {
        $prefix = strtoupper(substr($provider, 0, 2));
        $timestamp = time();
        $random = Str::random(6);
        return "{$prefix}_{$timestamp}_{$random}";
    }
}
