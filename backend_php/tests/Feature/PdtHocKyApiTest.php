<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\SinhVien\Persistence\Models\KyPhase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PdtHocKyApiTest extends TestCase
{
    private $token;
    private $testAccount;
    private $hocKyId;
    private $studentToken;
    private $studentAccount;

    public function setUp(): void
    {
        parent::setUp();

        // Get or create a test semester
        $hocKy = HocKy::where('trang_thai_hien_tai', true)->first();
        if (!$hocKy) {
            $hocKy = HocKy::first();
        }
        $this->hocKyId = $hocKy?->id;

        if (!$this->hocKyId) {
            $this->markTestSkipped('No hoc_ky found in database');
        }

        // Create test PDT account
        $this->testAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'pdt_hocky_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'phong_dao_tao',
            'trang_thai_hoat_dong' => true,
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);

        // Create student account for wrong role tests
        $this->studentAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'sv_for_pdt_hocky_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'sinh_vien',
            'trang_thai_hoat_dong' => true,
        ]);
        $this->studentToken = JWTAuth::fromUser($this->studentAccount);
    }

    public function tearDown(): void
    {
        if ($this->testAccount) {
            try {
                $this->testAccount->delete();
            } catch (\Exception $e) {
            }
        }
        if ($this->studentAccount) {
            try {
                $this->studentAccount->delete();
            } catch (\Exception $e) {
            }
        }
        parent::tearDown();
    }

    /**
     * Test POST /api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh - requires hocKyId
     */
    public function test_set_hoc_ky_hien_hanh_requires_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh', []);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ POST /api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh - Requires hocKyId (400)\n";
    }

    /**
     * Test POST /api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh - success
     */
    public function test_can_set_hoc_ky_hien_hanh()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh', [
                'hocKyId' => $this->hocKyId
            ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);

        echo "\n✅ POST /api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh - Success\n";
    }

    /**
     * Test POST /api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh - 404 for unknown
     */
    public function test_set_hoc_ky_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh', [
                'hocKyId' => $fakeId
            ]);

        $response->assertStatus(404);

        echo "\n✅ POST /api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh - Returns 404 for unknown\n";
    }

    /**
     * Test POST /api/pdt/quan-ly-hoc-ky/ky-phase/bulk - requires phases
     */
    public function test_create_bulk_ky_phase_requires_phases()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/quan-ly-hoc-ky/ky-phase/bulk', [
                'hocKyId' => $this->hocKyId,
                'hocKyStartAt' => '2024-09-01',
                'hocKyEndAt' => '2024-12-31',
                // Missing phases
            ]);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/quan-ly-hoc-ky/ky-phase/bulk - Requires phases (400)\n";
    }

    /**
     * Test GET /api/pdt/quan-ly-hoc-ky/ky-phase/{hocKyId} - success
     */
    public function test_can_get_phases_by_hoc_ky()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/pdt/quan-ly-hoc-ky/ky-phase/{$this->hocKyId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/pdt/quan-ly-hoc-ky/ky-phase/{hocKyId} - Success\n";
    }

    /**
     * Test all endpoints return 403 for non-PDT role
     */
    public function test_endpoints_return_403_for_non_pdt_role()
    {
        $endpoints = [
            ['POST', '/api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh', ['hocKyId' => $this->hocKyId]],
            ['GET', '/api/pdt/quan-ly-hoc-ky/ky-phase/' . $this->hocKyId, []],
        ];

        foreach ($endpoints as $endpoint) {
            $method = strtolower($endpoint[0]) . 'Json';
            $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
                ->$method($endpoint[1], $endpoint[2]);

            $this->assertEquals(403, $response->status(), "Expected 403 for {$endpoint[0]} {$endpoint[1]}");
        }

        echo "\n✅ All HocKy/KyPhase endpoints return 403 for non-PDT role\n";
    }

    /**
     * Test all endpoints return 401 without authentication
     */
    public function test_endpoints_return_401_without_auth()
    {
        $response = $this->postJson('/api/pdt/quan-ly-hoc-ky/hoc-ky-hien-hanh');
        $this->assertEquals(401, $response->status());

        $response = $this->getJson('/api/pdt/quan-ly-hoc-ky/ky-phase/' . $this->hocKyId);
        $this->assertEquals(401, $response->status());

        echo "\n✅ All HocKy/KyPhase endpoints return 401 without authentication\n";
    }
}
