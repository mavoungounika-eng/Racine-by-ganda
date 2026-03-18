<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Traits\AuditsPosOperations;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * PosSale - Vente POS liée à une session
 * 
 * INVARIANTS:
 * - session_id obligatoire (pas de vente sans session)
 * - Session doit être 'open' pour créer une vente
 * - uuid pour idempotence côté client
 * - status = 'pending' tant que non finalisée
 * 
 * @property int $id
 * @property string $uuid
 * @property int $order_id
 * @property string $machine_id
 * @property int $session_id
 * @property float $total_amount
 * @property string $payment_method
 * @property string $status
 * @property \DateTime|null $finalized_at
 * @property \DateTime|null $cancelled_at
 * @property int|null $cancelled_by
 * @property string|null $cancellation_reason
 * @property int $created_by
 */
class PosSale extends Model
{
    use AuditsPosOperations, HasFactory;

    protected $fillable = [
        'uuid',
        'idempotency_key',
        'order_id',
        'machine_id',
        'session_id',
        'total_amount',
        'payment_method',
        'status',
        'finalized_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'created_by',
        'customer_id',
        'currency',
        'amount_eur',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'finalized_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'customer_id' => 'integer',
        'amount_eur' => 'decimal:2',
    ];

    /**
     * Client lié à la vente
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // Statuts
    public const STATUS_PENDING = 'pending';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_CANCELLED = 'cancelled';

    // Méthodes de paiement
    public const PAYMENT_CASH = 'cash';
    public const PAYMENT_CARD = 'card';
    public const PAYMENT_MOBILE = 'mobile_money';
    public const PAYMENT_MIXED = 'mixed';

    /**
     * Boot method - auto-generate UUID and audit
     */
    protected static function booted(): void
    {
        static::creating(function (PosSale $sale) {
            if (empty($sale->uuid)) {
                $sale->uuid = Str::uuid()->toString();
            }
        });

        static::created(function (PosSale $sale) {
            self::logPosAction(PosOperatorAuditLog::ACTION_SALE_CREATED, [
                'sale_id' => $sale->id,
                'session_id' => $sale->session_id,
                'total_amount' => $sale->total_amount,
                'payment_method' => $sale->payment_method,
            ], $sale->created_by);
        });

        static::updated(function (PosSale $sale) {
            if ($sale->wasChanged('status')) {
                if ($sale->status === self::STATUS_CANCELLED) {
                    self::logPosAction(PosOperatorAuditLog::ACTION_SALE_CANCELLED, [
                        'sale_id' => $sale->id,
                        'session_id' => $sale->session_id,
                        'reason' => $sale->cancellation_reason,
                    ], $sale->cancelled_by);
                } 
            }
        });
    }

    /**
     * Session de caisse
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'session_id');
    }

    /**
     * Commande liée
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Paiements de cette vente
     */
    public function payments(): HasMany
    {
        return $this->hasMany(PosPayment::class, 'pos_sale_id');
    }

    /**
     * Mouvements cash liés
     */
    public function cashMovements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class, 'pos_sale_id');
    }

    /**
     * Créateur de la vente
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Utilisateur qui a annulé
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Scope: Ventes pending
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Ventes finalisées
     */
    public function scopeFinalized($query)
    {
        return $query->where('status', self::STATUS_FINALIZED);
    }

    /**
     * Scope: Ventes d'une session
     */
    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope: Ventes cash
     */
    public function scopeCash($query)
    {
        return $query->where('payment_method', self::PAYMENT_CASH);
    }

    /**
     * Vérifier si la vente est pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifier si la vente est finalisée
     */
    public function isFinalized(): bool
    {
        return $this->status === self::STATUS_FINALIZED;
    }

    /**
     * Vérifier si la vente est annulée
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Finaliser la vente (tous paiements confirmés)
     */
    public function finalize(): void
    {
        $this->update([
            'status' => self::STATUS_FINALIZED,
            'finalized_at' => now(),
        ]);
    }

    /**
     * Annuler la vente
     */
    public function cancel(?int $cancelledBy = null, string $reason = 'timeout'): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy,
            'cancellation_reason' => $reason,
        ]);

        // Annuler les paiements pending liés (si appel système)
        $this->payments()
            ->where('status', PosPayment::STATUS_PENDING)
            ->get()
            ->each(fn (PosPayment $payment) => $payment->cancel($reason));
    }
}
