<?php

namespace App\Traits;

use App\Models\PosOperatorAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
     * Liste statique des actions auditables
     */
    public static function auditableActions(): array
    {
        return [
            PosOperatorAuditLog::ACTION_SESSION_OPEN,
            PosOperatorAuditLog::ACTION_SESSION_CLOSE,
            PosOperatorAuditLog::ACTION_SALE_CREATED,
            PosOperatorAuditLog::ACTION_SALE_CANCELLED,
            PosOperatorAuditLog::ACTION_CASH_ADJUSTMENT,
            'OPERATOR_LOGIN',
            'OPERATOR_LOGOUT',
        ];
    }

    /**
     * Enregistrer action POS dans audit trail (Double stockage: DB + Log File)
     * Asynchrone / Tolérant aux pannes via try-catch.
     */
    public static function logPosAction(string $action, array $data, ?int $operatorId = null): void
    {
        try {
            $userId = $operatorId ?? Auth::id();
            
            // Fallbacks sur les données
            if (!$userId && isset($data['operator_id'])) {
                $userId = $data['operator_id'];
            }

            $ipAddress = request()->ip() ?? '127.0.0.1';
            $userAgent = request()->userAgent() ?? 'Console';
            $timestampMicro = now()->format('Y-m-d H:i:s.u');

            $logContext = array_merge($data, [
                'action' => $action,
                'user_id' => $userId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'timestamp' => $timestampMicro,
            ]);

            // 1. Log Laravel (File Channel 'pos')
            Log::channel('pos')->info("POS_AUDIT: {$action}", $logContext);

            // 2. Base de données
            // Ne pas logger en base si pas d'utilisateur (clé étrangère requise)
            if ($userId) {
                PosOperatorAuditLog::create([
                    'user_id' => $userId,
                    'action' => $action,
                    'pos_session_id' => $data['session_id'] ?? null,
                    'old_values' => isset($data['old_values']) ? json_encode($data['old_values']) : null,
                    'new_values' => json_encode(array_diff_key($data, array_flip(['old_values', 'session_id', 'notes']))),
                    'notes' => $data['notes'] ?? null,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                    // Use standard now() since migration usually uses standard timestamp
                    'timestamp' => now(), 
                ]);
            }
        } catch (\Exception $e) {
            // Un bug d'audit DB ne doit JAMAIS bloquer une vente
            try {
                Log::error("Failed to write POS audit log to DB: " . $e->getMessage(), ['action' => $action]);
            } catch (\Exception $ignored) {
                // Ignore final failure
            }
        }
    }
}
