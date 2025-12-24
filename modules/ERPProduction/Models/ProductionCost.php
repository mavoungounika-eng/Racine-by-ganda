<?php

namespace Modules\ERPProduction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Product;

class ProductionCost extends Model
{
    protected $table = 'erp_production_costs';

    protected $fillable = [
        'production_order_id',
        'product_id',
        'quantity_produced',
        'theoretical_unit_cost',
        'actual_unit_cost',
        'total_actual_cost',
        'cost_variance',
        'yield_rate',
        'calculated_at',
    ];

    protected $casts = [
        'quantity_produced' => 'decimal:2',
        'theoretical_unit_cost' => 'decimal:2',
        'actual_unit_cost' => 'decimal:2',
        'total_actual_cost' => 'decimal:2',
        'cost_variance' => 'decimal:2',
        'yield_rate' => 'decimal:2',
        'calculated_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(CostComponent::class);
    }

    /**
     * Méthodes métier
     */
    
    /**
     * Obtenir coût unitaire réel
     */
    public function getUnitCost(): float
    {
        return $this->actual_unit_cost;
    }

    /**
     * Obtenir écart coût
     */
    public function getVariance(): float
    {
        return $this->cost_variance;
    }

    /**
     * Vérifier si hors budget
     */
    public function isOverBudget(): bool
    {
        return $this->cost_variance > 0;
    }

    /**
     * Calculer impact marge
     */
    public function getMarginImpact(float $sellingPrice): array
    {
        $theoreticalMargin = $sellingPrice - $this->theoretical_unit_cost;
        $actualMargin = $sellingPrice - $this->actual_unit_cost;
        $marginLoss = $theoreticalMargin - $actualMargin;

        return [
            'selling_price' => $sellingPrice,
            'theoretical_margin' => $theoreticalMargin,
            'actual_margin' => $actualMargin,
            'margin_loss' => $marginLoss,
            'margin_loss_percentage' => $theoreticalMargin > 0 
                ? ($marginLoss / $theoreticalMargin) * 100 
                : 0,
        ];
    }

    /**
     * Obtenir résumé coûts
     */
    public function getSummary(): array
    {
        return [
            'quantity_produced' => $this->quantity_produced,
            'theoretical_unit_cost' => $this->theoretical_unit_cost,
            'actual_unit_cost' => $this->actual_unit_cost,
            'total_actual_cost' => $this->total_actual_cost,
            'cost_variance' => $this->cost_variance,
            'variance_percentage' => $this->theoretical_unit_cost > 0 
                ? ($this->cost_variance / $this->theoretical_unit_cost) * 100 
                : 0,
            'yield_rate' => $this->yield_rate,
            'is_over_budget' => $this->isOverBudget(),
        ];
    }
}
