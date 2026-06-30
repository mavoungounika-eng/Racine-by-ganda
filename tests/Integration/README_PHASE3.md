# Tests Phase 3 - RACINE BY GANDA

**Documentation complète pour la suite de tests d'intégration Phase 3**

---

## 📋 Vue d'Ensemble

Cette suite de tests valide les composants de protection et monitoring des queues:
- **QueueCircuitBreaker** - Protection contre surcharge
- **QueueRateLimiter** - Limitation débit jobs
- **QueueMonitor** - Collecte métriques
- **AlertService** - Notifications multi-canaux
- **MetricsController** - Endpoints exposition métriques

### Statistiques

| Métrique | Valeur |
|----------|--------|
| **Tests totaux** | 42 |
| **Suites de tests** | 5 |
| **Couverture cible** | ≥85% |
| **Type** | Tests d'intégration |
| **Framework** | PHPUnit 12 |
| **PHP Version** | 8.1+ |

---

## 🚀 Quick Start

### Prérequis

```bash
# Redis doit être installé et accessible
redis-cli ping  # Doit retourner "PONG"

# Composer dependencies
composer install

# Configuration
cp .env.example .env
php artisan key:generate
```

### Exécution Rapide

```bash
# Tous les tests Phase 3
php artisan test tests/Integration/ tests/Feature/Admin/MetricsEndpointsTest.php

# Avec couverture
php artisan test --coverage --min=85

# Tests spécifiques
php artisan test tests/Integration/Services/Queue/QueueCircuitBreakerIntegrationTest.php
```

---

## 🔧 Setup Redis Test

### Configuration

Les tests utilisent la **database Redis 15** (isolée de production).

**Automatique:** `IntegrationTestCase` configure automatiquement Redis pour les tests.

**Manuel (si nécessaire):**

```php
// config/database.php
'redis' => [
    'default' => [
        'database' => env('REDIS_DB', 0),
    ],
    'test' => [
        'database' => 15,  // Database dédiée tests
    ],
],
```

### Vérification

```bash
# Vérifier connexion Redis
redis-cli -n 15 ping

# Vérifier database vide
redis-cli -n 15 DBSIZE  # Doit retourner 0 avant tests

# Flush manual si nécessaire
redis-cli -n 15 FLUSHDB
```

---

## 📁 Structure Tests

```
tests/
├── Integration/
│   ├── IntegrationTestCase.php                    # Base class
│   ├── Services/
│   │   ├── Queue/
│   │   │   ├── QueueCircuitBreakerIntegrationTest.php  # 10 tests
│   │   │   ├── QueueRateLimiterIntegrationTest.php     # 8 tests
│   │   │   └── QueueMonitorIntegrationTest.php         # 8 tests
│   │   └── Monitoring/
│   │       └── AlertServiceIntegrationTest.php         # 6 tests
│   └── Feature/
│       └── Admin/
│           └── MetricsEndpointsTest.php                # 12 tests
```

### IntegrationTestCase

Classe de base fournissant:
- Setup/cleanup Redis automatique
- Helpers Redis (`assertRedisKeyExists`, `setRedisValue`, etc.)
- Isolation database 15
- Utilities communes

**Exemple utilisation:**

```php
use Tests\Integration\IntegrationTestCase;

class MyIntegrationTest extends IntegrationTestCase
{
    #[Test]
    public function my_test(): void
    {
        // Redis déjà configuré et vide
        $this->setRedisValue('test:key', 'value');
        $this->assertRedisKeyExists('test:key');
    }
}
```

---

## 🧪 Exécution Tests

### Commandes Principales

```bash
# Tous les tests Phase 3
php artisan test tests/Integration/ tests/Feature/Admin/MetricsEndpointsTest.php

# Par suite
php artisan test tests/Integration/Services/Queue/QueueCircuitBreakerIntegrationTest.php
php artisan test tests/Integration/Services/Queue/QueueRateLimiterIntegrationTest.php
php artisan test tests/Integration/Services/Queue/QueueMonitorIntegrationTest.php
php artisan test tests/Integration/Services/Monitoring/AlertServiceIntegrationTest.php
php artisan test tests/Feature/Admin/MetricsEndpointsTest.php

# Avec options
php artisan test --stop-on-failure  # Arrêter au premier échec
php artisan test --filter=circuit   # Tests contenant "circuit"
php artisan test --testsuite=Integration  # Suite spécifique (si configurée)
```

### Couverture Code

```bash
# Couverture complète
php artisan test --coverage

# Avec seuil minimum
php artisan test --coverage --min=85

# Rapport HTML
php artisan test --coverage-html=coverage/

# Rapport texte détaillé
php artisan test --coverage-text
```

### Mode Debug

```bash
# Verbose output
php artisan test --verbose

# Avec logs
php artisan test --log-junit=test-results.xml

# PHPUnit direct (plus d'options)
./vendor/bin/phpunit tests/Integration/Services/Queue/QueueCircuitBreakerIntegrationTest.php --testdox
```

---

## 🎯 Tests par Composant

### 1. QueueCircuitBreakerIntegrationTest (10 tests)

**Couvre:**
- États circuit (CLOSED, OPEN, HALF_OPEN)
- Transitions automatiques
- Seuils échecs/succès
- Timeout et cooldown
- Persistence Redis
- TTL expiration
- Alertes critiques
- Reset manuel
- Isolation queues multiples

**Exemple:**

```bash
php artisan test --filter=QueueCircuitBreaker
```

### 2. QueueRateLimiterIntegrationTest (8 tests)

**Couvre:**
- Limites par job type
- Compteurs Redis
- Fenêtres glissantes
- Reset automatique
- Métriques temps réel
- Clear manuel
- Limite par défaut
- Isolation job types

**Exemple:**

```bash
php artisan test --filter=QueueRateLimiter
```

### 3. QueueMonitorIntegrationTest (8 tests)

**Couvre:**
- Collecte métriques queues
- Temps traitement (avg, p50, p95, p99)
- Taux échec
- Seuils warning/critical
- Export Prometheus
- Intégration circuit breaker
- Intégration rate limiter

**Exemple:**

```bash
php artisan test --filter=QueueMonitor
```

### 4. AlertServiceIntegrationTest (6 tests)

**Couvre:**
- Notifications Slack (mock HTTP)
- Notifications Email (mock Mail)
- Logs (spy)
- Routing par sévérité
- Format messages
- Test multi-canaux

**Exemple:**

```bash
php artisan test --filter=AlertService
```

### 5. MetricsEndpointsTest (12 tests)

**Couvre:**
- GET /metrics (Prometheus)
- GET /health (JSON)
- GET /admin/queue-metrics (Dashboard)
- POST /admin/queue-metrics/circuit-breaker/{queue}/reset
- POST /admin/queue-metrics/rate-limiter/{jobType}/reset
- Authentication admin
- Authorization
- Codes HTTP
- Formats réponse

**Exemple:**

```bash
php artisan test tests/Feature/Admin/MetricsEndpointsTest.php
```

---

## 🏗️ Patterns Utilisés

### Tests d'Intégration (pas Mocks)

**Principe:** Tester services réels avec vraies dépendances.

**Mocks uniquement pour:**
- HTTP externe (Slack webhooks)
- Email (SMTP)
- Services tiers

**Services réels:**
- QueueCircuitBreaker
- QueueRateLimiter
- QueueMonitor
- AlertService
- Redis
- Configuration

**Exemple:**

```php
// ❌ MAUVAIS: Mocker service interne
$mockCircuitBreaker = Mockery::mock(QueueCircuitBreaker::class);

// ✅ BON: Utiliser service réel
$circuitBreaker = app(QueueCircuitBreaker::class);
$circuitBreaker->recordFailure('queue');
$this->assertTrue($circuitBreaker->isOpen('queue'));
```

### Assertions sur Résultats

**Principe:** Tester comportement, pas implémentation.

```php
// ❌ MAUVAIS: Assertion sur appels
$mock->shouldReceive('recordFailure')->once();

// ✅ BON: Assertion sur résultat
$this->assertTrue($circuitBreaker->isOpen('queue'));
$this->assertRedisKeyEquals('circuit_breaker:queue:state', 'open');
```

### Isolation Tests

**Principe:** Chaque test indépendant.

```php
protected function setUp(): void
{
    parent::setUp();  // Flush Redis automatique
    
    // Chaque test démarre avec Redis vide
}
```

---

## 📝 Guide Contribution

### Ajouter un Test

1. **Choisir la suite appropriée**
   - Tests service → `tests/Integration/Services/`
   - Tests endpoint → `tests/Feature/Admin/`

2. **Étendre IntegrationTestCase**

```php
<?php

namespace Tests\Integration\Services\Queue;

use Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\Test;

class MyNewTest extends IntegrationTestCase
{
    #[Test]
    public function my_new_test(): void
    {
        // Arrange
        $service = app(MyService::class);
        
        // Act
        $result = $service->doSomething();
        
        // Assert
        $this->assertTrue($result);
    }
}
```

3. **Utiliser PHP 8 Attributes**

```php
// ✅ BON
#[Test]
public function my_test(): void { }

// ❌ MAUVAIS
/** @test */
public function test_my_test() { }
```

4. **Nommer méthodes descriptives**

```php
// ✅ BON
public function circuit_opens_after_threshold_failures(): void

// ❌ MAUVAIS
public function test1(): void
```

### Conventions

- **Nommage:** `snake_case` pour méthodes test
- **Attribut:** `#[Test]` obligatoire
- **Imports:** Toujours `use PHPUnit\Framework\Attributes\Test;`
- **Setup:** Appeler `parent::setUp()` en premier
- **Assertions:** Préférer assertions spécifiques (`assertEquals` vs `assertTrue`)

---

## 🔄 Intégration CI/CD

### GitHub Actions

**Fichier:** `.github/workflows/tests.yml`

```yaml
name: Tests Phase 3

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      redis:
        image: redis:7
        ports:
          - 6379:6379
        options: >-
          --health-cmd "redis-cli ping"
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
          extensions: redis
          coverage: xdebug
      
      - name: Install Dependencies
        run: composer install --prefer-dist --no-progress
      
      - name: Run Tests
        env:
          REDIS_HOST: 127.0.0.1
          REDIS_PORT: 6379
        run: php artisan test tests/Integration/ tests/Feature/Admin/MetricsEndpointsTest.php --coverage --min=85
      
      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage.xml
```

### Variables Environnement

```env
# .env.testing
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_DB=15

QUEUE_CB_FAILURE_THRESHOLD=10
QUEUE_CB_SUCCESS_THRESHOLD=5
QUEUE_CB_TIMEOUT=60
QUEUE_CB_TTL=3600

SLACK_WEBHOOK_URL=https://hooks.slack.com/test
ALERT_EMAIL_RECIPIENTS=admin@racine.com
```

---

## 🐛 Troubleshooting

### Redis Connection Failed

**Erreur:**
```
Connection refused [tcp://127.0.0.1:6379]
```

**Solution:**
```bash
# Démarrer Redis
sudo service redis-server start

# Vérifier status
redis-cli ping
```

### Tests Échouent avec "Permission Denied"

**Erreur:**
```
Illuminate\Auth\Access\AuthorizationException
```

**Solution:**
```php
// Vérifier que user est admin
$admin = User::factory()->create(['role' => 'super_admin']);
$this->actingAs($admin)->get('/admin/queue-metrics');
```

### Redis Database Not Isolated

**Problème:** Tests affectent données production

**Solution:**
```php
// Vérifier IntegrationTestCase::setUp() appelé
protected function setUp(): void
{
    parent::setUp();  // OBLIGATOIRE
}
```

### Configuration Not Found

**Erreur:**
```
Undefined array key "circuit_breaker"
```

**Solution:**
```bash
# Vérifier config existe
php artisan config:cache
php artisan config:clear

# Vérifier fichier
ls -la config/queue-protection.php
```

---

## 📊 Métriques Succès

### Critères Validation

- [ ] 100% tests passants
- [ ] ≥85% couverture code
- [ ] 0 warnings PHPUnit
- [ ] 0 erreurs fatales
- [ ] Temps exécution <2min
- [ ] CI/CD intégré

### Commande Validation Complète

```bash
# Validation finale avant merge
php artisan test tests/Integration/ tests/Feature/Admin/MetricsEndpointsTest.php \
  --coverage \
  --min=85 \
  --stop-on-failure
```

---

## 📚 Ressources

### Documentation

- [PHPUnit 12](https://phpunit.de/documentation.html)
- [Laravel Testing](https://laravel.com/docs/testing)
- [PHP 8 Attributes](https://www.php.net/manual/en/language.attributes.overview.php)
- [Redis Commands](https://redis.io/commands)

### Fichiers Clés

- `tests/Integration/IntegrationTestCase.php` - Base class
- `config/queue-protection.php` - Configuration circuit breaker & rate limiter
- `config/alerts.php` - Configuration alertes
- `routes/web.php` - Routes métriques

---

**Dernière mise à jour:** 12 février 2026  
**Version:** 1.0.0  
**Statut:** ✅ Production Ready
