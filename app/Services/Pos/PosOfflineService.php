<?php

namespace App\Services\Pos;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\PosOfflineQueue;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\PosOperatorAuditLog;
use App\Services\Pos\PosSaleService;
use App\Traits\AuditsPosOperations;
use Illuminate\Support\Str;

/**
 * PosOfflineService - Gestion du mode offline POS
 *
 * FIX 1: Queue offline persistée en DB (pos_offline_queue) — plus de perte si Redis redémarre.
 * FIX 2: Flag offline par machine (clé Cache dédiée) — une machine offline n'impacte pas les autres.
 *

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
    use AuditsPosOperations;

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
    public function queueOfflineSale(string $machineId, array $saleData): string
    {
        $uuid = $saleData['uuid'] ?? Str::uuid()->toString();
        $saleData['uuid'] = $uuid;

        PosOfflineQueue::create([
            'machine_id' => $machineId,
            'sale_data' => $saleData,
            'status' => 'pending',
            'queued_at' => now(),
            'attempts' => 0,
            'error_message' => null,
        ]);

        return $uuid;
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

    /**
     * RÃ©cupÃ©rer les Ã©lÃ©ments de queue pour une machine.
     */
    public function getQueueItems(string $machineId, string $status = 'pending')
    {
        return PosOfflineQueue::query()
            ->where('machine_id', $machineId)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderByDesc('queued_at')
            ->get();
    }

    /**
     * Traiter un item de queue via PosSaleService.
     */
    public function processQueueItem(PosOfflineQueue $item, int $userId): array
    {
        $item->increment('attempts');

        $data = $item->sale_data ?? [];
        $items = $data['items'] ?? null;
        $paymentMethod = $data['payment_method'] ?? null;

        if (!$items || !$paymentMethod) {
            $item->update([
                'status' => 'failed',
                'error_message' => 'Invalid sale_data payload',
            ]);
            return ['success' => false, 'error' => 'Invalid sale_data payload'];
        }

        try {
            $sale = app(PosSaleService::class)->createSale(
                $item->machine_id,
                $items,
                $paymentMethod,
                $userId,
                [
                    'customer_name' => $data['customer_name'] ?? null,
                    'customer_email' => $data['customer_email'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                    'uuid' => $data['uuid'] ?? null,
                ],
                $data['idempotency_key'] ?? null
            );

            $item->update([
                'status' => 'synced',
                'synced_at' => now(),
                'error_message' => null,
            ]);

            return ['success' => true, 'sale' => $sale];
        } catch (\Exception $e) {
            $item->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Supprimer les items non-pending anciens.
     */
    public function clearOldItems(string $machineId, int $hoursOld = 24): int
    {
        $cutoff = now()->subHours($hoursOld);

        return PosOfflineQueue::query()
            ->where('machine_id', $machineId)
            ->where('status', '!=', 'pending')
            ->where(function ($q) use ($cutoff) {
                $q->whereNotNull('synced_at')->where('synced_at', '<', $cutoff)
                  ->orWhere(function ($q2) use ($cutoff) {
                      $q2->whereNull('synced_at')->where('updated_at', '<', $cutoff);
                  });
            })
            ->delete();
    }

    // =========================================================================
    // NOUVELLES METHODES (OFFLINE SYNC & CONFLICTS)
    // =========================================================================

    /**
     * Synchronise les ventes offline depuis le Frontend.
     */
    public function syncPendingSales(string $deviceId, array $sales, int $userId): array
    {
        $result = [
            'synced' => 0,
            'failed' => 0,
            'conflicts' => []
        ];

        foreach ($sales as $saleData) {
            $uuid = $saleData['uuid'] ?? null;
            if (!$uuid) {
                $result['failed']++;
                continue;
            }

            // 1. Idempotence : vérifier si la vente existe déjà
            if (PosSale::where('uuid', $uuid)->exists() || PosSale::where('idempotency_key', $saleData['idempotency_key'] ?? $uuid)->exists()) {
                $result['synced']++; // Déjà sync
                continue;
            }

            // 2. Vérification Stock
            $hasConflict = false;
            $items = $saleData['items'] ?? [];
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                if ($product && $product->stock < $item['quantity']) {
                    $hasConflict = true;
                    $result['conflicts'][] = [
                        'sale_uuid' => $uuid,
                        'product_id' => $product->id,
                        'product_name' => $product->title ?? $product->name,
                        'requested_qty' => $item['quantity'],
                        'available_stock' => $product->stock,
                        'sale_data' => $saleData // Garder pour resolve ultérieur
                    ];
                }
            }

            if ($hasConflict) {
                // Stocker en file d'attente coté backend pour résolution
                $this->queueOfflineConflict($deviceId, $saleData);
                continue; // Ne pas incrémenter ni valider cette vente maintenant
            }

            // 3. Stock OK -> store
            try {
                app(PosSaleService::class)->createSale(
                    $deviceId,
                    $items,
                    $saleData['payment_method'] ?? 'cash',
                    $userId,
                    [
                        'customer_name' => $saleData['customer_name'] ?? null,
                        'customer_email' => $saleData['customer_email'] ?? null,
                        'customer_phone' => $saleData['customer_phone'] ?? null,
                        'uuid' => $uuid,
                    ],
                    $saleData['idempotency_key'] ?? $uuid
                );

                $result['synced']++;
            } catch (\Exception $e) {
                Log::error("Failed to sync offline sale {$uuid}: " . $e->getMessage());
                $result['failed']++;
            }
        }

        // Logger l'action de synchronisation
        self::logPosAction('OFFLINE_SYNC_COMPLETED', [
            'device_id' => $deviceId,
            'synced' => $result['synced'],
            'failed' => $result['failed'],
            'conflicts_count' => count($result['conflicts']),
        ], $userId);

        return $result;
    }

    /**
     * Enregistre un conflit dans la table pos_offline_queue
     */
    private function queueOfflineConflict(string $deviceId, array $saleData): void
    {
        // Utiliser la syntaxe Laravel JSON (cross-DB: MySQL json_unquote + SQLite json_extract)
        $existing = PosOfflineQueue::query()
            ->where('sale_data->uuid', $saleData['uuid'])
            ->first();

        $record = $existing ?? new PosOfflineQueue();
        $record->machine_id   = $deviceId;
        $record->sale_data    = $saleData;
        $record->status       = 'conflict';
        $record->queued_at    = now();
        $record->error_message = 'SYNC_CONFLICT_STOCK';
        $record->save();
    }

    /**
     * Résoudre un conflit de stock
     */
    public function resolveConflict(string $saleUuid, string $resolution, int $userId): bool
    {
        $queueItem = PosOfflineQueue::query()
            ->where('sale_data->uuid', $saleUuid)
            ->where('status', 'conflict')
            ->first();

        if (!$queueItem) {
            return false;
        }

        if ($resolution === 'discard') {
            $queueItem->update(['status' => 'discarded', 'synced_at' => now()]);
            self::logPosAction('CONFLICT_DISCARDED', [
                'sale_uuid' => $saleUuid,
                'resolution' => $resolution
            ], $userId);
            return true;
        }

        if ($resolution === 'force_apply') {
            try {
                $saleData = is_string($queueItem->sale_data) ? json_decode($queueItem->sale_data, true) : $queueItem->sale_data;
                $items = $saleData['items'] ?? [];

                app(PosSaleService::class)->createSale(
                    $queueItem->machine_id,
                    $items,
                    $saleData['payment_method'] ?? 'cash',
                    $userId,
                    [
                        'customer_name' => $saleData['customer_name'] ?? null,
                        'customer_email' => $saleData['customer_email'] ?? null,
                        'customer_phone' => $saleData['customer_phone'] ?? null,
                        'uuid' => $saleUuid,
                        'force_stock' => true,
                    ],
                    $saleData['idempotency_key'] ?? $saleUuid
                );

                $queueItem->update(['status' => 'synced', 'synced_at' => now()]);

                self::logPosAction('CONFLICT_FORCE_APPLIED', [
                    'sale_uuid' => $saleUuid,
                    'resolution' => $resolution
                ], $userId);

                return true;
            } catch (\Exception $e) {
                Log::error("Failed to force apply conflict {$saleUuid}: " . $e->getMessage());
                return false;
            }
        }

        return false;
    }

    /**
     * Expirer les ventes offline de plus de 24h
     */
    public function expireOldSales(): int
    {
        $expiredCount = PosOfflineQueue::query()
            ->where('status', 'pending')
            ->where('queued_at', '<', now()->subHours(24))
            ->update(['status' => 'expired']);

        if ($expiredCount > 0) {
            self::logPosAction('OFFLINE_SALES_EXPIRED', [
                'count' => $expiredCount
            ]);
        }

        return $expiredCount;
    }
}
