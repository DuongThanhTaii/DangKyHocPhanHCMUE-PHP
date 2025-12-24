<?php

namespace App\Infrastructure\Payment\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MoMo Payment Gateway Implementation
 */
class MoMoGateway implements IPaymentGateway
{
    private string $accessKey;
    private string $secretKey;
    private string $partnerCode;
    private string $endpoint;

    public function __construct()
    {
        $this->accessKey = env('MOMO_ACCESS_KEY', '');
        $this->secretKey = env('MOMO_SECRET_KEY', '');
        $this->partnerCode = env('MOMO_PARTNER_CODE', 'MOMO');
        $this->endpoint = env('MOMO_ENDPOINT', 'https://test-payment.momo.vn');
    }

    public function createPayment(CreatePaymentRequest $request): CreatePaymentResponse
    {
        $sinhVienId = $request->metadata['sinhVienId'] ?? 'UNKNOWN';
        $orderId = 'ORDER_' . (int)(microtime(true) * 1000) . '_' . substr($sinhVienId, 0, 8);
        
        $extraData = '';
        $requestType = 'payWithMethod';
        
        // Build signature
        $rawSignature = "accessKey={$this->accessKey}"
            . "&amount=" . (int)$request->amount
            . "&extraData={$extraData}"
            . "&ipnUrl={$request->ipnUrl}"
            . "&orderId={$orderId}"
            . "&orderInfo={$request->orderInfo}"
            . "&partnerCode={$this->partnerCode}"
            . "&redirectUrl={$request->redirectUrl}"
            . "&requestId={$orderId}"
            . "&requestType={$requestType}";
        
        $signature = hash_hmac('sha256', $rawSignature, $this->secretKey);
        
        $payload = [
            'partnerCode' => $this->partnerCode,
            'accessKey' => $this->accessKey,
            'requestId' => $orderId,
            'amount' => (int)$request->amount,
            'orderId' => $orderId,
            'orderInfo' => $request->orderInfo,
            'redirectUrl' => $request->redirectUrl,
            'ipnUrl' => $request->ipnUrl,
            'requestType' => $requestType,
            'extraData' => $extraData,
            'signature' => $signature,
            'lang' => 'vi'
        ];
        
        Log::info('[MoMo] Creating payment', ['orderId' => $orderId, 'amount' => $request->amount]);
        
        $response = Http::timeout(30)->post("{$this->endpoint}/v2/gateway/api/create", $payload);
        $data = $response->json();
        
        Log::info('[MoMo] Response', $data);
        
        if (($data['resultCode'] ?? -1) != 0) {
            throw new \RuntimeException('MoMo error: ' . ($data['message'] ?? 'Unknown error'));
        }
        
        return new CreatePaymentResponse(
            payUrl: $data['payUrl'] ?? '',
            orderId: $orderId,
            requestId: $data['requestId'] ?? $orderId
        );
    }

    public function verifyIPN(VerifyIPNRequest $request): VerifyIPNResponse
    {
        $data = $request->data;
        $receivedSignature = $data['signature'] ?? '';
        
        $rawSignature = "accessKey={$this->accessKey}"
            . "&amount=" . ($data['amount'] ?? 0)
            . "&extraData=" . ($data['extraData'] ?? '')
            . "&message=" . ($data['message'] ?? '')
            . "&orderId=" . ($data['orderId'] ?? '')
            . "&orderInfo=" . ($data['orderInfo'] ?? '')
            . "&orderType=" . ($data['orderType'] ?? '')
            . "&partnerCode={$this->partnerCode}"
            . "&payType=" . ($data['payType'] ?? '')
            . "&requestId=" . ($data['requestId'] ?? '')
            . "&responseTime=" . ($data['responseTime'] ?? '')
            . "&resultCode=" . ($data['resultCode'] ?? '')
            . "&transId=" . ($data['transId'] ?? '');
        
        $calculatedSignature = hash_hmac('sha256', $rawSignature, $this->secretKey);
        $isValid = $calculatedSignature === $receivedSignature;
        
        return new VerifyIPNResponse(
            isValid: $isValid,
            orderId: $data['orderId'] ?? '',
            transactionId: (string)($data['transId'] ?? ''),
            resultCode: (string)($data['resultCode'] ?? ''),
            message: $data['message'] ?? ''
        );
    }
}
