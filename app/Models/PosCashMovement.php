<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PosCashMovement - Mouvement de caisse auditable
 * 
 * Types:
 * - opening: À l'ouverture session
 * - closing: À la clôture session
 * - sale: À chaque vente cash
 * - refund: À chaque remboursement
 * - adjustment: Écarts expliqués
 * 
 * @property int $id
 * @property int $session_id
 * @property string $type
 * @property float $amount
 * @property string $direction
 * @property string|null $reason
 * @property int|null $pos_sale_id
 * @property int $created_by
 * @property \DateTime $created_at
 */
class PosCashMovement extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'type',
        'amount',
        'direction',
        'reason',
        'pos_sale_id',
        'created_by',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Types de mouvement
    public const TYPE_OPENING = 'opening';
    public const TYPE_SALE = 'sale';
    public const TYPE_REFUND = 'refund';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_CLOSING = 'closing';

    // Directions
    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    /**
     * Session de caisse
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(PosSession::class, 'session_id');
    }

    /**
     * Vente POS liée (optionnel)
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    /**
     * Créateur du mouvement
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope: Mouvements d'une session
     */
    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope: Par type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Entrées (in)
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', self::DIRECTION_IN);
    }

    /**
     * Scope: Sorties (out)
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', self::DIRECTION_OUT);
    }

    /**
     * Créer mouvement d'ouverture
     */
    public static function createOpening(PosSession $session, float $amount, int $userId): self
    {
        return self::create([
            'session_id' => $session->id,
            'type' => self::TYPE_OPENING,
            'amount' => $amount,
            'direction' => self::DIRECTION_IN,
            'reason' => 'Fond de caisse ouverture',
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * Créer mouvement de vente cash
     */
    public static function createSale(PosSale $sale, float $amount, int $userId): self
    {
        return self::create([
            'session_id' => $sale->session_id,
            'type' => self::TYPE_SALE,
            'amount' => $amount,
            'direction' => self::DIRECTION_IN,
            'pos_sale_id' => $sale->id,
            'reason' => "Vente #{$sale->id}",
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * Créer mouvement de remboursement
     */
    public static function createRefund(PosSale $sale, float $amount, int $userId, string $reason): self
    {
        return self::create([
            'session_id' => $sale->session_id,
            'type' => self::TYPE_REFUND,
            'amount' => $amount,
            'direction' => self::DIRECTION_OUT,
            'pos_sale_id' => $sale->id,
            'reason' => $reason,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * Créer mouvement d'ajustement
     */
    public static function createAdjustment(PosSession $session, float $amount, string $direction, string $reason, int $userId): self
    {
        return self::create([
            'session_id' => $session->id,
            'type' => self::TYPE_ADJUSTMENT,
            'amount' => abs($amount),
            'direction' => $direction,
            'reason' => $reason,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * Créer mouvement de clôture
     */
    public static function createClosing(PosSession $session, float $closingCash, int $userId): self
    {
        return self::create([
            'session_id' => $session->id,
            'type' => self::TYPE_CLOSING,
            'amount' => $closingCash,
            'direction' => self::DIRECTION_OUT,
            'reason' => 'Clôture de caisse',
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }
}
