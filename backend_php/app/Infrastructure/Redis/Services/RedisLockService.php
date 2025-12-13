<?php

namespace App\Infrastructure\Redis\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Redis Distributed Lock Service
 *
 * Provides distributed locking mechanism using Redis to prevent race conditions
 * in concurrent operations like course registration.
 *
 * Usage:
 *   $lockService->withLock("dkhp:lop_123", function() {
 *       // Code inside runs atomically
 *   });
 */
class RedisLockService
{
    private const LOCK_PREFIX = 'lock:';
    private const DEFAULT_TTL = 10; // seconds

    /**
     * Acquire a distributed lock
     *
     * @param string $key Unique identifier for the resource to lock
     * @param int $ttl Time-to-live in seconds (auto-release if not released)
     * @return bool True if lock acquired, false otherwise
     */
    public function acquire(string $key, int $ttl = self::DEFAULT_TTL): bool
    {
        $lockKey = self::LOCK_PREFIX . $key;

        // Use Laravel's atomic lock (works with Redis)
        // Returns true if lock was acquired, false if already locked
        $lock = Cache::lock($lockKey, $ttl);

        return $lock->get();
    }

    /**
     * Release the lock
     *
     * @param string $key The lock key to release
     */
    public function release(string $key): void
    {
        $lockKey = self::LOCK_PREFIX . $key;

        try {
            Cache::lock($lockKey)->forceRelease();
        } catch (\Exception $e) {
            Log::warning("[RedisLock] Failed to release lock {$key}: " . $e->getMessage());
        }
    }

    /**
     * Execute a callback within a distributed lock
     *
     * This method will:
     * 1. Try to acquire the lock
     * 2. Execute the callback if lock acquired
     * 3. Release the lock after callback completes
     * 4. Retry if lock not available (with backoff)
     *
     * @param string $key Unique identifier for the resource to lock
     * @param callable $callback The code to execute while holding the lock
     * @param int $ttl Time-to-live for the lock in seconds
     * @param int $maxRetries Maximum number of retry attempts
     * @param int $retryDelayMs Delay between retries in milliseconds
     * @return mixed The return value of the callback
     * @throws \RuntimeException If lock cannot be acquired after all retries
     */
    public function withLock(
        string $key,
        callable $callback,
        int $ttl = self::DEFAULT_TTL,
        int $maxRetries = 50,
        int $retryDelayMs = 100
    ) {
        $lockKey = self::LOCK_PREFIX . $key;
        $lock = Cache::lock($lockKey, $ttl);

        // Try to acquire lock with retries
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            if ($lock->get()) {
                try {
                    Log::debug("[RedisLock] Acquired lock: {$key}");
                    return $callback();
                } finally {
                    $lock->release();
                    Log::debug("[RedisLock] Released lock: {$key}");
                }
            }

            // Wait before retrying (in microseconds)
            usleep($retryDelayMs * 1000);
        }

        // Failed to acquire lock after all retries
        Log::warning("[RedisLock] Failed to acquire lock after {$maxRetries} retries: {$key}");
        throw new \RuntimeException('Hệ thống đang bận, vui lòng thử lại sau giây lát.');
    }

    /**
     * Check if a lock exists (for debugging/monitoring)
     * Note: This checks if the lock key exists in cache, not if we own it
     *
     * @param string $key The lock key to check
     * @return bool True if lock exists
     */
    public function isLocked(string $key): bool
    {
        $lockKey = self::LOCK_PREFIX . $key;
        // Laravel atomic locks store the owner value, not just existence
        // We need to check if the cache key exists with any value
        return Cache::get($lockKey) !== null;
    }
}
