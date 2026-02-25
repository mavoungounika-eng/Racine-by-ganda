<?php

namespace App\Services\Pos;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PosOfflineService - Gestion du mode offline POS
 *
 * FIX 1: Queue offline persistée en DB (pos_offline_queue) — plus de perte si Redis redémarre.
 * FIX 2: Flag offline par machine (clé Cache dédiée) — une machine offline n'impacte pas les autres.
 *
 * Détection et gestion:
 * - Perte de connectivité réseau
 * - Queue indisponible
 * - Base de données indisponible
 * - Sync différée quand reconnecté
 */
class PosOfflineService
{
    /** @deprecated Use per-machine key. Kept for backward compat UI status. */
    public const OFFLINE_CACHE_KEY = 'pos:offline_mode';
    public const OFFLINE_TIMEOUT = 3600; // 1 heure

    // =========================================================================
    // FIX 2 — Offline par machine (pas global)
    // =========================================================================

    /**
     * Clé Cache pour le statut offline d'une machine donnée.
     */
    private function machineOfflineKey(string $machineId): string
    {
        return "pos:offline:{$machineId}";
    }

    /**
     * Détecter si UNE machine est offline.
     */
    public function isOffline(string $machineId = ''): bool
    {
        if ($machineId !== '') {
            return Cache::has($this->machineOfflineKey($machineId));
        }
        // Compat ascendante : comportement global (déprecié)
        return Cache::has(self::OFFLINE_CACHE_KEY);
    }

    /**
     * Vérifier si AU MOINS UNE machine est offline (pour l'UI globale).
     */
    public function isAnyOffline(): bool
    {
        return Cache::has(self::OFFLINE_CACHE_KEY);
    }

    /**
     * Marquer UNE machine comme offline.
     */
    public function markOffline(string $machineId, string $reason = 'Network unavailable'): void
    {
        $data = [
            'reason'     => $reason,
            'machine_id' => $machineId,
            'marked_at'  => now()->toIso8601String(),
        ];

        // Clé par machine
        Cache::put($this->machineOfflineKey($machineId), $data, self::OFFLINE_TIMEOUT);

        // Mise à jour du flag global (UI)
        Cache::put(self::OFFLINE_CACHE_KEY, $data, self::OFFLINE_TIMEOUT);

        Log::warning('POS machine marked offline', ['machine_id' => $machineId, 'reason' => $reason]);
    }

    /**
     * Marquer UNE machine comme online.
     */
    public function markOnline(string $machineId): void
    {
        Cache::forget($this->machineOfflineKey($machineId));
        // On ne supprime le flag global que si aucune machine n'est plus offline dans la queue DB
        if ($this->getOfflineQueueCount() === 0) {
            Cache::forget(self::OFFLINE_CACHE_KEY);
        }
        Log::info('POS machine marked online', ['machine_id' => $machineId]);
    }

    /**
     * Récupérer statut offline d'une machine (ou global).
     */
    public function getOfflineStatus(string $machineId = ''): ?array
    {
        if ($machineId !== '') {
            return Cache::get($this->machineOfflineKey($machineId));
        }
        return Cache::get(self::OFFLINE_CACHE_KEY);
    }

    // =========================================================================
    // FIX 1 — Queue offline persistée en DB
    // =========================================================================

    /**
     * Ajouter une vente à la queue offline (persistée en DB, résistante aux redémarrages Redis).
     */
    public function queueOfflineSale(string $machineId, array $saleData): void
    {
        DB::table('pos_offline_queue')->insert([
            'machine_id' => $machineId,
            'sale_data'  => json_encode($saleData),
            'status'     => 'pending',
            'queued_at'  => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info('POS offline sale queued to DB', [
            'machine_id' => $machineId,
            'items'      => count($saleData['items'] ?? []),
        ]);
    }

    /**
     * Récupérer et marquer "synced" toutes les ventes pending d'une machine.
     * Retourne un tableau groupé par machine_id.
     */
    public function flushOfflineQueue(string $machineId = ''): array
    {
        $query = DB::table('pos_offline_queue')->where('status', 'pending');

        if ($machineId !== '') {
            $query->where('machine_id', $machineId);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            return [];
        }

        // Marquer comme synced
        $ids = $rows->pluck('id')->toArray();
        DB::table('pos_offline_queue')
            ->whereIn('id', $ids)
            ->update(['status' => 'synced', 'synced_at' => now(), 'updated_at' => now()]);

        // Grouper par machine
        $result = [];
        foreach ($rows as $row) {
            $result[$row->machine_id][] = [
                'data'      => json_decode($row->sale_data, true),
                'queued_at' => $row->queued_at,
            ];
        }

        return $result;
    }

    /**
     * Compter les ventes pending dans la queue offline (toutes machines).
     */
    public function getOfflineQueueCount(): int
    {
        return DB::table('pos_offline_queue')->where('status', 'pending')->count();
    }
}
