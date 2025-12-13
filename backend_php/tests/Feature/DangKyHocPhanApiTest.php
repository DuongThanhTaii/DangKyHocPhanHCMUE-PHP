<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DangKyHocPhanApiTest extends TestCase
{
    private $token;
    private $testAccount;
    private $testUser;
    private $testSinhVien;
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

        // Create test student account
        $this->testAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'dkhp_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'sinh_vien',
            'trang_thai_hoat_dong' => true,
        ]);

        // Create user profile
        $this->testUser = UserProfile::create([
            'id' => Str::uuid()->toString(),
            'ho_ten' => 'Test DKHP SinhVien',
            'email' => 'dkhptest' . rand(1000, 9999) . '@student.hcmue.edu.vn',
            'tai_khoan_id' => $this->testAccount->id,
        ]);

        // Create sinh_vien record with same ID as user
        $this->testSinhVien = SinhVien::create([
            'id' => $this->testUser->id,
            'ma_so_sinh_vien' => 'DKHP' . rand(1000, 9999),
            'khoa_id' => $this->khoaId,
            'lop' => 'DKHP Test Class',
            'khoa_hoc' => '2024-2025',
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);
    }

    public function tearDown(): void
    {
        // Clean up test data
        if ($this->testSinhVien) {
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
     * Test GET /api/sv/check-phase-dang-ky
     */
    public function test_can_check_phase_dang_ky()
    {
        if (!$this->hocKyId) {
            $this->markTestSkipped('No current semester found');
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/sv/check-phase-dang-ky?hocKyId={$this->hocKyId}");

        // Should return 200 (phase open) or 400 (no phase)
        $this->assertContains($response->status(), [200, 400]);

        $data = $response->json();
        $this->assertArrayHasKey('isSuccess', $data);

        echo "\n✅ GET /api/sv/check-phase-dang-ky - Status: {$response->status()}\n";
    }

    /**
     * Test GET /api/sv/check-phase-dang-ky requires hocKyId
     */
    public function test_check_phase_requires_hoc_ky_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/sv/check-phase-dang-ky');

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ GET /api/sv/check-phase-dang-ky requires hocKyId (400)\n";
    }

    /**
     * Test GET /api/sv/lop-hoc-phan
     */
    public function test_can_get_lop_hoc_phan()
    {
        if (!$this->hocKyId) {
            $this->markTestSkipped('No current semester found');
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/sv/lop-hoc-phan?hocKyId={$this->hocKyId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/sv/lop-hoc-phan - Found " . count($data['data']) . " classes\n";
    }

    /**
     * Test GET /api/sv/lop-da-dang-ky
     */
    public function test_can_get_lop_da_dang_ky()
    {
        if (!$this->hocKyId) {
            $this->markTestSkipped('No current semester found');
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/sv/lop-da-dang-ky?hocKyId={$this->hocKyId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/sv/lop-da-dang-ky - Success\n";
    }

    /**
     * Test POST /api/sv/dang-ky-hoc-phan requires params
     */
    public function test_dang_ky_hoc_phan_requires_params()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/sv/dang-ky-hoc-phan', []);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ POST /api/sv/dang-ky-hoc-phan requires params (400)\n";
    }

    /**
     * Test POST /api/sv/huy-dang-ky-hoc-phan requires lopHocPhanId
     */
    public function test_huy_dang_ky_requires_lop_hoc_phan_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/sv/huy-dang-ky-hoc-phan', []);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ POST /api/sv/huy-dang-ky-hoc-phan requires lopHocPhanId (400)\n";
    }

    /**
     * Test POST /api/sv/chuyen-lop-hoc-phan requires params
     */
    public function test_chuyen_lop_requires_params()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/sv/chuyen-lop-hoc-phan', []);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ POST /api/sv/chuyen-lop-hoc-phan requires params (400)\n";
    }

    /**
     * Test GET /api/sv/lich-su-dang-ky
     */
    public function test_can_get_lich_su_dang_ky()
    {
        if (!$this->hocKyId) {
            $this->markTestSkipped('No current semester found');
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/sv/lich-su-dang-ky?hocKyId={$this->hocKyId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/sv/lich-su-dang-ky - Success\n";
    }

    /**
     * Test GET /api/sv/tkb-weekly requires params
     */
    public function test_tkb_weekly_requires_params()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/sv/tkb-weekly');

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ GET /api/sv/tkb-weekly requires params (400)\n";
    }

    /**
     * Test GET /api/sv/tkb-weekly with params
     */
    public function test_can_get_tkb_weekly()
    {
        if (!$this->hocKyId) {
            $this->markTestSkipped('No current semester found');
        }

        $today = now()->format('Y-m-d');
        $nextWeek = now()->addWeek()->format('Y-m-d');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/sv/tkb-weekly?hocKyId={$this->hocKyId}&dateStart={$today}&dateEnd={$nextWeek}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/sv/tkb-weekly - Success\n";
    }

    /**
     * Test GET /api/sv/tra-cuu-hoc-phan
     */
    public function test_can_tra_cuu_hoc_phan()
    {
        if (!$this->hocKyId) {
            $this->markTestSkipped('No current semester found');
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/sv/tra-cuu-hoc-phan?hocKyId={$this->hocKyId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/sv/tra-cuu-hoc-phan - Found " . count($data['data']) . " items\n";
    }

    /**
     * Test GET /api/sv/hoc-phi
     */
    public function test_can_get_hoc_phi()
    {
        if (!$this->hocKyId) {
            $this->markTestSkipped('No current semester found');
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/sv/hoc-phi?hocKyId={$this->hocKyId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertArrayHasKey('tongSoTinChi', $data['data']);
        $this->assertArrayHasKey('tongHocPhi', $data['data']);

        echo "\n✅ GET /api/sv/hoc-phi - Total credits: {$data['data']['tongSoTinChi']}\n";
    }

    /**
     * Test GET /api/sv/lop-da-dang-ky/tai-lieu
     */
    public function test_can_get_tai_lieu_lop_da_dang_ky()
    {
        if (!$this->hocKyId) {
            $this->markTestSkipped('No current semester found');
        }

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/sv/lop-da-dang-ky/tai-lieu?hocKyId={$this->hocKyId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/sv/lop-da-dang-ky/tai-lieu - Success\n";
    }

    /**
     * Test unauthorized access returns 401
     */
    public function test_unauthorized_access_returns_401()
    {
        $endpoints = [
            '/api/sv/check-phase-dang-ky?hocKyId=test',
            '/api/sv/lop-hoc-phan?hocKyId=test',
            '/api/sv/lop-da-dang-ky?hocKyId=test',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertEquals(401, $response->status(), "Expected 401 for {$endpoint}");
        }

        echo "\n✅ All course registration endpoints return 401 without authentication\n";
    }
}
