<?php

namespace Modules\ERPProduction\Services;

use Modules\ERPProduction\Models\ProductionOrder;
use Modules\ERPProduction\Models\ProductionCost;
use Modules\ERPProduction\Models\CostComponent;
use Modules\ERPProduction\Models\QualityCheck;
use Modules\ERPProduction\Events\ProductionCostCalculated;
use Illuminate\Support\Facades\DB;

class CostingService
{
    /**
     * Calculer coût pour ordre production
     */
    public function calculateForOrder(ProductionOrder $order): ProductionCost
    {
        // Valider préconditions
        $this->validateCompleteness($order);

        return DB::transaction(function () use ($order) {
            // Calculer composantes
            $materialCost = $this->calculateMaterialCost($order);
            $scrapCost = $this->calculateScrapCost($order);
            $reworkCost = $this->calculateReworkCost($order);
            
            $totalActualCost = $materialCost + $scrapCost + $reworkCost;
            $actualUnitCost = $order->quantity_produced > 0 
                ? $totalActualCost / $order->quantity_produced 
                : 0;

            $theoreticalCost = $this->calculateTheoreticalCost($order);
            $variance = $this->calculateVariance($order, $actualUnitCost, $theoreticalCost);
            $yieldRate = $this->getYieldRate($order);

            // Créer coût production
            $productionCost = ProductionCost::create([
                'production_order_id' => $order->id,
                'product_id' => $order->product_id,
                'quantity_produced' => $order->quantity_produced,
                'theoretical_unit_cost' => $theoreticalCost,
                'actual_unit_cost' => $actualUnitCost,
                'total_actual_cost' => $totalActualCost,
                'cost_variance' => $variance,
                'yield_rate' => $yieldRate,
                'calculated_at' => now(),
            ]);

            // Créer composantes
            $this->buildCostComponents($productionCost, $materialCost, $scrapCost, $reworkCost);

            // Dispatch événement
            $this->dispatchCostingCompletedEvent($productionCost);

            return $productionCost->fresh(['components']);
        });
    }

    /**
     * Calculer coût matières
     */
    public function calculateMaterialCost(ProductionOrder $order): float
    {
        // Coût matières = BOM × quantité planifiée
        return $order->bom->calculateTotalMaterialCost() * $order->quantity_planned;
    }

    /**
     * Calculer coût rebuts
     */
    public function calculateScrapCost(ProductionOrder $order): float
    {
        $qualityChecks = QualityCheck::where('production_order_id', $order->id)->get();
        $totalRejected = $qualityChecks->sum('quantity_rejected');

        if ($totalRejected <= 0) {
            return 0;
        }

        // Coût rebut = quantité rejetée × coût unitaire BOM
        $unitCost = $order->bom->calculateUnitMaterialCost();
        return $totalRejected * $unitCost;
    }

    /**
     * Calculer coût reprises
     */
    public function calculateReworkCost(ProductionOrder $order): float
    {
        $qualityChecks = QualityCheck::where('production_order_id', $order->id)->get();
        $totalReworked = $qualityChecks->sum('quantity_reworked');

        if ($totalReworked <= 0) {
            return 0;
        }

        // Coût reprise = quantité reprise × coût unitaire BOM × facteur reprise (50%)
        // Note: Facteur reprise estimé à 50% du coût initial
        $unitCost = $order->bom->calculateUnitMaterialCost();
        $reworkFactor = 0.5;
        
        return $totalReworked * $unitCost * $reworkFactor;
    }

    /**
     * Calculer coût unitaire réel
     */
    public function calculateActualUnitCost(ProductionOrder $order): float
    {
        $materialCost = $this->calculateMaterialCost($order);
        $scrapCost = $this->calculateScrapCost($order);
        $reworkCost = $this->calculateReworkCost($order);
        
        $totalCost = $materialCost + $scrapCost + $reworkCost;

        return $order->quantity_produced > 0 
            ? $totalCost / $order->quantity_produced 
            : 0;
    }

    /**
     * Calculer coût théorique (BOM)
     */
    public function calculateTheoreticalCost(ProductionOrder $order): float
    {
        return $order->bom->calculateUnitMaterialCost();
    }

    /**
     * Calculer écart
     */
    public function calculateVariance(ProductionOrder $order, ?float $actualUnitCost = null, ?float $theoreticalCost = null): float
    {
        $actualUnitCost = $actualUnitCost ?? $this->calculateActualUnitCost($order);
        $theoreticalCost = $theoreticalCost ?? $this->calculateTheoreticalCost($order);

        return $actualUnitCost - $theoreticalCost;
    }

    /**
     * Obtenir taux de rendement
     */
    public function getYieldRate(ProductionOrder $order): float
    {
        if ($order->quantity_planned <= 0) {
            return 0;
        }

        return ($order->quantity_produced / $order->quantity_planned) * 100;
    }

    /**
     * Construire composantes coût
     */
    protected function buildCostComponents(ProductionCost $productionCost, float $materialCost, float $scrapCost, float $reworkCost): void
    {
        // Matières
        CostComponent::create([
            'production_cost_id' => $productionCost->id,
            'component_type' => 'material',
            'amount' => $materialCost,
            'description' => 'Coût matières premières (BOM)',
        ]);

        // Rebuts
        if ($scrapCost > 0) {
            CostComponent::create([
                'production_cost_id' => $productionCost->id,
                'component_type' => 'scrap',
                'amount' => $scrapCost,
                'description' => 'Coût rebuts qualité',
            ]);
        }

        // Reprises
        if ($reworkCost > 0) {
            CostComponent::create([
                'production_cost_id' => $productionCost->id,
                'component_type' => 'rework',
                'amount' => $reworkCost,
                'description' => 'Coût reprises qualité',
            ]);
        }
    }

    /**
     * Valider complétude ordre
     */
    public function validateCompleteness(ProductionOrder $order): void
    {
        if ($order->status !== 'closed') {
            throw new \Exception("Production order must be closed before costing");
        }

        if (!QualityCheck::where('production_order_id', $order->id)->exists()) {
            throw new \Exception("Quality check required before costing");
        }

        if ($order->quantity_produced <= 0) {
            throw new \Exception("No units produced, cannot calculate cost");
        }
    }

    /**
     * Dispatch événement costing complété
     */
    protected function dispatchCostingCompletedEvent(ProductionCost $productionCost): void
    {
        event(new ProductionCostCalculated($productionCost));
    }

    /**
     * Obtenir résumé coûts
     */
    public function getCostSummary(ProductionOrder $order): array
    {
        $productionCost = ProductionCost::where('production_order_id', $order->id)->first();

        if (!$productionCost) {
            return [
                'calculated' => false,
                'message' => 'Cost not yet calculated',
            ];
        }

        $components = $productionCost->components;

        return [
            'calculated' => true,
            'quantity_produced' => $productionCost->quantity_produced,
            'theoretical_unit_cost' => $productionCost->theoretical_unit_cost,
            'actual_unit_cost' => $productionCost->actual_unit_cost,
            'total_actual_cost' => $productionCost->total_actual_cost,
            'cost_variance' => $productionCost->cost_variance,
            'variance_percentage' => $productionCost->theoretical_unit_cost > 0 
                ? ($productionCost->cost_variance / $productionCost->theoretical_unit_cost) * 100 
                : 0,
            'yield_rate' => $productionCost->yield_rate,
            'is_over_budget' => $productionCost->isOverBudget(),
            'components' => [
                'material' => $components->where('component_type', 'material')->sum('amount'),
                'scrap' => $components->where('component_type', 'scrap')->sum('amount'),
                'rework' => $components->where('component_type', 'rework')->sum('amount'),
                'overhead' => $components->where('component_type', 'overhead')->sum('amount'),
            ],
        ];
    }
}
