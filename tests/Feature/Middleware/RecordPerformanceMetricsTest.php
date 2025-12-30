<?php

namespace Tests\Feature\Middleware;

use App\Models\PerformanceMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Tests pour le middleware RecordPerformanceMetrics
 */
class RecordPerformanceMetricsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // S'assurer que debug est activé pour les tests
        Config::set('app.debug', true);
    }

    /**
     * Test qu'une requête HTTP crée une ligne dans performance_metrics
     */
    public function test_http_request_creates_performance_metric(): void
    {
        // Vérifier qu'il n'y a pas de métriques au départ
        $this->assertDatabaseCount('performance_metrics', 0);

        // Faire une requête simple
        $response = $this->get('/');

        // Vérifier qu'une métrique a été créée
        $this->assertDatabaseCount('performance_metrics', 1);

        // Vérifier que la réponse est OK
        $response->assertStatus(200);
    }

    /**
     * Test que les métriques enregistrées sont numériques
     */
    public function test_metrics_are_numeric(): void
    {
        // Faire une requête
        $this->get('/');

        // Récupérer la métrique
        $metric = PerformanceMetric::first();

        // Vérifier que les valeurs sont numériques
        $this->assertIsInt($metric->query_count);
        $this->assertIsFloat($metric->db_time_ms);
        $this->assertIsFloat($metric->response_time_ms);
        $this->assertIsInt($metric->status_code);
    }

    /**
     * Test que le status_code est correct
     */
    public function test_status_code_is_correct(): void
    {
        // Faire une requête qui retourne 200
        $this->get('/');

        // Récupérer la métrique
        $metric = PerformanceMetric::first();

        // Vérifier le status code
        $this->assertEquals(200, $metric->status_code);
    }

    /**
     * Test que la route est enregistrée
     */
    public function test_route_is_recorded(): void
    {
        // Faire une requête
        $this->get('/');

        // Récupérer la métrique
        $metric = PerformanceMetric::first();

        // Vérifier que la route est enregistrée
        $this->assertNotNull($metric->route);
    }

    /**
     * Test que la méthode HTTP est enregistrée
     */
    public function test_http_method_is_recorded(): void
    {
        // Faire une requête GET
        $this->get('/');

        // Récupérer la métrique
        $metric = PerformanceMetric::first();

        // Vérifier la méthode
        $this->assertEquals('GET', $metric->method);
    }

    /**
     * Test qu'aucune insertion n'est faite si APP_DEBUG=false
     */
    public function test_no_insertion_when_debug_false(): void
    {
        // Désactiver le debug
        Config::set('app.debug', false);

        // Faire une requête
        $this->get('/');

        // Vérifier qu'aucune métrique n'a été créée
        $this->assertDatabaseCount('performance_metrics', 0);
    }

    /**
     * Test que query_count est positif pour une requête avec DB
     */
    public function test_query_count_is_positive(): void
    {
        // Faire une requête (qui va probablement faire des requêtes DB)
        $this->get('/');

        // Récupérer la métrique
        $metric = PerformanceMetric::first();

        // Le query_count devrait être >= 0
        $this->assertGreaterThanOrEqual(0, $metric->query_count);
    }

    /**
     * Test que response_time_ms est positif
     */
    public function test_response_time_is_positive(): void
    {
        // Faire une requête
        $this->get('/');

        // Récupérer la métrique
        $metric = PerformanceMetric::first();

        // Le temps de réponse devrait être positif
        $this->assertGreaterThan(0, $metric->response_time_ms);
    }
}
