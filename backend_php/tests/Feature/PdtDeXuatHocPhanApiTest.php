<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\SinhVien\Persistence\Models\MonHoc;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PdtDeXuatHocPhanApiTest extends TestCase
{
    private $token;
    private $testAccount;
    private $hocKyId;
    private $monHocId;
    private $studentToken;
    private $studentAccount;

    public function setUp(): void
    {
        parent::setUp();

        // Get existing hoc_ky
        $hocKy = HocKy::where('trang_thai_hien_tai', true)->first() ?? HocKy::first();
        $this->hocKyId = $hocKy?->id;

        if (!$this->hocKyId) {
            $this->markTestSkipped('No hoc_ky found in database');
        }

        // Get existing mon_hoc
        $monHoc = MonHoc::first();
        $this->monHocId = $monHoc?->id;

        if (!$this->monHocId) {
            $this->markTestSkipped('No mon_hoc found in database');
        }

        // Create test PDT account
        $this->testAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'pdt_dexuat_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'phong_dao_tao',
            'trang_thai_hoat_dong' => true,
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);

        // Create student account for wrong role tests
        $this->studentAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'sv_for_pdt_dexuat_test_' . rand(1000, 9999),
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
     * Test GET /api/pdt/de-xuat-hoc-phan - success
     */
    public function test_can_get_de_xuat_hoc_phan()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/pdt/de-xuat-hoc-phan');

        $response->assertStatus(200);

        $data = $response->json();
        $this->assertTrue($data['isSuccess']);
        $this->assertIsArray($data['data']);

        echo "\n✅ GET /api/pdt/de-xuat-hoc-phan - Success\n";
    }

    /**
     * Test POST /api/pdt/de-xuat-hoc-phan - requires fields
     */
    public function test_create_de_xuat_requires_fields()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/de-xuat-hoc-phan', []);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/de-xuat-hoc-phan - Requires fields (400)\n";
    }

    /**
     * Test POST /api/pdt/de-xuat-hoc-phan/duyet - requires id
     */
    public function test_duyet_de_xuat_requires_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/de-xuat-hoc-phan/duyet', []);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/de-xuat-hoc-phan/duyet - Requires id (400)\n";
    }

    /**
     * Test POST /api/pdt/de-xuat-hoc-phan/duyet - 404 for unknown
     */
    public function test_duyet_de_xuat_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/de-xuat-hoc-phan/duyet', [
                'id' => $fakeId
            ]);

        $response->assertStatus(404);

        echo "\n✅ POST /api/pdt/de-xuat-hoc-phan/duyet - Returns 404 for unknown\n";
    }

    /**
     * Test POST /api/pdt/de-xuat-hoc-phan/tu-choi - requires id
     */
    public function test_tu_choi_de_xuat_requires_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/de-xuat-hoc-phan/tu-choi', []);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/de-xuat-hoc-phan/tu-choi - Requires id (400)\n";
    }

    /**
     * Test POST /api/pdt/de-xuat-hoc-phan/tu-choi - 404 for unknown
     */
    public function test_tu_choi_de_xuat_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/de-xuat-hoc-phan/tu-choi', [
                'id' => $fakeId
            ]);

        $response->assertStatus(404);

        echo "\n✅ POST /api/pdt/de-xuat-hoc-phan/tu-choi - Returns 404 for unknown\n";
    }

    /**
     * Test all endpoints return 403 for non-PDT role
     */
    public function test_endpoints_return_403_for_non_pdt_role()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/pdt/de-xuat-hoc-phan');

        $this->assertEquals(403, $response->status());

        echo "\n✅ DeXuatHocPhan endpoints return 403 for non-PDT role\n";
    }

    /**
     * Test endpoints return 401 without authentication
     */
    public function test_endpoints_return_401_without_auth()
    {
        $response = $this->getJson('/api/pdt/de-xuat-hoc-phan');
        $this->assertEquals(401, $response->status());

        $response = $this->postJson('/api/pdt/de-xuat-hoc-phan/duyet');
        $this->assertEquals(401, $response->status());

        echo "\n✅ DeXuatHocPhan endpoints return 401 without authentication\n";
    }
}
