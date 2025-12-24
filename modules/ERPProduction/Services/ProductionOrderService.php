<?php

namespace Modules\ERPProduction\Services;

use Modules\ERPProduction\Models\ProductionOrder;
use Modules\ERPProduction\Models\WorkStep;
use Modules\ERPProduction\Models\Bom;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductionOrderService
{
    /**
     * Créer un ordre de production
     */
    public function createProductionOrder(int $productId, ?int $bomId, float $quantity, array $data = []): ProductionOrder
    {
        return DB::transaction(function () use ($productId, $bomId, $quantity, $data) {
            // Si BOM non spécifiée, prendre la BOM par défaut
            if (!$bomId) {
                $bomId = Bom::forProduct($productId)
                    ->active()
                    ->default()
                    ->value('id');
            }
            
            $orderNumber = $this->generateOrderNumber();
            
            return ProductionOrder::create([
                'order_number' => $orderNumber,
                'product_id' => $productId,
                'bom_id' => $bomId,
                'quantity_planned' => $quantity,
                'status' => 'draft',
                'planned_start_date' => $data['planned_start_date'] ?? null,
                'planned_end_date' => $data['planned_end_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);
        });
    }

    /**
     * Planifier un ordre de production
     */
    public function planProductionOrder(ProductionOrder $order): ProductionOrder
    {
        if ($order->status !== 'draft') {
            throw new \Exception("Only draft orders can be planned");
        }
        
        if (!$order->bom_id) {
            throw new \Exception("Cannot plan order without BOM");
        }
        
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'planned']);
            
            // Générer automatiquement les étapes de travail
            $this->generateWorkSteps($order);
        });
        
        return $order->fresh(['steps']);
    }

    /**
     * Démarrer la production
     */
    public function startProductionOrder(ProductionOrder $order): ProductionOrder
    {
        if (!$order->canStart()) {
            throw new \Exception("Order cannot be started. Check status and BOM completeness.");
        }
        
        $order->update([
            'status' => 'in_progress',
            'actual_start_date' => now(),
        ]);
        
        return $order->fresh();
    }

    /**
     * Terminer la production
     */
    public function finishProductionOrder(ProductionOrder $order, float $quantityProduced, float $quantityRejected = 0): ProductionOrder
    {
        if ($order->status !== 'in_progress') {
            throw new \Exception("Only in-progress orders can be finished");
        }
        
        $order->update([
            'status' => 'finished',
            'actual_end_date' => now(),
            'quantity_produced' => $quantityProduced,
            'quantity_rejected' => $quantityRejected,
        ]);
        
        return $order->fresh();
    }

    /**
     * Clôturer l'ordre (après contrôle qualité et mise en stock)
     */
    public function closeProductionOrder(ProductionOrder $order): ProductionOrder
    {
        if ($order->status !== 'finished') {
            throw new \Exception("Only finished orders can be closed");
        }
        
        $order->update(['status' => 'closed']);
        
        return $order->fresh();
    }

    /**
     * Générer les étapes de travail standard
     */
    public function generateWorkSteps(ProductionOrder $order): void
    {
        // Étapes standard de production textile
        $standardSteps = [
            ['name' => 'Coupe tissu', 'sequence' => 1, 'estimated_duration' => 2.0],
            ['name' => 'Assemblage/Couture', 'sequence' => 2, 'estimated_duration' => 4.0],
            ['name' => 'Finitions', 'sequence' => 3, 'estimated_duration' => 1.5],
            ['name' => 'Contrôle qualité', 'sequence' => 4, 'estimated_duration' => 0.5],
        ];
        
        foreach ($standardSteps as $stepData) {
            WorkStep::create([
                'production_order_id' => $order->id,
                'name' => $stepData['name'],
                'sequence' => $stepData['sequence'],
                'estimated_duration' => $stepData['estimated_duration'],
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Démarrer une étape de travail
     */
    public function startWorkStep(WorkStep $step): WorkStep
    {
        if (!$step->canStart()) {
            throw new \Exception("Step cannot be started. Check sequence and previous step status.");
        }
        
        $step->startStep();
        
        return $step->fresh();
    }

    /**
     * Compléter une étape de travail
     */
    public function completeWorkStep(WorkStep $step): WorkStep
    {
        if ($step->status !== 'in_progress') {
            throw new \Exception("Only in-progress steps can be completed");
        }
        
        $step->completeStep();
        
        // Si toutes les étapes sont complétées, suggérer de terminer l'ordre
        $order = $step->productionOrder;
        if ($order->allStepsCompleted() && $order->status === 'in_progress') {
            // Note: Ne pas terminer automatiquement, laisser l'utilisateur confirmer
            // la quantité produite et rejetée
        }
        
        return $step->fresh();
    }

    /**
     * Obtenir ordres actifs
     */
    public function getActiveOrders()
    {
        return ProductionOrder::active()
            ->with(['product', 'bom', 'steps'])
            ->orderBy('planned_start_date')
            ->get();
    }

    /**
     * Obtenir ordres par statut
     */
    public function getOrdersByStatus(string $status)
    {
        return ProductionOrder::where('status', $status)
            ->with(['product', 'bom', 'steps'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Générer numéro d'ordre unique
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'PO';
        $year = date('Y');
        $month = date('m');
        
        $lastOrder = ProductionOrder::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastOrder ? (int) substr($lastOrder->order_number, -4) + 1 : 1;
        
        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }
}
