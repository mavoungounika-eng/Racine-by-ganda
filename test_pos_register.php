<?php

// Test script for POS terminal registration endpoint
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\Pos\PosAuthController;

echo "Testing POS terminal registration endpoint...\n";

// Create a mock request
$request = new Request();
$request->merge([
    'machine_id' => 'test-terminal-' . time(),
    'name' => 'Test Terminal'
]);

// Create controller instance
$controller = new PosAuthController();

// Call the method
try {
    $response = $controller->registerTerminal($request);
    echo "Response: " . $response->getContent() . "\n";
    echo "Status: " . $response->getStatusCode() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}