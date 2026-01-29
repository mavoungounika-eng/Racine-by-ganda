<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PosOperatorAuditLog - Audit trail des opérations POS
 *
 * Traçabilité complète:
 * - Qui a fait l'action
 * - Quand (timestamp microseconde)
 * - Quoi (action, changements avant/après)
 * - Où (IP, user agent)
 */
class PosOperatorAuditLog extends Model
{
    protected $table = 'pos_operator_audit_logs';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'pos_session_id',
        'old_values',
        'new_values',
        'notes',
        'ip_address',
        'user_agent',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    // Actions
    public const ACTION_SESSION_OPEN = 'SESSION_OPEN';
    public const ACTION_SESSION_CLOSE = 'SESSION_CLOSE';
    public const ACTION_SALE_CREATED = 'SALE_CREATED';
    public const ACTION_SALE_CANCELLED = 'SALE_CANCELLED';
    public const ACTION_CASH_ADJUSTMENT = 'CASH_ADJUSTMENT';
    public const ACTION_INCIDENT = 'INCIDENT';

    /**
     * Utilisateur qui a effectué l'action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Session POS concernée
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'pos_session_id');
    }

    /**
     * Formater action en texte lisible
     */
    public function getReadableActionAttribute(): string
    {
        return match($this->action) {
            self::ACTION_SESSION_OPEN => 'Ouverture session',
            self::ACTION_SESSION_CLOSE => 'Clôture session',
            self::ACTION_SALE_CREATED => 'Vente créée',
            self::ACTION_SALE_CANCELLED => 'Vente annulée',
            self::ACTION_CASH_ADJUSTMENT => 'Ajustement cash',
            default => str_replace('_', ' ', $this->action),
        };
    }

    /**
     * Formater les changements pour affichage
     */
    public function getFormattedChangesAttribute(): array
    {
        return [
            'before' => $this->old_values ?? [],
            'after' => $this->new_values ?? [],
        ];
    }

    /**
     * Scope: filtrer par action
     */
    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: filtrer par utilisateur
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: filtrer par session
     */
    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('pos_session_id', $sessionId);
    }

    /**
     * Scope: filtrer par plage de dates
     */
    public function scopeInDateRange($query, \DateTime $startDate, \DateTime $endDate)
    {
        return $query->whereBetween('timestamp', [$startDate, $endDate]);
    }
}
