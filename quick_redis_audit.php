#!/usr/bin/env php
<?php
/**
 * AUDIT ULTRA PRO - 30 SECONDES
 * Validation stack Redis sans dépendance redis-cli
 */

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║        AUDIT ULTRA PRO - REDIS STACK (30s)                ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$start = microtime(true);
$checks = ['pass' => 0, 'fail' => 0];

// 1. Extension PHP
echo "1️⃣  Extension PHP Redis: ";
if (extension_loaded('redis')) {
    echo "✅ PASS (" . phpversion('redis') . ")\n";
    $checks['pass']++;
} else {
    echo "❌ FAIL\n";
    $checks['fail']++;
}

// 2. Connexion Redis
echo "2️⃣  Connexion Redis: ";
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379, 2);
    echo "✅ PASS (127.0.0.1:6379)\n";
    $checks['pass']++;
    
    // 3. Ping
    echo "3️⃣  Ping Server: ";
    $pong = $redis->ping();
    echo "✅ PASS (" . $pong . ")\n";
    $checks['pass']++;
    
    // 4. Version Redis
    echo "4️⃣  Version Redis: ";
    $info = $redis->info('server');
    echo "✅ PASS (" . $info['redis_version'] . ")\n";
    $checks['pass']++;
    
    // 5. Opérations
    echo "5️⃣  Opérations SET/GET: ";
    $redis->set('audit:test', 'OK');
    $value = $redis->get('audit:test');
    $redis->del('audit:test');
    echo ($value === 'OK' ? "✅ PASS\n" : "❌ FAIL\n");
    $checks[$value === 'OK' ? 'pass' : 'fail']++;
    
} catch (Exception $e) {
    echo "❌ FAIL (" . $e->getMessage() . ")\n";
    $checks['fail'] += 4;
}

// 6. Laravel (si disponible)
echo "6️⃣  Laravel Integration: ";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    try {
        require __DIR__.'/vendor/autoload.php';
        $app = require_once __DIR__.'/bootstrap/app.php';
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        
        $pong = Illuminate\Support\Facades\Redis::ping();
        echo "✅ PASS (" . $pong . ")\n";
        $checks['pass']++;
    } catch (Exception $e) {
        echo "❌ FAIL (" . $e->getMessage() . ")\n";
        $checks['fail']++;
    }
} else {
    echo "⏭️  SKIP\n";
}

$duration = round((microtime(true) - $start), 2);

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║                    RÉSUMÉ                                 ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$total = $checks['pass'] + $checks['fail'];
echo "📊 {$checks['pass']}/{$total} tests passés en {$duration}s\n\n";

if ($checks['fail'] === 0) {
    echo "🎉 STACK VALIDÉE - Production Ready!\n\n";
    echo "✅ Extension PHP Redis: OK\n";
    echo "✅ Redis Server: OK\n";
    echo "✅ Connexion: OK\n";
    echo "✅ Opérations: OK\n";
    if ($checks['pass'] > 5) echo "✅ Laravel: OK\n";
    echo "\n📝 Prochaines étapes:\n";
    echo "   php artisan test tests/Integration/\n";
    exit(0);
} else {
    echo "❌ {$checks['fail']} problème(s) détecté(s)\n";
    exit(1);
}
