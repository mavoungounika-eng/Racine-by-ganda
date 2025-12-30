<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modèle pour les métriques de performance
 * 
 * Stocke les mesures de performance des requêtes HTTP :
 * - Nombre de requêtes SQL
 * - Temps DB cumulé
 * - Temps de réponse total
 * 
 * @property int $id
 * @property string|null $route
 * @property string $method
 * @property int $status_code
 * @property int $query_count
 * @property float $db_time_ms
 * @property float $response_time_ms
 * @property \Carbon\Carbon $created_at
 */
class PerformanceMetric extends Model
{
    /**
     * Désactiver updated_at (pas nécessaire pour des métriques)
     */
    public const UPDATED_AT = null;

    /**
     * Les attributs assignables en masse
     *
     * @var array<string>
     */
    protected $fillable = [
        'route',
        'method',
        'status_code',
        'query_count',
        'db_time_ms',
        'response_time_ms',
    ];

    /**
     * Les attributs qui doivent être castés
     *
     * @var array<string, string>
     */
    protected $casts = [
        'query_count' => 'integer',
        'db_time_ms' => 'float',
        'response_time_ms' => 'float',
        'status_code' => 'integer',
    ];
}
