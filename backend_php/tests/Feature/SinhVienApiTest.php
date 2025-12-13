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

class SinhVienApiTest extends TestCase
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
            'ten_dang_nhap' => 'sv_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'sinh_vien',
            'trang_thai_hoat_dong' => true,
        ]);

        // Create user profile
        $this->testUser = UserProfile::create([
            'id' => Str::uuid()->toString(),
            'ho_ten' => 'Test SinhVien',
            'email' => 'svtest' . rand(1000, 9999) . '@student.hcmue.edu.vn',
            'tai_khoan_id' => $this->testAccount->id,
        ]);

        // Create sinh_vien record with same ID as user
        $this->testSinhVien = SinhVien::create([
            'id' => $this->testUser->id,
            'ma_so_sinh_vien' => 'SVTEST' . rand(1000, 9999),
            'khoa_id' => $this->khoaId,
            'lop' => 'Test Class',
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
     * Test GET /api/sv/profile
     */
    public function test_can_get_student_profile()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/sv/profile');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertNotNull($data['data']);

        // Check expected fields
        $this->assertArrayHasKey('id', $data['data']);
        $this->assertArrayHasKey('maSoSinhVien', $data['data']);
        $this->assertArrayHasKey('hoTen', $data['data']);
        $this->assertArrayHasKey('email', $data['data']);
        $this->assertArrayHasKey('khoaId', $data['data']);
        $this->assertArrayHasKey('tenKhoa', $data['data']);

        $this->assertEquals($this->testSinhVien->ma_so_sinh_vien, $data['data']['maSoSinhVien']);

        echo "\n✅ GET /api/sv/profile - Success\n";
        echo "Response: " . json_encode($data['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * Test GET /api/sv/lop-hoc-phan/{id}/tai-lieu - Not enrolled
     */
    public function test_get_tai_lieu_requires_enrollment()
    {
        // Use random UUID (not enrolled)
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/sv/lop-hoc-phan/{$fakeId}/tai-lieu");

        $response->assertStatus(403);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);
        $this->assertStringContainsString('Không có quyền truy cập', $data['message']);

        echo "\n✅ GET /api/sv/lop-hoc-phan/{id}/tai-lieu returns 403 when not enrolled\n";
    }

    /**
     * Test unauthorized access (PDT user accessing SV endpoint)
     */
    public function test_pdt_user_cannot_access_sv_endpoints()
    {
        // Create PDT account
        $pdtAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'pdt_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'phong_dao_tao',
            'trang_thai_hoat_dong' => true,
        ]);

        $pdtToken = JWTAuth::fromUser($pdtAccount);

        $response = $this->withHeader('Authorization', 'Bearer ' . $pdtToken)
            ->getJson('/api/sv/profile');

        $response->assertStatus(403);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);
        $this->assertStringContainsString('sinh viên', $data['message']);

        // Cleanup
        $pdtAccount->delete();

        echo "\n✅ PDT user cannot access SV endpoints (403 Forbidden)\n";
    }

    /**
     * Test unauthorized access (no token)
     */
    public function test_unauthorized_access_returns_401()
    {
        $endpoints = [
            '/api/sv/profile',
            '/api/sv/lop-hoc-phan/fake-id/tai-lieu',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertEquals(401, $response->status(), "Expected 401 for {$endpoint}");
        }

        echo "\n✅ All SV endpoints return 401 without authentication\n";
    }
}
