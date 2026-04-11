<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integration\IntegrationTestCase;

/**
 * MetricsEndpointsTest
 * 
 * Tests fonctionnels pour les endpoints de monitoring
 */
class MetricsEndpointsTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock external dependencies
        Http::fake();
        Mail::fake();
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class);
    }
    #[Test]
    public function metrics_endpoint_returns_prometheus_format(): void
    {
        // Act
        $response = $this->get('/metrics');

        // Assert
        $response->assertStatus(200);
        $this->assertStringContainsString(
            'text/plain; version=0.0.4',
            (string) $response->headers->get('Content-Type')
        );
        
        $content = $response->getContent();
        $this->assertStringContainsString('queue_size{queue=', $content);
        $this->assertStringContainsString('queue_processing_time_avg{queue=', $content);
        $this->assertStringContainsString('circuit_breaker_state{queue=', $content);
    }
    #[Test]
    public function health_endpoint_returns_json(): void
    {
        // Act
        $response = $this->get('/health');

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'alerts',
            'timestamp',
        ]);
    }
    #[Test]
    public function health_returns_healthy_when_no_critical_alerts(): void
    {
        // Act
        $response = $this->get('/health');

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'healthy',
        ]);
    }
    #[Test]
    public function health_returns_degraded_when_critical_alerts_present(): void
    {
        // Arrange - Simulate critical queue size
        $criticalThreshold = config('queue-protection.monitoring.thresholds.queue_size.critical');
        for ($i = 0; $i < $criticalThreshold + 10; $i++) {
            \Illuminate\Support\Facades\Redis::rpush('queues:webhooks', json_encode(['job' => "job_{$i}"]));
        }

        // Act
        $response = $this->get('/health');

        // Assert
        $response->assertStatus(503);
        $response->assertJson([
            'status' => 'degraded',
        ]);
        
        // Cleanup
        \Illuminate\Support\Facades\Redis::del('queues:webhooks');
    }
}
