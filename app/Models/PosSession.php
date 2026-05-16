<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\AuditsPosOperations;

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
    use HasFactory, AuditsPosOperations;

    protected $fillable = [
        'machine_id',
        'opened_by',
        'opened_at',
        'opening_cash',
        'status',
        'is_active',
        'closed_at',
        'closing_cash',
        'expected_cash',
        'cash_difference',
        'closed_by',
        'notes',
        'machine_name',
        'panier_snapshot',
        'total_ventes',
        'nombre_tickets',
        'resumed_at',
        'resumed_by',
        'last_activity_at',
    ];

    protected $casts = [
        'opening_cash' => 'decimal:2',
        'closing_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'is_active'        => 'boolean',
        'panier_snapshot'  => 'array',
        'resumed_at'       => 'datetime',
        'last_activity_at' => 'datetime',
        'total_ventes'     => 'decimal:2',
        'nombre_tickets'   => 'integer',
    ];

    // Statuts
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSING = 'closing';
    public const STATUS_CLOSED = 'closed';

    protected static function booted(): void
    {
        static::created(function (PosSession $session) {
            self::logPosAction(PosOperatorAuditLog::ACTION_SESSION_OPEN, [
                'session_id' => $session->id,
                'opening_amount' => $session->opening_cash,
                'notes' => 'Session automatically logged on creation',
            ], $session->opened_by);
        });

        static::updated(function (PosSession $session) {
            if ($session->wasChanged('status') && $session->status === self::STATUS_CLOSED) {
                self::logPosAction(PosOperatorAuditLog::ACTION_SESSION_CLOSE, [
                    'session_id' => $session->id,
                    'closing_amount' => $session->closing_cash,
                    'expected_cash' => $session->expected_cash,
                    'discrepancy' => $session->cash_difference,
                    'notes' => $session->notes,
                ], $session->closed_by);
            }
        });
    }

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

    /**
     * Legacy alias for backward compatibility (user_id -> opened_by).
     */
    public function getUserIdAttribute(): ?int
    {
        return $this->opened_by;
    }

    /**
     * Legacy alias for backward compatibility (user_id -> opened_by).
     */
    public function setUserIdAttribute(?int $value): void
    {
        $this->attributes['opened_by'] = $value;
    }

    public function resumedBy(): \BelongsTo
    {
        return $this->belongsTo(User::class, 'resumed_by');
    }

    public function scopeFantome($query, int $seuilHeures = 24)
    {
        return $query->open()
            ->where(function ($q) use ($seuilHeures) {
                $q->where('last_activity_at', '<', now()->subHours($seuilHeures))
                  ->orWhereNull('last_activity_at');
            });
    }

    public function toSessionAlert(): array
    {
        $dureeMinutes = $this->opened_at->diffInMinutes(now());
        $h = intdiv($dureeMinutes, 60);
        $m = $dureeMinutes % 60;
        $dureeHuman = $h > 0 ? ($m > 0 ? "{$h}h{$m}min" : "{$h}h") : "{$dureeMinutes} min";
        return [
            'session_id'        => $this->id,
            'operateur_id'      => $this->opened_by,
            'operateur_nom'     => $this->opener?->name ?? 'Inconnu',
            'operateur_email'   => $this->opener?->email ?? '',
            'machine_id'        => $this->machine_id,
            'machine_name'      => $this->machine_name ?? substr($this->machine_id, 0, 8),
            'opened_at'         => $this->opened_at->toIso8601String(),
            'opened_at_human'   => $this->opened_at->format('d/m/Y à H:i'),
            'duree_minutes'     => $dureeMinutes,
            'duree_human'       => $dureeHuman,
            'total_ventes'      => $this->total_ventes ?? 0,
            'nombre_tickets'    => $this->nombre_tickets ?? 0,
            'opening_cash'      => $this->opening_cash,
            'a_panier_en_cours' => !empty($this->panier_snapshot),
        ];
    }

    public function toResumePayload(): array
    {
        return [
            'session_id'      => $this->id,
            'machine_id'      => $this->machine_id,
            'opening_cash'    => $this->opening_cash,
            'total_ventes'    => $this->total_ventes ?? 0,
            'nombre_tickets'  => $this->nombre_tickets ?? 0,
            'opened_at'       => $this->opened_at->toIso8601String(),
            'panier_snapshot' => $this->panier_snapshot ?? [],
            'operateur'       => [
                'id'    => $this->opener?->id,
                'name'  => $this->opener?->name,
                'email' => $this->opener?->email,
            ],
        ];
    }

}
