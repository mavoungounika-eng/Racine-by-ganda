<?php

namespace App\Services\Production;

use App\Models\ProductionOrder;
use App\Models\ProductionCostSummary;
use App\Models\StockMovement;
use App\Exceptions\Production\InvalidOrderStateException;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductionCostingService
{
    /**
     * Generate immutable cost summary on order closure
     * 
     * CRITICAL RULES:
     * - Called ONCE at order completion
     * - Result is IMMUTABLE (audit trail)
     * - Uses ONLY bom_snapshot for standard cost
     * - Updates stock movements with real unit cost
     * 
     * @throws InvalidOrderStateException
     * @throws Exception
     */
    public function generateCostSummary(ProductionOrder $order): ProductionCostSummary
    {
        if ($order->status !== 'completed') {
            throw InvalidOrderStateException::cannotCalculateCost($order->of_number);
        }
        
        // Prevent duplicate generation
        if ($order->costSummary()->exists()) {
            throw new Exception("Cost summary already exists for order {$order->of_number}");
        }
        
        // Calculate real costs
        $materialCost = $this->calculateMaterialCost($order);
        $laborCost = $this->calculateLaborCost($order);
        $totalCost = $materialCost + $laborCost;
        
        $qtyGood = $order->produced_qty_good;
        $qtySecond = $order->produced_qty_second;
        $qtyRejected = $order->rejected_qty;
        
        // Unit cost calculation (only good units bear the cost)
        $unitCostGood = $qtyGood > 0 ? $totalCost / $qtyGood : 0;
        
        // Standard cost from BOM snapshot
        $standardCost = $order->bom_snapshot['total_material_cost_standard'] ?? null;
        $variance = $standardCost ? ($totalCost - $standardCost) : null;
        $variancePercentage = ($standardCost && $standardCost > 0) 
            ? (($variance / $standardCost) * 100) 
            : null;
        
        DB::beginTransaction();
        
        try {
            // Create immutable summary
            $summary = ProductionCostSummary::create([
                'production_order_id' => $order->id,
                'material_cost_real' => round($materialCost, 2),
                'labor_cost_real' => round($laborCost, 2),
                'overhead_cost' => 0, // Future: allocate overhead
                'total_cost' => round($totalCost, 2),
                'unit_cost_good' => round($unitCostGood, 2),
                'unit_cost_second' => null, // Future: different pricing for 2nd choice
                'standard_cost' => $standardCost,
                'variance' => $variance ? round($variance, 2) : null,
                'variance_percentage' => $variancePercentage ? round($variancePercentage, 2) : null,
                'qty_good' => $qtyGood,
                'qty_second' => $qtySecond,
                'qty_rejected' => $qtyRejected,
                'bom_version' => $order->bom_snapshot['version'] ?? null,
            ]);
            
            // Update stock movements with real unit cost
            $this->updateStockValuation($order, $unitCostGood);
            
            DB::commit();
            
            return $summary;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Calculate material cost from actual consumption
     * 
     * TODO Phase C: Use real material prices from stock movements
     * Currently using placeholder value
     */
    private function calculateMaterialCost(ProductionOrder $order): float
    {
        // Placeholder: 10 XAF per unit
        // Future: Get weighted average cost from stock_movements
        return $order->materialLogs()->sum('quantity_used') * 10;
    }
    
    /**
     * Calculate labor cost from actual time logs
     * 
     * TODO: Get real hourly rates from HR module
     * Currently using placeholder value
     */
    private function calculateLaborCost(ProductionOrder $order): float
    {
        $totalMinutes = 0;
        foreach ($order->operations as $operation) {
            $totalMinutes += $operation->timeLogs()->sum('duration_minutes');
        }
        
        // Placeholder: 2000 XAF per hour
        // Future: Get actual operator hourly rates
        return ($totalMinutes / 60) * 2000;
    }
    
    /**
     * Update stock movements with real unit cost
     * 
     * This valorizes the finished goods stock at the REAL production cost,
     * not a theoretical or standard cost.
     */
    private function updateStockValuation(ProductionOrder $order, float $unitCost): void
    {
        StockMovement::where('source_type', 'PRODUCTION')
            ->where('source_id', $order->id)
            ->where('direction', 'IN')
            ->where('material_type', 'finished_good') // Only 1st choice
            ->update([
                'unit_cost' => $unitCost,
                'total_value' => DB::raw("quantity * {$unitCost}"),
            ]);
    }
}
