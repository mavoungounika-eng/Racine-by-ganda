<?php

namespace Modules\ERPProduction\Services;

use Modules\ERPProduction\Models\ProductionOrder;
use Modules\ERPProduction\Models\WorkStep;
use Modules\ERPProduction\Models\QualityCheck;
use Modules\ERPProduction\Models\QualityDefect;
use Modules\ERPProduction\Events\QualityCheckPassed;
use Modules\ERPProduction\Events\QualityCheckReworked;
use Modules\ERPProduction\Events\QualityCheckRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class QualityControlService
{
    /**
     * Inspecter ordre de production
     */
    public function inspectOrder(ProductionOrder $order, array $data): QualityCheck
    {
        return DB::transaction(function () use ($order, $data) {
            $check = QualityCheck::create([
                'production_order_id' => $order->id,
                'work_step_id' => $data['work_step_id'] ?? null,
                'status' => $data['status'],
                'quantity_checked' => $data['quantity_checked'],
                'quantity_passed' => $data['quantity_passed'] ?? 0,
                'quantity_reworked' => $data['quantity_reworked'] ?? 0,
                'quantity_rejected' => $data['quantity_rejected'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'checked_at' => now(),
                'checked_by' => Auth::id(),
            ]);

            // Dispatch événement selon statut
            $this->dispatchQualityEvent($check);

            return $check;
        });
    }

    /**
     * Inspecter étape de travail
     */
    public function inspectStep(WorkStep $step, array $data): QualityCheck
    {
        $data['work_step_id'] = $step->id;
        return $this->inspectOrder($step->productionOrder, $data);
    }

    /**
     * Approuver (pass)
     */
    public function approve(array $data): QualityCheck
    {
        $data['status'] = 'pass';
        $data['quantity_passed'] = $data['quantity_checked'];
        $data['quantity_reworked'] = 0;
        $data['quantity_rejected'] = 0;

        $order = ProductionOrder::findOrFail($data['production_order_id']);
        return $this->inspectOrder($order, $data);
    }

    /**
     * Marquer pour reprise (rework)
     */
    public function markForRework(array $data): QualityCheck
    {
        $data['status'] = 'rework';
        
        $order = ProductionOrder::findOrFail($data['production_order_id']);
        return $this->inspectOrder($order, $data);
    }

    /**
     * Rejeter (reject)
     */
    public function reject(array $data): QualityCheck
    {
        $data['status'] = 'reject';
        
        $order = ProductionOrder::findOrFail($data['production_order_id']);
        return $this->inspectOrder($order, $data);
    }

    /**
     * Enregistrer défaut
     */
    public function recordDefect(QualityCheck $check, array $defectData): QualityDefect
    {
        return QualityDefect::create([
            'quality_check_id' => $check->id,
            'defect_code' => $defectData['defect_code'],
            'defect_category' => $defectData['defect_category'],
            'severity' => $defectData['severity'],
            'description' => $defectData['description'],
        ]);
    }

    /**
     * Obtenir résumé qualité ordre
     */
    public function getQualitySummary(ProductionOrder $order): array
    {
        $checks = QualityCheck::where('production_order_id', $order->id)->get();

        $totalChecked = $checks->sum('quantity_checked');
        $totalPassed = $checks->sum('quantity_passed');
        $totalReworked = $checks->sum('quantity_reworked');
        $totalRejected = $checks->sum('quantity_rejected');

        $passRate = $totalChecked > 0 ? ($totalPassed / $totalChecked) * 100 : 0;
        $reworkRate = $totalChecked > 0 ? ($totalReworked / $totalChecked) * 100 : 0;
        $rejectionRate = $totalChecked > 0 ? ($totalRejected / $totalChecked) * 100 : 0;

        return [
            'total_checks' => $checks->count(),
            'total_checked' => $totalChecked,
            'total_passed' => $totalPassed,
            'total_reworked' => $totalReworked,
            'total_rejected' => $totalRejected,
            'pass_rate' => round($passRate, 2),
            'rework_rate' => round($reworkRate, 2),
            'rejection_rate' => round($rejectionRate, 2),
            'has_critical_defects' => $this->hasBlockingDefect($order),
        ];
    }

    /**
     * Vérifier défaut bloquant
     */
    public function hasBlockingDefect(ProductionOrder $order): bool
    {
        return QualityCheck::where('production_order_id', $order->id)
            ->whereHas('defects', function ($query) {
                $query->where('severity', 'critical');
            })
            ->exists();
    }

    /**
     * Vérifier si peut clôturer
     */
    public function canProceedToFinish(ProductionOrder $order): bool
    {
        // Vérifier qu'il y a au moins un contrôle qualité
        $hasChecks = QualityCheck::where('production_order_id', $order->id)->exists();
        
        if (!$hasChecks) {
            return false;
        }

        // Vérifier absence de défauts critiques non résolus
        if ($this->hasBlockingDefect($order)) {
            return false;
        }

        return true;
    }

    /**
     * Calculer rendement réel (yield rate)
     */
    public function getYieldRate(ProductionOrder $order): float
    {
        $summary = $this->getQualitySummary($order);

        if ($summary['total_checked'] <= 0) {
            return 0;
        }

        // Rendement = (Passés + Repris) / Total contrôlé
        // Note: Les reprises peuvent être repassées en contrôle
        $goodUnits = $summary['total_passed'];
        $totalChecked = $summary['total_checked'];

        return ($goodUnits / $totalChecked) * 100;
    }

    /**
     * Obtenir défauts par catégorie
     */
    public function getDefectsByCategory(ProductionOrder $order): array
    {
        $defects = QualityDefect::whereHas('qualityCheck', function ($query) use ($order) {
            $query->where('production_order_id', $order->id);
        })->get();

        return [
            'material' => $defects->where('defect_category', 'material')->count(),
            'process' => $defects->where('defect_category', 'process')->count(),
            'human' => $defects->where('defect_category', 'human')->count(),
            'machine' => $defects->where('defect_category', 'machine')->count(),
        ];
    }

    /**
     * Dispatch événement qualité
     */
    protected function dispatchQualityEvent(QualityCheck $check): void
    {
        match ($check->status) {
            'pass' => event(new QualityCheckPassed($check)),
            'rework' => event(new QualityCheckReworked($check)),
            'reject' => event(new QualityCheckRejected($check)),
        };
    }
}
