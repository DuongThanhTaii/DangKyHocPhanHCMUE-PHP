<?php

namespace App\Infrastructure\Payment\Gateways;

/**
 * Payment Gateway Interface
 */
interface IPaymentGateway
{
    public function createPayment(CreatePaymentRequest $request): CreatePaymentResponse;
    public function verifyIPN(VerifyIPNRequest $request): VerifyIPNResponse;
}

/**
 * Create Payment Request DTO
 */
class CreatePaymentRequest
{
    public function __construct(
        public readonly float $amount,
        public readonly string $orderInfo,
        public readonly string $redirectUrl,
        public readonly string $ipnUrl,
        public readonly array $metadata = [],
        public readonly string $ipAddr = '127.0.0.1'
    ) {}
}

/**
 * Create Payment Response DTO
 */
class CreatePaymentResponse
{
    public function __construct(
        public readonly string $payUrl,
        public readonly string $orderId,
        public readonly string $requestId
    ) {}
}

/**
 * Verify IPN Request DTO
 */
class VerifyIPNRequest
{
    public function __construct(
        public readonly array $data
    ) {}
}

/**
 * Verify IPN Response DTO
 */
class VerifyIPNResponse
{
    public function __construct(
        public readonly bool $isValid,
        public readonly string $orderId,
        public readonly string $transactionId,
        public readonly string $resultCode,
        public readonly string $message = ''
    ) {}
}

/**
 * Payment Gateway Factory
 */
class PaymentGatewayFactory
{
    public static function create(string $provider): IPaymentGateway
    {
        return match ($provider) {
            'momo' => new MoMoGateway(),
            'vnpay' => new VNPayGateway(),
            'zalopay' => new ZaloPayGateway(),
            default => throw new \InvalidArgumentException("Unknown payment provider: {$provider}")
        };
    }
}
