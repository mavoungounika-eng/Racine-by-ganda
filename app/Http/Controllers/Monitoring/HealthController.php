<?php

namespace App\Http\Controllers\Monitoring;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\HealthCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * HealthController - API de surveillance système
 * 
 * Expose l'état de santé des composants critiques.
 * Requis pour :
 * - Load balancers (L7 health checks)
 * - Systèmes de monitoring (Prometheus, Datadog)
 * - Alerting interne
 */
class HealthController extends Controller
{
    protected HealthCheckService $healthCheck;

    public function __construct(HealthCheckService $healthCheck)
    {
        $this->healthCheck = $healthCheck;
    }

    /**
     * Rapport complet de santé
     */
    public function index(Request $request): JsonResponse
    {
        // Optionnel : Vérification d'une clé API ou IP pour accès restreint
        // $this->authorizeMonitoring($request);

        $health = $this->healthCheck->checkAll();
        
        // Déterminer le code statut HTTP
        // Si un composant est en 'fail', on peut retourner 503
        $statusCode = $this->isSystemHealthy($health) ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Vérification simplifiée (pour load balancer)
     */
    public function liveness(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    /**
     * Déterminer si le système est globalement sain
     */
    protected function isSystemHealthy(array $health): bool
    {
        foreach ($health['components'] as $name => $component) {
            // Ignorer les warnings (ex: disque à 90% ou queue un peu longue)
            // Mais échouer si la DB ou le Cache sont tombés
            if (in_array($name, ['database', 'cache']) && $component['status'] === 'fail') {
                return false;
            }
        }
        return true;
    }
}
