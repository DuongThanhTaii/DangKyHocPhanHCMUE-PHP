<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\SinhVien\Persistence\Models\DotDangKy;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PdtDotDangKyApiTest extends TestCase
{
    private $token;
    private $testAccount;
    private $hocKyId;
    private $studentToken;
    private $studentAccount;

    public function setUp(): void
    {
        parent::setUp();

        $hocKy = HocKy::where('trang_thai_hien_tai', true)->first() ?? HocKy::first();
        $this->hocKyId = $hocKy?->id;

        if (!$this->hocKyId) {
            $this->markTestSkipped('No hoc_ky found in database');
        }

        $this->testAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'pdt_dotdangky_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'phong_dao_tao',
            'trang_thai_hoat_dong' => true,
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);

        $this->studentAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'sv_dotdangky_test_' . rand(1000, 9999),
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

    public function test_can_get_dot_dang_ky_list()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/pdt/dot-dang-ky');

        $response->assertStatus(200);
        $this->assertTrue($response->json('isSuccess'));
        $this->assertIsArray($response->json('data'));

        echo "\n✅ GET /api/pdt/dot-dang-ky - Success\n";
    }

    public function test_can_get_dot_dang_ky_by_hoc_ky()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/pdt/dot-dang-ky/{$this->hocKyId}");

        $response->assertStatus(200);
        $this->assertTrue($response->json('isSuccess'));

        echo "\n✅ GET /api/pdt/dot-dang-ky/{hocKyId} - Success\n";
    }

    public function test_update_dot_ghi_danh_requires_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/dot-ghi-danh/update', []);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/dot-ghi-danh/update - Requires id (400)\n";
    }

    public function test_update_dot_ghi_danh_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/dot-ghi-danh/update', [
                'id' => $fakeId
            ]);

        $response->assertStatus(404);

        echo "\n✅ POST /api/pdt/dot-ghi-danh/update - Returns 404 for unknown\n";
    }

    public function test_can_get_khoa_list()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/pdt/khoa');

        $response->assertStatus(200);
        $this->assertTrue($response->json('isSuccess'));
        $this->assertIsArray($response->json('data'));

        echo "\n✅ GET /api/pdt/khoa - Success\n";
    }

    public function test_endpoints_return_403_for_non_pdt_role()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/pdt/dot-dang-ky');

        $this->assertEquals(403, $response->status());

        echo "\n✅ DotDangKy/Khoa endpoints return 403 for non-PDT role\n";
    }

    public function test_endpoints_return_401_without_auth()
    {
        $response = $this->getJson('/api/pdt/dot-dang-ky');
        $this->assertEquals(401, $response->status());

        $response = $this->getJson('/api/pdt/khoa');
        $this->assertEquals(401, $response->status());

        echo "\n✅ DotDangKy/Khoa endpoints return 401 without authentication\n";
    }
}
