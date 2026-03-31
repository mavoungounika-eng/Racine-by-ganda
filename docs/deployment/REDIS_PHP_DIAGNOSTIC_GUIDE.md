# Guide Technique - Redis + PHP 8.1+ + Laravel 10+

**Environnement:** Windows (XAMPP) | PHP 8.1+ | Laravel 10+ | Redis 7.x  
**Objectif:** Diagnostic rapide et configuration production-ready

---

## 📋 Table des Matières

1. [Vérification Extension PHP Redis](#1-vérification-extension-php-redis)
2. [Vérification Redis Server](#2-vérification-redis-server)
3. [Test Connexion PHP](#3-test-connexion-php)
4. [Diagnostic Avancé](#4-diagnostic-avancé)
5. [Intégration Laravel](#5-intégration-laravel)
6. [Erreurs Fréquentes](#6-erreurs-fréquentes)
7. [Sécurité Minimale](#7-sécurité-minimale)
8. [Checklist Finale](#8-checklist-finale)

---

## 1. Vérification Extension PHP Redis

### 1.1 PowerShell - Vérification Rapide

```powershell
# Extension chargée ?
php -m | findstr redis
# Résultat attendu: redis

# Version et détails
php -r "echo 'Extension: ' . (extension_loaded('redis') ? 'OUI' : 'NON') . PHP_EOL;"
php -r "echo 'Version: ' . phpversion('redis') . PHP_EOL;"
```

### 1.2 Via phpinfo()

```php
<?php
// Créer: C:\xampp\htdocs\check_redis.php
phpinfo();
```

**Accès:** `http://localhost/check_redis.php`  
**Rechercher:** Section "redis" (Ctrl+F)

### 1.3 Script PHP Contrôle

```php
<?php
// verify_extension.php
if (extension_loaded('redis')) {
    echo "✅ Extension Redis: ACTIVE\n";
    echo "   Version: " . phpversion('redis') . "\n";
    echo "   Classe Redis: " . (class_exists('Redis') ? 'OUI' : 'NON') . "\n";
} else {
    echo "❌ Extension Redis: MANQUANTE\n";
    echo "\n📝 Installation:\n";
    echo "1. Télécharger: https://pecl.php.net/package/redis\n";
    echo "2. Copier php_redis.dll dans C:\\xampp\\php\\ext\\\n";
    echo "3. Éditer php.ini: extension=redis\n";
    echo "4. Redémarrer Apache\n";
}
```

**Exécution:**
```powershell
php verify_extension.php
```

---

## 2. Vérification Redis Server

### 2.1 Vérification Processus

```powershell
# Processus actif ?
tasklist | findstr redis-server
# Résultat: redis-server.exe    PID Console    1    XX,XXX K

# Port 6379 ouvert ?
netstat -ano | findstr :6379
# Résultat: TCP    127.0.0.1:6379    0.0.0.0:0    LISTENING    PID
```

### 2.2 Test redis-cli

```powershell
# Ping serveur
redis-cli ping
# Résultat: PONG

# Info version
redis-cli info server | findstr redis_version
# Résultat: redis_version:7.0.15
```

### 2.3 Gestion Service Windows

```powershell
# Démarrer service
net start Redis

# Arrêter service
net stop Redis

# Statut service
sc query Redis

# Installer comme service (première fois)
redis-server --service-install redis.windows.conf
redis-server --service-start
```

### 2.4 Alternative Moderne Recommandée ⭐

#### Option 1: WSL2 (Recommandé)

```powershell
# Installer WSL2
wsl --install

# Dans WSL2
sudo apt update
sudo apt install redis-server
sudo systemctl start redis
redis-cli ping
```

**Avantages:** Performance native Linux, compatibilité 100%, gratuit

#### Option 2: Docker

```powershell
# Lancer Redis
docker run -d -p 6379:6379 --name redis redis:7-alpine

# Vérifier
docker exec -it redis redis-cli ping
```

**Avantages:** Isolation, versions multiples, portable

#### Option 3: Memurai (Commercial)

```powershell
# Télécharger: https://www.memurai.com/
# Installation native Windows, compatible Redis 6.2+
```

**Avantages:** Support officiel Windows, performance optimisée

---

## 3. Test Connexion PHP

### 3.1 Script Complet

```php
<?php
// test_redis_connection.php

echo "=== Test Connexion Redis ===\n\n";

try {
    // 1. Créer instance
    $redis = new Redis();
    
    // 2. Connexion avec timeout
    $connected = $redis->connect('127.0.0.1', 6379, 2.5);
    if (!$connected) {
        throw new Exception("Échec connexion");
    }
    echo "✅ Connexion: 127.0.0.1:6379\n";
    
    // 3. Ping
    $pong = $redis->ping();
    echo "✅ Ping: " . $pong . "\n";
    
    // 4. SET/GET
    $redis->set('test:key', 'Hello Redis!');
    $value = $redis->get('test:key');
    echo "✅ SET/GET: " . $value . "\n";
    
    // 5. INCR
    $redis->set('test:counter', 0);
    $redis->incr('test:counter');
    $counter = $redis->get('test:counter');
    echo "✅ INCR: " . $counter . "\n";
    
    // 6. TTL
    $redis->setex('test:expire', 60, 'expires');
    $ttl = $redis->ttl('test:expire');
    echo "✅ TTL: " . $ttl . "s\n";
    
    // 7. Info serveur
    $info = $redis->info('server');
    echo "✅ Version: " . $info['redis_version'] . "\n";
    
    // Cleanup
    $redis->del('test:key', 'test:counter', 'test:expire');
    $redis->close();
    
    echo "\n🎉 SUCCÈS: Redis opérationnel!\n";
    
} catch (RedisException $e) {
    echo "\n❌ ERREUR Redis: " . $e->getMessage() . "\n";
    echo "\n📝 Vérifications:\n";
    echo "1. Redis lancé? → redis-cli ping\n";
    echo "2. Port ouvert? → netstat -ano | findstr :6379\n";
    echo "3. Firewall? → Désactiver temporairement\n";
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
}
```

**Exécution:**
```powershell
php test_redis_connection.php
```

---

## 4. Diagnostic Avancé

### 4.1 Test Databases (0-15)

```php
<?php
// test_databases.php

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

echo "=== Test Databases ===\n\n";

// Test isolation
$databases = [0, 5, 10, 15];
foreach ($databases as $db) {
    $redis->select($db);
    $redis->set("db{$db}:test", "Database {$db}");
    echo "✅ DB {$db}: OK\n";
}

// Vérifier isolation
$redis->select(0);
$value = $redis->get('db15:test');
echo "✅ Isolation: " . ($value === false ? 'OK' : 'ERREUR') . "\n";

// Cleanup
foreach ($databases as $db) {
    $redis->select($db);
    $redis->del("db{$db}:test");
}
```

### 4.2 Test Performance

```php
<?php
// test_performance.php

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

echo "=== Test Performance ===\n\n";

// 1000 SET
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $redis->set("perf:$i", "value_$i");
}
$duration = (microtime(true) - $start) * 1000;
echo "✅ 1000 SET: " . round($duration, 2) . " ms\n";
echo "   (" . round(1000 / ($duration / 1000)) . " ops/s)\n";

// 1000 GET
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $redis->get("perf:$i");
}
$duration = (microtime(true) - $start) * 1000;
echo "✅ 1000 GET: " . round($duration, 2) . " ms\n";
echo "   (" . round(1000 / ($duration / 1000)) . " ops/s)\n";

// Cleanup
for ($i = 0; $i < 1000; $i++) {
    $redis->del("perf:$i");
}

echo "\n📊 Référence:\n";
echo "   Excellent: <100ms (>10k ops/s)\n";
echo "   Bon: 100-500ms (2k-10k ops/s)\n";
echo "   Acceptable: 500-1000ms (1k-2k ops/s)\n";
```

### 4.3 Info Serveur

```php
<?php
// info_server.php

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$info = $redis->info();

echo "=== Info Serveur Redis ===\n\n";
echo "📊 Serveur:\n";
echo "   Version: " . $info['redis_version'] . "\n";
echo "   OS: " . $info['os'] . "\n";
echo "   Uptime: " . round($info['uptime_in_seconds'] / 3600, 2) . " heures\n";

echo "\n💾 Mémoire:\n";
echo "   Utilisée: " . round($info['used_memory'] / 1024 / 1024, 2) . " MB\n";
echo "   Peak: " . round($info['used_memory_peak'] / 1024 / 1024, 2) . " MB\n";

echo "\n📈 Statistiques:\n";
echo "   Connexions: " . number_format($info['total_connections_received']) . "\n";
echo "   Commandes: " . number_format($info['total_commands_processed']) . "\n";
```

---

## 5. Intégration Laravel

### 5.1 Configuration `config/database.php`

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'), // ⚠️ Utiliser phpredis, pas predis
    
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
    ],
    
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
    
    'test' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => 15, // Database isolée pour tests
    ],
],
```

### 5.2 Configuration `.env`

```env
# Redis Configuration
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# Drivers utilisant Redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 5.3 Tests via Artisan Tinker

```bash
# Lancer Tinker
php artisan tinker

# Test connexion
>>> use Illuminate\Support\Facades\Redis;
>>> Redis::ping()
=> "+PONG"

# Test SET/GET
>>> Redis::set('test', 'Laravel Redis OK')
=> true
>>> Redis::get('test')
=> "Laravel Redis OK"

# Test database spécifique
>>> Redis::connection('cache')->ping()
=> "+PONG"
>>> Redis::connection('test')->ping()
=> "+PONG"

# Info
>>> Redis::info('server')['redis_version']
=> "7.0.15"

# Cleanup
>>> Redis::del('test')
=> 1
```

### 5.4 Script Laravel Autonome

```php
<?php
// test_laravel_redis.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Redis;

echo "=== Test Laravel Redis ===\n\n";

try {
    // Connexion défaut
    echo "✅ Ping: " . Redis::ping() . "\n";
    
    // SET/GET
    Redis::set('laravel:test', 'OK');
    echo "✅ SET/GET: " . Redis::get('laravel:test') . "\n";
    
    // Cache
    Redis::connection('cache')->set('cache:test', 'OK');
    echo "✅ Cache DB: " . Redis::connection('cache')->get('cache:test') . "\n";
    
    // Test DB
    Redis::connection('test')->set('test:key', 'OK');
    echo "✅ Test DB: " . Redis::connection('test')->get('test:key') . "\n";
    
    // Cleanup
    Redis::del('laravel:test');
    Redis::connection('cache')->del('cache:test');
    Redis::connection('test')->del('test:key');
    
    echo "\n🎉 Laravel Redis: FONCTIONNEL!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
}
```

**Exécution:**
```powershell
php test_laravel_redis.php
```

### 5.5 Route Test API

```php
// routes/web.php ou routes/api.php

use Illuminate\Support\Facades\Redis;

Route::get('/test-redis', function () {
    try {
        $redis = Redis::connection();
        
        // Test opérations
        $redis->set('api:test', 'OK');
        $value = $redis->get('api:test');
        $redis->del('api:test');
        
        return response()->json([
            'status' => 'success',
            'message' => 'Redis opérationnel',
            'test_value' => $value,
            'server_info' => [
                'version' => $redis->info('server')['redis_version'],
                'uptime_hours' => round($redis->info('server')['uptime_in_seconds'] / 3600, 2),
            ]
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});
```

**Test:**
```powershell
curl http://localhost:8000/test-redis
```

---

## 6. Erreurs Fréquentes

### 6.1 Class 'Redis' not found

**Cause:** Extension PHP Redis non installée/activée

**Solution:**
```powershell
# 1. Télécharger DLL compatible
# https://pecl.php.net/package/redis
# Version: PHP 8.1, Thread Safe (TS), x64

# 2. Copier dans extensions
copy php_redis.dll C:\xampp\php\ext\

# 3. Activer dans php.ini
notepad C:\xampp\php\php.ini
# Ajouter: extension=redis

# 4. Redémarrer Apache
# Via XAMPP Control Panel

# 5. Vérifier
php -m | findstr redis
```

### 6.2 Connection refused [tcp://127.0.0.1:6379]

**Cause:** Redis server non lancé

**Solution:**
```powershell
# Vérifier processus
tasklist | findstr redis

# Si absent, démarrer
redis-server
# ou
net start Redis

# Vérifier port
netstat -ano | findstr :6379

# Test connexion
redis-cli ping
```

### 6.3 NOAUTH Authentication required

**Cause:** Redis configuré avec mot de passe

**Solution PHP:**
```php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$redis->auth('votre_mot_de_passe'); // Ajouter cette ligne
```

**Solution Laravel (.env):**
```env
REDIS_PASSWORD=votre_mot_de_passe
```

**Désactiver auth (dev uniquement):**
```bash
# Dans redis.conf
# requirepass votre_mot_de_passe  # Commenter cette ligne
```

### 6.4 MISCONF Redis is configured to save RDB snapshots

**Cause:** Espace disque insuffisant ou permissions

**Solution temporaire (dev):**
```bash
redis-cli config set stop-writes-on-bgsave-error no
```

**Solution permanente:**
```bash
# Dans redis.conf
stop-writes-on-bgsave-error no

# Ou vérifier espace disque
df -h  # Linux
Get-PSDrive C  # PowerShell
```

### 6.5 ERR max number of clients reached

**Cause:** Trop de connexions simultanées

**Solution:**
```bash
# Augmenter limite dans redis.conf
maxclients 10000

# Redémarrer Redis
net stop Redis
net start Redis

# Vérifier connexions actuelles
redis-cli info clients
```

---

## 7. Sécurité Minimale

### 7.1 Configuration `redis.conf`

```conf
# Bind uniquement localhost (pas d'accès externe)
bind 127.0.0.1 ::1

# Mode protégé activé
protected-mode yes

# Mot de passe requis (production)
requirepass VotreMotDePasseComplexe123!

# Désactiver commandes dangereuses
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command CONFIG ""

# Limiter connexions
maxclients 1000
timeout 300
```

### 7.2 Firewall Windows

```powershell
# Bloquer port 6379 depuis l'extérieur
New-NetFirewallRule -DisplayName "Redis Local Only" `
    -Direction Inbound `
    -LocalPort 6379 `
    -Protocol TCP `
    -Action Block `
    -RemoteAddress Any

# Autoriser uniquement localhost
New-NetFirewallRule -DisplayName "Redis Localhost" `
    -Direction Inbound `
    -LocalPort 6379 `
    -Protocol TCP `
    -Action Allow `
    -RemoteAddress 127.0.0.1
```

### 7.3 Laravel - Sécurisation

```php
// config/database.php
'redis' => [
    'client' => 'phpredis',
    
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'), // Jamais 0.0.0.0
        'password' => env('REDIS_PASSWORD'), // Toujours en production
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
        'read_timeout' => 60,
        'context' => [
            // SSL/TLS si Redis distant
            'stream' => [
                'verify_peer' => true,
            ],
        ],
    ],
],
```

---

## 8. Checklist Finale

### ✅ Diagnostic Ultra-Rapide (5 commandes)

```powershell
# 1. Extension PHP
php -m | findstr redis
# Attendu: redis

# 2. Redis Server
redis-cli ping
# Attendu: PONG

# 3. Connexion PHP
php -r "$r=new Redis();$r->connect('127.0.0.1',6379);echo $r->ping();"
# Attendu: 1 ou +PONG

# 4. Laravel (si applicable)
php artisan tinker --execute="echo Redis::ping();"
# Attendu: +PONG

# 5. Tests intégration
php artisan test tests/Integration/
# Attendu: Tests: X passed
```

### 📊 Validation Complète

| Vérification | Commande | Résultat Attendu |
|--------------|----------|------------------|
| Extension PHP | `php -m \| findstr redis` | `redis` |
| Redis actif | `redis-cli ping` | `PONG` |
| Port ouvert | `netstat -ano \| findstr :6379` | `LISTENING` |
| Connexion PHP | Script test | `✅ SUCCÈS` |
| Laravel config | `php artisan tinker` | `Redis::ping() => "+PONG"` |
| Tests passants | `php artisan test tests/Integration/` | `Tests: X passed` |

### 🚀 Production Readiness

- [ ] Extension PHP Redis installée (version ≥5.3.0)
- [ ] Redis 7.x lancé et accessible
- [ ] Configuration Laravel validée (phpredis, pas predis)
- [ ] Databases isolées (0=app, 1=cache, 15=test)
- [ ] Sécurité configurée (bind 127.0.0.1, password)
- [ ] Tests intégration passants (100%)
- [ ] Performance acceptable (>1000 ops/s)
- [ ] Monitoring configuré (logs, alertes)

---

**Guide créé:** 13 février 2026  
**Version:** 1.0 - Production Ready  
**Environnement:** Windows (XAMPP) + PHP 8.1+ + Laravel 10+ + Redis 7.x
