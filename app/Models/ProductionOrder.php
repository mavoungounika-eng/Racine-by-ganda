<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Exceptions\Production\ImmutableOrderException;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'of_number',
        'product_id',
        'workshop_id',
        'target_quantity',
        'status',
        'planned_start_date',
        'deadline_date',
        'started_at',
        'completed_at',
        'bom_snapshot',
        'notes',
    ];

    protected $casts = [
        'planned_start_date' => 'date',
        'deadline_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'bom_snapshot' => 'array',
        'target_quantity' => 'integer',
    ];

    /**
     * GOVERNANCE RULES (Model-Level Immutability)
     * 
     * These rules are enforced at the model level to prevent
     * any code path from violating immutability constraints.
     */
    protected static function booted()
    {
        // ========================================
        // HARD RULE R6: Prevent modification of completed orders
        // ========================================
        static::updating(function ($order) {
            if ($order->isDirty() && $order->getOriginal('status') === 'completed') {
                throw ImmutableOrderException::cannotModifyCompleted($order->of_number);
            }
            
            // ========================================
            // HARD RULE R8: Prevent BOM snapshot modification after creation
            // ========================================
            if ($order->isDirty('bom_snapshot') && $order->getOriginal('bom_snapshot') !== null) {
                throw ImmutableOrderException::cannotModifyBOMSnapshot($order->of_number);
            }
        });
        
        // ========================================
        // HARD RULE R7: Prevent deletion of completed orders
        // ========================================
        static::deleting(function ($order) {
            if ($order->status === 'completed') {
                throw ImmutableOrderException::cannotDeleteCompleted($order->of_number);
            }
        });
    }

    /**
     * Relationships
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outputs(): HasMany
    {
        return $this->hasMany(ProductionOutput::class);
    }

    public function operations(): HasMany
    {
        return $this->hasMany(ProductionOperation::class);
    }

    public function materialLogs(): HasMany
    {
        return $this->hasMany(ProductionMaterialLog::class);
    }

    public function qualityControls(): HasMany
    {
        return $this->hasMany(ProductionQualityControl::class);
    }

    public function costSummary(): HasOne
    {
        return $this->hasOne(ProductionCostSummary::class);
    }

    /**
     * Scopes
     */
    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['released', 'in_progress']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline_date', '<', now())
            ->whereNotIn('status', ['completed', 'cancelled']);
    }

    /**
     * Computed Properties (Dynamic Aggregation from outputs)
     * 
     * CRITICAL: These are NO LONGER stored columns.
     * They are calculated on-the-fly from production_outputs table.
     * This is the TRUTH, not a cache.
     */
    public function getProducedQtyGoodAttribute(): int
    {
        return $this->outputs()->sum('qty_good');
    }

    public function getProducedQtySecondAttribute(): int
    {
        return $this->outputs()->sum('qty_second');
    }

    public function getRejectedQtyAttribute(): int
    {
        return $this->outputs()->sum('qty_rejected');
    }

    public function getTotalProducedAttribute(): int
    {
        return $this->produced_qty_good + $this->produced_qty_second + $this->rejected_qty;
    }

    public function getQualityRateAttribute(): float
    {
        $total = $this->total_produced;
        if ($total === 0) {
            return 0;
        }
        
        return round(($this->produced_qty_good / $total) * 100, 2);
    }

    public function getCompletionRateAttribute(): float
    {
        if ($this->target_quantity === 0) {
            return 0;
        }
        
        return round(($this->total_produced / $this->target_quantity) * 100, 2);
    }

    /**
     * Status Checks
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function canBeStarted(): bool
    {
        return in_array($this->status, ['draft', 'planned', 'released']);
    }

    public function canBeClosed(): bool
    {
        return $this->status === 'in_progress' && $this->total_produced > 0;
    }
}
