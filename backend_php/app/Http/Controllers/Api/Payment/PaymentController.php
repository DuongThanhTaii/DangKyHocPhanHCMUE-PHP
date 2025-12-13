<?php

namespace App\Http\Controllers\Api\Payment;

use App\Http\Controllers\Controller;
use App\Application\Payment\UseCases\CreatePaymentUseCase;
use App\Application\Payment\UseCases\HandleIPNUseCase;
use App\Domain\Payment\Repositories\PaymentRepositoryInterface;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * PaymentController - Payment handling (Refactored - Clean Architecture)
 * 
 * Thin controller - delegates to UseCases
 */
class PaymentController extends Controller
{
    public function __construct(
        private CreatePaymentUseCase $createPaymentUseCase,
        private HandleIPNUseCase $handleIPNUseCase,
        private PaymentRepositoryInterface $repository,
    ) {
    }

    /**
     * POST /api/payment/create
     */
    public function createPayment(Request $request): JsonResponse
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

            $result = $this->createPaymentUseCase->execute($sinhVien->id, $hocKyId, $provider);
            return response()->json($result);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errorCode' => 'INVALID_PARAM'
            ], 400);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errorCode' => 'NO_REGISTRATION'
            ], 400);
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
     */
    public function getPaymentStatus(Request $request): JsonResponse
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

            $transaction = $this->repository->findByOrderId($orderId);

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
     */
    public function handleIPN(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $provider = $this->detectProvider($data);

            \Log::info("[IPN] Received from {$provider}: " . json_encode($data));

            $result = $this->handleIPNUseCase->execute($data, $provider);

            return $this->getProviderResponse($result['provider'], true);

        } catch (\InvalidArgumentException $e) {
            \Log::warning("[IPN] Invalid: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        } catch (\RuntimeException $e) {
            \Log::warning("[IPN] Not found: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Throwable $e) {
            \Log::error("[IPN] Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/payment/demo-complete
     */
    public function demoComplete(Request $request): JsonResponse
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

            $transaction = $this->repository->findByOrderId($orderId);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy giao dịch',
                    'errorCode' => 'TRANSACTION_NOT_FOUND'
                ], 404);
            }

            $isSuccess = $status === 'success';

            $this->repository->updateTransactionStatus($transaction, $isSuccess ? 'success' : 'failed', [
                'result_code' => $isSuccess ? '0' : '-1',
            ]);

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

    /**
     * Detect payment provider from IPN data
     */
    private function detectProvider(array $data): ?string
    {
        if (isset($data['partnerCode']) || (isset($data['orderId']) && isset($data['transId']))) {
            return 'momo';
        }

        if (isset($data['vnp_TxnRef']) || isset($data['vnp_ResponseCode'])) {
            return 'vnpay';
        }

        if (isset($data['data']) && isset($data['mac'])) {
            return 'zalopay';
        }

        return null;
    }

    /**
     * Get appropriate response format for each provider
     */
    private function getProviderResponse(string $provider, bool $success): JsonResponse
    {
        return match ($provider) {
            'momo' => response()->json([
                'resultCode' => $success ? 0 : 1,
                'message' => $success ? 'OK' : 'Failed'
            ]),
            'vnpay' => response()->json([
                'RspCode' => $success ? '00' : '99',
                'Message' => $success ? 'Confirm Success' : 'Confirm Failed'
            ]),
            'zalopay' => response()->json([
                'return_code' => $success ? 1 : 0,
                'return_message' => $success ? 'Success' : 'Failed'
            ]),
            default => response()->json(['success' => $success]),
        };
    }
}
