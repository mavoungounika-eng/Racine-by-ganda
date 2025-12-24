<?php

namespace Modules\POSSync\Services;

use Illuminate\Support\Facades\Redis;
use Modules\POSSync\Models\PosSyncedEvent;

class IdempotenceService
{
    /**
     * Vérifier si un événement a déjà été traité
     * 
     * @param string $machineId
     * @param string $eventUuid
     * @return bool
     */
    public function isProcessed(string $machineId, string $eventUuid): bool
    {
        // 1. Vérifier Redis d'abord (rapide)
        $redisKey = "pos:event:{$machineId}:{$eventUuid}";
        
        if (Redis::exists($redisKey)) {
            return true;
        }

        // 2. Vérifier PostgreSQL (persistant)
        $exists = PosSyncedEvent::where('machine_id', $machineId)
            ->where('event_uuid', $eventUuid)
            ->exists();

        if ($exists) {
            // Mettre en cache dans Redis pour 24h
            Redis::setex($redisKey, 86400, '1');
        }

        return $exists;
    }

    /**
     * Marquer un événement comme traité
     * 
     * @param string $machineId
     * @param string $eventUuid
     * @param array $eventData
     * @return PosSyncedEvent
     */
    public function markProcessed(string $machineId, string $eventUuid, array $eventData): PosSyncedEvent
    {
        // Créer l'enregistrement dans pos_synced_events
        $syncedEvent = PosSyncedEvent::create([
            'machine_id' => $machineId,
            'event_uuid' => $eventUuid,
            'event_type' => $eventData['type'],
            'version' => $eventData['version'],
            'payload' => $eventData['payload'],
            'signature' => $eventData['signature'] ?? null,
            'occurred_at' => \Carbon\Carbon::createFromTimestamp($eventData['occurred_at']),
            'synced_at' => now(),
        ]);

        // Mettre en cache dans Redis pour 24h
        $redisKey = "pos:event:{$machineId}:{$eventUuid}";
        Redis::setex($redisKey, 86400, '1');

        return $syncedEvent;
    }

    /**
     * Nettoyer le cache Redis (maintenance)
     * 
     * @param string $machineId
     * @return int Nombre de clés supprimées
     */
    public function clearCache(string $machineId): int
    {
        $pattern = "pos:event:{$machineId}:*";
        $keys = Redis::keys($pattern);
        
        if (empty($keys)) {
            return 0;
        }

        return Redis::del(...$keys);
    }
}
