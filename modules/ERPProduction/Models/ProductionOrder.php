<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Product;
use App\Models\User;

class ProductionOrder extends Model
{
    use SoftDeletes;

    protected $table = 'erp_production_orders';

    protected $fillable = [
        'order_number',
        'product_id',
        'bom_id',
        'quantity_planned',
        'quantity_produced',
        'quantity_rejected',
        'status',
        'planned_start_date',
        'planned_end_date',
        'actual_start_date',
        'actual_end_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity_planned' => 'decimal:2',
        'quantity_produced' => 'decimal:2',
        'quantity_rejected' => 'decimal:2',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_date' => 'datetime',
        'actual_end_date' => 'datetime',
    ];

    /**
     * Relations
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function bom(): BelongsTo
    {
        return $this->belongsTo(Bom::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkStep::class)->orderBy('sequence');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeFinished($query)
    {
        return $query->where('status', 'finished');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planned', 'in_progress']);
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Vérifier si ordre peut démarrer
     */
    public function canStart(): bool
    {
        return $this->status === 'planned' 
            && $this->bom_id !== null
            && $this->bom->isComplete();
    }

    /**
     * Vérifier si ordre est terminé
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['finished', 'closed']);
    }

    /**
     * Vérifier si toutes les étapes sont complétées
     */
    public function allStepsCompleted(): bool
    {
        return $this->steps()->where('status', '!=', 'completed')->count() === 0;
    }

    /**
     * Obtenir prochaine étape à exécuter
     */
    public function getNextStep(): ?WorkStep
    {
        return $this->steps()
            ->where('status', 'pending')
            ->orderBy('sequence')
            ->first();
    }

    /**
     * Calculer progression (%)
     */
    public function getProgressPercentage(): float
    {
        $totalSteps = $this->steps()->count();
        
        if ($totalSteps === 0) {
            return 0;
        }
        
        $completedSteps = $this->steps()->where('status', 'completed')->count();
        
        return ($completedSteps / $totalSteps) * 100;
    }

    /**
     * Calculer durée réelle production
     */
    public function getActualDuration(): ?float
    {
        if (!$this->actual_start_date || !$this->actual_end_date) {
            return null;
        }
        
        return $this->actual_start_date->diffInHours($this->actual_end_date);
    }
}
