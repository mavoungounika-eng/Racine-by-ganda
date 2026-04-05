<?php

namespace Tests\Integration\Services\Queue;

use App\Services\Queue\QueueRateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\IntegrationTestCase;

/**
 * QueueRateLimiterIntegrationTest
 * 
 * Tests d'intégration pour QueueRateLimiter avec Redis réel
 */
class QueueRateLimiterIntegrationTest extends IntegrationTestCase
{
    protected QueueRateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rateLimiter = app(QueueRateLimiter::class);
    }
    #[Test]
    public function allows_requests_within_limit(): void
    {
        // Arrange
        $jobType = 'webhooks';
        $limit = config('queue-protection.rate_limits.webhooks'); // "100/minute"
        [$maxAttempts, ] = $this->parseLimit($limit);

        // Act - Make requests under limit
        $results = [];
        for ($i = 0; $i < $maxAttempts - 10; $i++) {
            $results[] = $this->rateLimiter->attempt($jobType);
        }

        // Assert
        $this->assertContainsOnly('bool', $results);
        $this->assertNotContains(false, $results, 'All requests should be allowed within limit');
        $this->assertGreaterThan(0, $this->rateLimiter->remaining($jobType));
    }
    #[Test]
    public function blocks_requests_exceeding_limit(): void
    {
        // Arrange
        $jobType = 'emails';
        $limit = config('queue-protection.rate_limits.emails'); // "50/minute"
        [$maxAttempts, ] = $this->parseLimit($limit);

        // Act - Exhaust limit
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->rateLimiter->attempt($jobType);
        }

        // Try one more (should be blocked)
        $blocked = $this->rateLimiter->attempt($jobType);

        // Assert
        $this->assertFalse($blocked, 'Request should be blocked after exceeding limit');
        $this->assertEquals(0, $this->rateLimiter->remaining($jobType));
    }
    #[Test]
    public function counter_increments_correctly(): void
    {
        // Arrange
        $jobType = 'notifications';
        $key = "rate_limiter:queue:{$jobType}";

        // Act
        $this->rateLimiter->attempt($jobType);
        $this->rateLimiter->attempt($jobType);
        $this->rateLimiter->attempt($jobType);

        // Assert
        $count = (int) $this->getRedisValue($key);
        $this->assertEquals(3, $count, 'Counter should increment with each attempt');
    }
    #[Test]
    public function counter_resets_after_window(): void
    {
        // Arrange
        $jobType = 'webhooks';
        $key = "rate_limiter:queue:{$jobType}";

        // Act - Make some requests
        $this->rateLimiter->attempt($jobType);
        $this->rateLimiter->attempt($jobType);
        
        $initialCount = (int) $this->getRedisValue($key);
        $this->assertEquals(2, $initialCount);

        // Simulate window expiration by deleting key
        $this->flushTestRedis();

        // Make new request
        $this->rateLimiter->attempt($jobType);
        $newCount = (int) $this->getRedisValue($key);

        // Assert - Counter should reset
        $this->assertEquals(1, $newCount, 'Counter should reset after window expires');
    }
    #[Test]
    public function metrics_are_accurate(): void
    {
        // Arrange
        $jobType = 'webhooks';
        $limit = config('queue-protection.rate_limits.webhooks');
        [$maxAttempts, ] = $this->parseLimit($limit);

        // Act - Make 5 requests
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimiter->attempt($jobType);
        }

        $metrics = $this->rateLimiter->getMetrics($jobType);

        // Assert
        $this->assertArrayHasKey('job_type', $metrics);
        $this->assertArrayHasKey('current', $metrics);
        $this->assertArrayHasKey('remaining', $metrics);
        $this->assertArrayHasKey('max_attempts', $metrics);
        
        $this->assertEquals($jobType, $metrics['job_type']);
        $this->assertEquals(5, $metrics['current']);
        $this->assertEquals($maxAttempts - 5, $metrics['remaining']);
        $this->assertEquals($maxAttempts, $metrics['max_attempts']);
    }
    #[Test]
    public function clear_resets_rate_limiter(): void
    {
        // Arrange
        $jobType = 'emails';
        $limit = config('queue-protection.rate_limits.emails');
        [$maxAttempts, ] = $this->parseLimit($limit);

        // Exhaust limit
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->rateLimiter->attempt($jobType);
        }
        $this->assertEquals(0, $this->rateLimiter->remaining($jobType));

        // Act - Clear
        $this->rateLimiter->clear($jobType);

        // Assert - Should be able to make requests again
        $this->assertTrue($this->rateLimiter->attempt($jobType));
        $this->assertGreaterThan(0, $this->rateLimiter->remaining($jobType));
    }
    #[Test]
    public function multiple_job_types_are_independent(): void
    {
        // Arrange
        $jobType1 = 'webhooks';
        $jobType2 = 'emails';
        $limit1 = config('queue-protection.rate_limits.webhooks');
        [$maxAttempts1, ] = $this->parseLimit($limit1);

        // Act - Exhaust webhooks limit
        for ($i = 0; $i < $maxAttempts1; $i++) {
            $this->rateLimiter->attempt($jobType1);
        }

        // Assert - Webhooks blocked, emails still allowed
        $this->assertFalse($this->rateLimiter->attempt($jobType1), 'Webhooks should be blocked');
        $this->assertTrue($this->rateLimiter->attempt($jobType2), 'Emails should still be allowed');
    }
    #[Test]
    public function default_limit_applied_for_unknown_job_type(): void
    {
        // Arrange
        $jobType = 'unknown-job-type';

        // Act
        $allowed = $this->rateLimiter->attempt($jobType);
        $metrics = $this->rateLimiter->getMetrics($jobType);

        // Assert - Should use default limit
        $this->assertTrue($allowed, 'Unknown job type should use default limit');
        $this->assertArrayHasKey('limit', $metrics);
        $this->assertEquals(config('queue-protection.rate_limits.default'), $metrics['limit']);
    }

    /**
     * Helper to parse limit string
     */
    private function parseLimit(string $limit): array
    {
        [$maxAttempts, $period] = explode('/', $limit);
        
        $decaySeconds = match ($period) {
            'second' => 1,
            'minute' => 60,
            'hour' => 3600,
            'day' => 86400,
            default => 60,
        };
        
        return [(int) $maxAttempts, $decaySeconds];
    }
}
