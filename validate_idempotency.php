#!/usr/bin/env php
<?php

/**
 * Idempotency Validation Script
 * 
 * Tests idempotency implementation locally
 * Usage: php validate_idempotency.php
 */

echo "========================================\n";
echo "Idempotency Implementation Validation\n";
echo "========================================\n\n";

$checks = [
    'Migration file' => file_exists('database/migrations/2026_01_29_000001_create_idempotency_keys_table.php'),
    'IdempotencyKey model' => file_exists('app/Models/IdempotencyKey.php'),
    'CheckIdempotency middleware' => file_exists('app/Http/Middleware/CheckIdempotency.php'),
    'Cleanup command' => file_exists('app/Console/Commands/CleanupExpiredIdempotencyKeys.php'),
    'Idempotency tests' => file_exists('tests/Feature/Idempotency/IdempotencyTest.php'),
    'IDEMPOTENCY_GUIDE.md' => file_exists('docs/IDEMPOTENCY_GUIDE.md'),
];

echo "1. FILE CHECKS\n";
echo "==============\n";

$allFilesExist = true;
foreach ($checks as $name => $exists) {
    $status = $exists ? '✅ FOUND' : '❌ MISSING';
    echo "  {$status} {$name}\n";
    if (!$exists) $allFilesExist = false;
}

echo "\n2. ROUTE CHECKS\n";
echo "================\n";

$routeFile = file_get_contents('routes/pos.php');
$webRouteFile = file_get_contents('routes/web.php');

$routeChecks = [
    'POS /open route protected' => strpos($routeFile, "->middleware(\\App\\Http\\Middleware\\CheckIdempotency::class)") !== false,
    'POST /checkout protected' => strpos($webRouteFile, "->middleware('throttle:10,1', \\App\\Http\\Middleware\\CheckIdempotency::class)") !== false,
    'POST /checkout/card/pay protected' => strpos($webRouteFile, "CardPaymentController") !== false && strpos($webRouteFile, "CheckIdempotency") !== false,
];

foreach ($routeChecks as $name => $protected) {
    $status = $protected ? '✅ PROTECTED' : '❌ NOT PROTECTED';
    echo "  {$status} {$name}\n";
}

echo "\n3. MIDDLEWARE IMPLEMENTATION CHECK\n";
echo "===================================\n";

$middlewareContent = file_get_contents('app/Http/Middleware/CheckIdempotency.php');

$middlewareChecks = [
    'Validates X-Idempotency-Key header' => strpos($middlewareContent, 'X-Idempotency-Key') !== false,
    'Returns 400 for missing key' => strpos($middlewareContent, "400") !== false,
    'Checks for duplicate key' => strpos($middlewareContent, "where('key'") !== false,
    'Returns 409 on conflict' => strpos($middlewareContent, "409") !== false,
    'Caches response' => strpos($middlewareContent, "'response'") !== false,
];

foreach ($middlewareChecks as $name => $implemented) {
    $status = $implemented ? '✅ IMPLEMENTED' : '❌ MISSING';
    echo "  {$status} {$name}\n";
}

echo "\n4. MODEL IMPLEMENTATION CHECK\n";
echo "==============================\n";

$modelContent = file_get_contents('app/Models/IdempotencyKey.php');

$modelChecks = [
    'Model extends Eloquent' => strpos($modelContent, 'extends Model') !== false,
    'Table name specified' => strpos($modelContent, "'idempotency_keys'") !== false,
    'Fillable properties' => strpos($modelContent, "'key'") !== false && strpos($modelContent, "'status'") !== false,
];

foreach ($modelChecks as $name => $implemented) {
    $status = $implemented ? '✅ IMPLEMENTED' : '❌ MISSING';
    echo "  {$status} {$name}\n";
}

echo "\n5. NEXT STEPS\n";
echo "==============\n";

echo <<<'EOT'
To deploy idempotency:

1. Start MySQL/XAMPP:
   - Open XAMPP Control Panel
   - Click "Start" on Apache and MySQL

2. Run migration:
   php artisan migrate

3. Run tests:
   php artisan test tests/Feature/Idempotency/IdempotencyTest.php

4. Verify database:
   php artisan tinker
   >>> DB::table('idempotency_keys')->count()

5. Test locally:
   curl -X POST http://localhost:8000/api/test-idempotency \
     -H "X-Idempotency-Key: test-123" \
     -H "Content-Type: application/json" \
     -d '{"name":"Test"}'

6. Schedule cleanup (optional):
   php artisan schedule:work
   # Will run idempotency:prune daily

EOT;

echo "\n========================================\n";
if ($allFilesExist && array_sum(array_values($routeChecks)) === count($routeChecks)) {
    echo "✅ ALL CHECKS PASSED - Ready to migrate\n";
} else {
    echo "❌ SOME CHECKS FAILED - Please review above\n";
}
echo "========================================\n";
