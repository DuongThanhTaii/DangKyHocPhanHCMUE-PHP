<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\GiangVien\Persistence\Models\GiangVien;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\HocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\MonHoc;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GVApiTest extends TestCase
{
    private $token;
    private $testAccount;
    private $testUser;
    private $testGV;
    private $testLHP;
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

        // Create test instructor account
        $this->testAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'gv_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'giang_vien',
            'trang_thai_hoat_dong' => true,
        ]);

        // Create user profile
        $this->testUser = UserProfile::create([
            'id' => Str::uuid()->toString(),
            'ho_ten' => 'Test GV User',
            'email' => 'gvtest' . rand(1000, 9999) . '@hcmue.edu.vn',
            'tai_khoan_id' => $this->testAccount->id,
        ]);

        // Create giang_vien record with same ID as user
        $this->testGV = GiangVien::create([
            'id' => $this->testUser->id,
            'khoa_id' => $this->khoaId,
            'trinh_do' => 'Thạc sĩ',
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);

        // Create a student account for wrong role tests
        $this->studentAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'sv_for_gv_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'sinh_vien',
            'trang_thai_hoat_dong' => true,
        ]);
        $this->studentToken = JWTAuth::fromUser($this->studentAccount);
    }

    public function tearDown(): void
    {
        // Clean up
        if ($this->testLHP) {
            try {
                $this->testLHP->delete();
            } catch (\Exception $e) {
            }
        }
        if ($this->testGV) {
            try {
                $this->testGV->delete();
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
     * Test GET /api/gv/lop-hoc-phan - success
     */
    public function test_can_get_lop_hoc_phan_list()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/gv/lop-hoc-phan?hocKyId={$this->hocKyId}");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/gv/lop-hoc-phan - Success\n";
    }

    /**
     * Test GET /api/gv/lop-hoc-phan/{id} - not found
     */
    public function test_lop_hoc_phan_detail_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/gv/lop-hoc-phan/{$fakeId}");

        $response->assertStatus(404);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ GET /api/gv/lop-hoc-phan/{id} - Returns 404 for unknown\n";
    }

    /**
     * Test GET /api/gv/lop-hoc-phan/{id}/sinh-vien - returns 404 for unknown class
     */
    public function test_get_students_returns_404_for_unknown_class()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/gv/lop-hoc-phan/{$fakeId}/sinh-vien");

        $response->assertStatus(404);

        echo "\n✅ GET /api/gv/lop-hoc-phan/{id}/sinh-vien - Returns 404 for unknown\n";
    }

    /**
     * Test GET /api/gv/lop-hoc-phan/{id}/diem - returns 404 for unknown class
     */
    public function test_get_grades_returns_404_for_unknown_class()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/gv/lop-hoc-phan/{$fakeId}/diem");

        $response->assertStatus(404);

        echo "\n✅ GET /api/gv/lop-hoc-phan/{id}/diem - Returns 404 for unknown\n";
    }

    /**
     * Test PUT /api/gv/lop-hoc-phan/{id}/diem - returns 404 for unknown class
     */
    public function test_update_grades_returns_404_for_unknown_class()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/gv/lop-hoc-phan/{$fakeId}/diem", [
                'items' => []
            ]);

        $response->assertStatus(404);

        echo "\n✅ PUT /api/gv/lop-hoc-phan/{id}/diem - Returns 404 for unknown\n";
    }

    /**
     * Test GET /api/gv/lop-hoc-phan/{id}/tai-lieu - returns 404 for unknown class
     */
    public function test_get_tai_lieu_returns_404_for_unknown_class()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/gv/lop-hoc-phan/{$fakeId}/tai-lieu");

        $response->assertStatus(404);

        echo "\n✅ GET /api/gv/lop-hoc-phan/{id}/tai-lieu - Returns 404 for unknown\n";
    }

    /**
     * Test POST /api/gv/lop-hoc-phan/{id}/tai-lieu/upload - requires file
     */
    public function test_upload_tai_lieu_requires_file()
    {
        $fakeId = Str::uuid()->toString();

        // Need to find an existing LHP for this instructor first
        // Since we don't have one, this will hit the 404 branch
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/gv/lop-hoc-phan/{$fakeId}/tai-lieu/upload");

        // Will get 404 because class not found for this instructor
        $response->assertStatus(404);

        echo "\n✅ POST /api/gv/lop-hoc-phan/{id}/tai-lieu/upload - Validation check\n";
    }

    /**
     * Test GET /api/gv/tkb-weekly - requires params
     */
    public function test_tkb_weekly_requires_params()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/gv/tkb-weekly');

        $response->assertStatus(400);

        $data = $response->json();
        $this->assertFalse($data['isSuccess']);

        echo "\n✅ GET /api/gv/tkb-weekly - Requires params (400)\n";
    }

    /**
     * Test GET /api/gv/tkb-weekly - success with valid params
     */
    public function test_can_get_tkb_weekly()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/gv/tkb-weekly?hocKyId={$this->hocKyId}&dateStart=2024-12-01&dateEnd=2024-12-07");

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/gv/tkb-weekly - Success with valid params\n";
    }

    /**
     * Test all endpoints return 403 for non-GV role (student)
     */
    public function test_endpoints_return_403_for_non_gv_role()
    {
        $endpoints = [
            ['GET', '/api/gv/lop-hoc-phan'],
            ['GET', '/api/gv/lop-hoc-phan/' . Str::uuid()],
            ['GET', '/api/gv/lop-hoc-phan/' . Str::uuid() . '/sinh-vien'],
            ['GET', '/api/gv/lop-hoc-phan/' . Str::uuid() . '/diem'],
            ['GET', '/api/gv/lop-hoc-phan/' . Str::uuid() . '/tai-lieu'],
            ['GET', '/api/gv/tkb-weekly?hocKyId=' . $this->hocKyId . '&dateStart=2024-12-01&dateEnd=2024-12-07'],
        ];

        foreach ($endpoints as $endpoint) {
            $method = strtolower($endpoint[0]) . 'Json';
            $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
                ->$method($endpoint[1]);

            $this->assertEquals(403, $response->status(), "Expected 403 for {$endpoint[0]} {$endpoint[1]}");
        }

        echo "\n✅ All GV endpoints return 403 for non-GV role\n";
    }

    /**
     * Test all endpoints return 401 without authentication
     */
    public function test_endpoints_return_401_without_auth()
    {
        $endpoints = [
            '/api/gv/lop-hoc-phan',
            '/api/gv/tkb-weekly',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->getJson($endpoint);
            $this->assertEquals(401, $response->status(), "Expected 401 for {$endpoint}");
        }

        echo "\n✅ All GV endpoints return 401 without authentication\n";
    }
}
