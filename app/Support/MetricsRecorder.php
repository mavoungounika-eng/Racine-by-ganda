<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Helper pour enregistrer les métriques de performance
 * 
 * Mesure le nombre de requêtes SQL, le temps DB cumulé et le temps de réponse.
 * Fonctionne uniquement en mode debug (APP_DEBUG=true).
 * 
 * Utilisation :
 * ```php
 * MetricsRecorder::start();
 * // ... code à mesurer ...
 * $metrics = MetricsRecorder::stop();
 * ```
 * 
 * @package App\Support
 */
class MetricsRecorder
{
    /**
     * Nombre de requêtes SQL exécutées
     */
    protected static int $queryCount = 0;

    /**
     * Temps DB cumulé en millisecondes
     */
    protected static float $dbTime = 0;

    /**
     * Timestamp de début de mesure (microtime)
     */
    protected static ?float $startTime = null;

    /**
     * Indique si l'enregistrement est actif
     */
    protected static bool $isRecording = false;

    /**
     * Démarrer l'enregistrement des métriques
     * 
     * Active le listener DB et initialise les compteurs.
     * Ne fait rien si APP_DEBUG=false.
     * 
     * @return void
     */
    public static function start(): void
    {
        if (!config('app.debug')) {
            return;
        }

        static::reset();
        static::$isRecording = true;
        static::$startTime = microtime(true);

        DB::listen(function ($query) {
            if (static::$isRecording) {
                static::$queryCount++;
                static::$dbTime += $query->time;
            }
        });
    }

    /**
     * Arrêter l'enregistrement et retourner les métriques
     * 
     * @return array{query_count: int, db_time_ms: float, response_time_ms: float}
     */
    public static function stop(): array
    {
        if (!config('app.debug')) {
            return [
                'query_count' => 0,
                'db_time_ms' => 0.0,
                'response_time_ms' => 0.0,
            ];
        }

        static::$isRecording = false;

        $responseTime = static::$startTime !== null
            ? (microtime(true) - static::$startTime) * 1000
            : 0.0;

        return [
            'query_count' => static::$queryCount,
            'db_time_ms' => round(static::$dbTime, 2),
            'response_time_ms' => round($responseTime, 2),
        ];
    }

    /**
     * Réinitialiser les compteurs
     * 
     * @return void
     */
    protected static function reset(): void
    {
        static::$queryCount = 0;
        static::$dbTime = 0;
        static::$startTime = null;
        static::$isRecording = false;
    }

    /**
     * Obtenir le nombre de requêtes actuel (pour debug)
     * 
     * @return int
     */
    public static function getQueryCount(): int
    {
        return static::$queryCount;
    }

    /**
     * Obtenir le temps DB actuel (pour debug)
     * 
     * @return float
     */
    public static function getDbTime(): float
    {
        return static::$dbTime;
    }
}
