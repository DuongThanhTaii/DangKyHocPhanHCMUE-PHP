<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PdtChinhSachTinChiApiTest extends TestCase
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
            'ten_dang_nhap' => 'pdt_chinhsach_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'phong_dao_tao',
            'trang_thai_hoat_dong' => true,
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);

        $this->studentAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'sv_chinhsach_test_' . rand(1000, 9999),
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

    public function test_can_get_chinh_sach_list()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/pdt/chinh-sach-tin-chi');

        $response->assertStatus(200);
        $this->assertTrue($response->json('isSuccess'));
        $this->assertIsArray($response->json('data'));

        echo "\n✅ GET /api/pdt/chinh-sach-tin-chi - Success\n";
    }

    public function test_show_chinh_sach_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/pdt/chinh-sach-tin-chi/{$fakeId}");

        $response->assertStatus(404);

        echo "\n✅ GET /api/pdt/chinh-sach-tin-chi/{id} - Returns 404 for unknown\n";
    }

    public function test_create_chinh_sach_requires_fields()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/chinh-sach-tin-chi', []);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/chinh-sach-tin-chi - Requires fields (400)\n";
    }

    public function test_update_chinh_sach_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/pdt/chinh-sach-tin-chi/{$fakeId}", [
                'phiMoiTinChi' => 500000
            ]);

        $response->assertStatus(404);

        echo "\n✅ PUT /api/pdt/chinh-sach-tin-chi/{id} - Returns 404 for unknown\n";
    }

    public function test_delete_chinh_sach_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/pdt/chinh-sach-tin-chi/{$fakeId}");

        $response->assertStatus(404);

        echo "\n✅ DELETE /api/pdt/chinh-sach-tin-chi/{id} - Returns 404 for unknown\n";
    }

    public function test_tinh_toan_hang_loat_requires_hoc_ky_id()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/hoc-phi/tinh-toan-hang-loat', []);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/hoc-phi/tinh-toan-hang-loat - Requires hocKyId (400)\n";
    }

    public function test_endpoints_return_403_for_non_pdt_role()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/pdt/chinh-sach-tin-chi');

        $this->assertEquals(403, $response->status());

        echo "\n✅ ChinhSachTinChi endpoints return 403 for non-PDT role\n";
    }

    public function test_endpoints_return_401_without_auth()
    {
        $response = $this->getJson('/api/pdt/chinh-sach-tin-chi');
        $this->assertEquals(401, $response->status());

        echo "\n✅ ChinhSachTinChi endpoints return 401 without authentication\n";
    }
}
