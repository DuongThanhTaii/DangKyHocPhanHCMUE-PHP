<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

class CommonApiTest extends TestCase
{
    private $token;
    private $testAccount;

    public function setUp(): void
    {
        parent::setUp();

        // Create a test account
        $this->testAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'common_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'phong_dao_tao',
            'trang_thai_hoat_dong' => true,
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);
    }

    public function tearDown(): void
    {
        if ($this->testAccount) {
            $this->testAccount->delete();
        }
        parent::tearDown();
    }

    /**
     * Test GET /api/hoc-ky-hien-hanh
     */
    public function test_can_get_hoc_ky_hien_hanh()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/hoc-ky-hien-hanh');

        // Should return 200 or 404 (if no current semester set)
        $this->assertContains($response->status(), [200, 404]);

        $response->assertJsonStructure([
            'isSuccess',
            'data',
            'message'
        ]);

        echo "\n✅ GET /api/hoc-ky-hien-hanh - Status: {$response->status()}\n";
        echo "Response: " . json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * Test GET /api/hien-hanh (alias)
     */
    public function test_can_get_hien_hanh_alias()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/hien-hanh');

        // Should return 200 or 404 (if no current semester set)
        $this->assertContains($response->status(), [200, 404]);

        $response->assertJsonStructure([
            'isSuccess',
            'data',
            'message'
        ]);

        echo "\n✅ GET /api/hien-hanh - Status: {$response->status()}\n";
    }

    /**
     * Test GET /api/dm/khoa
     */
    public function test_can_get_danh_sach_khoa()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/dm/khoa');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'isSuccess',
            'data',
            'message'
        ]);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        // If data exists, check structure
        if (count($data['data']) > 0) {
            $this->assertArrayHasKey('id', $data['data'][0]);
            $this->assertArrayHasKey('maKhoa', $data['data'][0]);
            $this->assertArrayHasKey('tenKhoa', $data['data'][0]);
        }

        echo "\n✅ GET /api/dm/khoa - Found " . count($data['data']) . " khoa(s)\n";
    }

    /**
     * Test GET /api/dm/nganh
     */
    public function test_can_get_danh_sach_nganh()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/dm/nganh');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'isSuccess',
            'data',
            'message'
        ]);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        // If data exists, check structure
        if (count($data['data']) > 0) {
            $this->assertArrayHasKey('id', $data['data'][0]);
            $this->assertArrayHasKey('maNganh', $data['data'][0]);
            $this->assertArrayHasKey('tenNganh', $data['data'][0]);
        }

        echo "\n✅ GET /api/dm/nganh - Found " . count($data['data']) . " nganh(s)\n";
    }

    /**
     * Test GET /api/dm/nganh with khoa_id filter
     */
    public function test_can_get_danh_sach_nganh_filtered_by_khoa()
    {
        // First get a khoa id
        $khoaResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/dm/khoa');

        $khoaData = $khoaResponse->json();

        if (count($khoaData['data']) > 0) {
            $khoaId = $khoaData['data'][0]['id'];

            $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                ->getJson("/api/dm/nganh?khoa_id={$khoaId}");

            $response->assertStatus(200);
            $data = $response->json();

            // All results should have the same khoa_id
            foreach ($data['data'] as $nganh) {
                $this->assertEquals($khoaId, $nganh['khoaId']);
            }

            echo "\n✅ GET /api/dm/nganh?khoa_id={$khoaId} - Found " . count($data['data']) . " nganh(s)\n";
        } else {
            $this->markTestSkipped('No khoa found to test filtering');
        }
    }

    /**
     * Test unauthorized access
     */
    public function test_unauthorized_access_returns_401()
    {
        $endpoints = [
            '/api/hoc-ky-hien-hanh',
            '/api/hien-hanh',
            '/api/dm/khoa',
            '/api/dm/nganh',
            '/api/hoc-ky/dates',
            '/api/dm/nganh/chua-co-chinh-sach',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertEquals(401, $response->status(), "Expected 401 for {$endpoint}");
        }

        echo "\n✅ All protected endpoints return 401 without authentication\n";
    }

    /**
     * Test GET /api/hoc-ky/dates
     */
    public function test_can_get_hoc_ky_dates()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/hoc-ky/dates');

        // Should return 200 or 404 (if no current semester set)
        $this->assertContains($response->status(), [200, 404]);

        if ($response->status() === 200) {
            $data = $response->json();
            $this->assertTrue($data['isSuccess']);
            $this->assertArrayHasKey('ngayBatDau', $data['data']);
            $this->assertArrayHasKey('ngayKetThuc', $data['data']);
        }

        echo "\n✅ GET /api/hoc-ky/dates - Status: {$response->status()}\n";
    }

    /**
     * Test GET /api/dm/nganh/chua-co-chinh-sach
     */
    public function test_can_get_nganh_chua_co_chinh_sach()
    {
        // First get a khoa and hoc_ky for testing
        $khoaResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/dm/khoa');
        $khoaData = $khoaResponse->json();

        $hocKyResponse = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/hoc-ky-hien-hanh');
        $hocKyData = $hocKyResponse->json();

        if (count($khoaData['data']) > 0 && $hocKyData['data']) {
            $khoaId = $khoaData['data'][0]['id'];
            $hocKyId = $hocKyData['data']['id'];

            $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                ->getJson("/api/dm/nganh/chua-co-chinh-sach?hoc_ky_id={$hocKyId}&khoa_id={$khoaId}");

            $response->assertStatus(200);
            $data = $response->json();
            $this->assertTrue($data['isSuccess']);
            $this->assertIsArray($data['data']);

            echo "\n✅ GET /api/dm/nganh/chua-co-chinh-sach - Found " . count($data['data']) . " nganh(s)\n";
        } else {
            $this->markTestSkipped('No khoa or hoc_ky found to test');
        }
    }

    /**
     * Test GET /api/dm/nganh/chua-co-chinh-sach validation
     */
    public function test_nganh_chua_co_chinh_sach_requires_params()
    {
        // Missing both params
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/dm/nganh/chua-co-chinh-sach');
        $response->assertStatus(400);

        // Missing khoa_id
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/dm/nganh/chua-co-chinh-sach?hoc_ky_id=test');
        $response->assertStatus(400);

        echo "\n✅ GET /api/dm/nganh/chua-co-chinh-sach validation works\n";
    }

    /**
     * Test GET /api/config/tiet-hoc (public endpoint)
     */
    public function test_can_get_config_tiet_hoc()
    {
        // This endpoint is public, no auth required
        $response = $this->getJson('/api/config/tiet-hoc');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['success']);
        $this->assertIsArray($data['data']);
        $this->assertCount(15, $data['data']); // 15 periods

        // Check structure of first period
        $this->assertArrayHasKey('tiet', $data['data'][0]);
        $this->assertArrayHasKey('start', $data['data'][0]);
        $this->assertArrayHasKey('end', $data['data'][0]);

        echo "\n✅ GET /api/config/tiet-hoc - Found " . count($data['data']) . " periods\n";
    }
}
