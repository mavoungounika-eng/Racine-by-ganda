<?php

namespace Tests\Unit\Services\Queue;

use App\Services\Queue\QueueCircuitBreaker;
use App\Services\Monitoring\AlertService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;
use Carbon\Carbon;

class QueueCircuitBreakerTest extends TestCase
{
    protected QueueCircuitBreaker $circuitBreaker;
    protected $alertServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->alertServiceMock = Mockery::mock(AlertService::class);
        $this->app->instance(AlertService::class, $this->alertServiceMock);

        $this->circuitBreaker = new QueueCircuitBreaker();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function circuit_starts_in_closed_state()
    {
        $isOpen = $this->circuitBreaker->isOpen('default');
        $this->assertFalse($isOpen, 'Circuit should start in CLOSED state');
    }

    #[Test]
    public function circuit_opens_after_threshold_failures()
    {
        $queue = 'default';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold', 10);

        $this->alertServiceMock->shouldReceive('critical')->once();

        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordFailure($queue);
        }

        $this->assertEquals('open', $this->circuitBreaker->getState($queue));
        $this->assertTrue($this->circuitBreaker->isOpen($queue));
    }

    #[Test]
    public function circuit_transitions_to_half_open_after_timeout()
    {
        $queue = 'default';
        $timeout = config('queue-protection.circuit_breaker.timeout', 60);
        $ttl = config('queue-protection.circuit_breaker.ttl', 3600);

        Cache::put("circuit_breaker:{$queue}:state", 'open', $ttl);
        Cache::put(
            "circuit_breaker:{$queue}:opened_at",
            Carbon::now()->subSeconds($timeout + 10)->toIso8601String(),
            $ttl
        );

        $isOpen = $this->circuitBreaker->isOpen($queue);

        $this->assertFalse($isOpen, 'Circuit should transition to HALF_OPEN after timeout');
        $this->assertEquals('half_open', $this->circuitBreaker->getState($queue));
    }

    #[Test]
    public function circuit_closes_after_success_threshold_in_half_open()
    {
        $queue = 'default';
        $threshold = config('queue-protection.circuit_breaker.success_threshold', 5);
        $ttl = config('queue-protection.circuit_breaker.ttl', 3600);

        Cache::put("circuit_breaker:{$queue}:state", 'half_open', $ttl);

        $this->alertServiceMock->shouldReceive('info')->once();

        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordSuccess($queue);
        }

        $this->assertEquals('closed', $this->circuitBreaker->getState($queue));
        $this->assertFalse($this->circuitBreaker->isOpen($queue));
    }

    #[Test]
    public function circuit_reopens_on_failure_in_half_open()
    {
        $queue = 'default';
        $ttl = config('queue-protection.circuit_breaker.ttl', 3600);

        Cache::put("circuit_breaker:{$queue}:state", 'half_open', $ttl);

        // HALF_OPEN → OPEN re-opens silently (Log::warning, no alert)
        $this->alertServiceMock->shouldReceive('critical')->never();

        $this->circuitBreaker->recordFailure($queue);

        $this->assertEquals('open', $this->circuitBreaker->getState($queue));
    }

    #[Test]
    public function record_failure_increments_counter()
    {
        $queue = 'default';

        $this->circuitBreaker->recordFailure($queue);

        $metrics = $this->circuitBreaker->getMetrics($queue);
        $this->assertEquals(1, $metrics['failure_count']);
    }

    #[Test]
    public function record_success_resets_failure_counter()
    {
        $queue = 'default';
        $ttl = config('queue-protection.circuit_breaker.ttl', 3600);

        Cache::put("circuit_breaker:{$queue}:failures", 3, $ttl);

        $this->circuitBreaker->recordSuccess($queue);

        $metrics = $this->circuitBreaker->getMetrics($queue);
        $this->assertEquals(0, $metrics['failure_count']);
    }

    #[Test]
    public function consecutive_failures_trigger_open_state()
    {
        $queue = 'test-queue';
        $threshold = 3;

        config(['queue-protection.circuit_breaker.failure_threshold' => $threshold]);
        $this->circuitBreaker = new QueueCircuitBreaker();

        $this->alertServiceMock->shouldReceive('critical')->once();

        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordFailure($queue);
        }

        $this->assertEquals('open', $this->circuitBreaker->getState($queue));
    }

    #[Test]
    public function state_persists_in_cache()
    {
        $queue = 'persistent-queue';
        $ttl = config('queue-protection.circuit_breaker.ttl', 3600);

        Cache::put("circuit_breaker:{$queue}:state", 'open', $ttl);
        Cache::put("circuit_breaker:{$queue}:opened_at", now()->toIso8601String(), $ttl);

        $this->assertTrue($this->circuitBreaker->isOpen($queue), 'State should persist in cache');
    }

    #[Test]
    public function state_expires_after_ttl()
    {
        $queue = 'expiring-queue';
        $ttl = config('queue-protection.circuit_breaker.ttl', 3600);
        $threshold = config('queue-protection.circuit_breaker.failure_threshold', 10);

        Cache::put("circuit_breaker:{$queue}:failures", $threshold - 1, $ttl);

        $this->alertServiceMock->shouldReceive('critical')->once();

        $this->circuitBreaker->recordFailure($queue);

        $this->assertEquals('open', $this->circuitBreaker->getState($queue));
    }

    #[Test]
    public function multiple_queues_have_independent_states()
    {
        $queue1 = 'queue-1';
        $queue2 = 'queue-2';
        $ttl = config('queue-protection.circuit_breaker.ttl', 3600);

        Cache::put("circuit_breaker:{$queue1}:state", 'open', $ttl);
        Cache::put("circuit_breaker:{$queue1}:opened_at", now()->toIso8601String(), $ttl);

        $this->assertTrue($this->circuitBreaker->isOpen($queue1), 'Queue 1 should be OPEN');
        $this->assertFalse($this->circuitBreaker->isOpen($queue2), 'Queue 2 should be CLOSED');
    }

    #[Test]
    public function reset_clears_state_and_closes_circuit()
    {
        $queue = 'reset-queue';
        $ttl = config('queue-protection.circuit_breaker.ttl', 3600);

        Cache::put("circuit_breaker:{$queue}:state", 'open', $ttl);
        Cache::put("circuit_breaker:{$queue}:failures", 5, $ttl);
        Cache::put("circuit_breaker:{$queue}:opened_at", now()->toIso8601String(), $ttl);

        $this->circuitBreaker->reset($queue);

        $this->assertFalse($this->circuitBreaker->isOpen($queue));
        $this->assertEquals('closed', $this->circuitBreaker->getState($queue));
    }

    #[Test]
    public function alert_sent_when_circuit_opens()
    {
        $queue = 'alert-queue';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold', 10);
        $ttl = config('queue-protection.circuit_breaker.ttl', 3600);

        Cache::put("circuit_breaker:{$queue}:failures", $threshold - 1, $ttl);

        $this->alertServiceMock->shouldReceive('critical')
            ->once()
            ->with(
                Mockery::pattern('/Circuit Breaker Opened/'),
                Mockery::any(),
                Mockery::on(function ($context) use ($queue, $threshold) {
                    return $context['queue'] === $queue
                        && $context['failure_count'] === $threshold;
                })
            );

        $this->circuitBreaker->recordFailure($queue);

        // Mockery verifies ->once() in tearDown; add explicit assertion for PHPUnit
        $this->assertEquals('open', $this->circuitBreaker->getState($queue));
    }
}
