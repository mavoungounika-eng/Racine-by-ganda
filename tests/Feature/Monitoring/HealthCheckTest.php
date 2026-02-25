<?php

namespace Tests\Feature\Monitoring;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class HealthCheckTest extends TestCase
{
    /**
     * Test logiciel de liveness
     */
    public function test_liveness_endpoint_returns_200(): void
    {
        $response = $this->getJson('/api/liveness');

        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok']);
    }

    /**
     * Test complet de santé système
     */
    public function test_health_endpoint_returns_data_structure(): void
    {
        $response = $this->getJson('/api/health');

        // On accepte 200 (OK) ou 503 (FAIL d'un composant critique en test env)
        $this->assertContains($response->getStatusCode(), [200, 503]);

        $response->assertJsonStructure([
            'status',
            'timestamp',
            'environment',
            'components' => [
                'database',
                'cache',
                'queues',
                'external_apis',
                'storage'
            ]
        ]);
    }

    /**
     * Test que le HealthCheckService détecte bien les composants
     */
    public function test_health_check_service_logic(): void
    {
        $service = app(\App\Services\Monitoring\HealthCheckService::class);
        $result = $service->checkAll();

        $this->assertEquals('ok', $result['components']['database']['status']);
        $this->assertArrayHasKey('latency_ms', $result['components']['database']);
        $this->assertEquals('ok', $result['components']['cache']['status']);
    }
}
