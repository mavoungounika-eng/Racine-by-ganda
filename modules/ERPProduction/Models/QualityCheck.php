<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class QualityCheck extends Model
{
    protected $table = 'erp_quality_checks';

    protected $fillable = [
        'production_order_id',
        'work_step_id',
        'status',
        'quantity_checked',
        'quantity_passed',
        'quantity_reworked',
        'quantity_rejected',
        'notes',
        'checked_at',
        'checked_by',
    ];

    protected $casts = [
        'quantity_checked' => 'decimal:2',
        'quantity_passed' => 'decimal:2',
        'quantity_reworked' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
        'checked_at' => 'datetime',
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

    public function defects(): HasMany
    {
        return $this->hasMany(QualityDefect::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    /**
     * Scopes
     */
    public function scopePass($query)
    {
        return $query->where('status', 'pass');
    }

    public function scopeRework($query)
    {
        return $query->where('status', 'rework');
    }

    public function scopeReject($query)
    {
        return $query->where('status', 'reject');
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Vérifier si contrôle est passé
     */
    public function isPass(): bool
    {
        return $this->status === 'pass';
    }

    /**
     * Vérifier si nécessite reprise
     */
    public function isRework(): bool
    {
        return $this->status === 'rework';
    }

    /**
     * Vérifier si rejeté
     */
    public function isReject(): bool
    {
        return $this->status === 'reject';
    }

    /**
     * Vérifier si défaut critique
     */
    public function hasCriticalDefect(): bool
    {
        return $this->defects()->where('severity', 'critical')->exists();
    }

    /**
     * Calculer taux de rejet
     */
    public function getRejectionRate(): float
    {
        if ($this->quantity_checked <= 0) {
            return 0;
        }

        return ($this->quantity_rejected / $this->quantity_checked) * 100;
    }

    /**
     * Calculer taux de conformité
     */
    public function getPassRate(): float
    {
        if ($this->quantity_checked <= 0) {
            return 0;
        }

        return ($this->quantity_passed / $this->quantity_checked) * 100;
    }

    /**
     * Calculer taux de reprise
     */
    public function getReworkRate(): float
    {
        if ($this->quantity_checked <= 0) {
            return 0;
        }

        return ($this->quantity_reworked / $this->quantity_checked) * 100;
    }

    /**
     * Obtenir résumé qualité
     */
    public function getSummary(): array
    {
        return [
            'checked' => $this->quantity_checked,
            'passed' => $this->quantity_passed,
            'reworked' => $this->quantity_reworked,
            'rejected' => $this->quantity_rejected,
            'pass_rate' => round($this->getPassRate(), 2),
            'rework_rate' => round($this->getReworkRate(), 2),
            'rejection_rate' => round($this->getRejectionRate(), 2),
            'has_critical_defect' => $this->hasCriticalDefect(),
            'defects_count' => $this->defects()->count(),
        ];
    }
}
