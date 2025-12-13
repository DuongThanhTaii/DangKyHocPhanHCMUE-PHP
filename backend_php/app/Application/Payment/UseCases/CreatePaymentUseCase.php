<?php

namespace App\Application\Payment\UseCases;

use App\Domain\Payment\Repositories\PaymentRepositoryInterface;

/**
 * UseCase: Tạo giao dịch thanh toán
 */
class CreatePaymentUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $repository
    ) {
    }

    public function execute(string $sinhVienId, string $hocKyId, string $provider): array
    {
        if (!$sinhVienId || !$hocKyId) {
            throw new \InvalidArgumentException('Thiếu thông tin sinh viên hoặc học kỳ');
        }

        if (!in_array($provider, ['momo', 'vnpay', 'zalopay'])) {
            throw new \InvalidArgumentException('Provider không hợp lệ. Chọn: momo, vnpay, zalopay');
        }

        // Calculate tuition
        $tuition = $this->repository->calculateTuition($sinhVienId, $hocKyId);

        if (!$tuition || $tuition['amount'] <= 0) {
            throw new \RuntimeException('Không có đăng ký học phần nào hoặc số tiền không hợp lệ');
        }

        $orderId = $this->repository->generateOrderId($provider);
        $payUrl = $this->generatePayUrl($provider, $orderId, $tuition['amount']);

        $transaction = $this->repository->createTransaction([
            'provider' => $provider,
            'order_id' => $orderId,
            'sinh_vien_id' => $sinhVienId,
            'hoc_ky_id' => $hocKyId,
            'amount' => $tuition['amount'],
            'pay_url' => $payUrl,
        ]);

        return [
            'success' => true,
            'data' => [
                'payUrl' => $payUrl,
                'orderId' => $orderId,
                'amount' => $tuition['amount'],
            ],
            'message' => 'Tạo payment thành công'
        ];
    }

    private function generatePayUrl(string $provider, string $orderId, float $amount): string
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        return "{$frontendUrl}/sv/payment/demo?orderId={$orderId}&provider={$provider}&amount={$amount}";
    }
}
