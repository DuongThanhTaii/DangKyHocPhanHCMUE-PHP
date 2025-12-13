<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Infrastructure\Payment\Persistence\Models\PaymentTransaction;
use App\Infrastructure\Payment\Persistence\Models\HocPhi;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class PaymentController extends Controller
{
    /**
     * POST /api/payment/create
     * Create a payment transaction
     * Body: { "hocKyId": "uuid", "provider": "momo|vnpay|zalopay" }
     */
    public function createPayment(Request $request)
    {
        try {
            $hocKyId = $request->input('hocKyId') ?? $request->input('hoc_ky_id');
            $provider = $request->input('provider', 'momo');

            if (!$hocKyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu thông tin học kỳ (hocKyId)',
                    'errorCode' => 'MISSING_PARAM'
                ], 400);
            }

            // Validate provider
            if (!in_array($provider, ['momo', 'vnpay', 'zalopay'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider không hợp lệ. Chọn: momo, vnpay, zalopay',
                    'errorCode' => 'INVALID_PROVIDER'
                ], 400);
            }

            // Get user info
            $taiKhoan = JWTAuth::parseToken()->authenticate();
            $userProfile = UserProfile::where('tai_khoan_id', $taiKhoan->id)->first();

            if (!$userProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin người dùng',
                    'errorCode' => 'USER_NOT_FOUND'
                ], 404);
            }

            $sinhVien = SinhVien::find($userProfile->id);

            if (!$sinhVien) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thông tin sinh viên',
                    'errorCode' => 'STUDENT_NOT_FOUND'
                ], 404);
            }

            // Calculate tuition directly from registrations (like getHocPhi does)
            $dangKys = \App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan::with(['lopHocPhan.hocPhan.monHoc'])
                ->where('sinh_vien_id', $sinhVien->id)
                ->whereHas('lopHocPhan.hocPhan', function ($q) use ($hocKyId) {
                    $q->where('id_hoc_ky', $hocKyId);
                })
                ->get();

            if ($dangKys->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có đăng ký học phần nào trong học kỳ này. Vui lòng đăng ký học phần trước.',
                    'errorCode' => 'NO_REGISTRATION'
                ], 400);
            }

            // Calculate total credits and tuition
            $totalCredits = 0;
            $pricePerCredit = 800000; // Default price

            foreach ($dangKys as $dk) {
                $monHoc = $dk->lopHocPhan?->hocPhan?->monHoc;
                $totalCredits += $monHoc?->so_tin_chi ?? 0;
            }

            $amount = $totalCredits * $pricePerCredit;

            if ($amount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Số tiền học phí không hợp lệ',
                    'errorCode' => 'INVALID_AMOUNT'
                ], 400);
            }

            // Generate order ID (unique)
            $orderId = $this->generateOrderId($provider);

            // Generate payment URL (simulated - in production would call real gateway)
            $payUrl = $this->generatePayUrl($provider, $orderId, $amount);

            // Save transaction
            PaymentTransaction::create([
                'id' => Str::uuid()->toString(),
                'provider' => $provider,
                'order_id' => $orderId,
                'sinh_vien_id' => $sinhVien->id,
                'hoc_ky_id' => $hocKyId,
                'amount' => $amount,
                'currency' => 'VND',
                'status' => 'pending',
                'pay_url' => $payUrl,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'payUrl' => $payUrl,
                    'orderId' => $orderId,
                    'amount' => $amount,
                ],
                'message' => 'Tạo payment thành công'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
                'errorCode' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * GET /api/payment/status?orderId={id}
     * Get payment transaction status
     */
    public function getPaymentStatus(Request $request)
    {
        try {
            $orderId = $request->query('orderId') ?? $request->query('order_id');

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu mã giao dịch (orderId)',
                    'errorCode' => 'MISSING_PARAM'
                ], 400);
            }

            $transaction = PaymentTransaction::where('order_id', $orderId)->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy giao dịch',
                    'errorCode' => 'TRANSACTION_NOT_FOUND'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'orderId' => $transaction->order_id,
                    'status' => $transaction->status ?? 'pending',
                    'amount' => floatval($transaction->amount ?? 0),
                    'provider' => $transaction->provider,
                    'createdAt' => $transaction->created_at?->toISOString() ?? '',
                    'updatedAt' => $transaction->updated_at?->toISOString() ?? '',
                ],
                'message' => 'Lấy trạng thái giao dịch thành công'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
                'errorCode' => 'INTERNAL_ERROR'
            ], 500);
        }
    }

    /**
     * POST /api/payment/ipn
     * Unified IPN handler for all payment providers
     * No authentication required (callback from payment gateway)
     */
    public function handleIPN(Request $request)
    {
        try {
            $data = $request->all();

            // Detect provider from data
            $provider = $this->detectProvider($data);

            if (!$provider) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot detect payment provider'
                ], 400);
            }

            \Log::info("[IPN] Received from {$provider}: " . json_encode($data));

            // Extract order ID based on provider
            $orderId = $this->extractOrderId($provider, $data);

            if (!$orderId) {
                return $this->getProviderResponse($provider, false);
            }

            // Get transaction
            $transaction = PaymentTransaction::where('order_id', $orderId)->first();

            if (!$transaction) {
                \Log::warning("[IPN] Transaction not found: {$orderId}");
                return $this->getProviderResponse($provider, false);
            }

            // Verify signature (simplified - in production would verify with provider's secret)
            $isSignatureValid = $this->verifySignature($provider, $data);

            // Check if payment successful
            $resultCode = $this->extractResultCode($provider, $data);
            $isSuccess = $this->isPaymentSuccessful($provider, $resultCode);

            // Update transaction
            $transaction->status = $isSuccess ? 'success' : 'failed';
            $transaction->result_code = $resultCode;
            $transaction->callback_raw = $data;
            $transaction->signature_valid = $isSignatureValid;
            $transaction->updated_at = now();
            $transaction->save();

            if ($isSuccess) {
                // Update học phí status
                HocPhi::where('sinh_vien_id', $transaction->sinh_vien_id)
                    ->where('hoc_ky_id', $transaction->hoc_ky_id)
                    ->update([
                        'trang_thai_thanh_toan' => 'da_thanh_toan',
                        'ngay_thanh_toan' => now(),
                    ]);

                \Log::info("[IPN] Payment successful: {$orderId}");
            } else {
                \Log::info("[IPN] Payment failed: {$orderId}, code: {$resultCode}");
            }

            return $this->getProviderResponse($provider, true);

        } catch (\Throwable $e) {
            \Log::error("[IPN] Error processing: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique order ID
     */
    private function generateOrderId(string $provider): string
    {
        $prefix = strtoupper(substr($provider, 0, 2));
        $timestamp = time();
        $random = Str::random(6);
        return "{$prefix}_{$timestamp}_{$random}";
    }

    /**
     * Generate payment URL (simulated)
     * In production, this would call the real payment gateway API
     * For DEMO/TEST: always redirect to frontend demo page
     */
    private function generatePayUrl(string $provider, string $orderId, float $amount): string
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $backendUrl = env('APP_URL', 'http://localhost:8000');

        // DEMO MODE: Always use demo page for testing
        // In production, uncomment the switch below and comment this line
        return "{$frontendUrl}/sv/payment/demo?orderId={$orderId}&provider={$provider}&amount={$amount}";

        /*
        // PRODUCTION MODE:
        switch ($provider) {
            case 'momo':
                return "https://test-payment.momo.vn/v2/gateway/pay?orderId={$orderId}&amount={$amount}";
            case 'vnpay':
                return "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html?orderId={$orderId}&amount={$amount}";
            case 'zalopay':
                return "https://sbgateway.zalopay.vn/openinapp?orderId={$orderId}&amount={$amount}";
            default:
                return "{$frontendUrl}/sv/payment/demo?orderId={$orderId}";
        }
        */
    }

    /**
     * Detect payment provider from IPN data
     */
    private function detectProvider(array $data): ?string
    {
        // MoMo signature fields
        if (isset($data['partnerCode']) || (isset($data['orderId']) && isset($data['transId']))) {
            return 'momo';
        }

        // VNPay signature fields
        if (isset($data['vnp_TxnRef']) || isset($data['vnp_ResponseCode'])) {
            return 'vnpay';
        }

        // ZaloPay signature fields
        if (isset($data['data']) && isset($data['mac'])) {
            return 'zalopay';
        }

        return null;
    }

    /**
     * Extract order ID from IPN data based on provider
     */
    private function extractOrderId(string $provider, array $data): ?string
    {
        switch ($provider) {
            case 'momo':
                return $data['orderId'] ?? null;
            case 'vnpay':
                return $data['vnp_TxnRef'] ?? null;
            case 'zalopay':
                $appTransData = json_decode($data['data'] ?? '{}', true);
                return $appTransData['app_trans_id'] ?? null;
            default:
                return null;
        }
    }

    /**
     * Extract result code from IPN data based on provider
     */
    private function extractResultCode(string $provider, array $data): string
    {
        switch ($provider) {
            case 'momo':
                return strval($data['resultCode'] ?? '-1');
            case 'vnpay':
                return strval($data['vnp_ResponseCode'] ?? '-1');
            case 'zalopay':
                return strval($data['return_code'] ?? '-1');
            default:
                return '-1';
        }
    }

    /**
     * Verify IPN signature (simplified)
     * In production, would verify with provider's secret key
     */
    private function verifySignature(string $provider, array $data): bool
    {
        // Simplified - always return true for demo
        // In production: calculate HMAC and compare with signature in data
        return true;
    }

    /**
     * Check if payment was successful based on result code
     */
    private function isPaymentSuccessful(string $provider, string $resultCode): bool
    {
        switch ($provider) {
            case 'momo':
                return $resultCode === '0';
            case 'vnpay':
                return $resultCode === '00';
            case 'zalopay':
                return $resultCode === '1';
            default:
                return false;
        }
    }

    /**
     * Get appropriate response format for each provider
     */
    private function getProviderResponse(string $provider, bool $success)
    {
        switch ($provider) {
            case 'momo':
                return response()->json([
                    'resultCode' => $success ? 0 : 1,
                    'message' => $success ? 'OK' : 'Failed'
                ]);
            case 'vnpay':
                return response()->json([
                    'RspCode' => $success ? '00' : '99',
                    'Message' => $success ? 'Confirm Success' : 'Confirm Failed'
                ]);
            case 'zalopay':
                return response()->json([
                    'return_code' => $success ? 1 : 0,
                    'return_message' => $success ? 'Success' : 'Failed'
                ]);
            default:
                return response()->json(['success' => $success]);
        }
    }

    /**
     * POST /api/payment/demo-complete
     * Demo: Simulate payment completion (for testing without real gateway)
     * Body: { "orderId": "xxx", "status": "success|failed" }
     */
    public function demoComplete(Request $request)
    {
        try {
            $orderId = $request->input('orderId') ?? $request->input('order_id');
            $status = $request->input('status', 'success');

            if (!$orderId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thiếu mã giao dịch (orderId)',
                    'errorCode' => 'MISSING_PARAM'
                ], 400);
            }

            $transaction = PaymentTransaction::where('order_id', $orderId)->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy giao dịch',
                    'errorCode' => 'TRANSACTION_NOT_FOUND'
                ], 404);
            }

            // Update transaction status
            $isSuccess = $status === 'success';
            $transaction->status = $isSuccess ? 'success' : 'failed';
            $transaction->result_code = $isSuccess ? '0' : '-1';
            $transaction->updated_at = now();
            $transaction->save();

            // If success, update hoc phi status (if HocPhi table exists)
            // For now, just mark the transaction as complete

            \Log::info("[DEMO] Payment {$orderId} completed with status: {$status}");

            return response()->json([
                'success' => true,
                'data' => [
                    'orderId' => $orderId,
                    'status' => $transaction->status,
                    'amount' => floatval($transaction->amount),
                ],
                'message' => $isSuccess ? 'Thanh toán thành công!' : 'Thanh toán thất bại'
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage(),
                'errorCode' => 'INTERNAL_ERROR'
            ], 500);
        }
    }
}
