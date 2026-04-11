<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class WorkStep extends Model
{
    protected $table = 'erp_work_steps';

    protected $fillable = [
        'production_order_id',
        'work_center_id',
        'sequence',
        'name',
        'description',
        'status',
        'estimated_duration',
        'actual_duration',
        'started_at',
        'completed_at',
        'assigned_to',
        'notes',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'estimated_duration' => 'decimal:2',
        'actual_duration' => 'decimal:2',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Vérifier si étape peut démarrer
     */
    public function canStart(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }
        
        // Vérifier que l'étape précédente est complétée
        $previousStep = WorkStep::where('production_order_id', $this->production_order_id)
            ->where('sequence', '<', $this->sequence)
            ->orderBy('sequence', 'desc')
            ->first();
        
        if ($previousStep && $previousStep->status !== 'completed') {
            return false;
        }
        
        return true;
    }

    /**
     * Démarrer étape
     */
    public function startStep(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Compléter étape
     */
    public function completeStep(): void
    {
        $completedAt = now();
        
        $actualDuration = $this->started_at 
            ? $this->started_at->diffInHours($completedAt, true)
            : null;
        
        $this->update([
            'status' => 'completed',
            'completed_at' => $completedAt,
            'actual_duration' => $actualDuration,
        ]);
    }

    /**
     * Vérifier si étape est en retard
     */
    public function isDelayed(): bool
    {
        if (!$this->estimated_duration || $this->status !== 'in_progress') {
            return false;
        }
        
        $currentDuration = $this->started_at->diffInHours(now(), true);
        
        return $currentDuration > $this->estimated_duration;
    }
}
