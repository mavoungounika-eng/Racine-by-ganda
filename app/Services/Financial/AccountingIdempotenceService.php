<?php

namespace App\Services\Financial;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Service pour tracker les collisions d'idempotence comptable
 * 
 * Ce service fournit:
 * - Compteur de collisions (idempotence_collision)
 * - Logging métier structuré
 * - Point d'alerte exploitable
 */
class AccountingIdempotenceService
{
    /**
     * Clé de cache pour le compteur de collisions
     */
    private const COLLISION_COUNTER_KEY = 'accounting:idempotence_collision_count';
    
    /**
     * Durée de rétention du compteur (24h)
     */
    private const COUNTER_TTL = 86400;

    /**
     * Enregistrer une collision d'idempotence (écriture déjà existante)
     * 
     * @param string $referenceType Type de référence (order, creator_payout, etc.)
     * @param int $referenceId ID de la référence
     * @param string $listener Nom du listener ayant détecté la collision
     * @param int|null $existingEntryId ID de l'écriture existante
     */
    public static function recordCollision(
        string $referenceType,
        int $referenceId,
        string $listener,
        ?int $existingEntryId = null
    ): void {
        // 1. Incrémenter le compteur
        $count = Cache::increment(self::COLLISION_COUNTER_KEY, 1);
        
        // Si le compteur n'existait pas, le créer avec TTL
        if ($count === 1 || $count === false) {
            Cache::put(self::COLLISION_COUNTER_KEY, 1, self::COUNTER_TTL);
            $count = 1;
        }

        // 2. Log métier structuré
        Log::channel('daily')->warning('ACCOUNTING_IDEMPOTENCE_COLLISION', [
            'event' => 'idempotence_collision',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'listener' => $listener,
            'existing_entry_id' => $existingEntryId,
            'collision_count_24h' => $count,
            'timestamp' => now()->toIso8601String(),
        ]);

        // 3. Alerte si seuil critique atteint (> 10 collisions en 24h)
        if ($count === 10 || $count === 50 || $count === 100) {
            Log::channel('daily')->error('ACCOUNTING_COLLISION_THRESHOLD_EXCEEDED', [
                'event' => 'collision_threshold_alert',
                'threshold' => $count,
                'message' => "Seuil de {$count} collisions atteint en 24h - Investigation requise",
            ]);
        }
    }

    /**
     * Obtenir le nombre de collisions sur les dernières 24h
     */
    public static function getCollisionCount(): int
    {
        return (int) Cache::get(self::COLLISION_COUNTER_KEY, 0);
    }

    /**
     * Réinitialiser le compteur (pour tests ou maintenance)
     */
    public static function resetCounter(): void
    {
        Cache::forget(self::COLLISION_COUNTER_KEY);
    }

    /**
     * Vérifier si le seuil d'alerte est atteint
     * 
     * @param int $threshold Seuil (défaut: 10)
     */
    public static function isAlertThresholdReached(int $threshold = 10): bool
    {
        return self::getCollisionCount() >= $threshold;
    }
}
