<?php

namespace Tests\Unit\Services\Queue;

use App\Services\Queue\QueueRateLimiter;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Carbon\Carbon;

class QueueRateLimiterTest extends TestCase
{
    protected QueueRateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->rateLimiter = new QueueRateLimiter();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function allows_requests_within_limit()
    {
        $allowed = $this->rateLimiter->attempt('webhooks');
        $this->assertTrue($allowed, 'Should allow requests within limit');
    }

    #[Test]
    public function blocks_requests_exceeding_limit()
    {
        $jobType = 'emails';
        [$max] = $this->parseLimit(config('queue-protection.rate_limits.emails', '50/minute'));

        Cache::put("rate_limiter:queue:{$jobType}", $max, 60);

        $this->assertFalse($this->rateLimiter->attempt($jobType), 'Should block requests exceeding limit');
    }

    #[Test]
    public function limit_resets_after_window()
    {
        $jobType = 'notifications';

        $this->assertTrue($this->rateLimiter->attempt($jobType));

        Cache::forget("rate_limiter:queue:{$jobType}");

        $this->assertTrue($this->rateLimiter->attempt($jobType), 'Should reset limit after window expires');
    }

    #[Test]
    public function sliding_window_algorithm()
    {
        $jobType = 'webhooks';
        [$max] = $this->parseLimit(config('queue-protection.rate_limits.webhooks', '100/minute'));

        Cache::put("rate_limiter:queue:{$jobType}", $max - 5, 60);

        $this->assertTrue($this->rateLimiter->attempt($jobType), 'Sliding window should allow gradual requests');
    }

    #[Test]
    public function distributed_rate_limiting_across_workers()
    {
        $jobType = 'emails';
        [$max] = $this->parseLimit(config('queue-protection.rate_limits.emails', '50/minute'));

        Cache::put("rate_limiter:queue:{$jobType}", (int) ($max / 2), 60);

        $this->assertTrue($this->rateLimiter->attempt($jobType), 'Should work across distributed workers');
    }

    #[Test]
    public function webhooks_rate_limit()
    {
        $jobType = 'webhooks';
        [$max] = $this->parseLimit(config('queue-protection.rate_limits.webhooks', '100/minute'));

        Cache::put("rate_limiter:queue:{$jobType}", $max, 60);

        $this->assertFalse($this->rateLimiter->attempt($jobType), 'Should respect webhooks rate limit');
    }

    #[Test]
    public function emails_rate_limit()
    {
        $jobType = 'emails';
        [$max] = $this->parseLimit(config('queue-protection.rate_limits.emails', '50/minute'));

        Cache::put("rate_limiter:queue:{$jobType}", $max, 60);

        $this->assertFalse($this->rateLimiter->attempt($jobType), 'Should respect emails rate limit');
    }

    #[Test]
    public function notifications_rate_limit()
    {
        $jobType = 'notifications';
        [$max] = $this->parseLimit(config('queue-protection.rate_limits.notifications', '200/minute'));

        Cache::put("rate_limiter:queue:{$jobType}", $max, 60);

        $this->assertFalse($this->rateLimiter->attempt($jobType), 'Should respect notifications rate limit');
    }

    #[Test]
    public function attempt_returns_true_when_allowed()
    {
        $result = $this->rateLimiter->attempt('webhooks');
        $this->assertTrue($result, 'Should return true when allowed');
    }

    #[Test]
    public function attempt_returns_false_when_rate_limited()
    {
        $jobType = 'emails';
        [$max] = $this->parseLimit(config('queue-protection.rate_limits.emails', '50/minute'));

        Cache::put("rate_limiter:queue:{$jobType}", $max + 10, 60);

        $this->assertFalse($this->rateLimiter->attempt($jobType), 'Should return false when rate limited');
    }

    #[Test]
    public function clear_resets_rate_limiter()
    {
        $jobType = 'webhooks';
        [$max] = $this->parseLimit(config('queue-protection.rate_limits.webhooks', '100/minute'));

        Cache::put("rate_limiter:queue:{$jobType}", $max, 60);
        $this->assertFalse($this->rateLimiter->attempt($jobType));

        $this->rateLimiter->clear($jobType);

        $this->assertTrue($this->rateLimiter->attempt($jobType), 'Should allow requests after clear');
    }

    private function parseLimit(string $limit): array
    {
        [$max, $period] = explode('/', $limit);
        $seconds = match ($period) {
            'second' => 1,
            'minute' => 60,
            'hour'   => 3600,
            default  => 60,
        };
        return [(int) $max, $seconds];
    }
}
