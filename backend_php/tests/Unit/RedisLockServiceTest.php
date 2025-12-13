<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Infrastructure\Redis\Services\RedisLockService;
use Illuminate\Support\Facades\Cache;

/**
 * Unit tests for RedisLockService
 * These tests don't require database, only Redis
 */
class RedisLockServiceTest extends TestCase
{
    private RedisLockService $lockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lockService = app(RedisLockService::class);
    }

    /**
     * Test basic acquire and release
     */
    public function test_can_acquire_and_release_lock()
    {
        $key = 'test:acquire:' . time() . rand(1000, 9999);

        // Acquire lock
        $acquired = $this->lockService->acquire($key, 5);
        $this->assertTrue($acquired, 'Should acquire lock successfully');

        // Try to acquire same lock again (should fail)
        $acquiredAgain = $this->lockService->acquire($key, 5);
        $this->assertFalse($acquiredAgain, 'Should not acquire already locked key');

        // Release and try again
        $this->lockService->release($key);
        $acquiredAfterRelease = $this->lockService->acquire($key, 5);
        $this->assertTrue($acquiredAfterRelease, 'Should acquire lock after release');

        // Cleanup
        $this->lockService->release($key);

        echo "\n✅ Acquire and release lock works correctly\n";
    }

    /**
     * Test withLock callback execution
     */
    public function test_with_lock_executes_callback()
    {
        $key = 'test:callback:' . time() . rand(1000, 9999);
        $executed = false;

        $result = $this->lockService->withLock($key, function () use (&$executed) {
            $executed = true;
            return 'success';
        });

        $this->assertTrue($executed, 'Callback should be executed');
        $this->assertEquals('success', $result, 'Should return callback result');

        // Lock should be released
        $this->assertFalse($this->lockService->isLocked($key), 'Lock should be released after callback');

        echo "\n✅ withLock executes callback correctly\n";
    }

    /**
     * Test withLock releases lock even on exception
     */
    public function test_with_lock_releases_on_exception()
    {
        $key = 'test:exception:' . time() . rand(1000, 9999);

        try {
            $this->lockService->withLock($key, function () {
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            // Expected
        }

        // Lock should still be released
        $this->assertFalse($this->lockService->isLocked($key), 'Lock should be released even after exception');

        echo "\n✅ withLock releases lock on exception\n";
    }

    /**
     * Test isLocked check
     * Note: This test may be flaky due to Laravel Cache lock internal implementation
     * The isLocked method may not work reliably - it's for debugging only
     */
    public function test_is_locked_after_with_lock_is_released()
    {
        $key = 'test:status:' . time() . rand(1000, 9999);

        // After withLock completes, lock should be released
        $this->lockService->withLock($key, function () {
            return 'done';
        }, 5);

        // Check that lock is not held
        $canAcquire = $this->lockService->acquire($key, 1);
        $this->assertTrue($canAcquire, 'Should be able to acquire lock after withLock completes');

        $this->lockService->release($key);

        echo "\n✅ Lock is properly released after withLock\n";
    }
}
