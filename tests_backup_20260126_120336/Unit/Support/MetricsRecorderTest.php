<?php

namespace Tests\Unit\Support;

use App\Support\MetricsRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests unitaires pour MetricsRecorder
 */
class MetricsRecorderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // S'assurer que debug est activé pour les tests
        Config::set('app.debug', true);
    }

    /**
     * Test que start() puis stop() retourne les clés attendues
     */
    public function test_start_and_stop_returns_expected_keys(): void
    {
        MetricsRecorder::start();
        
        // Exécuter une requête simple
        DB::table('users')->count();
        
        $metrics = MetricsRecorder::stop();

        // Vérifier que toutes les clés sont présentes
        $this->assertArrayHasKey('query_count', $metrics);
        $this->assertArrayHasKey('db_time_ms', $metrics);
        $this->assertArrayHasKey('response_time_ms', $metrics);
    }

    /**
     * Test que les valeurs retournées sont numériques
     */
    public function test_metrics_values_are_numeric(): void
    {
        MetricsRecorder::start();
        
        // Exécuter quelques requêtes
        DB::table('users')->count();
        DB::table('users')->where('id', 1)->first();
        
        $metrics = MetricsRecorder::stop();

        // Vérifier que les valeurs sont numériques
        $this->assertIsInt($metrics['query_count']);
        $this->assertIsFloat($metrics['db_time_ms']);
        $this->assertIsFloat($metrics['response_time_ms']);
    }

    /**
     * Test que query_count est correct
     */
    public function test_query_count_is_accurate(): void
    {
        MetricsRecorder::start();
        
        // Exécuter exactement 3 requêtes
        DB::table('users')->count();
        DB::table('users')->count();
        DB::table('users')->count();
        
        $metrics = MetricsRecorder::stop();

        // Vérifier que le compteur est correct
        $this->assertGreaterThanOrEqual(3, $metrics['query_count']);
    }

    /**
     * Test que db_time_ms est positif quand il y a des requêtes
     */
    public function test_db_time_is_positive_with_queries(): void
    {
        MetricsRecorder::start();
        
        DB::table('users')->count();
        
        $metrics = MetricsRecorder::stop();

        // Le temps DB doit être positif
        $this->assertGreaterThan(0, $metrics['db_time_ms']);
    }

    /**
     * Test que response_time_ms est positif
     */
    public function test_response_time_is_positive(): void
    {
        MetricsRecorder::start();
        
        // Simuler un petit délai
        usleep(1000); // 1ms
        
        $metrics = MetricsRecorder::stop();

        // Le temps de réponse doit être positif
        $this->assertGreaterThan(0, $metrics['response_time_ms']);
    }

    /**
     * Test que le recorder ne fonctionne pas quand debug est désactivé
     */
    public function test_recorder_disabled_when_debug_false(): void
    {
        Config::set('app.debug', false);
        
        MetricsRecorder::start();
        DB::table('users')->count();
        $metrics = MetricsRecorder::stop();

        // Toutes les valeurs doivent être à 0
        $this->assertEquals(0, $metrics['query_count']);
        $this->assertEquals(0.0, $metrics['db_time_ms']);
        $this->assertEquals(0.0, $metrics['response_time_ms']);
    }

    /**
     * Test que les métriques sont réinitialisées entre deux mesures
     */
    public function test_metrics_reset_between_measurements(): void
    {
        // Première mesure
        MetricsRecorder::start();
        DB::table('users')->count();
        $metrics1 = MetricsRecorder::stop();

        // Deuxième mesure
        MetricsRecorder::start();
        DB::table('users')->count();
        $metrics2 = MetricsRecorder::stop();

        // Les deux mesures doivent être indépendantes
        $this->assertEquals($metrics1['query_count'], $metrics2['query_count']);
    }
}
