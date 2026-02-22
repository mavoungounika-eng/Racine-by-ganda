<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Redis;

/**
 * IntegrationTestCase - Base class for integration tests
 * 
 * Provides:
 * - Redis test database setup/cleanup
 * - Common helpers for Phase 3 tests
 * - Service instantiation helpers
 */
abstract class IntegrationTestCase extends BaseTestCase
{
    /**
     * Creates the application.
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }


    /**
     * Redis database for tests (isolated from production)
     */
    protected const REDIS_TEST_DB = 15;

    /**
     * Setup before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Switch to test Redis database
        $this->switchToTestRedis();

        // Flush test database
        $this->flushTestRedis();
    }

    /**
     * Cleanup after each test
     */
    protected function tearDown(): void
    {
        // Flush test database
        $this->flushTestRedis();

        parent::tearDown();
    }

    /**
     * Switch Redis connection to test database
     */
    protected function switchToTestRedis(): void
    {
        config(['database.redis.default.database' => self::REDIS_TEST_DB]);
        
        // Reconnect Redis with new config
        Redis::connection()->client()->select(self::REDIS_TEST_DB);
    }

    /**
     * Flush test Redis database
     */
    protected function flushTestRedis(): void
    {
        Redis::connection()->flushdb();
    }

    /**
     * Get a Redis key value
     */
    protected function getRedisValue(string $key): mixed
    {
        return Redis::get($key);
    }

    /**
     * Set a Redis key value
     */
    protected function setRedisValue(string $key, mixed $value, ?int $ttl = null): void
    {
        if ($ttl) {
            Redis::setex($key, $ttl, $value);
        } else {
            Redis::set($key, $value);
        }
    }

    /**
     * Check if Redis key exists
     */
    protected function redisKeyExists(string $key): bool
    {
        return Redis::exists($key) > 0;
    }

    /**
     * Get Redis TTL for a key
     */
    protected function getRedisTtl(string $key): int
    {
        return Redis::ttl($key);
    }

    /**
     * Wait for a condition to be true (polling)
     * 
     * @param callable $condition
     * @param int $timeoutMs Timeout in milliseconds
     * @param int $intervalMs Polling interval in milliseconds
     * @return bool True if condition met, false if timeout
     */
    protected function waitFor(callable $condition, int $timeoutMs = 5000, int $intervalMs = 100): bool
    {
        $start = microtime(true);
        $timeoutSec = $timeoutMs / 1000;
        $intervalSec = $intervalMs / 1000;

        while ((microtime(true) - $start) < $timeoutSec) {
            if ($condition()) {
                return true;
            }
            usleep($intervalMs * 1000);
        }

        return false;
    }

    /**
     * Assert Redis key exists
     */
    protected function assertRedisKeyExists(string $key, string $message = ''): void
    {
        $this->assertTrue(
            $this->redisKeyExists($key),
            $message ?: "Failed asserting that Redis key '{$key}' exists"
        );
    }

    /**
     * Assert Redis key does not exist
     */
    protected function assertRedisKeyNotExists(string $key, string $message = ''): void
    {
        $this->assertFalse(
            $this->redisKeyExists($key),
            $message ?: "Failed asserting that Redis key '{$key}' does not exist"
        );
    }

    /**
     * Assert Redis key has specific value
     */
    protected function assertRedisKeyEquals(string $key, mixed $expected, string $message = ''): void
    {
        $actual = $this->getRedisValue($key);
        $this->assertEquals(
            $expected,
            $actual,
            $message ?: "Failed asserting that Redis key '{$key}' equals '{$expected}', got '{$actual}'"
        );
    }
}
