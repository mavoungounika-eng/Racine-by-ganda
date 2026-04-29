<?php

namespace Tests\Unit\Services\Queue;

use App\Services\Queue\QueueMonitor;
use App\Services\Queue\QueueCircuitBreaker;
use App\Services\Queue\QueueRateLimiter;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class QueueMonitorTest extends TestCase
{
    protected QueueMonitor $monitor;
    protected $circuitBreakerMock;
    protected $rateLimiterMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->circuitBreakerMock = Mockery::mock(QueueCircuitBreaker::class);
        $this->rateLimiterMock = Mockery::mock(QueueRateLimiter::class);

        $this->monitor = new QueueMonitor($this->circuitBreakerMock, $this->rateLimiterMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Set up Redis mocks required for collect() / checkThresholds() calls.
     */
    private function mockRedisForCollect(int $queueSize = 10, array $processingTimes = [], int $total = 0, int $failed = 0): void
    {
        Redis::shouldReceive('llen')->andReturn($queueSize);
        Redis::shouldReceive('lrange')->andReturn(array_map('strval', $processingTimes));
        Redis::shouldReceive('get')
            ->with(Mockery::pattern('/total_jobs/'))
            ->andReturn($total ?: null);
        Redis::shouldReceive('get')
            ->with(Mockery::pattern('/failed_jobs/'))
            ->andReturn($failed ?: null);
        Redis::shouldReceive('keys')->andReturn([]);
    }

    private function mockDependenciesForCollect(string $cbState = 'closed'): void
    {
        $this->circuitBreakerMock->shouldReceive('getMetrics')
            ->andReturn([
                'state' => $cbState,
                'failure_count' => 0,
                'success_count' => 0,
                'opened_at' => null,
                'failure_threshold' => 10,
                'success_threshold' => 5,
                'timeout' => 60,
                'current_timeout' => 60,
                'retry_count' => 0,
            ]);
        $this->rateLimiterMock->shouldReceive('getMetrics')
            ->andReturn([
                'job_type' => 'default',
                'limit' => '500/minute',
                'max_attempts' => 500,
                'decay_seconds' => 60,
                'current' => 0,
                'remaining' => 500,
                'available_in' => 0,
            ]);
    }

    #[Test]
    public function collects_queue_size()
    {
        $expectedSize = 42;
        $this->mockRedisForCollect(queueSize: $expectedSize);
        $this->mockDependenciesForCollect();

        $metrics = $this->monitor->collect();

        $this->assertArrayHasKey('default', $metrics);
        $this->assertEquals($expectedSize, $metrics['default']['queue_size']);
    }

    #[Test]
    public function collects_processing_time()
    {
        $this->mockRedisForCollect();
        $this->mockDependenciesForCollect();

        $metrics = $this->monitor->collect();

        $this->assertArrayHasKey('default', $metrics);
        $this->assertArrayHasKey('processing_time', $metrics['default']);
        $this->assertArrayHasKey('avg', $metrics['default']['processing_time']);
        $this->assertArrayHasKey('p95', $metrics['default']['processing_time']);
    }

    #[Test]
    public function collects_failure_rate()
    {
        $this->mockRedisForCollect();
        $this->mockDependenciesForCollect();

        $metrics = $this->monitor->collect();

        $this->assertArrayHasKey('default', $metrics);
        $this->assertArrayHasKey('failure_rate', $metrics['default']);
        $this->assertIsFloat($metrics['default']['failure_rate']);
        $this->assertGreaterThanOrEqual(0, $metrics['default']['failure_rate']);
    }

    #[Test]
    public function collects_circuit_breaker_state()
    {
        $this->mockRedisForCollect();
        $this->mockDependenciesForCollect(cbState: 'open');

        $metrics = $this->monitor->collect();

        $this->assertArrayHasKey('default', $metrics);
        $this->assertEquals('open', $metrics['default']['circuit_breaker']['state']);
    }

    #[Test]
    public function exports_prometheus_format()
    {
        $this->mockRedisForCollect(queueSize: 10);
        $this->mockDependenciesForCollect();

        $prometheus = $this->monitor->exportPrometheus();

        $this->assertIsString($prometheus);
        $this->assertStringContainsString('queue_size{queue=', $prometheus);
        $this->assertStringContainsString('circuit_breaker_state{queue=', $prometheus);
    }

    #[Test]
    public function prometheus_metrics_syntax_valid()
    {
        $this->mockRedisForCollect(queueSize: 25);
        $this->mockDependenciesForCollect();

        $prometheus = $this->monitor->exportPrometheus();
        $lines = array_filter(explode("\n", trim($prometheus)));

        $this->assertNotEmpty($lines);
        foreach ($lines as $line) {
            // Each line: metric_name{labels} value
            $this->assertMatchesRegularExpression(
                '/^[a-z_0-9]+\{[^}]+\}\s+[-\d.]+$/',
                $line,
                "Invalid Prometheus format: {$line}"
            );
        }
    }

    #[Test]
    public function detects_queue_size_warning()
    {
        $warningThreshold = config('queue-protection.monitoring.thresholds.queue_size.warning', 500);

        Redis::shouldReceive('llen')->andReturn($warningThreshold + 10);
        Redis::shouldReceive('lrange')->andReturn([]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('keys')->andReturn([]);

        $alerts = $this->monitor->checkThresholds();

        $sizeAlerts = array_filter($alerts, fn($a) => $a['metric'] === 'queue_size');
        $this->assertNotEmpty($sizeAlerts);

        $severities = array_column(array_values($sizeAlerts), 'severity');
        $this->assertContains('warning', $severities);
    }

    #[Test]
    public function detects_queue_size_critical()
    {
        $criticalThreshold = config('queue-protection.monitoring.thresholds.queue_size.critical', 1000);

        Redis::shouldReceive('llen')->andReturn($criticalThreshold + 100);
        Redis::shouldReceive('lrange')->andReturn([]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('keys')->andReturn([]);

        $alerts = $this->monitor->checkThresholds();

        $sizeAlerts = array_filter($alerts, fn($a) => $a['metric'] === 'queue_size');
        $this->assertNotEmpty($sizeAlerts);

        $severities = array_column(array_values($sizeAlerts), 'severity');
        $this->assertContains('critical', $severities);
    }

    #[Test]
    public function detects_processing_time_warning()
    {
        $criticalP95 = config('queue-protection.monitoring.thresholds.processing_time.critical', 10.0);

        // Return processing times that result in p95 above critical
        $times = array_fill(0, 100, (string) ($criticalP95 + 5.0));
        Redis::shouldReceive('llen')->andReturn(10);
        Redis::shouldReceive('lrange')->andReturn($times);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('keys')->andReturn([]);

        $alerts = $this->monitor->checkThresholds();

        $timeAlerts = array_filter($alerts, fn($a) => $a['metric'] === 'processing_time_p95');
        $this->assertNotEmpty($timeAlerts);
    }

    #[Test]
    public function detects_failure_rate_critical()
    {
        $criticalRate = config('queue-protection.monitoring.thresholds.failure_rate.critical', 0.10);

        Redis::shouldReceive('llen')->andReturn(10);
        Redis::shouldReceive('lrange')->andReturn([]);
        // Simulate high failure rate: 50 total, 20 failed = 40%
        Redis::shouldReceive('get')
            ->with(Mockery::pattern('/total_jobs/'))
            ->andReturn('50');
        Redis::shouldReceive('get')
            ->with(Mockery::pattern('/failed_jobs/'))
            ->andReturn('30');
        Redis::shouldReceive('keys')->andReturn([]);

        $alerts = $this->monitor->checkThresholds();

        $rateAlerts = array_filter($alerts, fn($a) => $a['metric'] === 'failure_rate');
        $this->assertNotEmpty($rateAlerts);
        $this->assertEquals('critical', array_values($rateAlerts)[0]['severity']);
    }

    #[Test]
    public function generates_alerts_for_threshold_violations()
    {
        $criticalSize = config('queue-protection.monitoring.thresholds.queue_size.critical', 1000);

        Redis::shouldReceive('llen')->andReturn($criticalSize + 500);
        Redis::shouldReceive('lrange')->andReturn([]);
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('keys')->andReturn([]);

        $alerts = $this->monitor->checkThresholds();

        $this->assertIsArray($alerts);
        $this->assertNotEmpty($alerts);
        $this->assertArrayHasKey('severity', $alerts[0]);
        $this->assertArrayHasKey('metric', $alerts[0]);
    }
}
