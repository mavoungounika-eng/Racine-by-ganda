<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Helper pour logger les requêtes SQL en environnement local
 * 
 * Utilisé uniquement pour l'audit de performance N+1
 * Ne s'active qu'en mode debug (APP_DEBUG=true)
 * 
 * @package App\Support
 */
class QueryLogger
{
    /**
     * Nombre de requêtes exécutées
     */
    protected static int $queryCount = 0;

    /**
     * Temps total d'exécution des requêtes (ms)
     */
    protected static float $totalTime = 0;

    /**
     * Détails des requêtes
     */
    protected static array $queries = [];

    /**
     * Activer le logging des requêtes
     * 
     * @param bool $detailed Si true, log les détails de chaque requête
     * @return void
     */
    public static function enable(bool $detailed = false): void
    {
        if (!config('app.debug')) {
            return;
        }

        static::reset();

        DB::listen(function ($query) use ($detailed) {
            static::$queryCount++;
            static::$totalTime += $query->time;

            if ($detailed) {
                static::$queries[] = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ];
            }
        });
    }

    /**
     * Réinitialiser les compteurs
     * 
     * @return void
     */
    public static function reset(): void
    {
        static::$queryCount = 0;
        static::$totalTime = 0;
        static::$queries = [];
    }

    /**
     * Obtenir le nombre de requêtes exécutées
     * 
     * @return int
     */
    public static function getQueryCount(): int
    {
        return static::$queryCount;
    }

    /**
     * Obtenir le temps total d'exécution (ms)
     * 
     * @return float
     */
    public static function getTotalTime(): float
    {
        return static::$totalTime;
    }

    /**
     * Obtenir les détails des requêtes
     * 
     * @return array
     */
    public static function getQueries(): array
    {
        return static::$queries;
    }

    /**
     * Logger les statistiques dans les logs
     * 
     * @param string $context Contexte de la mesure (ex: "Admin Dashboard")
     * @return void
     */
    public static function logStats(string $context = 'Query Stats'): void
    {
        if (!config('app.debug')) {
            return;
        }

        Log::info("[QueryLogger] {$context}", [
            'queries' => static::$queryCount,
            'total_time' => round(static::$totalTime, 2) . 'ms',
            'avg_time' => static::$queryCount > 0 
                ? round(static::$totalTime / static::$queryCount, 2) . 'ms' 
                : '0ms',
        ]);
    }

    /**
     * Afficher les statistiques dans la console (pour tests manuels)
     * 
     * @param string $context Contexte de la mesure
     * @return void
     */
    public static function dump(string $context = 'Query Stats'): void
    {
        if (!config('app.debug')) {
            return;
        }

        dump([
            'context' => $context,
            'queries' => static::$queryCount,
            'total_time' => round(static::$totalTime, 2) . 'ms',
            'avg_time' => static::$queryCount > 0 
                ? round(static::$totalTime / static::$queryCount, 2) . 'ms' 
                : '0ms',
        ]);
    }

    /**
     * Mesurer l'exécution d'un callback
     * 
     * @param callable $callback
     * @param string $context
     * @return mixed
     */
    public static function measure(callable $callback, string $context = 'Measurement')
    {
        static::reset();
        static::enable();

        $result = $callback();

        static::logStats($context);

        return $result;
    }
}
