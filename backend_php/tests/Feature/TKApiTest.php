<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\TK\Persistence\Models\TruongKhoa;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TKApiTest extends TestCase
{
    private $token;
    private $testAccount;
    private $testUser;
    private $testTK;
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

        // Create test TK account
        $this->testAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'tk_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'truong_khoa',
            'trang_thai_hoat_dong' => true,
        ]);

        // Create user profile
        $this->testUser = UserProfile::create([
            'id' => Str::uuid()->toString(),
            'ho_ten' => 'Test TK User',
            'email' => 'tktest' . rand(1000, 9999) . '@hcmue.edu.vn',
            'tai_khoan_id' => $this->testAccount->id,
        ]);

        // Create TruongKhoa record with same ID as user
        $this->testTK = TruongKhoa::create([
            'id' => $this->testUser->id,
            'khoa_id' => $this->khoaId,
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);

        // Create a student account for wrong role tests
        $this->studentAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'sv_for_tk_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'sinh_vien',
            'trang_thai_hoat_dong' => true,
        ]);
        $this->studentToken = JWTAuth::fromUser($this->studentAccount);
    }

    public function tearDown(): void
    {
        // Clean up
        if ($this->testTK) {
            try {
                $this->testTK->delete();
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
     * Test GET /api/tk/de-xuat-hoc-phan - success
     */
    public function test_can_get_de_xuat_hoc_phan()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/tk/de-xuat-hoc-phan?hocKyId={$this->hocKyId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/tk/de-xuat-hoc-phan - Success\n";
    }

    /**
     * Test POST /api/tk/de-xuat-hoc-phan/duyet - requires id
     */
    public function test_duyet_de_xuat_requires_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tk/de-xuat-hoc-phan/duyet', []);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ POST /api/tk/de-xuat-hoc-phan/duyet - Requires id (400)\n";
    }

    /**
     * Test POST /api/tk/de-xuat-hoc-phan/duyet - returns 404 for unknown id
     */
    public function test_duyet_de_xuat_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tk/de-xuat-hoc-phan/duyet', [
                'id' => $fakeId
            ]);

        $response->assertStatus(404);

        echo "\n✅ POST /api/tk/de-xuat-hoc-phan/duyet - Returns 404 for unknown\n";
    }

    /**
     * Test POST /api/tk/de-xuat-hoc-phan/tu-choi - requires id
     */
    public function test_tu_choi_de_xuat_requires_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tk/de-xuat-hoc-phan/tu-choi', []);

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ POST /api/tk/de-xuat-hoc-phan/tu-choi - Requires id (400)\n";
    }

    /**
     * Test POST /api/tk/de-xuat-hoc-phan/tu-choi - returns 404 for unknown id
     */
    public function test_tu_choi_de_xuat_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tk/de-xuat-hoc-phan/tu-choi', [
                'id' => $fakeId,
                'lyDo' => 'Test rejection'
            ]);

        $response->assertStatus(404);

        echo "\n✅ POST /api/tk/de-xuat-hoc-phan/tu-choi - Returns 404 for unknown\n";
    }

    /**
     * Test all endpoints return 403 for non-TK role (student)
     */
    public function test_endpoints_return_403_for_non_tk_role()
    {
        $endpoints = [
            ['GET', '/api/tk/de-xuat-hoc-phan'],
            ['POST', '/api/tk/de-xuat-hoc-phan/duyet'],
            ['POST', '/api/tk/de-xuat-hoc-phan/tu-choi'],
        ];

        foreach ($endpoints as $endpoint) {
            $method = strtolower($endpoint[0]) . 'Json';
            $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
                ->$method($endpoint[1], ['id' => 'test']);

            $this->assertEquals(403, $response->status(), "Expected 403 for {$endpoint[0]} {$endpoint[1]}");
        }

        echo "\n✅ All TK endpoints return 403 for non-TK role\n";
    }

    /**
     * Test all endpoints return 401 without authentication
     */
    public function test_endpoints_return_401_without_auth()
    {
        $response = $this->getJson('/api/tk/de-xuat-hoc-phan');
        $this->assertEquals(401, $response->status());

        $response = $this->postJson('/api/tk/de-xuat-hoc-phan/duyet');
        $this->assertEquals(401, $response->status());

        $response = $this->postJson('/api/tk/de-xuat-hoc-phan/tu-choi');
        $this->assertEquals(401, $response->status());

        echo "\n✅ All TK endpoints return 401 without authentication\n";
    }
}
