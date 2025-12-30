<?php

namespace App\Http\Middleware;

use App\Models\PerformanceMetric;
use App\Support\MetricsRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour enregistrer les métriques de performance
 * 
 * Collecte automatiquement :
 * - Nombre de requêtes SQL
 * - Temps DB cumulé
 * - Temps de réponse total
 * 
 * Actif uniquement si APP_DEBUG=true
 * 
 * @package App\Http\Middleware
 */
class RecordPerformanceMetrics
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ne rien faire si debug est désactivé
        if (!config('app.debug')) {
            return $next($request);
        }

        // Démarrer l'enregistrement des métriques
        MetricsRecorder::start();

        // Exécuter la requête
        $response = $next($request);

        // Arrêter l'enregistrement et récupérer les métriques
        $metrics = MetricsRecorder::stop();

        // Persister les métriques en base
        PerformanceMetric::create([
            'route' => $request->route()?->getName() ?? $request->path(),
            'method' => $request->method(),
            'status_code' => $response->getStatusCode(),
            'query_count' => $metrics['query_count'],
            'db_time_ms' => $metrics['db_time_ms'],
            'response_time_ms' => $metrics['response_time_ms'],
        ]);

        return $response;
    }
}
