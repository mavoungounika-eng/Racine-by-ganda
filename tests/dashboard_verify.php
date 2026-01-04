<?php

use App\Services\Dashboard\DashboardService;
use App\Http\Controllers\Admin\AdminDashboardController;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$service = app(DashboardService::class);
$service->refresh(); // Clear cache first
$data = $service->getData();

echo "=== DASHBOARD VERIFICATION REPORT ===\n";
echo "1. Global State:\n";
echo "   - Revenue: " . ($data['global_state']['revenue']['formatted'] ?? 'ERR') . " (Status: " . ($data['global_state']['revenue']['status'] ?? 'ERR') . ")\n";
echo "   - Orders: " . ($data['global_state']['orders_count']['value'] ?? 'ERR') . "\n";
echo "   - Pending: " . ($data['global_state']['pending_orders']['value'] ?? 'ERR') . "\n";

echo "\n2. Alerts (Should be > 0):\n";
print_r($data['alerts']);

echo "\n3. Marketplace:\n";
echo "   - Revenue: " . ($data['marketplace']['revenue'] ?? 'ERR') . "\n";
echo "   - Orders: " . ($data['marketplace']['orders_count'] ?? 'ERR') . "\n";

echo "\n4. Operations:\n";
print_r($data['operations']);

echo "\n=== END REPORT ===\n";
