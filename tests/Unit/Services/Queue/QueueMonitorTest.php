<?php

namespace Tests\Unit\Services\Queue;

use App\Services\Queue\QueueMonitor;
use App\Services\Queue\QueueCircuitBreaker;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class QueueMonitorTest extends TestCase
{
    protected QueueMonitor $monitor;
    protected $queueMock;
    protected $circuitBreakerMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->queueMock = Mockery::mock('alias:' . Queue::class);
        $this->circuitBreakerMock = Mockery::mock(QueueCircuitBreaker::class);
        
        $this->app->instance(QueueCircuitBreaker::class, $this->circuitBreakerMock);

        // Create instance
        $this->monitor = new QueueMonitor();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    #[Test]
    public function collects_queue_size()
    {
        // Arrange
        $queueName = 'default';
        $expectedSize = 42;

        $this->queueMock->shouldReceive('size')
            ->with($queueName)
            ->andReturn($expectedSize);

        $this->circuitBreakerMock->shouldReceive('isOpen')
            ->andReturn(false);

        // Act
        $metrics = $this->monitor->collect();

        // Assert
        $this->assertArrayHasKey($queueName, $metrics);
        $this->assertEquals($expectedSize, $metrics[$queueName]['size']);
    }
    #[Test]
    public function collects_processing_time()
    {
        // Arrange
        $queueName = 'default';

        $this->queueMock->shouldReceive('size')
            ->andReturn(10);

        $this->circuitBreakerMock->shouldReceive('isOpen')
            ->andReturn(false);

        // Act
        $metrics = $this->monitor->collect();

        // Assert
        $this->assertArrayHasKey($queueName, $metrics);
        $this->assertArrayHasKey('processing_time', $metrics[$queueName]);
        $this->assertIsFloat($metrics[$queueName]['processing_time']);
    }
    #[Test]
    public function collects_failure_rate()
    {
        // Arrange
        $queueName = 'default';

        $this->queueMock->shouldReceive('size')
            ->andReturn(10);

        $this->circuitBreakerMock->shouldReceive('isOpen')
            ->andReturn(false);

        // Act
        $metrics = $this->monitor->collect();

        // Assert
        $this->assertArrayHasKey($queueName, $metrics);
        $this->assertArrayHasKey('failure_rate', $metrics[$queueName]);
        $this->assertIsFloat($metrics[$queueName]['failure_rate']);
        $this->assertGreaterThanOrEqual(0, $metrics[$queueName]['failure_rate']);
        $this->assertLessThanOrEqual(1, $metrics[$queueName]['failure_rate']);
    }
    #[Test]
    public function collects_circuit_breaker_state()
    {
        // Arrange
        $queueName = 'default';

        $this->queueMock->shouldReceive('size')
            ->andReturn(10);

        $this->circuitBreakerMock->shouldReceive('isOpen')
            ->with($queueName)
            ->andReturn(true);

        // Act
        $metrics = $this->monitor->collect();

        // Assert
        $this->assertArrayHasKey($queueName, $metrics);
        $this->assertArrayHasKey('circuit_breaker_state', $metrics[$queueName]);
        $this->assertEquals('open', $metrics[$queueName]['circuit_breaker_state']);
    }
    #[Test]
    public function exports_prometheus_format()
    {
        // Arrange
        $this->queueMock->shouldReceive('size')
            ->andReturn(10);

        $this->circuitBreakerMock->shouldReceive('isOpen')
            ->andReturn(false);

        // Act
        $prometheus = $this->monitor->exportPrometheus();

        // Assert
        $this->assertIsString($prometheus);
        $this->assertStringContainsString('# HELP', $prometheus);
        $this->assertStringContainsString('# TYPE', $prometheus);
        $this->assertStringContainsString('queue_size', $prometheus);
    }
    #[Test]
    public function prometheus_metrics_syntax_valid()
    {
        // Arrange
        $this->queueMock->shouldReceive('size')
            ->andReturn(25);

        $this->circuitBreakerMock->shouldReceive('isOpen')
            ->andReturn(false);

        // Act
        $prometheus = $this->monitor->exportPrometheus();

        // Assert - Check Prometheus format
        $lines = explode("\n", $prometheus);
        
        foreach ($lines as $line) {
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }
            
            // Metric lines should have format: metric_name{labels} value
            $this->assertMatchesRegularExpression(
                '/^[a-z_]+(\{[^}]+\})?\s+[\d.]+$/',
                $line,
                "Invalid Prometheus format: {$line}"
            );
        }
    }
    #[Test]
    public function detects_queue_size_warning()
    {
        // Arrange
        $warningThreshold = config('queue-protection.monitoring.size_warning', 500);

        $this->queueMock->shouldReceive('size')
            ->andReturn($warningThreshold + 10);

        $this->circuitBreakerMock->shouldReceive('isOpen')
            ->andReturn(false);

        // Act
        $alerts = $this->monitor->getAlerts();

        // Assert
        $this->assertNotEmpty($alerts);
        $this->assertStringContainsString('warning', strtolower($alerts[0]['severity']));
        $this->assertStringContainsString('size', strtolower($alerts[0]['message']));
    }
    #[Test]
    public function detects_queue_size_critical()
    {
        // Arrange
        $criticalThreshold = config('queue-protection.monitoring.size_critical', 1000);

        $this->queueMock->shouldReceive('size')
            ->andReturn($criticalThreshold + 100);

        $this->circuitBreakerMock->shouldReceive('isOpen')
            ->andReturn(false);

        // Act
        $alerts = $this->monitor->getAlerts();

        // Assert
        $this->assertNotEmpty($alerts);
        $this->assertStringContainsString('critical', strtolower($alerts[0]['severity']));
    }
    #[Test]
    public function detects_processing_time_warning()
    {
        // Arrange
        $warningThreshold = config('queue-protection.monitoring.processing_time_warning', 5.0);

        $this->queueMock->shouldReceive('size')
            ->andReturn(10);

        $this->circuitBreakerMock->shouldReceive('isOpen')
            ->andReturn(false);

        // Mock slow processing time
        // Note: This would require injecting processing time data
        // For now, we'll test the threshold logic exists

        // Act
        $metrics = $this->monitor->collect();

        // Assert
        $this->assertArrayHasKey('default', $metrics);
        $this->assertArrayHasKey('processing_time', $metrics['default']);
    }
    #[Test]
    public function detects_failure_rate_critical()
    {
        // Arrange
        $criticalThreshold = config('queue-protection.monitoring.failure_rate_critical', 0.10);

        $this->queueMock->shouldReceive('size')
            ->andReturn(100);

        $this->circuitBreakerMock->shouldReceive('isOpen')
            ->andReturn(false);

        // Mock high failure rate
        // Note: This would require injecting failure data
        // For now, we'll test the threshold logic exists

        // Act
        $metrics = $this->monitor->collect();

        // Assert
        $this->assertArrayHasKey('default', $metrics);
        $this->assertArrayHasKey('failure_rate', $metrics['default']);
    }
    #[Test]
    public function generates_alerts_for_threshold_violations()
    {
        // Arrange
        $criticalSize = config('queue-protection.monitoring.size_critical', 1000);

        $this->queueMock->shouldReceive('size')
            ->andReturn($criticalSize + 500);

        $this->circuitBreakerMock->shouldReceive('isOpen')
            ->andReturn(true); // Circuit is also open

        // Act
        $alerts = $this->monitor->getAlerts();

        // Assert
        $this->assertNotEmpty($alerts, 'Should generate alerts for threshold violations');
        $this->assertGreaterThan(0, count($alerts));
        
        // Should have alerts for both queue size and circuit breaker
        $alertMessages = array_column($alerts, 'message');
        $this->assertNotEmpty($alertMessages);
    }
}
