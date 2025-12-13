<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GhiDanhApiTest extends TestCase
{
    private $token;
    private $testAccount;
    private $testUser;
    private $testSinhVien;
    private $khoaId;

    public function setUp(): void
    {
        parent::setUp();

        // Get an existing khoa for testing
        $khoa = Khoa::first();
        $this->khoaId = $khoa?->id;

        if (!$this->khoaId) {
            $this->markTestSkipped('No khoa found in database');
        }

        // Create test student account
        $this->testAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'gd_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'sinh_vien',
            'trang_thai_hoat_dong' => true,
        ]);

        // Create user profile
        $this->testUser = UserProfile::create([
            'id' => Str::uuid()->toString(),
            'ho_ten' => 'Test GhiDanh SinhVien',
            'email' => 'gdtest' . rand(1000, 9999) . '@student.hcmue.edu.vn',
            'tai_khoan_id' => $this->testAccount->id,
        ]);

        // Create sinh_vien record with same ID as user
        $this->testSinhVien = SinhVien::create([
            'id' => $this->testUser->id,
            'ma_so_sinh_vien' => 'GDTEST' . rand(1000, 9999),
            'khoa_id' => $this->khoaId,
            'lop' => 'GhiDanh Test Class',
            'khoa_hoc' => '2024-2025',
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);
    }

    public function tearDown(): void
    {
        // Clean up test data
        if ($this->testSinhVien) {
            // Clean up any ghi danh records
            \App\Infrastructure\SinhVien\Persistence\Models\GhiDanhHocPhan::where('sinh_vien_id', $this->testSinhVien->id)->delete();
            $this->testSinhVien->delete();
        }
        if ($this->testUser) {
            $this->testUser->delete();
        }
        if ($this->testAccount) {
            $this->testAccount->delete();
        }
        parent::tearDown();
    }

    /**
     * Test GET /api/sv/check-ghi-danh
     */
    public function test_can_check_ghi_danh()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/sv/check-ghi-danh');

        // Should return 200 (can enroll) or 400 (cannot enroll - no active period)
        $this->assertContains($response->status(), [200, 400]);

        $data = $response->json();
        $this->assertArrayHasKey('isSuccess', $data);
        $this->assertArrayHasKey('message', $data);

        echo "\n✅ GET /api/sv/check-ghi-danh - Status: {$response->status()}\n";
        echo "Message: {$data['message']}\n";
    }

    /**
     * Test GET /api/sv/mon-hoc-ghi-danh
     */
    public function test_can_get_mon_hoc_ghi_danh()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/sv/mon-hoc-ghi-danh');

        // Should return 200 or 400 (no current semester)
        $this->assertContains($response->status(), [200, 400]);

        $data = $response->json();
        $this->assertArrayHasKey('isSuccess', $data);

        if ($response->status() === 200) {
            $this->assertTrue($data['isSuccess']);
            $this->assertIsArray($data['data']);

            // Check structure if data exists
            if (count($data['data']) > 0) {
                $this->assertArrayHasKey('id', $data['data'][0]);
                $this->assertArrayHasKey('maMonHoc', $data['data'][0]);
                $this->assertArrayHasKey('tenMonHoc', $data['data'][0]);
                $this->assertArrayHasKey('soTinChi', $data['data'][0]);
            }
        }

        echo "\n✅ GET /api/sv/mon-hoc-ghi-danh - Status: {$response->status()}\n";
    }

    /**
     * Test POST /api/sv/ghi-danh - Missing monHocId
     */
    public function test_ghi_danh_requires_mon_hoc_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/sv/ghi-danh', []);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);
        $this->assertStringContainsString('không hợp lệ', strtolower($data['message']));

        echo "\n✅ POST /api/sv/ghi-danh requires monHocId (400 returned)\n";
    }

    /**
     * Test POST /api/sv/ghi-danh - Invalid monHocId
     */
    public function test_ghi_danh_invalid_mon_hoc_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/sv/ghi-danh', [
                'monHocId' => Str::uuid()->toString()  // Random non-existent ID
            ]);

        $response->assertStatus(404);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ POST /api/sv/ghi-danh with invalid ID returns 404\n";
    }

    /**
     * Test GET /api/sv/ghi-danh/my
     */
    public function test_can_get_da_ghi_danh()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/sv/ghi-danh/my');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/sv/ghi-danh/my - Found {data['data'] count} enrollments\n";
    }

    /**
     * Test unauthorized access (no token)
     */
    public function test_unauthorized_access_returns_401()
    {
        $endpoints = [
            '/api/sv/check-ghi-danh',
            '/api/sv/mon-hoc-ghi-danh',
            '/api/sv/ghi-danh/my',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertEquals(401, $response->status(), "Expected 401 for {$endpoint}");
        }

        echo "\n✅ All enrollment endpoints return 401 without authentication\n";
    }

    /**
     * Test PDT user cannot access SV enrollment endpoints
     */
    public function test_pdt_user_cannot_access_ghi_danh_endpoints()
    {
        // Create PDT account
        $pdtAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'pdt_gd_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'phong_dao_tao',
            'trang_thai_hoat_dong' => true,
        ]);

        $pdtToken = JWTAuth::fromUser($pdtAccount);

        $response = $this->withHeader('Authorization', 'Bearer ' . $pdtToken)
            ->getJson('/api/sv/check-ghi-danh');

        $response->assertStatus(403);

        // Cleanup
        $pdtAccount->delete();

        echo "\n✅ PDT user cannot access ghi-danh endpoints (403 Forbidden)\n";
    }
}
