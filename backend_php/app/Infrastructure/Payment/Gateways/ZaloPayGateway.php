<?php

namespace App\Infrastructure\Payment\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ZaloPay Payment Gateway Implementation
 */
class ZaloPayGateway implements IPaymentGateway
{
    private string $appId;
    private string $key1;
    private string $key2;
    private string $endpoint;
    private string $callbackUrl;

    public function __construct()
    {
        $this->appId = env('ZALOPAY_APP_ID', '');
        $this->key1 = env('ZALOPAY_KEY1', '');
        $this->key2 = env('ZALOPAY_KEY2', '');
        $this->endpoint = env('ZALOPAY_ENDPOINT', 'https://sb-openapi.zalopay.vn');
        
        // Build callback URL - append /api/payment/ipn if not present
        $baseUrl = env('UNIFIED_IPN_URL', env('APP_URL', 'http://localhost:8000'));
        $this->callbackUrl = str_contains($baseUrl, '/api/payment/ipn') 
            ? $baseUrl 
            : rtrim($baseUrl, '/') . '/api/payment/ipn';
    }

    public function createPayment(CreatePaymentRequest $request): CreatePaymentResponse
    {
        $appTime = (int)(microtime(true) * 1000);
        $transId = (int)(microtime(true)) % 1000000;
        
        // ZaloPay app_trans_id format: yyMMdd_transId
        $appTransId = date('ymd') . '_' . $transId;
        
        $sinhVienId = $request->metadata['sinhVienId'] ?? 'UNKNOWN';
        
        // ZaloPay embed_data - exactly matching legacy TypeScript implementation
        $embedData = json_encode([
            'redirecturl' => $request->redirectUrl,
            'merchant_order_id' => 'ORDER_' . $appTime . '_' . substr($sinhVienId, 0, 8)
        ]);
        
        $items = json_encode([[
            'itemid' => $appTransId,
            'itemname' => $request->orderInfo,
            'itemprice' => (int)$request->amount,
            'itemquantity' => 1
        ]]);
        
        // Build MAC: app_id|app_trans_id|app_user|amount|app_time|embed_data|item
        $data = "{$this->appId}|{$appTransId}|user123|" . (int)$request->amount . "|{$appTime}|{$embedData}|{$items}";
        $mac = hash_hmac('sha256', $data, $this->key1);
        
        $payload = [
            'app_id' => (int)$this->appId,
            'app_trans_id' => $appTransId,
            'app_user' => 'user123',
            'app_time' => $appTime,
            'amount' => (int)$request->amount,
            'item' => $items,
            'embed_data' => $embedData,
            'description' => $request->orderInfo,
            'bank_code' => '',
            'callback_url' => $this->callbackUrl,
            'mac' => $mac
        ];
        
        Log::info('[ZaloPay] Request payload', $payload);
        Log::info('[ZaloPay] Redirect URL in embed_data: ' . $request->redirectUrl);
        
        $response = Http::timeout(30)->post("{$this->endpoint}/v2/create", $payload);
        $result = $response->json();
        
        Log::info('[ZaloPay] Response', $result);
        
        if (($result['return_code'] ?? 0) != 1) {
            throw new \RuntimeException('ZaloPay error: ' . ($result['return_message'] ?? 'Unknown error'));
        }
        
        return new CreatePaymentResponse(
            payUrl: $result['order_url'] ?? '',
            orderId: $appTransId,
            requestId: $appTransId
        );
    }

    public function verifyIPN(VerifyIPNRequest $request): VerifyIPNResponse
    {
        $data = $request->data;
        
        $receivedMac = $data['mac'] ?? '';
        $dataStr = $data['data'] ?? '';
        
        // Verify MAC using key2
        $calculatedMac = hash_hmac('sha256', $dataStr, $this->key2);
        $isValid = $calculatedMac === $receivedMac;
        
        // Parse data JSON
        $dataJson = [];
        try {
            $dataJson = json_decode($dataStr, true) ?? [];
        } catch (\Exception $e) {
            $dataJson = [];
        }
        
        return new VerifyIPNResponse(
            isValid: $isValid,
            orderId: $dataJson['app_trans_id'] ?? '',
            transactionId: (string)($dataJson['zp_trans_id'] ?? ''),
            resultCode: $isValid ? '1' : '0',
            message: $isValid ? 'Success' : 'Invalid signature'
        );
    }
}
