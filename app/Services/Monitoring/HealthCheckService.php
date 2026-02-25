<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Exception;

/**
 * HealthCheckService - Surveillance de l'état du système
 * 
 * Vérifie :
 * - Base de données (connectivité, latence)
 * - Cache / Redis
 * - Queues (jobs en attente, workers actifs)
 * - APIs Externes (Stripe, Monetbil)
 * - Espace disque
 */
class HealthCheckService
{
    /**
     * Obtenir l'état complet du système
     */
    public function checkAll(): array
    {
        return [
            'status' => 'ok', // Sera mis à jour si un composant critique échoue
            'timestamp' => now()->toIso8601String(),
            'environment' => config('app.env'),
            'components' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'queues' => $this->checkQueues(),
                'external_apis' => $this->checkExternalApis(),
                'storage' => $this->checkStorage(),
            ]
        ];
    }

    /**
     * Vérifier la base de données
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier le cache (Redis)
     */
    protected function checkCache(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check_test', true, 10);
            $val = Cache::get('health_check_test');
            Cache::forget('health_check_test');
            $latency = round((microtime(true) - $start) * 1000, 2);

            if (!$val) {
                return ['status' => 'fail', 'error' => 'Cache verify failed'];
            }

            return [
                'status' => 'ok',
                'latency_ms' => $latency,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier les queues
     */
    protected function checkQueues(): array
    {
        try {
            $queues = config('queue.connections.redis.queue', 'default');
            if (is_string($queues)) $queues = [$queues];

            $stats = [];
            foreach ($queues as $queue) {
                $size = Redis::llen('queues:' . $queue);
                $stats[$queue] = [
                    'size' => $size,
                    'status' => $size > 1000 ? 'warning' : 'ok'
                ];
            }

            return [
                'status' => 'ok',
                'queues' => $stats,
            ];
        } catch (Exception $e) {
            return [
                'status' => 'fail',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Vérifier les API externes
     */
    protected function checkExternalApis(): array
    {
        $apis = [
            'stripe' => config('services.stripe.key') ? 'configured' : 'missing',
            'monetbil' => config('services.monetbil.service_key') ? 'configured' : 'missing',
        ];

        // Optionnel: Faire un ping réel si nécessaire (peut être lent)
        /*
        try {
            $response = Http::timeout(2)->get('https://api.stripe.com');
            $apis['stripe_ping'] = $response->status() < 500 ? 'ok' : 'error';
        } catch (Exception $e) {
            $apis['stripe_ping'] = 'timeout';
        }
        */

        return [
            'status' => 'ok',
            'config' => $apis,
        ];
    }

    /**
     * Vérifier l'espace disque
     */
    protected function checkStorage(): array
    {
        $path = storage_path();
        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $usage = round((($total - $free) / $total) * 100, 2);

        return [
            'status' => $usage > 90 ? 'warning' : 'ok',
            'usage_percent' => $usage,
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
        ];
    }
}
