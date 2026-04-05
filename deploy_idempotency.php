#!/usr/bin/env php
<?php

/**
 * Deployment Runbook - Order Idempotency Task 1/11
 * 
 * Step-by-step guide to deploy idempotency to local environment
 * Usage: php deploy_idempotency.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    Order Idempotency Deployment Runbook (Task 1/11)        ║\n";
echo "║    Status: Ready for Local Deployment                      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$steps = [
    [
        'title' => 'STEP 1: Start MySQL Server',
        'manual' => true,
        'instructions' => [
            '1. Open XAMPP Control Panel',
            '2. Click "Start" button next to "MySQL"',
            '3. Wait for status to show "Running"',
            '4. Verify: mysql.exe process is active',
        ],
        'verification' => 'mysql.exe running in Task Manager',
    ],
    [
        'title' => 'STEP 2: Verify Database Connection',
        'manual' => false,
        'command' => 'php artisan tinker',
        'input' => 'DB::connection()->getPdo()',
        'expected' => 'PDOStatement object (confirms connection works)',
    ],
    [
        'title' => 'STEP 3: Run Migration',
        'manual' => false,
        'command' => 'php artisan migrate',
        'expected' => 'Migrated: 2026_01_29_000001_create_idempotency_keys_table',
    ],
    [
        'title' => 'STEP 4: Verify Table Created',
        'manual' => false,
        'command' => 'php artisan tinker',
        'input' => "DB::table('idempotency_keys')->count()",
        'expected' => '0 (table exists and is empty)',
    ],
    [
        'title' => 'STEP 5: Run Test Suite',
        'manual' => false,
        'command' => 'php artisan test tests/Feature/Idempotency/IdempotencyTest.php --verbose',
        'expected' => 'PASSED 8/8 tests',
    ],
    [
        'title' => 'STEP 6: Start Laravel Dev Server',
        'manual' => false,
        'command' => 'php artisan serve --port=8000',
        'expected' => 'Server running at http://127.0.0.1:8000',
        'note' => 'Keep this terminal open',
    ],
    [
        'title' => 'STEP 7: Test First Request (New Terminal)',
        'manual' => false,
        'command' => 'curl -X POST http://localhost:8000/api/test-idempotency ' .
                    '-H "X-Idempotency-Key: test-$(date +%s)" ' .
                    '-H "Content-Type: application/json" ' .
                    '-d \'{"name":"Test"}\'',
        'expected' => '{"success":true,"timestamp":"2026-01-29 ..."}',
        'note' => 'Open new terminal window',
    ],
    [
        'title' => 'STEP 8: Test Duplicate Request (Same Key)',
        'manual' => false,
        'command' => 'curl -X POST http://localhost:8000/api/test-idempotency ' .
                    '-H "X-Idempotency-Key: test-identical-key" ' .
                    '-H "Content-Type: application/json" ' .
                    '-d \'{"name":"First"}\'',
        'expected' => 'First response cached',
    ],
    [
        'title' => 'STEP 9: Test Second Request (Same Key)',
        'manual' => false,
        'command' => 'curl -X POST http://localhost:8000/api/test-idempotency ' .
                    '-H "X-Idempotency-Key: test-identical-key" ' .
                    '-H "Content-Type: application/json" ' .
                    '-d \'{"name":"Second"}\'',
        'expected' => 'Same response as Step 8 (identical, different payload ignored)',
    ],
    [
        'title' => 'STEP 10: Verify Database Records',
        'manual' => false,
        'command' => 'php artisan tinker',
        'input' => "DB::table('idempotency_keys')->get()",
        'expected' => 'Multiple rows with status=completed',
    ],
];

// Print steps
foreach ($steps as $index => $step) {
    $num = $index + 1;
    echo "┌" . str_repeat("─", 60) . "┐\n";
    echo "│ {$step['title']}\n";
    echo "└" . str_repeat("─", 60) . "┘\n";
    
    if ($step['manual']) {
        echo "\n📋 MANUAL STEP - Please perform these actions:\n\n";
        foreach ($step['instructions'] as $instruction) {
            echo "   {$instruction}\n";
        }
        echo "\n✓ Verification: {$step['verification']}\n";
    } else {
        echo "\n💻 AUTOMATED COMMAND:\n";
        echo "   $ {$step['command']}\n";
        
        if (isset($step['input'])) {
            echo "\n   Then in tinker, run:\n";
            echo "   >>> {$step['input']}\n";
        }
    }
    
    echo "\n✅ Expected Result:\n";
    echo "   {$step['expected']}\n";
    
    if (isset($step['note'])) {
        echo "\n📝 Note: {$step['note']}\n";
    }
    
    echo "\n";
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║               TROUBLESHOOTING GUIDE                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$troubleshoots = [
    'MySQL connection refused' => [
        'Cause' => 'MySQL server not running',
        'Solution' => [
            '1. Open XAMPP Control Panel',
            '2. Click "Start" next to MySQL',
            '3. Wait 5 seconds for it to fully start',
            '4. Retry command',
        ],
    ],
    'Migration already exists error' => [
        'Cause' => 'Migration was already run',
        'Solution' => [
            '1. Run: php artisan migrate:status',
            '2. If "2026_01_29_000001" shows as "Ran", it\'s already done',
            '3. If error, run: php artisan migrate:rollback',
            '4. Then retry: php artisan migrate',
        ],
    ],
    'Tests fail with "Cannot find User model"' => [
        'Cause' => 'Test environment issue',
        'Solution' => [
            '1. Run: php artisan config:clear',
            '2. Run: php artisan cache:clear',
            '3. Retry: php artisan test',
        ],
    ],
    'Port 8000 already in use' => [
        'Cause' => 'Another service using port 8000',
        'Solution' => [
            '1. Use different port: php artisan serve --port=8001',
            '2. Update cURL commands to use :8001',
            '3. Or kill process: Get-Process | Where-Object {$_.Name -match "php"} | Stop-Process',
        ],
    ],
    'curl command not found' => [
        'Cause' => 'PowerShell doesn\'t have curl aliased',
        'Solution' => [
            '1. Use Invoke-WebRequest instead:',
            'Invoke-WebRequest -Uri http://localhost:8000/api/test-idempotency -Method POST -Headers @{"X-Idempotency-Key"="test-123"} -Body \'{"name":"Test"}\'',
            '2. Or download curl from: https://curl.se/download.html',
        ],
    ],
];

foreach ($troubleshoots as $issue => $details) {
    echo "❌ Issue: {$issue}\n";
    echo "   Cause: {$details['Cause']}\n";
    echo "   Solution:\n";
    foreach ($details['Solution'] as $solution) {
        echo "      {$solution}\n";
    }
    echo "\n";
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    SUCCESS CRITERIA                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$criteria = [
    '✅ Migration created table `idempotency_keys` with UNIQUE constraint on `key`',
    '✅ All 8 tests in IdempotencyTest.php pass (PASSED 8/8)',
    '✅ First POST request with unique key returns 200 OK',
    '✅ Second POST request with same key returns 200 OK with cached response',
    '✅ Database contains idempotency records with status=completed',
    '✅ Cleanup command executes without errors',
    '✅ API documentation is available at docs/IDEMPOTENCY_GUIDE.md',
];

foreach ($criteria as $criterion) {
    echo "  {$criterion}\n";
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                   NEXT STEPS AFTER SUCCESS                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$nextSteps = [
    '1. Commit changes to git:',
    '   git add .',
    '   git commit -m "feat: Implement order idempotency with X-Idempotency-Key header"',
    '',
    '2. Push to remote:',
    '   git push origin feature/task-1-idempotency',
    '',
    '3. Create Pull Request on GitHub with:',
    '   - Title: "feat: Order Idempotency (Task 1/11)"',
    '   - Description: See TASK_1_11_ORDER_IDEMPOTENCY_COMPLETE.md',
    '',
    '4. Review checklist:',
    '   - [x] Migration works',
    '   - [x] Tests pass',
    '   - [x] Documentation complete',
    '   - [x] Ready for Task 2/11',
    '',
    '5. Proceed to Task 2/11 - Rate Limiting',
];

foreach ($nextSteps as $step) {
    echo "  {$step}\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "For questions or issues, refer to: docs/IDEMPOTENCY_GUIDE.md\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
