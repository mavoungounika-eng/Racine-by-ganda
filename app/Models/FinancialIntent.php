<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Accounting\Models\AccountingEntry;
use App\Models\User;

/**
 * FinancialIntent - Représente l'intention d'une opération financière
 * 
 * ARCHITECTURE:
 * Un intent représente l'INTENTION d'une opération financière AVANT sa réalisation.
 * Les listeners CONSOMMENT des intents, ils ne CRÉENT pas de vérité financière.
 * Le point d'irréversibilité est marqué par le passage à 'committed'.
 * 
 * STATUTS:
 * - pending: Intent créé, en attente de traitement
 * - processing: En cours de traitement par un listener  
 * - committed: Écriture comptable créée, IRRÉVERSIBLE
 * - reversed: Contre-passation effectuée
 * - failed: Échec définitif après retries
 * 
 * @property int $id
 * @property string $intent_type
 * @property string $reference_type
 * @property int $reference_id
 * @property float $amount
 * @property string $currency
 * @property string $status
 * @property int|null $accounting_entry_id
 * @property array|null $metadata
 * @property string $idempotency_key
 * @property int $attempt_count
 * @property \DateTime|null $last_attempt_at
 * @property string|null $last_error
 * @property int|null $created_by
 * @property int|null $committed_by
 * @property \DateTime|null $committed_at
 */
class FinancialIntent extends Model
{
    protected $table = 'financial_intents';

    protected $fillable = [
        'intent_type',
        'reference_type',
        'reference_id',
        'amount',
        'currency',
        'status',
        'accounting_entry_id',
        'metadata',
        'idempotency_key',
        'attempt_count',
        'last_attempt_at',
        'last_error',
        'created_by',
        'committed_by',
        'committed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'last_attempt_at' => 'datetime',
        'committed_at' => 'datetime',
    ];

    /**
     * Statuts possibles
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMMITTED = 'committed';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_FAILED = 'failed';

    /**
     * Types d'intent
     */
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_REFUND = 'refund';
    public const TYPE_ADJUSTMENT = 'adjustment';
    
    // POS Intent Types (Audit-Ready)
    public const TYPE_POS_CASH_SETTLEMENT = 'pos_cash_settlement';
    public const TYPE_POS_CARD_PAYMENT = 'pos_card_payment';
    public const TYPE_POS_MOBILE_PAYMENT = 'pos_mobile_payment';

    /**
     * Relation: Écriture comptable liée
     */
    public function accountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'accounting_entry_id');
    }

    /**
     * Relation: Utilisateur créateur
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation: Utilisateur ayant commis
     */
    public function committer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'committed_by');
    }

    /**
     * Scope: Intents en attente
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Intents commis
     */
    public function scopeCommitted($query)
    {
        return $query->where('status', self::STATUS_COMMITTED);
    }

    /**
     * Scope: Par référence
     */
    public function scopeForReference($query, string $type, int $id)
    {
        return $query->where('reference_type', $type)->where('reference_id', $id);
    }

    /**
     * Vérifier si intent peut être traité
     */
    public function canProcess(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING]);
    }

    /**
     * Vérifier si intent est déjà commis
     */
    public function isCommitted(): bool
    {
        return $this->status === self::STATUS_COMMITTED;
    }

    /**
     * Marquer comme en traitement
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'attempt_count' => $this->attempt_count + 1,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Marquer comme commis (IRRÉVERSIBLE)
     */
    public function markAsCommitted(AccountingEntry $entry, ?int $userId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMMITTED,
            'accounting_entry_id' => $entry->id,
            'committed_by' => $userId ?? auth()->id(),
            'committed_at' => now(),
        ]);
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'last_error' => $error,
        ]);
    }

    /**
     * Générer clé d'idempotence
     */
    public static function generateIdempotencyKey(string $type, int $id): string
    {
        return hash('sha256', "{$type}:{$id}");
    }
}
