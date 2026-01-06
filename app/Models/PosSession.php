<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * PosSession - Session de caisse obligatoire
 * 
 * INVARIANTS:
 * - Une machine ne peut avoir qu'UNE session 'open' à la fois
 * - opening_cash obligatoire à l'ouverture
 * - closing_cash obligatoire pour passer à 'closed'
 * - Pas de vente sans session ouverte
 * 
 * @property int $id
 * @property string $machine_id
 * @property int $opened_by
 * @property \DateTime $opened_at
 * @property float $opening_cash
 * @property string $status
 * @property \DateTime|null $closed_at
 * @property float|null $closing_cash
 * @property float|null $expected_cash
 * @property float|null $cash_difference
 * @property int|null $closed_by
 * @property string|null $notes
 */
class PosSession extends Model
{
    protected $fillable = [
        'machine_id',
        'opened_by',
        'opened_at',
        'opening_cash',
        'status',
        'closed_at',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'closed_by',
        'notes',
    ];

    protected $casts = [
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Statuts
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSING = 'closing';
    public const STATUS_CLOSED = 'closed';

    /**
     * Utilisateur qui a ouvert la session
     */
    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * Utilisateur qui a fermé la session
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Ventes de cette session
     */
    public function sales(): HasMany
    {
        return $this->hasMany(PosSale::class, 'session_id');
    }

    /**
     * Mouvements cash de cette session
     */
    public function cashMovements(): HasMany
    {
        return $this->hasMany(PosCashMovement::class, 'session_id');
    }

    /**
     * Scope: Sessions ouvertes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope: Sessions pour une machine
     */
    public function scopeForMachine($query, string $machineId)
    {
        return $query->where('machine_id', $machineId);
    }

    /**
     * Scope: Sessions d'aujourd'hui
     */
    public function scopeToday($query)
    {
        return $query->whereDate('opened_at', today());
    }

    /**
     * Vérifier si la session est ouverte
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    /**
     * Vérifier si la session est fermée
     */
    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    /**
     * Vérifier si on peut vendre sur cette session
     */
    public function canSell(): bool
    {
        return $this->isOpen();
    }

    /**
     * Vérifier si on peut fermer cette session
     */
    public function canClose(): bool
    {
        return $this->isOpen();
    }

    /**
     * Calculer le cash attendu
     * = opening_cash + sum(cash_in) - sum(cash_out)
     * Note: Exclude 'opening' type movement to avoid double-counting
     */
    public function calculateExpectedCash(): float
    {
        // Exclude 'opening' movements since opening_cash is already a field
        $movements = $this->cashMovements()->where('type', '!=', 'opening')->get();
        
        $cashIn = $movements->where('direction', 'in')->sum('amount');
        $cashOut = $movements->where('direction', 'out')->sum('amount');
        
        return $this->opening_cash + $cashIn - $cashOut;
    }

    /**
     * Préparer la clôture (calcul expected_cash)
     */
    public function prepareClose(): void
    {
        $this->update([
            'status' => self::STATUS_CLOSING,
            'expected_cash' => $this->calculateExpectedCash(),
        ]);
    }

    /**
     * Finaliser la clôture
     */
    public function close(float $closingCash, int $closedBy, ?string $notes = null): void
    {
        $expectedCash = $this->expected_cash ?? $this->calculateExpectedCash();
        
        $this->update([
            'status' => self::STATUS_CLOSED,
            'closed_at' => now(),
            'closing_cash' => $closingCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $closingCash - $expectedCash,
            'closed_by' => $closedBy,
            'notes' => $notes,
        ]);
    }
}
