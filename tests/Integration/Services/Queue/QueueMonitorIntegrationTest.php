<?php

namespace Tests\Integration\Services\Queue;

use App\Services\Queue\QueueMonitor;
use App\Services\Queue\QueueCircuitBreaker;
use App\Services\Queue\QueueRateLimiter;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\IntegrationTestCase;

/**
 * QueueMonitorIntegrationTest
 * 
 * Tests d'intégration pour QueueMonitor avec services réels
 */
class QueueMonitorIntegrationTest extends IntegrationTestCase
{
    protected QueueMonitor $monitor;
    protected QueueCircuitBreaker $circuitBreaker;
    protected QueueRateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock external dependencies
        Http::fake();
        Mail::fake();

        // Use real services
        $this->circuitBreaker = app(QueueCircuitBreaker::class);
        $this->rateLimiter = app(QueueRateLimiter::class);
        $this->monitor = app(QueueMonitor::class);
    }
    #[Test]
    public function collects_queue_size(): void
    {
        // Arrange
        $queue = 'webhooks';
        
        // Simulate queue with items (use rpush directly, no setRedisValue)
        for ($i = 0; $i < 5; $i++) {
            \Illuminate\Support\Facades\Redis::rpush("queues:{$queue}", json_encode(['job' => "job_{$i}"]));
        }

        // Act
        $size = $this->monitor->getQueueSize($queue);

        // Assert
        $this->assertEquals(5, $size, 'Queue size should be 5');
    }
    #[Test]
    public function collects_processing_time_metrics(): void
    {
        // Arrange
        $queue = 'emails';
        $times = [1.5, 2.0, 2.5, 3.0, 5.0];

        foreach ($times as $time) {
            $this->monitor->recordProcessingTime($queue, $time);
        }

        // Act
        $metrics = $this->monitor->getProcessingTime($queue);

        // Assert
        $this->assertArrayHasKey('avg', $metrics);
        $this->assertArrayHasKey('p50', $metrics);
        $this->assertArrayHasKey('p95', $metrics);
        $this->assertArrayHasKey('p99', $metrics);
        
        $this->assertGreaterThan(0, $metrics['avg']);
        $this->assertEquals(2.5, $metrics['p50']); // Median
    }
    #[Test]
    public function calculates_failure_rate_correctly(): void
    {
        // Arrange
        $queue = 'notifications';

        // Record 10 jobs: 8 success, 2 failures
        for ($i = 0; $i < 8; $i++) {
            $this->monitor->recordJobProcessed($queue, true);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->monitor->recordJobProcessed($queue, false);
        }

        // Act
        $failureRate = $this->monitor->getFailureRate($queue);

        // Assert
        $this->assertEquals(20.0, $failureRate, 'Failure rate should be 20%');
    }
    #[Test]
    public function detects_warning_thresholds(): void
    {
        // Arrange
        $queue = 'webhooks';
        $warningThreshold = config('queue-protection.monitoring.thresholds.queue_size.warning');

        // Simulate queue at warning level
        for ($i = 0; $i < $warningThreshold + 10; $i++) {
            \Illuminate\Support\Facades\Redis::rpush("queues:{$queue}", json_encode(['job' => "job_{$i}"]));
        }

        // Act
        $alerts = $this->monitor->checkThresholds();

        // Assert
        $this->assertNotEmpty($alerts, 'Should generate alerts');
        $warningAlerts = array_filter($alerts, fn($a) => $a['severity'] === 'warning');
        $this->assertNotEmpty($warningAlerts, 'Should have warning alerts');
    }
    #[Test]
    public function detects_critical_thresholds(): void
    {
        // Arrange
        $queue = 'emails';
        $criticalThreshold = config('queue-protection.monitoring.thresholds.queue_size.critical');

        // Simulate queue at critical level
        for ($i = 0; $i < $criticalThreshold + 10; $i++) {
            \Illuminate\Support\Facades\Redis::rpush("queues:{$queue}", json_encode(['job' => "job_{$i}"]));
        }

        // Act
        $alerts = $this->monitor->checkThresholds();

        // Assert
        $criticalAlerts = array_filter($alerts, fn($a) => $a['severity'] === 'critical');
        $this->assertNotEmpty($criticalAlerts, 'Should have critical alerts');
        
        $queueSizeAlert = array_filter($criticalAlerts, fn($a) => $a['metric'] === 'queue_size');
        $this->assertNotEmpty($queueSizeAlert, 'Should have queue_size critical alert');
    }
    #[Test]
    public function exports_prometheus_format(): void
    {
        // Arrange
        $queue = 'webhooks';
        
        // Add some metrics
        $this->monitor->recordProcessingTime($queue, 1.5);
        $this->monitor->recordJobProcessed($queue, true);

        // Act
        $prometheus = $this->monitor->exportPrometheus();

        // Assert
        $this->assertIsString($prometheus);
        $this->assertStringContainsString('queue_size{queue="webhooks"}', $prometheus);
        $this->assertStringContainsString('queue_processing_time_avg{queue="webhooks"}', $prometheus);
        $this->assertStringContainsString('queue_failure_rate{queue="webhooks"}', $prometheus);
        $this->assertStringContainsString('circuit_breaker_state{queue="webhooks"}', $prometheus);
        $this->assertStringContainsString('rate_limiter_remaining{queue="webhooks"}', $prometheus);
    }
    #[Test]
    public function integrates_circuit_breaker_state(): void
    {
        // Arrange
        $queue = 'webhooks';
        $threshold = config('queue-protection.circuit_breaker.failure_threshold');

        // Open circuit breaker
        for ($i = 0; $i < $threshold; $i++) {
            $this->circuitBreaker->recordFailure($queue);
        }

        // Act
        $metrics = $this->monitor->collect();

        // Assert
        $this->assertArrayHasKey($queue, $metrics);
        $this->assertArrayHasKey('circuit_breaker', $metrics[$queue]);
        $this->assertEquals('open', $metrics[$queue]['circuit_breaker']['state']);
    }
    #[Test]
    public function integrates_rate_limiter_metrics(): void
    {
        // Arrange
        $queue = 'webhooks';

        // Use some rate limit
        for ($i = 0; $i < 10; $i++) {
            $this->rateLimiter->attempt($queue);
        }

        // Act
        $metrics = $this->monitor->collect();

        // Assert
        $this->assertArrayHasKey($queue, $metrics);
        $this->assertArrayHasKey('rate_limiter', $metrics[$queue]);
        $this->assertArrayHasKey('current', $metrics[$queue]['rate_limiter']);
        $this->assertEquals(10, $metrics[$queue]['rate_limiter']['current']);
    }
}
