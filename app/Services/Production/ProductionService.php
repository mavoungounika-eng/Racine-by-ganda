<?php

namespace App\Services\Production;

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\ProductionOperation;
use App\Models\ProductionMaterialLog;
use App\Models\ProductionTimeLog;
use App\Models\ProductionQualityControl;
use App\Models\ProductionOutput;
use App\Services\Stock\StockService;
use App\Services\Production\ProductionCostingService;
use App\Exceptions\Production\InvalidOrderStateException;
use App\Exceptions\Production\MissingProductionDataException;
use App\Exceptions\Production\InvalidProductionOutputException;
use App\Exceptions\Production\ImmutableOrderException;
use App\Exceptions\Production\MissingBOMSnapshotException;
use App\Exceptions\Stock\InsufficientStockException;
use Illuminate\Support\Facades\DB;
use Exception;

class ProductionService
{
    protected StockService $stockService;
    protected ProductionCostingService $costingService;

    public function __construct(StockService $stockService, ProductionCostingService $costingService)
    {
        $this->stockService = $stockService;
        $this->costingService = $costingService;
    }

    /**
     * Create a new Manufacturing Order (OF)
     */
    public function createOrder(array $data): ProductionOrder
    {
        DB::beginTransaction();
        
        try {
            // Validate product exists
            $product = Product::findOrFail($data['product_id']);
            
            // Generate OF Number
            $ofNumber = $this->generateOFNumber();
            
            $order = ProductionOrder::create([
                'of_number' => $ofNumber,
                'product_id' => $data['product_id'],
                'workshop_id' => $data['workshop_id'] ?? null,
                'target_quantity' => $data['target_quantity'],
                'planned_start_date' => $data['planned_start_date'] ?? null,
                'deadline_date' => $data['deadline_date'],
                'bom_snapshot' => $data['bom_snapshot'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'draft',
            ]);
            
            // Create default operations if provided
            if (isset($data['operations']) && is_array($data['operations'])) {
                foreach ($data['operations'] as $index => $operation) {
                    ProductionOperation::create([
                        'production_order_id' => $order->id,
                        'name' => $operation['name'],
                        'sequence_order' => $index + 1,
                        'standard_time_minutes' => $operation['standard_time_minutes'] ?? null,
                        'description' => $operation['description'] ?? null,
                    ]);
                }
            }
            
            DB::commit();
            
            return $order->fresh(['operations', 'product']);
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Start production (transition to in_progress)
     */
    public function startProduction(ProductionOrder $order): ProductionOrder
    {
        if (!$order->canBeStarted()) {
            throw new Exception("Order {$order->of_number} cannot be started. Current status: {$order->status}");
        }
        
        $order->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
        
        return $order->fresh();
    }

    /**
     * Log material consumption
     * 
     * GOVERNANCE RULES (HARD - NON-NEGOTIABLE):
     * - R12: Cannot consume more than available stock
     * 
     * INTEGRATION:
     * - Creates production_material_logs entry
     * - Creates stock_movements entry (OUT)
     * 
     * @throws InsufficientStockException
     */
    public function logMaterial(ProductionOrder $order, array $data): ProductionMaterialLog
    {
        if ($order->isCompleted()) {
            throw new Exception("Cannot log material for completed order {$order->of_number}");
        }
        
        // ========================================
        // HARD RULE R12: Stock Availability Check
        // ========================================
        $this->stockService->validateStockAvailability(
            $data['material_type'],
            $data['material_reference'],
            $data['quantity_used'],
            $data['unit']
        );
        
        DB::beginTransaction();
        
        try {
            // Create production material log
            $log = ProductionMaterialLog::create([
                'production_order_id' => $order->id,
                'material_type' => $data['material_type'],
                'material_id' => $data['material_id'] ?? null,
                'material_reference' => $data['material_reference'] ?? null,
                'quantity_used' => $data['quantity_used'],
                'unit' => $data['unit'],
                'marker_efficiency' => $data['marker_efficiency'] ?? null,
                'waste_quantity' => $data['waste_quantity'] ?? null,
                'logged_by' => $data['logged_by'] ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);
            
            // Create stock movement (OUT)
            $this->stockService->recordMovement([
                'material_type' => $data['material_type'],
                'material_reference' => $data['material_reference'],
                'quantity' => $data['quantity_used'],
                'unit' => $data['unit'],
                'direction' => 'OUT',
                'source_type' => 'PRODUCTION',
                'source_id' => $order->id,
                'user_id' => $data['logged_by'] ?? auth()->id(),
                'notes' => "Material consumption for OF {$order->of_number}",
            ]);
            
            DB::commit();
            
            return $log;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Log labor time
     */
    public function logTime(ProductionOperation $operation, array $data): ProductionTimeLog
    {
        return ProductionTimeLog::create([
            'production_operation_id' => $operation->id,
            'operator_id' => $data['operator_id'],
            'duration_minutes' => $data['duration_minutes'],
            'started_at' => $data['started_at'] ?? null,
            'ended_at' => $data['ended_at'] ?? null,
            'workstation_id' => $data['workstation_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Record quality control inspection
     */
    public function recordQualityControl(ProductionOrder $order, array $data): ProductionQualityControl
    {
        return ProductionQualityControl::create([
            'production_order_id' => $order->id,
            'inspector_id' => $data['inspector_id'] ?? auth()->id(),
            'inspected_qty' => $data['inspected_qty'],
            'passed_qty' => $data['passed_qty'],
            'failed_qty' => $data['failed_qty'],
            'defect_type' => $data['defect_type'] ?? null,
            'defect_details' => $data['defect_details'] ?? null,
            'severity' => $data['severity'] ?? null,
            'decision' => $data['decision'] ?? 'approved',
            'comments' => $data['comments'] ?? null,
        ]);
    }

    /**
     * Close/Complete a Manufacturing Order
     * 
     * GOVERNANCE RULES (HARD - NON-NEGOTIABLE):
     * - R5: Status must be 'in_progress'
     * - R1: Material logs required
     * - R2: Time logs required (if operations exist)
     * - R3: Outputs required
     * - R4: Each output must have non-zero total quantity
     * 
     * @param ProductionOrder $order
     * @param array $outputsByVariant [
     *     ['variant_sku' => 'CHEM-BLEU-S', 'variant_attributes' => ['size' => 'S'], 'qty_good' => 10, 'qty_second' => 1, 'qty_rejected' => 0],
     *     ['variant_sku' => 'CHEM-BLEU-M', 'variant_attributes' => ['size' => 'M'], 'qty_good' => 15, 'qty_second' => 0, 'qty_rejected' => 2],
     * ]
     * @throws InvalidOrderStateException
     * @throws MissingProductionDataException
     * @throws InvalidProductionOutputException
     */
    public function closeOrder(ProductionOrder $order, array $outputsByVariant): ProductionOrder
    {
        // ========================================
        // HARD RULE R5: Status Validation
        // ========================================
        if ($order->status !== 'in_progress') {
            throw InvalidOrderStateException::cannotClose($order->of_number, $order->status);
        }
        
        // ========================================
        // HARD RULE R1: Material Logs Required
        // ========================================
        if ($order->materialLogs()->count() === 0) {
            throw MissingProductionDataException::noMaterialLogs($order->of_number);
        }
        
        // ========================================
        // HARD RULE R2: Time Logs Required (if operations exist)
        // ========================================
        if ($order->operations()->count() > 0) {
            $totalTimeLogs = DB::table('production_time_logs')
                ->whereIn('production_operation_id', $order->operations->pluck('id'))
                ->count();
                
            if ($totalTimeLogs === 0) {
                throw MissingProductionDataException::noTimeLogs($order->of_number);
            }
        }
        
        // ========================================
        // HARD RULE R3: Outputs Required
        // ========================================
        if (empty($outputsByVariant)) {
            throw MissingProductionDataException::noOutputs($order->of_number);
        }
        
        // ========================================
        // HARD RULE R4: Validate Each Output
        // ========================================
        foreach ($outputsByVariant as $output) {
            // Validate required fields
            if (!isset($output['variant_sku'])) {
                throw InvalidProductionOutputException::missingRequiredField('variant_sku');
            }
            if (!isset($output['qty_good'])) {
                throw InvalidProductionOutputException::missingRequiredField('qty_good');
            }
            
            // Validate non-zero total
            $total = ($output['qty_good'] ?? 0) + ($output['qty_second'] ?? 0) + ($output['qty_rejected'] ?? 0);
            if ($total === 0) {
                throw InvalidProductionOutputException::zeroTotalQuantity($output['variant_sku']);
            }
        }
        
        // ========================================
        // EXECUTION (All rules passed)
        // ========================================
        DB::beginTransaction();
        
        try {
            // Create variant-level outputs (TRUTH)
            foreach ($outputsByVariant as $output) {
                ProductionOutput::create([
                    'production_order_id' => $order->id,
                    'product_id' => $order->product_id,
                    'variant_sku' => $output['variant_sku'],
                    'variant_attributes' => $output['variant_attributes'] ?? null,
                    'qty_good' => $output['qty_good'],
                    'qty_second' => $output['qty_second'] ?? 0,
                    'qty_rejected' => $output['qty_rejected'] ?? 0,
                ]);
                
                // ========================================
                // PHASE C.2: Stock Movements for Finished Goods
                // ========================================
                
                // 1st Choice → Stock IN (sellable)
                if ($output['qty_good'] > 0) {
                    $this->stockService->recordMovement([
                        'material_type' => 'finished_good',
                        'material_reference' => $output['variant_sku'],
                        'quantity' => $output['qty_good'],
                        'unit' => 'pcs',
                        'direction' => 'IN',
                        'source_type' => 'PRODUCTION',
                        'source_id' => $order->id,
                        'unit_cost' => null, // Will be updated after cost calculation
                        'notes' => "Production output (1st choice) from OF {$order->of_number}",
                    ]);
                }
                
                // 2nd Choice → Separate Stock (outlet/discount)
                if (($output['qty_second'] ?? 0) > 0) {
                    $this->stockService->recordMovement([
                        'material_type' => 'finished_good_second',
                        'material_reference' => $output['variant_sku'],
                        'quantity' => $output['qty_second'],
                        'unit' => 'pcs',
                        'direction' => 'IN',
                        'source_type' => 'PRODUCTION',
                        'source_id' => $order->id,
                        'notes' => "Production output (2nd choice) from OF {$order->of_number}",
                    ]);
                }
                
                // Rejected items → NOT stocked (waste)
                // No stock movement for rejected items
            }
            
            // Close the order
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            
            // ========================================
            // PHASE C.3: Generate Cost Summary & Valorize Stock
            // ========================================
            
            // Refresh order to get outputs relationship
            $order->refresh();
            
            // Generate immutable cost summary
            $costSummary = $this->costingService->generateCostSummary($order);
            
            // Stock PF is now valorized at real unit cost
            // (done automatically in ProductionCostingService::updateStockValuation)
            
            DB::commit();
            
            return $order->fresh(['outputs', 'operations', 'materialLogs', 'costSummary']);
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculate Real Production Cost
     * 
     * GOVERNANCE RULES (HARD - NON-NEGOTIABLE):
     * - R11: Only calculate for completed orders
     * - R9: Use ONLY bom_snapshot, never current product BOM
     * 
     * Formula: (Material Cost + Labor Cost) / Good Quantity
     * 
     * @throws InvalidOrderStateException
     * @throws MissingBOMSnapshotException
     */
    public function calculateRealCost(ProductionOrder $order): array
    {
        // ========================================
        // HARD RULE R11: Only for Completed Orders
        // ========================================
        if ($order->status !== 'completed') {
            throw InvalidOrderStateException::cannotCalculateCost($order->of_number);
        }
        
        // ========================================
        // HARD RULE R9: BOM Snapshot Required
        // ========================================
        $snapshot = $order->bom_snapshot;
        if ($snapshot === null) {
            throw MissingBOMSnapshotException::forOrder($order->of_number);
        }
        
        // ========================================
        // CALCULATION (Using snapshot ONLY)
        // ========================================
        
        // Material Cost (from actual consumption, priced from snapshot)
        // TODO Phase C: Use real material prices from stock movements
        $materialCost = $order->materialLogs()->sum('quantity_used') * 10; // Placeholder: 10 XAF per unit
        
        // Labor Cost (from actual time logs)
        $totalMinutes = 0;
        foreach ($order->operations as $operation) {
            $totalMinutes += $operation->timeLogs()->sum('duration_minutes');
        }
        $laborCost = ($totalMinutes / 60) * 2000; // Placeholder: 2000 XAF per hour
        
        $totalCost = $materialCost + $laborCost;
        
        $unitCost = $order->produced_qty_good > 0 
            ? $totalCost / $order->produced_qty_good 
            : 0;
        
        return [
            'material_cost' => round($materialCost, 2),
            'labor_cost' => round($laborCost, 2),
            'total_cost' => round($totalCost, 2),
            'unit_cost' => round($unitCost, 2),
            'good_quantity' => $order->produced_qty_good,
            'bom_version' => $snapshot['version'] ?? 'unknown',
        ];
    }

    /**
     * Generate unique OF Number
     */
    private function generateOFNumber(): string
    {
        $year = date('y');
        $lastOrder = ProductionOrder::whereYear('created_at', date('Y'))
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastOrder ? (int) substr($lastOrder->of_number, -4) + 1 : 1;
        
        return sprintf('OF-%s-%04d', $year, $sequence);
    }
}
