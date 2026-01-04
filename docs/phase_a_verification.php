# VERIFICATION SCRIPT: Phase A Structural Correction
# Run this in `php artisan tinker` to verify the refactoring

# 1. Create a test product
$product = App\Models\Product::first() ?? App\Models\Product::factory()->create();

# 2. Create a Production Order
$service = new App\Services\Production\ProductionService();
$order = $service->createOrder([
    'product_id' => $product->id,
    'target_quantity' => 50,
    'deadline_date' => now()->addDays(7),
    'operations' => [
        ['name' => 'Cutting', 'standard_time_minutes' => 120],
        ['name' => 'Assembly', 'standard_time_minutes' => 180],
    ]
]);

echo "✓ Order created: {$order->of_number}\n";

# 3. Start production
$service->startProduction($order);
echo "✓ Production started\n";

# 4. Log material consumption (REQUIRED)
$service->logMaterial($order, [
    'material_type' => 'fabric',
    'material_reference' => 'LIN-BLEU-001',
    'quantity_used' => 75.5,
    'unit' => 'm',
    'logged_by' => 1,
]);
echo "✓ Material logged\n";

# 5. Log time (REQUIRED)
$operation = $order->operations()->first();
$service->logTime($operation, [
    'operator_id' => 1,
    'duration_minutes' => 130,
]);
echo "✓ Time logged\n";

# 6. Close order with VARIANT-LEVEL outputs (NEW BEHAVIOR)
$closedOrder = $service->closeOrder($order, [
    [
        'variant_sku' => 'CHEM-BLEU-S',
        'variant_attributes' => ['size' => 'S', 'color' => 'Bleu'],
        'qty_good' => 10,
        'qty_second' => 1,
        'qty_rejected' => 0,
    ],
    [
        'variant_sku' => 'CHEM-BLEU-M',
        'variant_attributes' => ['size' => 'M', 'color' => 'Bleu'],
        'qty_good' => 15,
        'qty_second' => 0,
        'qty_rejected' => 2,
    ],
    [
        'variant_sku' => 'CHEM-BLEU-L',
        'variant_attributes' => ['size' => 'L', 'color' => 'Bleu'],
        'qty_good' => 20,
        'qty_second' => 0,
        'qty_rejected' => 1,
    ],
]);

echo "✓ Order closed\n";

# 7. VERIFY: Computed properties work (backward compatibility)
echo "\n=== VERIFICATION ===\n";
echo "Total Good (computed): {$closedOrder->produced_qty_good}\n";  // Should be 45
echo "Total Second (computed): {$closedOrder->produced_qty_second}\n";  // Should be 1
echo "Total Rejected (computed): {$closedOrder->rejected_qty}\n";  // Should be 3
echo "Total Produced: {$closedOrder->total_produced}\n";  // Should be 49

# 8. VERIFY: Outputs table contains granular data
echo "\n=== OUTPUTS (TRUTH) ===\n";
foreach ($closedOrder->outputs as $output) {
    echo "{$output->variant_sku}: Good={$output->qty_good}, Second={$output->qty_second}, Rejected={$output->qty_rejected}\n";
}

# 9. VERIFY: Quality rate per variant
echo "\n=== QUALITY RATES ===\n";
foreach ($closedOrder->outputs as $output) {
    echo "{$output->variant_sku}: {$output->quality_rate}%\n";
}

echo "\n✅ Phase A verification complete. The model no longer lies.\n";
