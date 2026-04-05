<?php

namespace App\Services\Stock;

use App\Models\StockMovement;
use App\Exceptions\Stock\InsufficientStockException;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Get available stock for a specific material
     * 
     * CALCULATION: SUM(IN movements) - SUM(OUT movements)
     * This is the TRUTH - calculated on-the-fly, never cached.
     * 
     * @param string $materialType Type of material (fabric, thread, etc.)
     * @param string $materialReference Unique identifier (SKU, roll number)
     * @return float Available quantity
     */
    public function getAvailableStock(string $materialType, string $materialReference): float
    {
        $inMovements = StockMovement::forMaterial($materialType, $materialReference)
            ->incoming()
            ->sum('quantity');
            
        $outMovements = StockMovement::forMaterial($materialType, $materialReference)
            ->outgoing()
            ->sum('quantity');
            
        return (float) ($inMovements - $outMovements);
    }

    /**
     * HARD RULE R12: Validate stock availability before consumption
     * 
     * This is a BLOCKING rule - if stock is insufficient, production CANNOT proceed.
     * This prevents the ERP from lying about what materials are physically available.
     * 
     * @throws InsufficientStockException
     */
    public function validateStockAvailability(
        string $materialType,
        string $materialReference,
        float $requestedQuantity,
        string $unit = null
    ): void {
        $available = $this->getAvailableStock($materialType, $materialReference);
        
        if ($requestedQuantity > $available) {
            if ($unit) {
                throw InsufficientStockException::forMaterialWithUnit(
                    $materialReference,
                    $requestedQuantity,
                    $available,
                    $unit
                );
            }
            
            throw InsufficientStockException::forMaterial(
                $materialReference,
                $requestedQuantity,
                $available
            );
        }
    }

    /**
     * Record a stock movement (IN or OUT)
     * 
     * This is the ONLY way to modify stock. No direct updates to cached totals.
     * Every movement must be justified (source_type + source_id).
     * 
     * @param array $data Movement data
     * @return StockMovement
     */
    public function recordMovement(array $data): StockMovement
    {
        // Calculate total value if unit cost provided
        $totalValue = null;
        if (isset($data['unit_cost']) && isset($data['quantity'])) {
            $totalValue = $data['unit_cost'] * $data['quantity'];
        }

        return StockMovement::create([
            'material_type' => $data['material_type'],
            'material_id' => $data['material_id'] ?? null,
            'material_reference' => $data['material_reference'],
            'quantity' => $data['quantity'],
            'unit' => $data['unit'],
            'direction' => $data['direction'],
            'source_type' => $data['source_type'],
            'source_id' => $data['source_id'] ?? null,
            'unit_cost' => $data['unit_cost'] ?? null,
            'total_value' => $totalValue,
            'user_id' => $data['user_id'] ?? auth()->id(),
            'notes' => $data['notes'] ?? null,
            'movement_date' => $data['movement_date'] ?? now(),
        ]);
    }

    /**
     * Get stock movement history for a material
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getMovementHistory(string $materialType, string $materialReference)
    {
        return StockMovement::forMaterial($materialType, $materialReference)
            ->with('user')
            ->orderBy('movement_date', 'desc')
            ->get();
    }

    /**
     * Get stock value for a material (using weighted average cost)
     * 
     * @return float Total value of available stock
     */
    public function getStockValue(string $materialType, string $materialReference): float
    {
        // Get all IN movements with cost
        $inMovements = StockMovement::forMaterial($materialType, $materialReference)
            ->incoming()
            ->whereNotNull('unit_cost')
            ->get();

        if ($inMovements->isEmpty()) {
            return 0;
        }

        // Calculate weighted average cost
        $totalQuantity = $inMovements->sum('quantity');
        $totalValue = $inMovements->sum('total_value');

        if ($totalQuantity == 0) {
            return 0;
        }

        $weightedAvgCost = $totalValue / $totalQuantity;
        $availableStock = $this->getAvailableStock($materialType, $materialReference);

        return $weightedAvgCost * $availableStock;
    }
}
