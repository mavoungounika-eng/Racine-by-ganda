<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Count users without auth_version
$usersWithoutAuthVersion = DB::table('users')->whereNull('auth_version')->count();

echo "==========================================\n";
echo "AUTH VERSION DIAGNOSTIC\n";
echo "==========================================\n";
echo "Users without auth_version: {$usersWithoutAuthVersion}\n";
echo "\n";

if ($usersWithoutAuthVersion > 0) {
    echo "List of users without auth_version:\n";
    echo "--------------------\n";
    DB::table('users')
        ->whereNull('auth_version')
        ->select('id', 'email', 'auth_version')
        ->each(function ($user) {
            echo "  ID: {$user->id}, Email: {$user->email}, auth_version: " . ($user->auth_version ?? 'NULL') . "\n";
        });
    
    echo "\n";
    echo "⚠️  CRITICAL: These users will be automatically logged out!\n";
    echo "\n";
    echo "To fix this issue, run:\n";
    echo "  php artisan migrate\n";
    echo "\n";
    echo "Or update manually with:\n";
    echo "  UPDATE users SET auth_version = 1 WHERE auth_version IS NULL;\n";
} else {
    echo "✅ All users have auth_version set!\n";
}

echo "==========================================\n";
