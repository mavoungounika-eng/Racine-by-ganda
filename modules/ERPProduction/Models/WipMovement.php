<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class WipMovement extends Model
{
    protected $table = 'erp_wip_movements';

    protected $fillable = [
        'production_order_id',
        'work_step_id',
        'type',
        'quantity',
        'description',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    /**
     * Relations
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function workStep(): BelongsTo
    {
        return $this->belongsTo(WorkStep::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('production_order_id', $orderId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Vérifier si mouvement est un démarrage production
     */
    public function isStart(): bool
    {
        return $this->type === 'production_started';
    }

    /**
     * Vérifier si mouvement est une fin production
     */
    public function isFinish(): bool
    {
        return $this->type === 'production_finished';
    }

    /**
     * Vérifier si mouvement est un rebut
     */
    public function isScrap(): bool
    {
        return $this->type === 'scrap';
    }

    /**
     * Vérifier si mouvement est une reprise
     */
    public function isRework(): bool
    {
        return $this->type === 'rework';
    }

    /**
     * Vérifier si mouvement affecte le stock
     */
    public function affectsStock(): bool
    {
        return in_array($this->type, ['production_started', 'production_finished', 'scrap']);
    }

    /**
     * Obtenir description complète
     */
    public function getFullDescription(): string
    {
        $desc = match ($this->type) {
            'production_started' => "Démarrage production - {$this->quantity} unités",
            'step_completed' => "Étape complétée - {$this->quantity} unités",
            'production_finished' => "Production terminée - {$this->quantity} unités",
            'scrap' => "Rebut - {$this->quantity} unités",
            'rework' => "Reprise - {$this->quantity} unités",
        };

        if ($this->description) {
            $desc .= " - {$this->description}";
        }

        return $desc;
    }
}
