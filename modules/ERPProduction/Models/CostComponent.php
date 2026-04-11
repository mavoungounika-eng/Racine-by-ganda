<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostComponent extends Model
{
    protected $table = 'erp_cost_components';

    protected $fillable = [
        'production_cost_id',
        'component_type',
        'amount',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Relations
     */
    public function productionCost(): BelongsTo
    {
        return $this->belongsTo(ProductionCost::class);
    }

    /**
     * Scopes
     */
    public function scopeMaterial($query)
    {
        return $query->where('component_type', 'material');
    }

    public function scopeScrap($query)
    {
        return $query->where('component_type', 'scrap');
    }

    public function scopeRework($query)
    {
        return $query->where('component_type', 'rework');
    }

    public function scopeOverhead($query)
    {
        return $query->where('component_type', 'overhead');
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Vérifier si composante matière
     */
    public function isMaterial(): bool
    {
        return $this->component_type === 'material';
    }

    /**
     * Vérifier si composante rebut
     */
    public function isScrap(): bool
    {
        return $this->component_type === 'scrap';
    }

    /**
     * Vérifier si composante reprise
     */
    public function isRework(): bool
    {
        return $this->component_type === 'rework';
    }

    /**
     * Vérifier si composante frais généraux
     */
    public function isOverhead(): bool
    {
        return $this->component_type === 'overhead';
    }
}
