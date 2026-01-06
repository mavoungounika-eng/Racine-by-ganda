<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PosPayment - Paiement terrain (fait, pas vérité comptable)
 * 
 * RÈGLES CRITIQUES:
 * - CASH: status = 'pending' jusqu'à clôture session
 * - CARD: status = 'pending' jusqu'à callback/confirmation TPE
 * - MOBILE: status = 'pending' jusqu'à callback Monetbil
 * - JAMAIS 'confirmed' automatiquement à la création
 * 
 * @property int $id
 * @property int $pos_sale_id
 * @property string $method
 * @property float $amount
 * @property string $status
 * @property \DateTime|null $confirmed_at
 * @property int|null $confirmed_by
 * @property string|null $external_reference
 * @property string|null $provider
 * @property array|null $metadata
 */
class PosPayment extends Model
{
    protected $fillable = [
        'pos_sale_id',
        'method',
        'amount',
        'status',
        'confirmed_at',
        'confirmed_by',
        'external_reference',
        'provider',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Statuts
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

    // Méthodes
    public const METHOD_CASH = 'cash';
    public const METHOD_CARD = 'card';
    public const METHOD_MOBILE = 'mobile_money';

    /**
     * Vente POS liée
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    /**
     * Utilisateur qui a confirmé
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Scope: Paiements pending
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope: Paiements confirmés
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope: Paiements cash
     */
    public function scopeCash($query)
    {
        return $query->where('method', self::METHOD_CASH);
    }

    /**
     * Scope: Paiements card
     */
    public function scopeCard($query)
    {
        return $query->where('method', self::METHOD_CARD);
    }

    /**
     * Scope: Paiements mobile
     */
    public function scopeMobile($query)
    {
        return $query->where('method', self::METHOD_MOBILE);
    }

    /**
     * Vérifier si pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Vérifier si confirmé
     */
    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    /**
     * Vérifier si c'est du cash
     */
    public function isCash(): bool
    {
        return $this->method === self::METHOD_CASH;
    }

    /**
     * Confirmer le paiement
     * 
     * @param int $confirmedBy User ID
     * @param string|null $externalReference TPE receipt, txn_id, etc.
     */
    public function confirm(int $confirmedBy, ?string $externalReference = null): void
    {
        $this->update([
            'status' => self::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirmed_by' => $confirmedBy,
            'external_reference' => $externalReference ?? $this->external_reference,
        ]);
    }

    /**
     * Annuler le paiement
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }
}
