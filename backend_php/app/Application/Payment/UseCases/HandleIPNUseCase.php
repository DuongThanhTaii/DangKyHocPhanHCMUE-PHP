<?php

namespace App\Application\Payment\UseCases;

use App\Domain\Payment\Repositories\PaymentRepositoryInterface;

/**
 * UseCase: Xử lý IPN callback
 */
class HandleIPNUseCase
{
    public function __construct(
        private PaymentRepositoryInterface $repository
    ) {
    }

    public function execute(array $data, ?string $provider): array
    {
        if (!$provider) {
            throw new \InvalidArgumentException('Cannot detect payment provider');
        }

        $orderId = $this->extractOrderId($provider, $data);

        if (!$orderId) {
            throw new \InvalidArgumentException('Cannot extract order ID');
        }

        $transaction = $this->repository->findByOrderId($orderId);

        if (!$transaction) {
            throw new \RuntimeException('Transaction not found');
        }

        $resultCode = $this->extractResultCode($provider, $data);
        $isSuccess = $this->isPaymentSuccessful($provider, $resultCode);

        $this->repository->updateTransactionStatus($transaction, $isSuccess ? 'success' : 'failed', [
            'result_code' => $resultCode,
            'callback_raw' => $data,
            'signature_valid' => true,
        ]);

        if ($isSuccess) {
            $this->repository->updateHocPhiStatus($transaction->sinh_vien_id, $transaction->hoc_ky_id);
        }

        return [
            'success' => true,
            'provider' => $provider,
            'isPaymentSuccess' => $isSuccess,
        ];
    }

    private function extractOrderId(string $provider, array $data): ?string
    {
        return match ($provider) {
            'momo' => $data['orderId'] ?? null,
            'vnpay' => $data['vnp_TxnRef'] ?? null,
            'zalopay' => json_decode($data['data'] ?? '{}', true)['app_trans_id'] ?? null,
            default => null,
        };
    }

    private function extractResultCode(string $provider, array $data): string
    {
        return match ($provider) {
            'momo' => strval($data['resultCode'] ?? '-1'),
            'vnpay' => strval($data['vnp_ResponseCode'] ?? '-1'),
            'zalopay' => strval($data['return_code'] ?? '-1'),
            default => '-1',
        };
    }

    private function isPaymentSuccessful(string $provider, string $resultCode): bool
    {
        return match ($provider) {
            'momo' => $resultCode === '0',
            'vnpay' => $resultCode === '00',
            'zalopay' => $resultCode === '1',
            default => false,
        };
    }
}
