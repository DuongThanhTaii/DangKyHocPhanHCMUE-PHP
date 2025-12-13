<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PdtPhongHocApiTest extends TestCase
{
    private $token;
    private $testAccount;
    private $khoaId;
    private $studentToken;
    private $studentAccount;

    public function setUp(): void
    {
        parent::setUp();

        $khoa = Khoa::first();
        $this->khoaId = $khoa?->id;

        if (!$this->khoaId) {
            $this->markTestSkipped('No khoa found in database');
        }

        $this->testAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'pdt_phonghoc_test_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'phong_dao_tao',
            'trang_thai_hoat_dong' => true,
        ]);

        $this->token = JWTAuth::fromUser($this->testAccount);

        $this->studentAccount = TaiKhoan::create([
            'ten_dang_nhap' => 'sv_phonghoc_test_' . rand(1000, 9999),
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

    public function test_can_get_available_rooms()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/pdt/phong-hoc/available');

        $response->assertStatus(200);
        $this->assertTrue($response->json('isSuccess'));
        $this->assertIsArray($response->json('data'));

        echo "\n✅ GET /api/pdt/phong-hoc/available - Success\n";
    }

    public function test_can_get_rooms_by_khoa()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/pdt/phong-hoc/khoa/{$this->khoaId}");

        $response->assertStatus(200);
        $this->assertTrue($response->json('isSuccess'));

        echo "\n✅ GET /api/pdt/phong-hoc/khoa/{khoaId} - Success\n";
    }

    public function test_assign_room_requires_params()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/phong-hoc/assign', []);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/phong-hoc/assign - Requires params (400)\n";
    }

    public function test_unassign_room_requires_params()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/pdt/phong-hoc/unassign', []);

        $response->assertStatus(400);

        echo "\n✅ POST /api/pdt/phong-hoc/unassign - Requires params (400)\n";
    }

    public function test_endpoints_return_403_for_non_pdt_role()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/pdt/phong-hoc/available');

        $this->assertEquals(403, $response->status());

        echo "\n✅ PhongHoc endpoints return 403 for non-PDT role\n";
    }

    public function test_endpoints_return_401_without_auth()
    {
        $response = $this->getJson('/api/pdt/phong-hoc/available');
        $this->assertEquals(401, $response->status());

        echo "\n✅ PhongHoc endpoints return 401 without authentication\n";
    }
}
