<?php

namespace Tests\Unit\Services\Queue;

use App\Services\Queue\QueueRateLimiter;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;
use Carbon\Carbon;

class QueueRateLimiterTest extends TestCase
{
    protected QueueRateLimiter $rateLimiter;
    protected $redisMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Redis
        $this->redisMock = Mockery::mock('alias:' . Redis::class);

        // Create instance
        $this->rateLimiter = new QueueRateLimiter();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }
    #[Test]
    public function allows_requests_within_limit()
    {
        // Arrange
        $jobType = 'webhooks';
        $limit = config('queue-protection.rate_limits.webhooks.max', 100);

        $this->redisMock->shouldReceive('get')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn($limit - 10); // Well within limit

        $this->redisMock->shouldReceive('incr')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn($limit - 9);

        $this->redisMock->shouldReceive('expire')
            ->with("rate_limiter:{$jobType}:count", Mockery::any())
            ->andReturn(true);

        // Act
        $allowed = $this->rateLimiter->attempt($jobType, function () {
            return true;
        });

        // Assert
        $this->assertTrue($allowed, 'Should allow requests within limit');
    }
    #[Test]
    public function blocks_requests_exceeding_limit()
    {
        // Arrange
        $jobType = 'emails';
        $limit = config('queue-protection.rate_limits.emails.max', 50);

        $this->redisMock->shouldReceive('get')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn($limit); // At limit

        // Act
        $allowed = $this->rateLimiter->attempt($jobType, function () {
            return true;
        });

        // Assert
        $this->assertFalse($allowed, 'Should block requests exceeding limit');
    }
    #[Test]
    public function limit_resets_after_window()
    {
        // Arrange
        $jobType = 'notifications';
        $window = config('queue-protection.rate_limits.notifications.window', 60);

        // First request - set initial count
        $this->redisMock->shouldReceive('get')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn(null);

        $this->redisMock->shouldReceive('incr')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn(1);

        $this->redisMock->shouldReceive('expire')
            ->with("rate_limiter:{$jobType}:count", $window)
            ->andReturn(true);

        // Act
        $allowed = $this->rateLimiter->attempt($jobType, function () {
            return true;
        });

        // Assert
        $this->assertTrue($allowed, 'Should reset limit after window expires');
    }
    #[Test]
    public function sliding_window_algorithm()
    {
        // Arrange
        $jobType = 'webhooks';
        $currentCount = 50;

        // Simulate sliding window
        $this->redisMock->shouldReceive('get')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn($currentCount);

        $this->redisMock->shouldReceive('incr')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn($currentCount + 1);

        $this->redisMock->shouldReceive('expire')
            ->andReturn(true);

        // Act
        $allowed = $this->rateLimiter->attempt($jobType, function () {
            return true;
        });

        // Assert
        $this->assertTrue($allowed, 'Sliding window should allow gradual requests');
    }
    #[Test]
    public function distributed_rate_limiting_across_workers()
    {
        // Arrange
        $jobType = 'emails';

        // Simulate distributed counter in Redis
        $this->redisMock->shouldReceive('get')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn(25);

        $this->redisMock->shouldReceive('incr')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn(26);

        $this->redisMock->shouldReceive('expire')
            ->andReturn(true);

        // Act
        $allowed = $this->rateLimiter->attempt($jobType, function () {
            return true;
        });

        // Assert
        $this->assertTrue($allowed, 'Should work across distributed workers via Redis');
    }
    #[Test]
    public function webhooks_rate_limit()
    {
        // Arrange
        $jobType = 'webhooks';
        $limit = config('queue-protection.rate_limits.webhooks.max', 100);

        $this->redisMock->shouldReceive('get')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn($limit - 1);

        $this->redisMock->shouldReceive('incr')
            ->andReturn($limit);

        $this->redisMock->shouldReceive('expire')
            ->andReturn(true);

        // Act
        $allowed = $this->rateLimiter->attempt($jobType, function () {
            return true;
        });

        // Assert
        $this->assertTrue($allowed, 'Should respect webhooks rate limit (100/min)');
    }
    #[Test]
    public function emails_rate_limit()
    {
        // Arrange
        $jobType = 'emails';
        $limit = config('queue-protection.rate_limits.emails.max', 50);

        $this->redisMock->shouldReceive('get')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn($limit - 1);

        $this->redisMock->shouldReceive('incr')
            ->andReturn($limit);

        $this->redisMock->shouldReceive('expire')
            ->andReturn(true);

        // Act
        $allowed = $this->rateLimiter->attempt($jobType, function () {
            return true;
        });

        // Assert
        $this->assertTrue($allowed, 'Should respect emails rate limit (50/min)');
    }
    #[Test]
    public function notifications_rate_limit()
    {
        // Arrange
        $jobType = 'notifications';
        $limit = config('queue-protection.rate_limits.notifications.max', 200);

        $this->redisMock->shouldReceive('get')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn($limit - 1);

        $this->redisMock->shouldReceive('incr')
            ->andReturn($limit);

        $this->redisMock->shouldReceive('expire')
            ->andReturn(true);

        // Act
        $allowed = $this->rateLimiter->attempt($jobType, function () {
            return true;
        });

        // Assert
        $this->assertTrue($allowed, 'Should respect notifications rate limit (200/min)');
    }
    #[Test]
    public function attempt_executes_callback_when_allowed()
    {
        // Arrange
        $jobType = 'webhooks';
        $callbackExecuted = false;

        $this->redisMock->shouldReceive('get')
            ->andReturn(10);

        $this->redisMock->shouldReceive('incr')
            ->andReturn(11);

        $this->redisMock->shouldReceive('expire')
            ->andReturn(true);

        // Act
        $result = $this->rateLimiter->attempt($jobType, function () use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'success';
        });

        // Assert
        $this->assertTrue($callbackExecuted, 'Callback should be executed when allowed');
        $this->assertEquals('success', $result, 'Should return callback result');
    }
    #[Test]
    public function attempt_returns_false_when_rate_limited()
    {
        // Arrange
        $jobType = 'emails';
        $limit = config('queue-protection.rate_limits.emails.max', 50);
        $callbackExecuted = false;

        $this->redisMock->shouldReceive('get')
            ->andReturn($limit + 10); // Over limit

        // Act
        $result = $this->rateLimiter->attempt($jobType, function () use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'success';
        });

        // Assert
        $this->assertFalse($callbackExecuted, 'Callback should not be executed when rate limited');
        $this->assertFalse($result, 'Should return false when rate limited');
    }
    #[Test]
    public function clear_resets_rate_limiter()
    {
        // Arrange
        $jobType = 'webhooks';

        $this->redisMock->shouldReceive('del')
            ->with("rate_limiter:{$jobType}:count")
            ->once()
            ->andReturn(1);

        // Act
        $this->rateLimiter->clear($jobType);

        // Assert - After clear, should allow requests
        $this->redisMock->shouldReceive('get')
            ->with("rate_limiter:{$jobType}:count")
            ->andReturn(null);

        $this->redisMock->shouldReceive('incr')
            ->andReturn(1);

        $this->redisMock->shouldReceive('expire')
            ->andReturn(true);

        $allowed = $this->rateLimiter->attempt($jobType, function () {
            return true;
        });

        $this->assertTrue($allowed, 'Should allow requests after clear');
    }
}
