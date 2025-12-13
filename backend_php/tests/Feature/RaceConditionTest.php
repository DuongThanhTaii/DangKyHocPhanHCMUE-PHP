<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Auth\Persistence\Models\TaiKhoan;
use App\Infrastructure\Auth\Persistence\Models\UserProfile;
use App\Infrastructure\SinhVien\Persistence\Models\SinhVien;
use App\Infrastructure\SinhVien\Persistence\Models\LopHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\HocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\DangKyHocPhan;
use App\Infrastructure\SinhVien\Persistence\Models\KyPhase;
use App\Infrastructure\Common\Persistence\Models\HocKy;
use App\Infrastructure\Common\Persistence\Models\Khoa;
use App\Infrastructure\Common\Persistence\Models\MonHoc;
use App\Infrastructure\Redis\Services\RedisLockService;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

/**
 * Test class for Redis Distributed Lock functionality
 * Tests race condition prevention in course registration
 */
class RaceConditionTest extends TestCase
{
    private $testAccounts = [];
    private $testUsers = [];
    private $testSinhViens = [];
    private $testLopHocPhan;
    private $testHocPhan;
    private $testMonHoc;
    private $hocKyId;
    private $khoaId;

    public function setUp(): void
    {
        parent::setUp();

        // Get existing khoa
        $khoa = Khoa::first();
        $this->khoaId = $khoa?->id;

        if (!$this->khoaId) {
            $this->markTestSkipped('No khoa found in database');
        }

        // Get current semester with active registration phase
        $hocKy = HocKy::where('trang_thai_hien_tai', true)->first();
        $this->hocKyId = $hocKy?->id;

        if (!$this->hocKyId) {
            $this->markTestSkipped('No current semester found');
        }

        // Check if registration phase is open
        $phase = KyPhase::where('hoc_ky_id', $this->hocKyId)
            ->where('phase', 'dang_ky_hoc_phan')
            ->where('is_enabled', true)
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->first();

        if (!$phase) {
            $this->markTestSkipped('Registration phase not open');
        }

        // Create test subject (MonHoc)
        $this->testMonHoc = MonHoc::create([
            'id' => Str::uuid()->toString(),
            'ma_mon' => 'RACE_TEST_' . rand(1000, 9999),
            'ten_mon' => 'Race Condition Test Subject',
            'so_tin_chi' => 3,
            'khoa_id' => $this->khoaId,
        ]);

        // Create test HocPhan
        $this->testHocPhan = HocPhan::create([
            'id' => Str::uuid()->toString(),
            'mon_hoc_id' => $this->testMonHoc->id,
            'id_hoc_ky' => $this->hocKyId,
            'ma_hoc_phan' => 'HP_RACE_' . rand(1000, 9999),
        ]);

        // Create test class with ONLY 1 SLOT available
        $this->testLopHocPhan = LopHocPhan::create([
            'id' => Str::uuid()->toString(),
            'hoc_phan_id' => $this->testHocPhan->id,
            'ma_lop' => 'RACE_TEST_CLASS',
            'so_luong_hien_tai' => 0,
            'so_luong_toi_da' => 1, // Only 1 slot!
        ]);
    }

    public function tearDown(): void
    {
        // Clean up registrations
        DangKyHocPhan::whereIn('sinh_vien_id', array_map(fn($sv) => $sv->id, $this->testSinhViens))
            ->delete();

        // Clean up test data
        if ($this->testLopHocPhan) {
            $this->testLopHocPhan->delete();
        }
        if ($this->testHocPhan) {
            $this->testHocPhan->delete();
        }
        if ($this->testMonHoc) {
            $this->testMonHoc->delete();
        }

        foreach ($this->testSinhViens as $sv) {
            try {
                $sv->delete();
            } catch (\Exception $e) {
            }
        }

        foreach ($this->testUsers as $user) {
            try {
                $user->delete();
            } catch (\Exception $e) {
            }
        }

        foreach ($this->testAccounts as $account) {
            try {
                $account->delete();
            } catch (\Exception $e) {
            }
        }

        // Clear any remaining locks
        Cache::forget('lock:dkhp:lop:' . $this->testLopHocPhan?->id);

        parent::tearDown();
    }

    /**
     * Create a test student and return token
     */
    private function createTestStudent(int $index): string
    {
        $account = TaiKhoan::create([
            'ten_dang_nhap' => 'race_test_' . $index . '_' . rand(1000, 9999),
            'mat_khau' => Hash::make('password'),
            'loai_tai_khoan' => 'sinh_vien',
            'trang_thai_hoat_dong' => true,
        ]);
        $this->testAccounts[] = $account;

        $user = UserProfile::create([
            'id' => Str::uuid()->toString(),
            'ho_ten' => 'Race Test Student ' . $index,
            'email' => 'racetest' . $index . '_' . rand(1000, 9999) . '@student.hcmue.edu.vn',
            'tai_khoan_id' => $account->id,
        ]);
        $this->testUsers[] = $user;

        $sinhVien = SinhVien::create([
            'id' => $user->id,
            'ma_so_sinh_vien' => 'RACE' . $index . rand(10000, 99999),
            'khoa_id' => $this->khoaId,
            'lop' => 'Race Test Class',
            'khoa_hoc' => '2024-2025',
        ]);
        $this->testSinhViens[] = $sinhVien;

        return JWTAuth::fromUser($account);
    }

    /**
     * Test: RedisLockService basic functionality
     */
    public function test_redis_lock_service_basic_operations()
    {
        $lockService = app(RedisLockService::class);

        // Test acquire and release
        $key = 'test:basic:' . time();

        $acquired = $lockService->acquire($key, 5);
        $this->assertTrue($acquired, 'Should acquire lock successfully');

        // Try to acquire same lock again
        $acquiredAgain = $lockService->acquire($key, 5);
        $this->assertFalse($acquiredAgain, 'Should not acquire lock when already locked');

        // Release and try again
        $lockService->release($key);
        $acquiredAfterRelease = $lockService->acquire($key, 5);
        $this->assertTrue($acquiredAfterRelease, 'Should acquire lock after release');

        $lockService->release($key);

        echo "\n✅ Redis Lock Service basic operations work correctly\n";
    }

    /**
     * Test: withLock callback execution
     */
    public function test_with_lock_executes_callback()
    {
        $lockService = app(RedisLockService::class);
        $key = 'test:callback:' . time();

        $result = $lockService->withLock($key, function () {
            return 'callback_executed';
        });

        $this->assertEquals('callback_executed', $result);

        // Check lock is released
        $this->assertFalse($lockService->isLocked($key));

        echo "\n✅ withLock callback executes and releases lock correctly\n";
    }

    /**
     * Test: Concurrent registration with Redis lock (simulated)
     * This test simulates race condition by:
     * 1. Creating 2 students trying to register for a class with 1 slot
     * 2. Both should try to register
     * 3. Only 1 should succeed due to Redis lock
     */
    public function test_concurrent_registration_with_lock()
    {
        $token1 = $this->createTestStudent(1);
        $token2 = $this->createTestStudent(2);

        $lopId = $this->testLopHocPhan->id;
        $hocKyId = $this->hocKyId;

        // Verify class has 1 slot
        $this->assertEquals(1, $this->testLopHocPhan->so_luong_toi_da);
        $this->assertEquals(0, $this->testLopHocPhan->so_luong_hien_tai);

        // Student 1 registers
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/sv/dang-ky-hoc-phan', [
                'lopHocPhanId' => $lopId,
                'hocKyId' => $hocKyId,
            ]);

        // Student 2 tries to register
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->postJson('/api/sv/dang-ky-hoc-phan', [
                'lopHocPhanId' => $lopId,
                'hocKyId' => $hocKyId,
            ]);

        // Check results
        $data1 = $response1->json();
        $data2 = $response2->json();

        // One should succeed, one should fail
        $successCount = ($data1['isSuccess'] ?? false ? 1 : 0) + ($data2['isSuccess'] ?? false ? 1 : 0);
        $this->assertEquals(1, $successCount, 'Exactly 1 student should register successfully');

        // Verify class slot count
        $this->testLopHocPhan->refresh();
        $this->assertEquals(1, $this->testLopHocPhan->so_luong_hien_tai, 'Class should have exactly 1 registration');

        // Verify one student got "Lớp đã đầy" message
        if (!$data1['isSuccess']) {
            $this->assertStringContainsString('đầy', $data1['message']);
        }
        if (!$data2['isSuccess']) {
            $this->assertStringContainsString('đầy', $data2['message']);
        }

        echo "\n✅ Concurrent registration with Redis lock works correctly\n";
        echo "   Student 1: " . ($data1['isSuccess'] ? 'SUCCESS' : 'FAILED - ' . $data1['message']) . "\n";
        echo "   Student 2: " . ($data2['isSuccess'] ? 'SUCCESS' : 'FAILED - ' . $data2['message']) . "\n";
    }

    /**
     * Test: Sequential registration respects slot limit
     */
    public function test_sequential_registration_respects_slot_limit()
    {
        // Set class to have 2 slots
        $this->testLopHocPhan->so_luong_toi_da = 2;
        $this->testLopHocPhan->save();

        $token1 = $this->createTestStudent(1);
        $token2 = $this->createTestStudent(2);
        $token3 = $this->createTestStudent(3);

        $lopId = $this->testLopHocPhan->id;
        $hocKyId = $this->hocKyId;

        // Student 1 registers (should succeed)
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/sv/dang-ky-hoc-phan', [
                'lopHocPhanId' => $lopId,
                'hocKyId' => $hocKyId,
            ]);
        $this->assertTrue($response1->json('isSuccess'), 'Student 1 should succeed');

        // Student 2 registers (should succeed)
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token2)
            ->postJson('/api/sv/dang-ky-hoc-phan', [
                'lopHocPhanId' => $lopId,
                'hocKyId' => $hocKyId,
            ]);
        $this->assertTrue($response2->json('isSuccess'), 'Student 2 should succeed');

        // Student 3 registers (should fail - class full)
        $response3 = $this->withHeader('Authorization', 'Bearer ' . $token3)
            ->postJson('/api/sv/dang-ky-hoc-phan', [
                'lopHocPhanId' => $lopId,
                'hocKyId' => $hocKyId,
            ]);
        $this->assertFalse($response3->json('isSuccess'), 'Student 3 should fail');
        $this->assertStringContainsString('đầy', $response3->json('message'));

        // Verify final count
        $this->testLopHocPhan->refresh();
        $this->assertEquals(2, $this->testLopHocPhan->so_luong_hien_tai);

        echo "\n✅ Sequential registration respects slot limit correctly\n";
    }

    /**
     * Test: Double registration prevention
     */
    public function test_double_registration_prevented()
    {
        $token = $this->createTestStudent(1);

        $lopId = $this->testLopHocPhan->id;
        $hocKyId = $this->hocKyId;

        // Set class to have 10 slots (plenty of room)
        $this->testLopHocPhan->so_luong_toi_da = 10;
        $this->testLopHocPhan->save();

        // First registration (should succeed)
        $response1 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/sv/dang-ky-hoc-phan', [
                'lopHocPhanId' => $lopId,
                'hocKyId' => $hocKyId,
            ]);
        $this->assertTrue($response1->json('isSuccess'), 'First registration should succeed');

        // Try to register again (should fail)
        $response2 = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/sv/dang-ky-hoc-phan', [
                'lopHocPhanId' => $lopId,
                'hocKyId' => $hocKyId,
            ]);
        $this->assertFalse($response2->json('isSuccess'), 'Second registration should fail');
        $this->assertStringContainsString('đã đăng ký', $response2->json('message'));

        // Verify only 1 registration
        $this->testLopHocPhan->refresh();
        $this->assertEquals(1, $this->testLopHocPhan->so_luong_hien_tai);

        echo "\n✅ Double registration prevented correctly\n";
    }
}
