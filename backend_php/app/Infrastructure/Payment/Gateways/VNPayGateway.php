<?php

namespace App\Infrastructure\Payment\Gateways;

use Illuminate\Support\Facades\Log;

/**
 * VNPay Payment Gateway Implementation
 */
class VNPayGateway implements IPaymentGateway
{
    private string $tmnCode;
    private string $secretKey;
    private string $endpoint;

    public function __construct()
    {
        $this->tmnCode = env('VNPAY_TMN_CODE', '');
        $this->secretKey = env('VNPAY_SECRET_KEY', '');
        $this->endpoint = env('VNPAY_ENDPOINT', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
    }

    public function createPayment(CreatePaymentRequest $request): CreatePaymentResponse
    {
        $sinhVienId = $request->metadata['sinhVienId'] ?? 'UNKNOWN';
        $orderId = 'ORDER_' . (int)(microtime(true) * 1000) . '_' . substr($sinhVienId, 0, 8);
        
        $now = new \DateTime();
        $expire = (clone $now)->modify('+1 day');
        
        // VNPay params (alphabetical order for signature)
        $params = [
            'vnp_Amount' => (int)($request->amount * 100), // VNPay uses VND * 100
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => $now->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_ExpireDate' => $expire->format('YmdHis'),
            'vnp_IpAddr' => $request->ipAddr,
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => $request->orderInfo,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $request->redirectUrl,
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_TxnRef' => $orderId,
            'vnp_Version' => '2.1.0',
        ];
        
        // Sort params and build query string
        ksort($params);
        $queryString = http_build_query($params);
        
        // Create signature
        $signature = hash_hmac('sha512', $queryString, $this->secretKey);
        
        $payUrl = "{$this->endpoint}?{$queryString}&vnp_SecureHash={$signature}";
        
        Log::info('[VNPay] Creating payment', ['orderId' => $orderId, 'amount' => $request->amount]);
        
        return new CreatePaymentResponse(
            payUrl: $payUrl,
            orderId: $orderId,
            requestId: $orderId
        );
    }

    public function verifyIPN(VerifyIPNRequest $request): VerifyIPNResponse
    {
        $data = $request->data;
        
        $receivedSignature = $data['vnp_SecureHash'] ?? '';
        unset($data['vnp_SecureHash'], $data['vnp_SecureHashType']);
        
        // Sort and rebuild query string
        ksort($data);
        $queryString = http_build_query($data);
        
        // Calculate signature
        $calculatedSignature = hash_hmac('sha512', $queryString, $this->secretKey);
        
        $isValid = strtolower($calculatedSignature) === strtolower($receivedSignature);
        $isSuccess = ($data['vnp_ResponseCode'] ?? '') === '00';
        
        return new VerifyIPNResponse(
            isValid: $isValid && $isSuccess,
            orderId: $data['vnp_TxnRef'] ?? '',
            transactionId: (string)($data['vnp_TransactionNo'] ?? ''),
            resultCode: (string)($data['vnp_ResponseCode'] ?? ''),
            message: $isValid && $isSuccess ? 'Success' : 'Failed'
        );
    }
}
