<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\TLK\Persistence\Models\TroLyKhoa;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TLKApiTest extends TestCase
{
    private $token;
    private $testAccount;
    private $testUser;
    private $testTLK;
    private $khoaId;
    private $hocKyId;
    private $studentToken;
    private $studentAccount;

    public function setUp(): void
    {
        parent::setUp();

        // Get existing khoa
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

        // Create test TLK account
        $this->testAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'tlk_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'tro_ly_khoa',
            'trang_thai_hoat_dong' => true,
        ]);

        // Create user profile
        $this->testUser = UserProfile::create([
            'id' => Str::uuid()->toString(),
            'ho_ten' => 'Test TLK User',
            'email' => 'tlktest' . rand(1000, 9999) . '@hcmue.edu.vn',
            'tai_khoan_id' => $this->testAccount->id,
        ]);

        // Create TroLyKhoa record with same ID as user
        $this->testTLK = TroLyKhoa::create([
            'id' => $this->testUser->id,
            'khoa_id' => $this->khoaId,
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);

        // Create a student account for wrong role tests
        $this->studentAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'sv_for_tlk_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'sinh_vien',
            'trang_thai_hoat_dong' => true,
        ]);
        $this->studentToken = JWTAuth::fromUser($this->studentAccount);
    }

    public function tearDown(): void
    {
        // Clean up
        if ($this->testTLK) {
            try {
                $this->testTLK->delete();
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
        if ($this->studentAccount) {
            try {
                $this->studentAccount->delete();
            } catch (\Exception $e) {
            }
        }
        parent::tearDown();
    }

    /**
     * Test GET /api/tlk/mon-hoc - success
     */
    public function test_can_get_mon_hoc()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/tlk/mon-hoc');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/tlk/mon-hoc - Success\n";
    }

    /**
     * Test GET /api/tlk/giang-vien - success
     */
    public function test_can_get_giang_vien()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/tlk/giang-vien');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/tlk/giang-vien - Success\n";
    }

    /**
     * Test GET /api/tlk/lop-hoc-phan/get-hoc-phan/{hoc_ky_id} - success
     */
    public function test_can_get_hoc_phan_for_semester()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/tlk/lop-hoc-phan/get-hoc-phan/{$this->hocKyId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/tlk/lop-hoc-phan/get-hoc-phan/{hoc_ky_id} - Success\n";
    }

    /**
     * Test GET /api/tlk/phong-hoc - success
     */
    public function test_can_get_phong_hoc()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/tlk/phong-hoc');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/tlk/phong-hoc - Success\n";
    }

    /**
     * Test GET /api/tlk/phong-hoc/available - success
     */
    public function test_can_get_available_phong_hoc()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/tlk/phong-hoc/available');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/tlk/phong-hoc/available - Success\n";
    }

    /**
     * Test GET /api/tlk/de-xuat-hoc-phan - success
     */
    public function test_can_get_de_xuat_hoc_phan()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/tlk/de-xuat-hoc-phan?hocKyId={$this->hocKyId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/tlk/de-xuat-hoc-phan - Success\n";
    }

    /**
     * Test POST /api/tlk/thoi-khoa-bieu/batch - requires params
     */
    public function test_tkb_batch_requires_params()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tlk/thoi-khoa-bieu/batch', []);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ POST /api/tlk/thoi-khoa-bieu/batch - Requires params (400)\n";
    }

    /**
     * Test POST /api/tlk/thoi-khoa-bieu/batch - success with valid data
     */
    public function test_tkb_batch_with_valid_data()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tlk/thoi-khoa-bieu/batch', [
                'maHocPhans' => ['TEST_MON_001'],
                'hocKyId' => $this->hocKyId
            ]);

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);

        echo "\n✅ POST /api/tlk/thoi-khoa-bieu/batch - Success with valid data\n";
    }

    /**
     * Test POST /api/tlk/thoi-khoa-bieu - requires params
     */
    public function test_xep_tkb_requires_params()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tlk/thoi-khoa-bieu', []);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ POST /api/tlk/thoi-khoa-bieu - Requires params (400)\n";
    }

    /**
     * Test all endpoints return 403 for non-TLK role (student)
     */
    public function test_endpoints_return_403_for_non_tlk_role()
    {
        $endpoints = [
            ['GET', '/api/tlk/mon-hoc'],
            ['GET', '/api/tlk/giang-vien'],
            ['GET', '/api/tlk/phong-hoc'],
            ['GET', '/api/tlk/phong-hoc/available'],
            ['GET', '/api/tlk/de-xuat-hoc-phan'],
        ];

        foreach ($endpoints as $endpoint) {
            $method = strtolower($endpoint[0]) . 'Json';
            $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
                ->$method($endpoint[1]);

            $this->assertEquals(403, $response->status(), "Expected 403 for {$endpoint[0]} {$endpoint[1]}");
        }

        echo "\n✅ All TLK endpoints return 403 for non-TLK role\n";
    }

    /**
     * Test all endpoints return 401 without authentication
     */
    public function test_endpoints_return_401_without_auth()
    {
        $endpoints = [
            '/api/tlk/mon-hoc',
            '/api/tlk/giang-vien',
            '/api/tlk/phong-hoc',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertEquals(401, $response->status(), "Expected 401 for {$endpoint}");
        }

        echo "\n✅ All TLK endpoints return 401 without authentication\n";
    }
}
