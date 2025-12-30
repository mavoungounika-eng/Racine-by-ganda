<?php

namespace Modules\ERPProduction\Services;

use Modules\ERPProduction\Models\ProductionOrder;
use Modules\ERPProduction\Models\WorkStep;
use Modules\ERPProduction\Models\WipMovement;
use Modules\ERPProduction\Events\ProductionStarted;
use Modules\ERPProduction\Events\ProductionFinished;
use Modules\ERPProduction\Events\ProductionScrapped;
use Modules\ERPProduction\Events\ProductionStepCompleted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WipService
{
    /**
     * Enregistrer démarrage production
     */
    public function startProduction(ProductionOrder $order): WipMovement
    {
        if ($order->status !== 'in_progress') {
            throw new \Exception("Production order must be in progress to start WIP tracking");
        }

        return DB::transaction(function () use ($order) {
            // Créer mouvement WIP
            $movement = WipMovement::create([
                'production_order_id' => $order->id,
                'type' => 'production_started',
                'quantity' => $order->quantity_planned,
                'description' => "Démarrage production {$order->order_number}",
                'created_by' => Auth::id(),
            ]);

            // Dispatch événement pour Finance Hub
            event(new ProductionStarted($order));

            return $movement;
        });
    }

    /**
     * Enregistrer complétion d'une étape
     */
    public function completeStep(WorkStep $step, float $quantity): WipMovement
    {
        if ($step->status !== 'completed') {
            throw new \Exception("Work step must be completed before recording WIP movement");
        }

        return DB::transaction(function () use ($step, $quantity) {
            $movement = WipMovement::create([
                'production_order_id' => $step->production_order_id,
                'work_step_id' => $step->id,
                'type' => 'step_completed',
                'quantity' => $quantity,
                'description' => "Étape '{$step->name}' complétée",
                'created_by' => Auth::id(),
            ]);

            // Dispatch événement
            event(new ProductionStepCompleted($step));

            return $movement;
        });
    }

    /**
     * Enregistrer fin production
     */
    public function finishProduction(ProductionOrder $order): WipMovement
    {
        if ($order->status !== 'finished') {
            throw new \Exception("Production order must be finished to complete WIP tracking");
        }

        return DB::transaction(function () use ($order) {
            $movement = WipMovement::create([
                'production_order_id' => $order->id,
                'type' => 'production_finished',
                'quantity' => $order->quantity_produced,
                'description' => "Production terminée - {$order->quantity_produced} unités produites",
                'created_by' => Auth::id(),
            ]);

            // Dispatch événement pour Finance Hub
            event(new ProductionFinished($order));

            return $movement;
        });
    }

    /**
     * Enregistrer rebut/perte
     */
    public function recordScrap(ProductionOrder $order, float $quantity, string $reason): WipMovement
    {
        if ($order->status !== 'in_progress') {
            throw new \Exception("Can only record scrap for in-progress orders");
        }

        // Vérifier quantité cohérente
        $totalProcessed = $this->getTotalQuantityProcessed($order);
        if ($totalProcessed + $quantity > $order->quantity_planned) {
            throw new \Exception("Scrap quantity exceeds planned quantity");
        }

        return DB::transaction(function () use ($order, $quantity, $reason) {
            $movement = WipMovement::create([
                'production_order_id' => $order->id,
                'type' => 'scrap',
                'quantity' => $quantity,
                'description' => $reason,
                'created_by' => Auth::id(),
            ]);

            // Dispatch événement pour Finance Hub
            event(new ProductionScrapped($order, $quantity, $reason));

            return $movement;
        });
    }

    /**
     * Enregistrer reprise/rework
     */
    public function recordRework(ProductionOrder $order, float $quantity, string $notes = null): WipMovement
    {
        if ($order->status !== 'in_progress') {
            throw new \Exception("Can only record rework for in-progress orders");
        }

        return WipMovement::create([
            'production_order_id' => $order->id,
            'type' => 'rework',
            'quantity' => $quantity,
            'description' => 'Reprise qualité',
            'notes' => $notes,
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * Obtenir balance WIP pour un ordre
     */
    public function getWipBalance(ProductionOrder $order): array
    {
        $movements = $this->getMovements($order);

        $started = $movements->where('type', 'production_started')->sum('quantity');
        $finished = $movements->where('type', 'production_finished')->sum('quantity');
        $scrapped = $movements->where('type', 'scrap')->sum('quantity');

        $inProgress = $started - $finished - $scrapped;

        return [
            'started' => $started,
            'finished' => $finished,
            'scrapped' => $scrapped,
            'in_progress' => $inProgress,
            'movements_count' => $movements->count(),
        ];
    }

    /**
     * Obtenir tous les mouvements d'un ordre
     */
    public function getMovements(ProductionOrder $order)
    {
        return WipMovement::forOrder($order->id)
            ->with(['workStep', 'creator'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Calculer quantité totale traitée (produite + rebutée)
     */
    protected function getTotalQuantityProcessed(ProductionOrder $order): float
    {
        $finished = WipMovement::forOrder($order->id)
            ->byType('production_finished')
            ->sum('quantity');

        $scrapped = WipMovement::forOrder($order->id)
            ->byType('scrap')
            ->sum('quantity');

        return $finished + $scrapped;
    }

    /**
     * Vérifier si ordre a des mouvements WIP
     */
    public function hasMovements(ProductionOrder $order): bool
    {
        return WipMovement::forOrder($order->id)->exists();
    }

    /**
     * Obtenir dernier mouvement
     */
    public function getLastMovement(ProductionOrder $order): ?WipMovement
    {
        return WipMovement::forOrder($order->id)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
