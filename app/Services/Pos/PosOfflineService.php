<?php

namespace App\Services\Pos;

use Illuminate\Support\Facades\Cache;

/**
 * PosOfflineService - Gestion du mode offline POS
 *
 * Détection et gestion:
 * - Perte de connectivité réseau
 * - Queue indisponible
 * - Base de données indisponible
 * - Sync différée quand reconnecté
 */
class PosOfflineService
{
    public const OFFLINE_CACHE_KEY = 'pos:offline_mode';
    public const OFFLINE_QUEUE_KEY = 'pos:offline_queue';
    public const OFFLINE_TIMEOUT = 3600; // 1 heure cache

    /**
     * Détecter si système est offline
     */
    public function isOffline(): bool
    {
        return Cache::has(self::OFFLINE_CACHE_KEY);
    }

    /**
     * Marquer système comme offline
     */
    public function markOffline(string $reason = 'Network unavailable'): void
    {
        Cache::put(self::OFFLINE_CACHE_KEY, [
            'reason' => $reason,
            'marked_at' => now(),
            'machine_ids' => [],
        ], self::OFFLINE_TIMEOUT);

        \Illuminate\Support\Facades\Log::warning('POS marked offline', ['reason' => $reason]);
    }

    /**
     * Marquer système comme online
     */
    public function markOnline(): void
    {
        Cache::forget(self::OFFLINE_CACHE_KEY);
        \Illuminate\Support\Facades\Log::info('POS marked online');
    }

    /**
     * Ajouter vente à queue offline
     */
    public function queueOfflineSale(string $machineId, array $saleData): void
    {
        $queue = Cache::get(self::OFFLINE_QUEUE_KEY, []);
        $queue[$machineId][] = [
            'data' => $saleData,
            'queued_at' => now()->toIso8601String(),
        ];
        Cache::put(self::OFFLINE_QUEUE_KEY, $queue, self::OFFLINE_TIMEOUT);
    }

    /**
     * Récupérer et vider queue offline
     */
    public function flushOfflineQueue(): array
    {
        $queue = Cache::get(self::OFFLINE_QUEUE_KEY, []);
        Cache::forget(self::OFFLINE_QUEUE_KEY);
        return $queue;
    }

    /**
     * Compter ventes en queue offline
     */
    public function getOfflineQueueCount(): int
    {
        $queue = Cache::get(self::OFFLINE_QUEUE_KEY, []);
        return array_sum(array_map('count', $queue));
    }

    /**
     * Récupérer statut offline
     */
    public function getOfflineStatus(): ?array
    {
        return Cache::get(self::OFFLINE_CACHE_KEY);
    }
}
