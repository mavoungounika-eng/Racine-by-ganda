<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionCostSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id',
        'material_cost_real',
        'labor_cost_real',
        'overhead_cost',
        'total_cost',
        'unit_cost_good',
        'unit_cost_second',
        'standard_cost',
        'variance',
        'variance_percentage',
        'qty_good',
        'qty_second',
        'qty_rejected',
        'bom_version',
        'calculated_at',
    ];

    protected $casts = [
        'material_cost_real' => 'decimal:2',
        'labor_cost_real' => 'decimal:2',
        'overhead_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'unit_cost_good' => 'decimal:2',
        'unit_cost_second' => 'decimal:2',
        'standard_cost' => 'decimal:2',
        'variance' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
        'qty_good' => 'integer',
        'qty_second' => 'integer',
        'qty_rejected' => 'integer',
        'calculated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * Computed Properties
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->qty_good + $this->qty_second + $this->qty_rejected;
    }

    public function getEfficiencyRateAttribute(): float
    {
        if ($this->total_quantity === 0) {
            return 0;
        }

        return round(($this->qty_good / $this->total_quantity) * 100, 2);
    }

    public function isOverBudget(): bool
    {
        if ($this->standard_cost === null) {
            return false;
        }

        return $this->total_cost > $this->standard_cost;
    }
}
