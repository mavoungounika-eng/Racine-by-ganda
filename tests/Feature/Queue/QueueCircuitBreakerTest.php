<?php

namespace Tests\Feature\Queue;

use App\Jobs\Middleware\CircuitBreakerJob;
use App\Services\Queue\QueueCircuitBreaker;
use App\Services\Queue\QueueMonitor;
use App\Exceptions\CircuitBreakerOpenException;
use App\Services\Monitoring\AlertService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use Mockery;

class QueueCircuitBreakerTest extends TestCase
{
    protected QueueCircuitBreaker $circuitBreaker;
    protected QueueMonitor $monitor;
    protected string $testQueue = 'test-queue';

    protected function setUp(): void
    {
        parent::setUp();

        // Clear Cache for test queue
        Cache::forget("circuit_breaker:{$this->testQueue}:state");
        Cache::forget("circuit_breaker:{$this->testQueue}:failures");
        Cache::forget("circuit_breaker:{$this->testQueue}:successes");
        Cache::forget("circuit_breaker:{$this->testQueue}:opened_at");
        Cache::forget("circuit_breaker:{$this->testQueue}:retries");

        $this->circuitBreaker = app(QueueCircuitBreaker::class);
        $this->monitor = app(QueueMonitor::class);
    }

    /** @test */
    public function it_remains_closed_when_jobs_succeed()
    {
        $middleware = new CircuitBreakerJob($this->circuitBreaker, $this->monitor);
        $job = new class { public $queue = 'test-queue'; };

        $middleware->handle($job, function ($job) {
            // Success
        });

        $this->assertEquals('closed', $this->circuitBreaker->getState($this->testQueue));
    }

    /** @test */
    public function it_opens_after_reaching_failure_threshold()
    {
        $threshold = config('queue-protection.circuit_breaker.failure_threshold', 10);
        $middleware = new CircuitBreakerJob($this->circuitBreaker, $this->monitor);
        $job = new class { public $queue = 'test-queue'; };

        $alertMock = Mockery::mock(AlertService::class);
        $alertMock->shouldReceive('critical')->once();
        $this->app->instance(AlertService::class, $alertMock);

        for ($i = 0; $i < $threshold; $i++) {
            try {
                $middleware->handle($job, function ($job) {
                    throw new \Exception('Test failure');
                });
            } catch (\Exception $e) {
                // Expected
            }
        }

        $this->assertEquals('open', $this->circuitBreaker->getState($this->testQueue));
    }

    /** @test */
    public function it_rejects_jobs_when_open()
    {
        // Force OPEN state via Cache
        Cache::put("circuit_breaker:{$this->testQueue}:state", 'open', 3600);
        Cache::put("circuit_breaker:{$this->testQueue}:opened_at", now()->toIso8601String(), 3600);

        $middleware = new CircuitBreakerJob($this->circuitBreaker, $this->monitor);
        $job = new class { public $queue = 'test-queue'; };

        $this->expectException(CircuitBreakerOpenException::class);

        $middleware->handle($job, function ($job) {
            // Should not be called
        });
    }

    /** @test */
    public function it_transitions_to_half_open_after_timeout()
    {
        $timeout = config('queue-protection.circuit_breaker.timeout', 60);

        // Force OPEN state with old timestamp
        Cache::put("circuit_breaker:{$this->testQueue}:state", 'open', 3600);
        Cache::put("circuit_breaker:{$this->testQueue}:opened_at", now()->subSeconds($timeout + 1)->toIso8601String(), 3600);

        $this->assertFalse($this->circuitBreaker->isOpen($this->testQueue));
        $this->assertEquals('half_open', $this->circuitBreaker->getState($this->testQueue));
    }

    /** @test */
    public function it_closes_after_success_threshold_in_half_open()
    {
        $threshold = config('queue-protection.circuit_breaker.success_threshold', 5);
        $middleware = new CircuitBreakerJob($this->circuitBreaker, $this->monitor);
        $job = new class { public $queue = 'test-queue'; };

        // Force HALF_OPEN
        Cache::put("circuit_breaker:{$this->testQueue}:state", 'half_open', 3600);

        for ($i = 0; $i < $threshold; $i++) {
            $middleware->handle($job, function ($job) {
                // Success
            });

            if ($i < $threshold - 1) {
                $this->assertEquals('half_open', $this->circuitBreaker->getState($this->testQueue));
            }
        }

        $this->assertEquals('closed', $this->circuitBreaker->getState($this->testQueue));
    }

    /** @test */
    public function it_reopens_if_job_fails_in_half_open()
    {
        $middleware = new CircuitBreakerJob($this->circuitBreaker, $this->monitor);
        $job = new class { public $queue = 'test-queue'; };

        // Force HALF_OPEN
        Cache::put("circuit_breaker:{$this->testQueue}:state", 'half_open', 3600);

        try {
            $middleware->handle($job, function ($job) {
                throw new \Exception('Test failure in half-open');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertEquals('open', $this->circuitBreaker->getState($this->testQueue));
    }

    /** @test */
    public function it_applies_exponential_backoff_on_repeated_half_open_failures()
    {
        $baseTimeout = config('queue-protection.circuit_breaker.timeout', 60);
        $middleware = new CircuitBreakerJob($this->circuitBreaker, $this->monitor);
        $job = new class { public $queue = 'test-queue'; };

        // 1. Force HALF_OPEN and fail
        Cache::put("circuit_breaker:{$this->testQueue}:state", 'half_open', 3600);
        try {
            $middleware->handle($job, function ($job) { throw new \Exception('Fail 1'); });
        } catch (\Exception $e) {}

        // Should be OPEN with retry_count = 1
        $this->assertEquals(1, Cache::get("circuit_breaker:{$this->testQueue}:retries"));
        $this->assertEquals($baseTimeout * 2, $this->circuitBreaker->getMetrics($this->testQueue)['current_timeout']);

        // 2. Fail again in HALF_OPEN (force state first)
        Cache::put("circuit_breaker:{$this->testQueue}:state", 'half_open', 3600);
        try {
            $middleware->handle($job, function ($job) { throw new \Exception('Fail 2'); });
        } catch (\Exception $e) {}

        // Should be OPEN with retry_count = 2
        $this->assertEquals(2, Cache::get("circuit_breaker:{$this->testQueue}:retries"));
        $this->assertEquals($baseTimeout * 4, $this->circuitBreaker->getMetrics($this->testQueue)['current_timeout']);

        // 3. Success in HALF_OPEN then CLOSE should reset retries
        Cache::put("circuit_breaker:{$this->testQueue}:state", 'half_open', 3600);
        $successThreshold = config('queue-protection.circuit_breaker.success_threshold', 5);
        for ($i = 0; $i < $successThreshold; $i++) {
            $middleware->handle($job, function ($job) {});
        }

        $this->assertEquals('closed', $this->circuitBreaker->getState($this->testQueue));
        $this->assertNull(Cache::get("circuit_breaker:{$this->testQueue}:retries"));
        $this->assertEquals($baseTimeout, $this->circuitBreaker->getMetrics($this->testQueue)['current_timeout']);
    }
}
