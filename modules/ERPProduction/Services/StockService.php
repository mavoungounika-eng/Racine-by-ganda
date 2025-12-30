<?php

namespace Modules\ERPProduction\Services;

use Modules\ERPProduction\Models\ProductionOrder;
use Modules\ERPProduction\Models\StockMovement;
use Modules\ERPProduction\Models\StockBalance;
use Modules\ERPProduction\Events\RawMaterialConsumed;
use Modules\ERPProduction\Events\FinishedGoodsProduced;
use Modules\ERPProduction\Events\StockScrapped;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StockService
{
    /**
     * Consommer matières premières (démarrage production)
     */
    public function consumeRawMaterials(ProductionOrder $order): array
    {
        return DB::transaction(function () use ($order) {
            $movements = [];
            $materials = [];

            foreach ($order->bom->items as $bomItem) {
                $quantityNeeded = $bomItem->getQuantityWithWaste() * $order->quantity_planned;
                $rawMaterial = $bomItem->rawMaterial;

                // Créer mouvement sortie matières premières
                $movement = StockMovement::create([
                    'material_id' => $rawMaterial->id,
                    'production_order_id' => $order->id,
                    'type' => 'out',
                    'source' => 'raw',
                    'quantity' => $quantityNeeded,
                    'unit_cost' => $rawMaterial->unit_cost,
                    'total_cost' => $quantityNeeded * $rawMaterial->unit_cost,
                    'description' => "Consommation pour production {$order->order_number}",
                    'created_by' => Auth::id(),
                ]);

                // Mettre à jour balance stock matières premières
                $this->updateStockBalance(
                    materialId: $rawMaterial->id,
                    stockType: 'raw',
                    quantityChange: -$quantityNeeded,
                    unitCost: $rawMaterial->unit_cost
                );

                $movements[] = $movement;
                $materials[] = [
                    'material_id' => $rawMaterial->id,
                    'material_name' => $rawMaterial->name,
                    'quantity' => $quantityNeeded,
                    'unit_cost' => $rawMaterial->unit_cost,
                    'total_cost' => $movement->total_cost,
                ];
            }

            // Dispatch événement pour Finance Hub
            event(new RawMaterialConsumed($order, $materials));

            return $movements;
        });
    }

    /**
     * Augmenter WIP (démarrage production)
     */
    public function increaseWip(ProductionOrder $order): StockMovement
    {
        return DB::transaction(function () use ($order) {
            $totalCost = $order->bom->calculateTotalMaterialCost() * $order->quantity_planned;
            $unitCost = $totalCost / $order->quantity_planned;

            // Créer mouvement entrée WIP
            $movement = StockMovement::create([
                'product_id' => $order->product_id,
                'production_order_id' => $order->id,
                'type' => 'in',
                'source' => 'wip',
                'quantity' => $order->quantity_planned,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'description' => "Entrée en-cours production {$order->order_number}",
                'created_by' => Auth::id(),
            ]);

            // Mettre à jour balance WIP
            $this->updateStockBalance(
                productId: $order->product_id,
                stockType: 'wip',
                quantityChange: $order->quantity_planned,
                unitCost: $unitCost
            );

            return $movement;
        });
    }

    /**
     * Diminuer WIP sur rebut
     */
    public function decreaseWipOnScrap(ProductionOrder $order, float $quantity, string $reason): StockMovement
    {
        return DB::transaction(function () use ($order, $quantity, $reason) {
            // Obtenir coût unitaire WIP actuel
            $wipBalance = $this->getStockBalance($order->product_id, 'wip');
            $unitCost = $wipBalance ? $wipBalance->average_cost : 0;
            $totalCost = $quantity * $unitCost;

            // Créer mouvement sortie WIP (rebut)
            $movement = StockMovement::create([
                'product_id' => $order->product_id,
                'production_order_id' => $order->id,
                'type' => 'out',
                'source' => 'wip',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'description' => "Rebut - {$reason}",
                'created_by' => Auth::id(),
            ]);

            // Mettre à jour balance WIP
            $this->updateStockBalance(
                productId: $order->product_id,
                stockType: 'wip',
                quantityChange: -$quantity,
                unitCost: $unitCost
            );

            // Dispatch événement pour Finance Hub
            event(new StockScrapped($order, $quantity, $unitCost, $totalCost, $reason));

            return $movement;
        });
    }

    /**
     * Augmenter produits finis (fin production)
     */
    public function increaseFinishedGoods(ProductionOrder $order, float $quantity): StockMovement
    {
        return DB::transaction(function () use ($order, $quantity) {
            // Obtenir coût unitaire WIP actuel
            $wipBalance = $this->getStockBalance($order->product_id, 'wip');
            $unitCost = $wipBalance ? $wipBalance->average_cost : 0;
            $totalCost = $quantity * $unitCost;

            // Créer mouvement entrée produits finis
            $movement = StockMovement::create([
                'product_id' => $order->product_id,
                'production_order_id' => $order->id,
                'type' => 'in',
                'source' => 'finished',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'description' => "Production terminée {$order->order_number}",
                'created_by' => Auth::id(),
            ]);

            // Mettre à jour balance produits finis
            $this->updateStockBalance(
                productId: $order->product_id,
                stockType: 'finished',
                quantityChange: $quantity,
                unitCost: $unitCost
            );

            // Diminuer WIP
            $this->updateStockBalance(
                productId: $order->product_id,
                stockType: 'wip',
                quantityChange: -$quantity,
                unitCost: $unitCost
            );

            // Dispatch événement pour Finance Hub
            event(new FinishedGoodsProduced($order, $quantity, $unitCost, $totalCost));

            return $movement;
        });
    }

    /**
     * Obtenir balance stock
     */
    public function getStockBalance(?int $materialId = null, ?int $productId = null, string $stockType = 'raw'): ?StockBalance
    {
        $query = StockBalance::where('stock_type', $stockType);

        if ($materialId) {
            $query->where('material_id', $materialId);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->first();
    }

    /**
     * Mettre à jour balance stock avec CMP (Coût Moyen Pondéré)
     */
    protected function updateStockBalance(
        ?int $materialId = null,
        ?int $productId = null,
        string $stockType = 'raw',
        float $quantityChange = 0,
        float $unitCost = 0
    ): StockBalance {
        $balance = $this->getStockBalance($materialId, $productId, $stockType);

        if (!$balance) {
            // Créer nouvelle balance
            $balance = StockBalance::create([
                'material_id' => $materialId,
                'product_id' => $productId,
                'stock_type' => $stockType,
                'quantity' => max(0, $quantityChange),
                'average_cost' => $unitCost,
                'total_value' => max(0, $quantityChange * $unitCost),
            ]);
        } else {
            // Calculer nouveau CMP
            if ($quantityChange > 0) {
                // Entrée: recalculer CMP
                $oldValue = $balance->total_value;
                $newValue = $quantityChange * $unitCost;
                $totalValue = $oldValue + $newValue;
                
                $newQuantity = $balance->quantity + $quantityChange;
                $newAverageCost = $newQuantity > 0 ? $totalValue / $newQuantity : 0;

                $balance->update([
                    'quantity' => $newQuantity,
                    'average_cost' => $newAverageCost,
                    'total_value' => $totalValue,
                ]);
            } else {
                // Sortie: utiliser CMP existant
                $newQuantity = max(0, $balance->quantity + $quantityChange);
                $newTotalValue = $newQuantity * $balance->average_cost;

                $balance->update([
                    'quantity' => $newQuantity,
                    'total_value' => $newTotalValue,
                ]);
            }
        }

        return $balance->fresh();
    }

    /**
     * Vérifier disponibilité stock
     */
    public function checkStockAvailability(ProductionOrder $order): array
    {
        $availability = [];

        foreach ($order->bom->items as $bomItem) {
            $quantityNeeded = $bomItem->getQuantityWithWaste() * $order->quantity_planned;
            $balance = $this->getStockBalance($bomItem->raw_material_id, null, 'raw');
            
            $available = $balance ? $balance->quantity : 0;
            $sufficient = $available >= $quantityNeeded;

            $availability[] = [
                'material_id' => $bomItem->raw_material_id,
                'material_name' => $bomItem->rawMaterial->name,
                'needed' => $quantityNeeded,
                'available' => $available,
                'sufficient' => $sufficient,
                'shortage' => max(0, $quantityNeeded - $available),
            ];
        }

        return $availability;
    }

    /**
     * Obtenir tous les mouvements stock
     */
    public function getMovements(?int $materialId = null, ?int $productId = null, ?string $stockType = null)
    {
        $query = StockMovement::with(['material', 'product', 'productionOrder']);

        if ($materialId) {
            $query->where('material_id', $materialId);
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($stockType) {
            $query->where('source', $stockType);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
