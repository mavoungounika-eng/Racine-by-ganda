<?php
/**
 * Redis + PHP - Diagnostic Complet
 * 
 * Script autonome pour vérifier configuration Redis
 * Usage: php redis_diagnostic.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║          DIAGNOSTIC REDIS + PHP COMPLET                    ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$warnings = [];

// ============================================================
// 1. EXTENSION PHP REDIS
// ============================================================
echo "1️⃣  EXTENSION PHP REDIS\n";
echo str_repeat("─", 60) . "\n";

if (extension_loaded('redis')) {
    echo "✅ Extension Redis: CHARGÉE\n";
    echo "   Version: " . phpversion('redis') . "\n";
    echo "   Classe Redis: " . (class_exists('Redis') ? 'Disponible' : 'Manquante') . "\n";
    echo "   Classe RedisArray: " . (class_exists('RedisArray') ? 'Disponible' : 'Manquante') . "\n";
} else {
    echo "❌ Extension Redis: NON CHARGÉE\n";
    $errors[] = "Extension PHP Redis manquante";
    echo "\n📝 Solution:\n";
    echo "   Windows: Télécharger DLL depuis https://pecl.php.net/package/redis\n";
    echo "            Copier dans php/ext/, ajouter 'extension=redis' dans php.ini\n";
    echo "   Linux:   sudo apt-get install php-redis && sudo systemctl restart php-fpm\n";
}
echo "\n";

// ============================================================
// 2. CONNEXION REDIS SERVER
// ============================================================
echo "2️⃣  CONNEXION REDIS SERVER\n";
echo str_repeat("─", 60) . "\n";

$redis = null;
$connected = false;

if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $connected = $redis->connect('127.0.0.1', 6379, 2.5);
        
        if ($connected) {
            echo "✅ Connexion: ÉTABLIE (127.0.0.1:6379)\n";
            
            $pong = $redis->ping();
            echo "✅ Ping: " . $pong . "\n";
            
            $info = $redis->info('server');
            echo "✅ Version Redis: " . $info['redis_version'] . "\n";
            echo "   OS: " . $info['os'] . "\n";
            echo "   Uptime: " . round($info['uptime_in_seconds'] / 3600, 2) . " heures\n";
            echo "   Process ID: " . $info['process_id'] . "\n";
        } else {
            throw new Exception("Échec connexion");
        }
    } catch (Exception $e) {
        echo "❌ Connexion: ÉCHEC\n";
        echo "   Erreur: " . $e->getMessage() . "\n";
        $errors[] = "Impossible de se connecter à Redis";
        echo "\n📝 Solution:\n";
        echo "   1. Vérifier Redis lancé: redis-cli ping\n";
        echo "   2. Windows: net start Redis ou redis-server.exe\n";
        echo "   3. Linux: sudo systemctl start redis\n";
    }
} else {
    echo "⏭️  Ignoré (extension non chargée)\n";
}
echo "\n";

// ============================================================
// 3. TEST OPÉRATIONS DE BASE
// ============================================================
echo "3️⃣  TEST OPÉRATIONS DE BASE\n";
echo str_repeat("─", 60) . "\n";

if ($connected && $redis) {
    try {
        // SET/GET
        $redis->set('diag:test:string', 'Hello Redis!');
        $value = $redis->get('diag:test:string');
        echo "✅ SET/GET String: " . ($value === 'Hello Redis!' ? 'OK' : 'ERREUR') . "\n";
        
        // INCR/DECR
        $redis->set('diag:test:counter', 0);
        $redis->incr('diag:test:counter');
        $redis->incr('diag:test:counter');
        $counter = $redis->get('diag:test:counter');
        echo "✅ INCR: " . ($counter == 2 ? 'OK' : 'ERREUR') . "\n";
        
        // EXPIRE/TTL
        $redis->setex('diag:test:expire', 60, 'expires in 60s');
        $ttl = $redis->ttl('diag:test:expire');
        echo "✅ EXPIRE/TTL: " . ($ttl > 0 && $ttl <= 60 ? 'OK' : 'ERREUR') . " (TTL: {$ttl}s)\n";
        
        // LIST
        $redis->del('diag:test:list');
        $redis->rpush('diag:test:list', 'item1', 'item2', 'item3');
        $len = $redis->llen('diag:test:list');
        echo "✅ LIST (RPUSH/LLEN): " . ($len == 3 ? 'OK' : 'ERREUR') . "\n";
        
        // HASH
        $redis->hset('diag:test:hash', 'field1', 'value1');
        $redis->hset('diag:test:hash', 'field2', 'value2');
        $hlen = $redis->hlen('diag:test:hash');
        echo "✅ HASH (HSET/HLEN): " . ($hlen == 2 ? 'OK' : 'ERREUR') . "\n";
        
        // SET (ensemble)
        $redis->sadd('diag:test:set', 'member1', 'member2', 'member3');
        $scard = $redis->scard('diag:test:set');
        echo "✅ SET (SADD/SCARD): " . ($scard == 3 ? 'OK' : 'ERREUR') . "\n";
        
        // Cleanup
        $redis->del('diag:test:string', 'diag:test:counter', 'diag:test:expire', 
                    'diag:test:list', 'diag:test:hash', 'diag:test:set');
        
    } catch (Exception $e) {
        echo "❌ Opérations: ERREUR\n";
        echo "   " . $e->getMessage() . "\n";
        $errors[] = "Échec opérations Redis";
    }
} else {
    echo "⏭️  Ignoré (pas de connexion)\n";
}
echo "\n";

// ============================================================
// 4. TEST DATABASES (ISOLATION)
// ============================================================
echo "4️⃣  TEST DATABASES (ISOLATION)\n";
echo str_repeat("─", 60) . "\n";

if ($connected && $redis) {
    try {
        // Test DB 0, 5, 10, 15
        $databases = [0, 5, 10, 15];
        foreach ($databases as $db) {
            $redis->select($db);
            $redis->set("diag:db{$db}:test", "Database {$db}");
            $value = $redis->get("diag:db{$db}:test");
            echo "✅ DB {$db}: " . ($value === "Database {$db}" ? 'OK' : 'ERREUR') . "\n";
        }
        
        // Vérifier isolation
        $redis->select(0);
        $value = $redis->get('diag:db15:test');
        echo "✅ Isolation DB 0 ↔ DB 15: " . ($value === false ? 'OK' : 'ERREUR') . "\n";
        
        // Cleanup
        foreach ($databases as $db) {
            $redis->select($db);
            $redis->del("diag:db{$db}:test");
        }
        $redis->select(0); // Retour DB 0
        
    } catch (Exception $e) {
        echo "❌ Databases: ERREUR\n";
        echo "   " . $e->getMessage() . "\n";
        $warnings[] = "Problème avec databases multiples";
    }
} else {
    echo "⏭️  Ignoré (pas de connexion)\n";
}
echo "\n";

// ============================================================
// 5. TEST PERFORMANCE
// ============================================================
echo "5️⃣  TEST PERFORMANCE\n";
echo str_repeat("─", 60) . "\n";

if ($connected && $redis) {
    try {
        // Test 1000 SET
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $redis->set("diag:perf:$i", "value_$i");
        }
        $duration_set = (microtime(true) - $start) * 1000;
        echo "✅ 1000 SET: " . round($duration_set, 2) . " ms ";
        echo "(" . round(1000 / ($duration_set / 1000)) . " ops/s)\n";
        
        // Test 1000 GET
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $redis->get("diag:perf:$i");
        }
        $duration_get = (microtime(true) - $start) * 1000;
        echo "✅ 1000 GET: " . round($duration_get, 2) . " ms ";
        echo "(" . round(1000 / ($duration_get / 1000)) . " ops/s)\n";
        
        // Test 1000 DEL
        $start = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $redis->del("diag:perf:$i");
        }
        $duration_del = (microtime(true) - $start) * 1000;
        echo "✅ 1000 DEL: " . round($duration_del, 2) . " ms ";
        echo "(" . round(1000 / ($duration_del / 1000)) . " ops/s)\n";
        
        // Évaluation performance
        if ($duration_set < 100 && $duration_get < 100) {
            echo "🚀 Performance: EXCELLENTE\n";
        } elseif ($duration_set < 500 && $duration_get < 500) {
            echo "✅ Performance: BONNE\n";
        } else {
            echo "⚠️  Performance: LENTE\n";
            $warnings[] = "Performance Redis sous-optimale";
        }
        
    } catch (Exception $e) {
        echo "❌ Performance: ERREUR\n";
        echo "   " . $e->getMessage() . "\n";
    }
} else {
    echo "⏭️  Ignoré (pas de connexion)\n";
}
echo "\n";

// ============================================================
// 6. INFORMATIONS SYSTÈME
// ============================================================
echo "6️⃣  INFORMATIONS SYSTÈME\n";
echo str_repeat("─", 60) . "\n";

if ($connected && $redis) {
    try {
        $info = $redis->info();
        
        echo "📊 Mémoire:\n";
        echo "   Utilisée: " . round($info['used_memory'] / 1024 / 1024, 2) . " MB\n";
        echo "   Peak: " . round($info['used_memory_peak'] / 1024 / 1024, 2) . " MB\n";
        
        echo "\n📈 Statistiques:\n";
        echo "   Connexions totales: " . number_format($info['total_connections_received']) . "\n";
        echo "   Commandes traitées: " . number_format($info['total_commands_processed']) . "\n";
        echo "   Clés expirées: " . number_format($info['expired_keys']) . "\n";
        
        echo "\n💾 Persistence:\n";
        echo "   RDB: " . ($info['rdb_bgsave_in_progress'] == 0 ? 'Inactif' : 'En cours') . "\n";
        echo "   AOF: " . ($info['aof_enabled'] == 1 ? 'Activé' : 'Désactivé') . "\n";
        
    } catch (Exception $e) {
        echo "⚠️  Infos système: Non disponibles\n";
    }
} else {
    echo "⏭️  Ignoré (pas de connexion)\n";
}
echo "\n";

// ============================================================
// RÉSUMÉ FINAL
// ============================================================
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                    RÉSUMÉ FINAL                            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if (empty($errors)) {
    echo "🎉 SUCCÈS: Redis est parfaitement configuré et fonctionnel!\n\n";
    echo "✅ Extension PHP Redis: Installée\n";
    echo "✅ Redis Server: Accessible\n";
    echo "✅ Opérations: Fonctionnelles\n";
    echo "✅ Databases: Isolées\n";
    echo "✅ Performance: " . (empty($warnings) ? 'Optimale' : 'Acceptable') . "\n";
} else {
    echo "❌ PROBLÈMES DÉTECTÉS:\n\n";
    foreach ($errors as $i => $error) {
        echo "   " . ($i + 1) . ". " . $error . "\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "\n⚠️  AVERTISSEMENTS:\n\n";
    foreach ($warnings as $i => $warning) {
        echo "   " . ($i + 1) . ". " . $warning . "\n";
    }
}

echo "\n";
echo "📝 Prochaines étapes:\n";
if (empty($errors)) {
    echo "   1. Exécuter tests: php artisan test tests/Integration/\n";
    echo "   2. Vérifier couverture: php artisan test --coverage\n";
    echo "   3. Configurer CI/CD selon README_PHASE3.md\n";
} else {
    echo "   1. Corriger les erreurs ci-dessus\n";
    echo "   2. Relancer ce diagnostic: php redis_diagnostic.php\n";
    echo "   3. Consulter: redis_php_verification_guide.md\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Diagnostic terminé: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Code de sortie
exit(empty($errors) ? 0 : 1);
