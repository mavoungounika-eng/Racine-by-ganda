<?php

namespace App\Traits;

use App\Models\PosOperatorAuditLog;
use Illuminate\Support\Facades\Auth;

/**
 * Trait pour auditer les opérations POS des opérateurs
 *
 * Automatise la traçabilité:
 * - Qui a fait l'action
 * - Quand
 * - Quoi exactement
 * - Changements de valeurs
 */
trait AuditsPosOperations
{
    /**
     * Enregistrer action POS dans audit trail
     */
    public static function auditOperation(
        string $action,
        ?int $sessionId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $notes = null,
        ?int $actorId = null
    ): PosOperatorAuditLog {
        return PosOperatorAuditLog::create([
            'user_id' => $actorId ?? Auth::id(),
            'action' => $action,
            'pos_session_id' => $sessionId,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'notes' => $notes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Enregistrer ouverture session
     */
    public static function auditSessionOpen(int $sessionId, float $openingCash, ?int $actorId = null): PosOperatorAuditLog
    {
        return self::auditOperation(
            'SESSION_OPEN',
            $sessionId,
            null,
            ['opening_cash' => $openingCash, 'status' => 'open'],
            'Session de caisse ouverte',
            $actorId
        );
    }

    /**
     * Enregistrer clôture session
     */
    public static function auditSessionClose(
        int $sessionId,
        float $expectedCash,
        float $actualCash,
        float $difference,
        ?string $notes = null,
        ?int $actorId = null
    ): PosOperatorAuditLog {
        return self::auditOperation(
            'SESSION_CLOSE',
            $sessionId,
            ['status' => 'open'],
            [
                'status' => 'closed',
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'difference' => $difference,
            ],
            $notes ?? 'Session de caisse clôturée',
            $actorId
        );
    }

    /**
     * Enregistrer vente
     */
    public static function auditSaleCreated(int $sessionId, float $amount, string $paymentMethod): PosOperatorAuditLog
    {
        return self::auditOperation(
            'SALE_CREATED',
            $sessionId,
            null,
            ['amount' => $amount, 'payment_method' => $paymentMethod],
            "Vente {$amount}€ ({$paymentMethod})"
        );
    }

    /**
     * Enregistrer annulation vente
     */
    public static function auditSaleCancelled(int $sessionId, float $amount, string $reason): PosOperatorAuditLog
    {
        return self::auditOperation(
            'SALE_CANCELLED',
            $sessionId,
            null,
            ['amount' => $amount, 'reason' => $reason],
            "Vente annulée: {$reason}"
        );
    }

    /**
     * Enregistrer ajustement cash
     */
    public static function auditCashAdjustment(int $sessionId, float $amount, string $direction, string $reason): PosOperatorAuditLog
    {
        return self::auditOperation(
            'CASH_ADJUSTMENT',
            $sessionId,
            null,
            ['amount' => $amount, 'direction' => $direction],
            "Ajustement cash {$direction}: {$amount}€ ({$reason})"
        );
    }

    /**
     * Enregistrer incident
     */
    public static function auditIncident(int $sessionId, string $incidentType, string $resolution, ?string $notes = null): PosOperatorAuditLog
    {
        return self::auditOperation(
            'INCIDENT_' . strtoupper($incidentType),
            $sessionId,
            null,
            ['type' => $incidentType, 'resolution' => $resolution],
            $notes ?? "Incident {$incidentType} — Résolution: {$resolution}"
        );
    }

    /**
     * Récupérer l'audit trail complet d'une session
     */
    public static function getSessionAuditTrail(int $sessionId): \Illuminate\Database\Eloquent\Collection
    {
        return PosOperatorAuditLog::where('pos_session_id', $sessionId)
            ->orderBy('timestamp', 'asc')
            ->get();
    }
}
