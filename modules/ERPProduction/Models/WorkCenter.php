<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCenter extends Model
{
    protected $table = 'erp_work_centers';

    protected $fillable = [
        'name',
        'code',
        'description',
        'capacity_per_day',
        'is_active',
    ];

    protected $casts = [
        'capacity_per_day' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Relations
     */
    public function steps(): HasMany
    {
        return $this->hasMany(WorkStep::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Vérifier si centre disponible
     */
    public function isAvailable(): bool
    {
        return $this->is_active;
    }

    /**
     * Obtenir capacité journalière
     */
    public function getDailyCapacity(): float
    {
        return $this->capacity_per_day ?? 0;
    }

    /**
     * Obtenir étapes en cours
     */
    public function getActiveSteps()
    {
        return $this->steps()->where('status', 'in_progress')->get();
    }

    /**
     * Calculer charge actuelle (nombre d'étapes en cours)
     */
    public function getCurrentLoad(): int
    {
        return $this->steps()->where('status', 'in_progress')->count();
    }
}
