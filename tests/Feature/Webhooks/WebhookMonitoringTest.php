<?php

namespace Tests\Feature\Webhooks;

use PHPUnit\Framework\Attributes\Test;
use App\Http\Controllers\Webhooks\WebhookMonitoringController;
use App\Models\User;
use App\Models\WebhookHealthCheck;
use App\Models\WebhookMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user for authenticated routes
        $this->seed(\Database\Seeders\RolesTableSeeder::class);
        $adminRole = \App\Models\Role::where('slug', 'admin')->first();
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_admin' => true,
            'two_factor_secret' => 'base32secret',
            'two_factor_confirmed_at' => now(),
        ]);
    }

    // ========================================================================
    // PROMETHEUS METRICS EXPORT
    // ========================================================================
    #[Test]
    public function exports_prometheus_metrics(): void
    {
        // Create sample metrics
        WebhookMetric::create([
            'provider' => 'stripe',
            'event_type' => 'charge.succeeded',
            'response_time_ms' => 250,
            'success' => true,
            'received_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/prometheus');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
        $this->assertStringContainsString('webhook_total_events', $response->getContent());
        $this->assertStringContainsString('webhook_success_rate', $response->getContent());
    }

    // ========================================================================
    // DASHBOARD DATA
    // ========================================================================
    #[Test]
    public function dashboard_returns_complete_data(): void
    {
        // Create sample data
        for ($i = 0; $i < 10; $i++) {
            WebhookMetric::create([
                'provider' => 'stripe',
                'event_type' => 'charge.succeeded',
                'response_time_ms' => 200,
                'success' => true,
                'received_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary',
            'providers',
            'recent_events',
            'health_status',
            'performance_trends',
            'error_distribution',
        ]);
    }
    #[Test]
    public function dashboard_respects_time_range_parameter(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/dashboard?range=120');

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    // ========================================================================
    // SYSTEM HEALTH
    // ========================================================================
    #[Test]
    public function system_health_returns_overall_status(): void
    {
        WebhookHealthCheck::forProvider('stripe')->update(['is_healthy' => true, 'circuit_state' => 'closed']);

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'code',
            'timestamp',
            'health',
            'summary',
        ]);
        $response->assertJsonPath('health.total_providers', 1);
    }
    #[Test]
    public function system_health_returns_ok_status_when_healthy(): void
    {
        WebhookHealthCheck::forProvider('stripe')->update(['is_healthy' => true, 'circuit_state' => 'closed']);

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/health');

        $response->assertJsonPath('status', 'ok');
        $response->assertJsonPath('code', 200);
    }
    #[Test]
    public function system_health_returns_error_status_when_critical(): void
    {
        WebhookHealthCheck::forProvider('stripe')->update(['is_healthy' => false, 'circuit_state' => 'open']);

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/health');

        $response->assertJsonPath('status', 'error');
        $response->assertJsonPath('code', 503);
    }

    // ========================================================================
    // PROVIDER HEALTH
    // ========================================================================
    #[Test]
    public function provider_health_returns_specific_provider_status(): void
    {
        WebhookHealthCheck::forProvider('stripe')->update([
            'is_healthy' => true,
            'success_rate' => 99.5,
            'avg_response_time_ms' => 150,
            'circuit_state' => 'closed',
        ]);

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/health/stripe');

        $response->assertStatus(200);
        $response->assertJsonPath('provider', 'stripe');
        $response->assertJsonPath('is_healthy', true);
        $response->assertJsonPath('success_rate', 99.5);
    }

    // ========================================================================
    // KPI METRICS
    // ========================================================================
    #[Test]
    public function kpis_endpoint_returns_key_performance_indicators(): void
    {
        // Create metrics
        for ($i = 0; $i < 100; $i++) {
            WebhookMetric::create([
                'provider' => 'stripe',
                'event_type' => 'charge.succeeded',
                'response_time_ms' => 200 + random_int(0, 500),
                'success' => random_int(0, 100) > 5,  // ~95% success
                'received_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/kpis');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success_rate',
            'error_rate',
            'avg_latency_ms',
            'p95_latency_ms',
            'p99_latency_ms',
            'throughput_per_minute',
        ]);

        // Check KPI structure
        $kpis = $response->json();
        $this->assertArrayHasKey('value', $kpis['success_rate']);
        $this->assertArrayHasKey('threshold', $kpis['success_rate']);
        $this->assertArrayHasKey('status', $kpis['success_rate']);
    }
    #[Test]
    public function kpis_marks_alerts_when_thresholds_exceeded(): void
    {
        // Create high-latency metrics
        for ($i = 0; $i < 10; $i++) {
            WebhookMetric::create([
                'provider' => 'stripe',
                'event_type' => 'charge.succeeded',
                'response_time_ms' => 2000,  // Above 1000ms threshold
                'success' => true,
                'received_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/kpis');

        $kpis = $response->json();
        $this->assertEquals('alert', $kpis['avg_latency_ms']['status']);
    }

    // ========================================================================
    // SLA METRICS
    // ========================================================================
    #[Test]
    public function sla_metrics_tracks_uptime_compliance(): void
    {
        WebhookHealthCheck::forProvider('stripe');

        // Create successful metrics
        for ($i = 0; $i < 1000; $i++) {
            WebhookMetric::create([
                'provider' => 'stripe',
                'event_type' => 'charge.succeeded',
                'response_time_ms' => 200,
                'success' => true,
                'created_at' => now()->subDays(5),
                'received_at' => now()->subDays(5),
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/sla?days=7');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'time_range_days',
            'time_range_start',
            'time_range_end',
            'overall_sla_compliance',
            'providers',
        ]);

        $providers = $response->json('providers');
        $this->assertCount(1, $providers);
        $this->assertEquals('stripe', $providers[0]['provider']);
        $this->assertTrue($providers[0]['compliant']);
    }

    // ========================================================================
    // ALERT STATUS
    // ========================================================================
    #[Test]
    public function alert_status_identifies_critical_issues(): void
    {
        WebhookHealthCheck::forProvider('stripe')->update(['circuit_state' => 'open']);

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/alerts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_alerts',
            'critical_alerts',
            'warning_alerts',
            'alerts',
            'recommendations',
        ]);

        $alerts = $response->json('alerts');
        $this->assertNotEmpty($alerts);
    }
    #[Test]
    public function alert_status_warns_on_low_success_rate(): void
    {
        // Create mostly failed metrics
        for ($i = 0; $i < 100; $i++) {
            WebhookMetric::create([
                'provider' => 'stripe',
                'event_type' => 'charge.succeeded',
                'response_time_ms' => 200,
                'success' => $i < 5,  // Only 5% success
                'created_at' => now()->subHours(1),
                'received_at' => now()->subHours(1),
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/alerts');

        $alerts = $response->json('alerts');
        $lowSuccessAlert = collect($alerts)->firstWhere('type', 'LOW_SUCCESS_RATE');
        $this->assertNotNull($lowSuccessAlert);
    }

    // ========================================================================
    // ERROR ANALYSIS
    // ========================================================================
    #[Test]
    public function error_analysis_shows_error_distribution(): void
    {
        // Create various error types
        WebhookMetric::create([
            'provider' => 'stripe',
            'event_type' => 'charge.failed',
            'response_time_ms' => 500,
            'success' => false,
            'error_message' => 'Connection timeout',
            'received_at' => now(),
        ]);

        WebhookMetric::create([
            'provider' => 'stripe',
            'event_type' => 'charge.failed',
            'response_time_ms' => 500,
            'success' => false,
            'error_message' => 'Invalid signature',
            'received_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/errors');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'time_range_hours',
            'time_range_start',
            'time_range_end',
            'total_errors',
            'error_distribution',
            'recent_errors',
        ]);

        $distribution = $response->json('error_distribution');
        $this->assertCount(2, $distribution);
    }

    // ========================================================================
    // PERFORMANCE TRENDS
    // ========================================================================
    #[Test]
    public function performance_trends_shows_time_series_data(): void
    {
        $now = now();

        // Create metrics across 2 hours
        for ($hour = 0; $hour < 2; $hour++) {
            for ($i = 0; $i < 10; $i++) {
                WebhookMetric::create([
                    'provider' => 'stripe',
                    'event_type' => 'charge.succeeded',
                    'response_time_ms' => 200 + ($hour * 100),
                    'success' => true,
                    'created_at' => $now->copy()->addHour($hour)->addMinute($i),
                    'received_at' => $now->copy()->addHour($hour)->addMinute($i),
                ]);
            }
        }

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/trends?hours=2&buckets=2');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'time_range_hours',
            'bucket_count',
            'trends',
        ]);

        $trends = $response->json('trends');
        $this->assertCount(2, $trends);
    }

    // ========================================================================
    // COMPREHENSIVE REPORT
    // ========================================================================
    #[Test]
    public function generates_comprehensive_health_report(): void
    {
        // Create sample data
        for ($i = 0; $i < 20; $i++) {
            WebhookMetric::create([
                'provider' => 'stripe',
                'event_type' => 'charge.succeeded',
                'response_time_ms' => 200,
                'success' => true,
                'received_at' => now(),
            ]);
        }

        WebhookHealthCheck::forProvider('stripe');

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/report?days=1');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'report_generated_at',
            'time_range_minutes',
            'system_summary',
            'overall_health',
            'error_distribution',
            'recommendations',
        ]);
    }

    // ========================================================================
    // JSON METRICS EXPORT
    // ========================================================================
    #[Test]
    public function exports_metrics_as_json(): void
    {
        WebhookMetric::create([
            'provider' => 'stripe',
            'event_type' => 'charge.succeeded',
            'response_time_ms' => 250,
            'success' => true,
            'received_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/metrics/json');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total_events',
            'successful_events',
            'failed_events',
            'success_rate',
            'avg_response_time_ms',
        ]);
    }

    // ========================================================================
    // PUBLIC STATUS PAGE
    // ========================================================================
    #[Test]
    public function status_page_is_publicly_accessible(): void
    {
        WebhookHealthCheck::forProvider('stripe')->update(['is_healthy' => true, 'circuit_state' => 'closed']);

        $response = $this->get('/api/webhooks/status');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/html; charset=utf-8');
        $this->assertStringContainsString('Webhook Monitoring', $response->getContent());
        $this->assertStringContainsString('HEALTHY', $response->getContent());
    }
    #[Test]
    public function status_page_shows_critical_when_unhealthy(): void
    {
        WebhookHealthCheck::forProvider('stripe')->update(['is_healthy' => false, 'circuit_state' => 'open']);

        $response = $this->get('/api/webhooks/status');

        $response->assertStatus(200);
        $this->assertStringContainsString('CRITICAL', $response->getContent());
    }

    // ========================================================================
    // AUTHENTICATION & AUTHORIZATION
    // ========================================================================
    #[Test]
    public function monitoring_endpoints_require_authentication(): void
    {
        $response = $this->get('/api/webhooks/monitoring/dashboard');

        $this->assertTrue(in_array($response->status(), [401, 403, 302], true));
    }
    #[Test]
    public function monitoring_endpoints_require_admin_role(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/webhooks/monitoring/dashboard');

        $this->assertTrue(in_array($response->status(), [401, 403, 302], true));
    }
    #[Test]
    public function prometheus_endpoint_requires_admin(): void
    {
        $response = $this->get('/api/webhooks/monitoring/prometheus');

        $this->assertTrue(in_array($response->status(), [401, 403, 302], true));
    }

    // ========================================================================
    // QUERY PARAMETERS
    // ========================================================================
    #[Test]
    public function time_range_parameters_work_correctly(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/kpis?hours=24');

        $response->assertStatus(200);
    }
    #[Test]
    public function sla_days_parameter_accepts_custom_values(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/sla?days=30');

        $response->assertStatus(200);
        $response->assertJsonPath('time_range_days', 30);
    }

    // ========================================================================
    // DATA CONSISTENCY
    // ========================================================================
    #[Test]
    public function kpi_values_are_consistent_with_raw_metrics(): void
    {
        // Create 100 metrics with 95% success rate
        for ($i = 0; $i < 100; $i++) {
            WebhookMetric::create([
                'provider' => 'stripe',
                'event_type' => 'charge.succeeded',
                'response_time_ms' => 300,
                'success' => $i < 95,
                'created_at' => now(),
                'received_at' => now(),
            ]);
        }

        $response = $this->actingAs($this->admin)->get('/api/webhooks/monitoring/kpis?hours=1');

        $kpis = $response->json();
        
        // Should be approximately 95%
        $this->assertGreaterThan(94, $kpis['success_rate']['value']);
        $this->assertLessThan(96, $kpis['success_rate']['value']);
    }
}
