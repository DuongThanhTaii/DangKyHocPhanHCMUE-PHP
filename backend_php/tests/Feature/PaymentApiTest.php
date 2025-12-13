<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\Payment\Persistence\Models\PaymentTransaction;
use App\Infrastructure\Payment\Persistence\Models\HocPhi;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PaymentApiTest extends TestCase
{
    private $token;
    private $testAccount;
    private $testUser;
    private $testSinhVien;
    private $testHocPhi;
    private $testTransaction;
    private $khoaId;
    private $hocKyId;

    public function setUp(): void
    {
        parent::setUp();

        // Get an existing khoa for testing
        $khoa = Khoa::first();
        $this->khoaId = $khoa?->id;

        if (!$this->khoaId) {
            $this->markTestSkipped('No khoa found in database');
        }

        // Get current semester
        $hocKy = HocKy::where('trang_thai_hien_tai', true)->first();
        $this->hocKyId = $hocKy?->id;

        if (!$this->hocKyId) {
            $this->markTestSkipped('No current semester found');
        }

        // Create test student account
        $this->testAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'payment_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'sinh_vien',
            'trang_thai_hoat_dong' => true,
        ]);

        // Create user profile
        $this->testUser = UserProfile::create([
            'id' => Str::uuid()->toString(),
            'ho_ten' => 'Test Payment SinhVien',
            'email' => 'paymenttest' . rand(1000, 9999) . '@student.hcmue.edu.vn',
            'tai_khoan_id' => $this->testAccount->id,
        ]);

        // Create sinh_vien record with same ID as user
        $this->testSinhVien = SinhVien::create([
            'id' => $this->testUser->id,
            'ma_so_sinh_vien' => 'PAY' . rand(10000, 99999),
            'khoa_id' => $this->khoaId,
            'lop' => 'Payment Test Class',
            'khoa_hoc' => '2024-2025',
        ]);

        // Create test hoc_phi
        $this->testHocPhi = HocPhi::create([
            'id' => Str::uuid()->toString(),
            'sinh_vien_id' => $this->testSinhVien->id,
            'hoc_ky_id' => $this->hocKyId,
            'tong_hoc_phi' => 5000000,
            'trang_thai_thanh_toan' => 'chua_thanh_toan',
            'ngay_tinh_toan' => now(),
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);
    }

    public function tearDown(): void
    {
        // Clean up test data
        if ($this->testTransaction) {
            try {
                $this->testTransaction->delete();
            } catch (\Exception $e) {
            }
        }
        if ($this->testHocPhi) {
            try {
                $this->testHocPhi->delete();
            } catch (\Exception $e) {
            }
        }
        if ($this->testSinhVien) {
            try {
                $this->testSinhVien->delete();
            } catch (\Exception $e) {
            }
        }
        if ($this->testUser) {
            try {
                $this->testUser->delete();
            } catch (\Exception $e) {
            }
        }
        if ($this->testAccount) {
            try {
                $this->testAccount->delete();
            } catch (\Exception $e) {
            }
        }
        parent::tearDown();
    }

    /**
     * Test POST /api/payment/create - success
     */
    public function test_can_create_payment()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/payment/create', [
                'hocKyId' => $this->hocKyId,
                'provider' => 'momo'
            ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('payUrl', $data['data']);
        $this->assertArrayHasKey('orderId', $data['data']);
        $this->assertArrayHasKey('amount', $data['data']);

        // Clean up transaction
        if (isset($data['data']['orderId'])) {
            $this->testTransaction = PaymentTransaction::where('order_id', $data['data']['orderId'])->first();
        }

        echo "\n✅ POST /api/payment/create - Success (orderId: {$data['data']['orderId']})\n";
    }

    /**
     * Test POST /api/payment/create - missing hocKyId
     */
    public function test_create_payment_requires_hoc_ky_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/payment/create', [
                'provider' => 'momo'
            ]);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertEquals('MISSING_PARAM', $data['errorCode']);

        echo "\n✅ POST /api/payment/create - Requires hocKyId (400)\n";
    }

    /**
     * Test POST /api/payment/create - invalid provider
     */
    public function test_create_payment_validates_provider()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/payment/create', [
                'hocKyId' => $this->hocKyId,
                'provider' => 'invalid_provider'
            ]);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertEquals('INVALID_PROVIDER', $data['errorCode']);

        echo "\n✅ POST /api/payment/create - Validates provider (400)\n";
    }

    /**
     * Test GET /api/payment/status - success
     */
    public function test_can_get_payment_status()
    {
        // First create a transaction
        $orderId = 'TEST_' . time() . '_' . Str::random(6);
        $this->testTransaction = PaymentTransaction::create([
            'id' => Str::uuid()->toString(),
            'provider' => 'momo',
            'order_id' => $orderId,
            'sinh_vien_id' => $this->testSinhVien->id,
            'hoc_ky_id' => $this->hocKyId,
            'amount' => 5000000,
            'currency' => 'VND',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/payment/status?orderId={$orderId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals($orderId, $data['data']['orderId']);
        $this->assertEquals('pending', $data['data']['status']);

        echo "\n✅ GET /api/payment/status - Success (status: pending)\n";
    }

    /**
     * Test GET /api/payment/status - missing orderId
     */
    public function test_payment_status_requires_order_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/payment/status');

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertEquals('MISSING_PARAM', $data['errorCode']);

        echo "\n✅ GET /api/payment/status - Requires orderId (400)\n";
    }

    /**
     * Test GET /api/payment/status - transaction not found
     */
    public function test_payment_status_returns_404_for_unknown_order()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/payment/status?orderId=FAKE_ORDER_12345');

        $response->assertStatus(404);

        $data = $response->json();
        $this->assertFalse($data['success']);
        $this->assertEquals('TRANSACTION_NOT_FOUND', $data['errorCode']);

        echo "\n✅ GET /api/payment/status - Returns 404 for unknown order\n";
    }

    /**
     * Test POST /api/payment/ipn - MoMo format
     */
    public function test_ipn_handles_momo_format()
    {
        // Create a pending transaction first
        $orderId = 'MO_' . time() . '_test';
        $this->testTransaction = PaymentTransaction::create([
            'id' => Str::uuid()->toString(),
            'provider' => 'momo',
            'order_id' => $orderId,
            'sinh_vien_id' => $this->testSinhVien->id,
            'hoc_ky_id' => $this->hocKyId,
            'amount' => 5000000,
            'currency' => 'VND',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send MoMo IPN
        $response = $this->postJson('/api/payment/ipn', [
            'partnerCode' => 'MOMO',
            'orderId' => $orderId,
            'transId' => 'MOMO_TRANS_12345',
            'resultCode' => 0, // Success
            'message' => 'Thành công',
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals(0, $data['resultCode']);
        $this->assertEquals('OK', $data['message']);

        // Verify transaction status updated
        $this->testTransaction->refresh();
        $this->assertEquals('success', $this->testTransaction->status);

        echo "\n✅ POST /api/payment/ipn - MoMo format success\n";
    }

    /**
     * Test POST /api/payment/ipn - VNPay format
     */
    public function test_ipn_handles_vnpay_format()
    {
        // Create a pending transaction first
        $orderId = 'VN_' . time() . '_test';
        $this->testTransaction = PaymentTransaction::create([
            'id' => Str::uuid()->toString(),
            'provider' => 'vnpay',
            'order_id' => $orderId,
            'sinh_vien_id' => $this->testSinhVien->id,
            'hoc_ky_id' => $this->hocKyId,
            'amount' => 5000000,
            'currency' => 'VND',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Send VNPay IPN
        $response = $this->postJson('/api/payment/ipn', [
            'vnp_TxnRef' => $orderId,
            'vnp_ResponseCode' => '00', // Success
            'vnp_TransactionNo' => 'VNPAY_TRANS_12345',
            'vnp_SecureHash' => 'mock_hash',
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertEquals('00', $data['RspCode']);

        // Verify transaction status updated
        $this->testTransaction->refresh();
        $this->assertEquals('success', $this->testTransaction->status);

        echo "\n✅ POST /api/payment/ipn - VNPay format success\n";
    }

    /**
     * Test POST /api/payment/ipn - cannot detect provider
     */
    public function test_ipn_returns_400_for_unknown_provider()
    {
        $response = $this->postJson('/api/payment/ipn', [
            'unknown_field' => 'value'
        ]);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['success']);

        echo "\n✅ POST /api/payment/ipn - Returns 400 for unknown provider\n";
    }

    /**
     * Test unauthorized access to create payment
     */
    public function test_create_payment_requires_auth()
    {
        $response = $this->postJson('/api/payment/create', [
            'hocKyId' => $this->hocKyId,
            'provider' => 'momo'
        ]);

        $response->assertStatus(401);

        echo "\n✅ POST /api/payment/create - Requires authentication (401)\n";
    }

    /**
     * Test unauthorized access to payment status
     */
    public function test_payment_status_requires_auth()
    {
        $response = $this->getJson('/api/payment/status?orderId=test');

        $response->assertStatus(401);

        echo "\n✅ GET /api/payment/status - Requires authentication (401)\n";
    }

    /**
     * Test IPN is public (no auth required)
     */
    public function test_ipn_is_public()
    {
        // IPN should be accessible without auth (but may return 400 for invalid data)
        $response = $this->postJson('/api/payment/ipn', [
            'partnerCode' => 'MOMO',
            'orderId' => 'FAKE_ORDER',
            'transId' => 'FAKE_TRANS',
            'resultCode' => 0,
        ]);

        // Should not be 401 (unauthorized)
        $this->assertNotEquals(401, $response->status());

        echo "\n✅ POST /api/payment/ipn - Is public (no auth required)\n";
    }
}
