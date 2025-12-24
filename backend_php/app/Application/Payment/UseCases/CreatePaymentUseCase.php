<?php

namespace App\Application\Payment\UseCases;

use App\Domain\Payment\Repositories\PaymentRepositoryInterface;
use App\Infrastructure\Payment\Gateways\PaymentGatewayFactory;
use App\Infrastructure\Payment\Gateways\CreatePaymentRequest;

/**
 * UseCase: Tạo giao dịch thanh toán
 * Port từ Django - sử dụng Gateway Factory pattern
 */
class CreatePaymentUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $paymentRepo,
        private $hocPhiRepo = null
    ) {}

    public function execute(string $sinhVienId, string $hocKyId, string $provider = 'momo'): array
    {
        if (!$sinhVienId || !$hocKyId) {
            throw new \InvalidArgumentException('Thiếu thông tin sinh viên hoặc học kỳ');
        }

        if (!in_array($provider, ['momo', 'vnpay', 'zalopay'])) {
            throw new \InvalidArgumentException('Provider không hợp lệ. Chọn: momo, vnpay, zalopay');
        }

        // 1. Get học phí to determine amount
        $tuition = $this->paymentRepo->calculateTuition($sinhVienId, $hocKyId);

        if (!$tuition || ($tuition['amount'] ?? 0) <= 0) {
            return [
                'isSuccess' => false,
                'data' => null,
                'message' => 'Không có đăng ký học phần nào hoặc số tiền không hợp lệ',
                'error' => 'TUITION_NOT_FOUND'
            ];
        }

        $amount = (float)$tuition['amount'];

        // 2. Create payment via gateway
        $gateway = PaymentGatewayFactory::create($provider);
        
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $ipnUrl = env('UNIFIED_IPN_URL', env('APP_URL', 'http://localhost:8000') . '/api/payment/ipn');
        
        // ZaloPay requires public URL for redirect
        if ($provider === 'zalopay') {
            $zalopayRedirect = env('ZALOPAY_REDIRECT_URL');
            $redirectUrl = $zalopayRedirect 
                ? "{$zalopayRedirect}/payment/result" 
                : "{$frontendUrl}/payment/result";
        } else {
            $redirectUrl = "{$frontendUrl}/payment/result";
        }
        
        try {
            $gatewayResponse = $gateway->createPayment(new CreatePaymentRequest(
                amount: $amount,
                orderInfo: "Thanh toan hoc phi HK {$hocKyId}",
                redirectUrl: $redirectUrl,
                ipnUrl: $ipnUrl,
                metadata: [
                    'sinhVienId' => $sinhVienId,
                    'hocKyId' => $hocKyId
                ]
            ));
        } catch (\Exception $e) {
            \Log::error("[CreatePayment] Gateway error: " . $e->getMessage());
            return [
                'isSuccess' => false,
                'data' => null,
                'message' => $e->getMessage(),
                'error' => 'GATEWAY_ERROR'
            ];
        }

        // 3. Save transaction to DB
        $this->paymentRepo->createTransaction([
            'sinh_vien_id' => $sinhVienId,
            'hoc_ky_id' => $hocKyId,
            'amount' => $amount,
            'provider' => $provider,
            'order_id' => $gatewayResponse->orderId,
            'pay_url' => $gatewayResponse->payUrl
        ]);

        return [
            'isSuccess' => true,
            'data' => [
                'payUrl' => $gatewayResponse->payUrl,
                'orderId' => $gatewayResponse->orderId,
                'amount' => $amount
            ],
            'message' => 'Tạo payment thành công'
        ];
    }
}
