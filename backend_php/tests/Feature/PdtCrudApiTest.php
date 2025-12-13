<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\SinhVien\Persistence\Models\KyPhase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PdtCrudApiTest extends TestCase
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
            'ten_dang_nhap' => 'pdt_crud_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'phong_dao_tao',
            'trang_thai_hoat_dong' => true,
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);

        $this->studentAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'sv_crud_test_' . rand(1000, 9999),
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

    // MonHoc Tests
    public function test_can_get_mon_hoc_list()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/pdt/mon-hoc');

        $response->assertStatus(200);
        $this->assertTrue($response->json('isSuccess'));

        echo "\n✅ GET /api/pdt/mon-hoc - Success\n";
    }

    public function test_create_mon_hoc_requires_fields()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/mon-hoc', []);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/mon-hoc - Requires fields (400)\n";
    }

    public function test_update_mon_hoc_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/pdt/mon-hoc/{$fakeId}", ['tenMon' => 'Test']);

        $response->assertStatus(404);

        echo "\n✅ PUT /api/pdt/mon-hoc/{id} - Returns 404 for unknown\n";
    }

    public function test_delete_mon_hoc_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/pdt/mon-hoc/{$fakeId}");

        $response->assertStatus(404);

        echo "\n✅ DELETE /api/pdt/mon-hoc/{id} - Returns 404 for unknown\n";
    }

    // GiangVien Tests
    public function test_can_get_giang_vien_list()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/pdt/giang-vien');

        $response->assertStatus(200);
        $this->assertTrue($response->json('isSuccess'));

        echo "\n✅ GET /api/pdt/giang-vien - Success\n";
    }

    public function test_create_giang_vien_requires_fields()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/giang-vien', []);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/giang-vien - Requires fields (400)\n";
    }

    public function test_update_giang_vien_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/pdt/giang-vien/{$fakeId}", ['hoTen' => 'Test']);

        $response->assertStatus(404);

        echo "\n✅ PUT /api/pdt/giang-vien/{id} - Returns 404 for unknown\n";
    }

    public function test_delete_giang_vien_returns_404_for_unknown()
    {
        $fakeId = Str::uuid()->toString();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/pdt/giang-vien/{$fakeId}");

        $response->assertStatus(404);

        echo "\n✅ DELETE /api/pdt/giang-vien/{id} - Returns 404 for unknown\n";
    }

    // Demo Tests
    public function test_toggle_phase_requires_params()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/demo/toggle-phase', []);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/demo/toggle-phase - Requires params (400)\n";
    }

    public function test_reset_data_returns_success()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/demo/reset-data', []);

        $response->assertStatus(200);

        echo "\n✅ POST /api/pdt/demo/reset-data - Success\n";
    }

    // Auth Tests
    public function test_endpoints_return_403_for_non_pdt_role()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/pdt/mon-hoc');

        $this->assertEquals(403, $response->status());

        echo "\n✅ CRUD endpoints return 403 for non-PDT role\n";
    }

    public function test_endpoints_return_401_without_auth()
    {
        $response = $this->getJson('/api/pdt/mon-hoc');
        $this->assertEquals(401, $response->status());

        $response = $this->getJson('/api/pdt/giang-vien');
        $this->assertEquals(401, $response->status());

        echo "\n✅ CRUD endpoints return 401 without authentication\n";
    }
}
