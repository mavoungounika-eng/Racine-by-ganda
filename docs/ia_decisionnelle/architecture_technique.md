# ARCHITECTURE TECHNIQUE — IA DÉCISIONNELLE

**Référence** : [Cartographie Officielle](./cartographie_ia_decisionnelle.md)  
**Statut** : `PRODUCTION-GRADE`

---

## 1. VUE D'ENSEMBLE

L'IA Décisionnelle de RACINE BY GANDA est construite sur une architecture modulaire, découplée et désactivable.

### Principes architecturaux

1. **Modularité** : Chaque module est indépendant
2. **Observabilité** : Tous les calculs sont tracés
3. **Désactivable** : Chaque module peut être éteint
4. **Non-bloquante** : Aucune dépendance critique
5. **Asynchrone** : Exécution en arrière-plan

---

## 2. MODULES EXISTANTS

### État actuel du codebase

Le projet contient déjà **3 modules d'IA décisionnelle** dans `app/Services/Decision/` :

| Module | Fichier | Rôle |
|--------|---------|------|
| **Churn Prediction** | `ChurnPredictionService.php` | Prédiction du risque de désabonnement |
| **Creator Decision Score** | `CreatorDecisionScoreService.php` | Scoring des créateurs |
| **Recommendation Engine** | `RecommendationEngineService.php` | Moteur de recommandations |

### Modules complémentaires à créer

| Module | Priorité | Rôle |
|--------|----------|------|
| **Product Performance Analyzer** | P0 | Analyse performance produits |
| **Stock Prediction Engine** | P0 | Prédiction ruptures stock |
| **Anomaly Detection Service** | P1 | Détection anomalies ventes/comportement |
| **Conversion Optimization Analyzer** | P2 | Analyse et optimisation conversion |

---

## 3. ARCHITECTURE GLOBALE

### 3.1 Structure des répertoires

```
app/
├── Services/
│   └── Decision/                           # IA Décisionnelle
│       ├── ChurnPredictionService.php      # [EXISTANT]
│       ├── CreatorDecisionScoreService.php # [EXISTANT]
│       ├── RecommendationEngineService.php # [EXISTANT]
│       ├── ProductPerformanceAnalyzer.php  # [À CRÉER]
│       ├── StockPredictionEngine.php       # [À CRÉER]
│       ├── AnomalyDetectionService.php     # [À CRÉER]
│       └── ConversionOptimizationAnalyzer.php # [À CRÉER]
│
├── Jobs/
│   └── AI/
│       ├── AnalyzeProductPerformanceJob.php
│       ├── PredictStockRupturesJob.php
│       ├── DetectAnomaliesJob.php
│       └── GenerateRecommendationsJob.php
│
├── Models/
│   ├── AIRecommendation.php
│   ├── AIAlert.php
│   ├── AICalculationLog.php
│   └── AIMetric.php
│
└── Console/
    └── Commands/
        └── AI/
            ├── RunAIAnalysisCommand.php
            └── GenerateAIReportCommand.php

config/
└── ai_decisional.php                       # Configuration centralisée

database/
└── migrations/
    ├── create_ai_recommendations_table.php
    ├── create_ai_alerts_table.php
    ├── create_ai_calculation_logs_table.php
    └── create_ai_metrics_table.php
```

---

## 4. CONFIGURATION CENTRALISÉE

### `config/ai_decisional.php`

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IA Décisionnelle - Configuration Globale
    |--------------------------------------------------------------------------
    | Configuration centralisée de tous les modules d'IA décisionnelle
    */

    'enabled' => env('AI_DECISIONAL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Modules activables/désactivables
    |--------------------------------------------------------------------------
    */
    'modules' => [
        'churn_prediction' => env('AI_MODULE_CHURN_PREDICTION', true),
        'creator_scoring' => env('AI_MODULE_CREATOR_SCORING', true),
        'recommendation_engine' => env('AI_MODULE_RECOMMENDATION', true),
        'product_performance' => env('AI_MODULE_PRODUCT_PERFORMANCE', true),
        'stock_prediction' => env('AI_MODULE_STOCK_PREDICTION', true),
        'anomaly_detection' => env('AI_MODULE_ANOMALY_DETECTION', true),
        'conversion_optimization' => env('AI_MODULE_CONVERSION_OPT', false), // P2
    ],

    /*
    |--------------------------------------------------------------------------
    | Seuils configurables
    |--------------------------------------------------------------------------
    */
    'thresholds' => [
        // Stock
        'stock_critical_days' => env('AI_STOCK_CRITICAL_DAYS', 7),
        'stock_warning_days' => env('AI_STOCK_WARNING_DAYS', 14),
        
        // Créateurs
        'creator_processing_time_max' => env('AI_CREATOR_PROCESSING_MAX', 3), // jours
        'creator_return_rate_max' => env('AI_CREATOR_RETURN_RATE_MAX', 15), // %
        'creator_dispute_rate_max' => env('AI_CREATOR_DISPUTE_RATE_MAX', 5), // %
        
        // Produits
        'product_performance_min_score' => env('AI_PRODUCT_MIN_SCORE', 40),
        'product_rotation_min' => env('AI_PRODUCT_ROTATION_MIN', 0.5),
        
        // Ventes
        'sales_anomaly_drop_percent' => env('AI_SALES_ANOMALY_DROP', 50),
        'conversion_rate_min' => env('AI_CONVERSION_RATE_MIN', 2), // %
        
        // Churn
        'churn_risk_threshold' => env('AI_CHURN_RISK_THRESHOLD', 0.7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Planification des jobs
    |--------------------------------------------------------------------------
    */
    'schedule' => [
        'product_performance' => 'daily',      // Tous les jours à 2h
        'stock_prediction' => 'daily',         // Tous les jours à 3h
        'anomaly_detection' => 'hourly',       // Toutes les heures
        'churn_prediction' => 'weekly',        // Tous les lundis
        'recommendations' => 'daily',          // Tous les jours à 4h
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging et traçabilité
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('AI_LOGGING_ENABLED', true),
        'log_calculations' => true,
        'log_recommendations' => true,
        'log_alerts' => true,
        'retention_days' => 90, // Conservation des logs
    ],

    /*
    |--------------------------------------------------------------------------
    | Alertes
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'enabled' => env('AI_ALERTS_ENABLED', true),
        'channels' => ['database', 'mail'], // database, mail, slack
        'recipients' => [
            'critical' => explode(',', env('AI_ALERT_CRITICAL_EMAILS', '')),
            'warning' => explode(',', env('AI_ALERT_WARNING_EMAILS', '')),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'cache_enabled' => true,
        'cache_ttl' => 3600, // 1 heure
        'queue' => 'ai-processing', // Queue dédiée
        'timeout' => 300, // 5 minutes max par job
    ],
];
```

---

## 5. MODÈLES DE DONNÉES

### 5.1 Migration : `ai_recommendations`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // recommendation
            $table->enum('priority', ['high', 'medium', 'low']);
            $table->string('category'); // product, creator, stock, sales
            $table->string('title');
            $table->text('description');
            $table->text('suggested_action');
            $table->json('data'); // Données de support
            $table->string('created_by_module'); // Module source
            $table->enum('status', ['pending', 'accepted', 'rejected', 'executed'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'priority']);
            $table->index('created_by_module');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_recommendations');
    }
};
```

### 5.2 Migration : `ai_alerts`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // alert
            $table->enum('severity', ['critical', 'warning', 'info']);
            $table->string('category'); // threshold, anomaly, performance
            $table->string('title');
            $table->text('message');
            $table->json('data');
            $table->string('triggered_by_module');
            $table->boolean('is_read')->default(false);
            $table->foreignId('read_by')->nullable()->constrained('users');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('triggered_at');
            $table->timestamps();
            
            $table->index(['severity', 'is_read']);
            $table->index('triggered_by_module');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_alerts');
    }
};
```

### 5.3 Migration : `ai_calculation_logs`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_calculation_logs', function (Blueprint $table) {
            $table->id();
            $table->string('module'); // Module qui a effectué le calcul
            $table->string('calculation_type'); // Type de calcul
            $table->json('input_data'); // Données d'entrée
            $table->json('output_data'); // Résultat
            $table->decimal('calculation_time', 8, 3); // Temps d'exécution (secondes)
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index(['module', 'calculated_at']);
            $table->index('success');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_calculation_logs');
    }
};
```

### 5.4 Migration : `ai_metrics`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // product_score, creator_score, churn_risk, etc.
            $table->string('entity_type'); // product, creator, user, order
            $table->unsignedBigInteger('entity_id');
            $table->decimal('value', 10, 2); // Valeur de la métrique
            $table->json('metadata')->nullable(); // Données additionnelles
            $table->date('calculated_for_date'); // Date de référence
            $table->timestamps();
            
            $table->index(['entity_type', 'entity_id', 'calculated_for_date']);
            $table->index(['metric_type', 'calculated_for_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_metrics');
    }
};
```

---

## 6. SERVICES D'IA DÉCISIONNELLE

### 6.1 Service de base abstrait

```php
<?php

namespace App\Services\Decision;

use App\Models\AICalculationLog;
use Illuminate\Support\Facades\Cache;

abstract class BaseDecisionService
{
    /**
     * Nom du module (à définir dans chaque service)
     */
    abstract protected function getModuleName(): string;

    /**
     * Vérifie si le module est activé
     */
    protected function isEnabled(): bool
    {
        $moduleName = $this->getModuleName();
        return config('ai_decisional.enabled') && 
               config("ai_decisional.modules.{$moduleName}", false);
    }

    /**
     * Exécute un calcul avec logging automatique
     */
    protected function executeCalculation(
        string $calculationType,
        array $inputData,
        callable $calculation
    ): mixed {
        if (!$this->isEnabled()) {
            return null;
        }

        $startTime = microtime(true);
        $success = true;
        $errorMessage = null;
        $outputData = null;

        try {
            $outputData = $calculation($inputData);
        } catch (\Exception $e) {
            $success = false;
            $errorMessage = $e->getMessage();
            \Log::error("AI Calculation Error [{$this->getModuleName()}]", [
                'type' => $calculationType,
                'error' => $errorMessage,
            ]);
        }

        $calculationTime = microtime(true) - $startTime;

        // Log du calcul
        if (config('ai_decisional.logging.log_calculations')) {
            AICalculationLog::create([
                'module' => $this->getModuleName(),
                'calculation_type' => $calculationType,
                'input_data' => $inputData,
                'output_data' => $outputData,
                'calculation_time' => $calculationTime,
                'success' => $success,
                'error_message' => $errorMessage,
                'calculated_at' => now(),
            ]);
        }

        return $outputData;
    }

    /**
     * Cache un résultat de calcul
     */
    protected function cacheResult(string $key, mixed $value, ?int $ttl = null): void
    {
        if (!config('ai_decisional.performance.cache_enabled')) {
            return;
        }

        $ttl = $ttl ?? config('ai_decisional.performance.cache_ttl');
        Cache::put("ai_decisional:{$key}", $value, $ttl);
    }

    /**
     * Récupère un résultat du cache
     */
    protected function getCachedResult(string $key): mixed
    {
        if (!config('ai_decisional.performance.cache_enabled')) {
            return null;
        }

        return Cache::get("ai_decisional:{$key}");
    }
}
```

---

### 6.2 Exemple : ProductPerformanceAnalyzer

```php
<?php

namespace App\Services\Decision;

use App\Models\Product;
use App\Models\AIMetric;
use App\Models\AIRecommendation;
use Illuminate\Support\Collection;

class ProductPerformanceAnalyzer extends BaseDecisionService
{
    protected function getModuleName(): string
    {
        return 'product_performance';
    }

    /**
     * Analyse la performance de tous les produits
     */
    public function analyzeAllProducts(): Collection
    {
        return $this->executeCalculation(
            'analyze_all_products',
            ['timestamp' => now()],
            function () {
                $products = Product::with(['orderItems', 'category'])
                    ->where('is_active', true)
                    ->get();

                return $products->map(function ($product) {
                    return $this->analyzeProduct($product);
                });
            }
        );
    }

    /**
     * Analyse la performance d'un produit spécifique
     */
    public function analyzeProduct(Product $product): array
    {
        $cacheKey = "product_performance:{$product->id}:" . now()->format('Y-m-d');
        
        if ($cached = $this->getCachedResult($cacheKey)) {
            return $cached;
        }

        $score = $this->calculatePerformanceScore($product);
        $status = $this->determineStatus($score);
        $recommendations = $this->generateRecommendations($product, $score, $status);

        // Enregistrer la métrique
        AIMetric::create([
            'metric_type' => 'product_performance_score',
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'value' => $score,
            'metadata' => [
                'status' => $status,
                'category' => $product->category->name ?? null,
            ],
            'calculated_for_date' => now()->toDateString(),
        ]);

        $result = [
            'product_id' => $product->id,
            'score' => $score,
            'status' => $status,
            'recommendations' => $recommendations,
        ];

        $this->cacheResult($cacheKey, $result);

        return $result;
    }

    /**
     * Calcule le score de performance (0-100)
     */
    private function calculatePerformanceScore(Product $product): float
    {
        return $this->executeCalculation(
            'calculate_performance_score',
            ['product_id' => $product->id],
            function () use ($product) {
                $rotationRate = $this->calculateRotationRate($product);
                $conversionRate = $this->calculateConversionRate($product);
                $marginRate = $this->calculateMarginRate($product);
                $stockHealth = $this->calculateStockHealth($product);

                // Pondération selon la cartographie
                $score = (
                    ($rotationRate * 0.4) +
                    ($conversionRate * 0.3) +
                    ($marginRate * 0.2) +
                    ($stockHealth * 0.1)
                ) * 100;

                return round($score, 2);
            }
        );
    }

    /**
     * Génère des recommandations basées sur le score
     */
    private function generateRecommendations(Product $product, float $score, string $status): array
    {
        $recommendations = [];

        if ($score < config('ai_decisional.thresholds.product_performance_min_score')) {
            AIRecommendation::create([
                'type' => 'recommendation',
                'priority' => 'high',
                'category' => 'product',
                'title' => 'Produit sous-performant détecté',
                'description' => "Le produit #{$product->id} a un score de {$score}/100",
                'suggested_action' => 'Analyser les causes : prix, images, description, ou retirer temporairement',
                'data' => [
                    'product_id' => $product->id,
                    'score' => $score,
                    'product_name' => $product->name,
                ],
                'created_by_module' => $this->getModuleName(),
            ]);

            $recommendations[] = 'Analyser ou retirer ce produit';
        }

        if ($score > 80) {
            AIRecommendation::create([
                'type' => 'recommendation',
                'priority' => 'medium',
                'category' => 'product',
                'title' => 'Produit à fort potentiel',
                'description' => "Le produit #{$product->id} performe très bien ({$score}/100)",
                'suggested_action' => 'Mettre en avant sur la page d\'accueil ou dans les promotions',
                'data' => [
                    'product_id' => $product->id,
                    'score' => $score,
                    'product_name' => $product->name,
                ],
                'created_by_module' => $this->getModuleName(),
            ]);

            $recommendations[] = 'Mettre en avant ce produit';
        }

        return $recommendations;
    }

    // ... Méthodes de calcul privées (rotation, conversion, marge, stock)
}
```

---

## 7. JOBS ASYNCHRONES

### 7.1 Job : Analyse de performance produits

```php
<?php

namespace App\Jobs\AI;

use App\Services\Decision\ProductPerformanceAnalyzer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeProductPerformanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes

    public function __construct()
    {
        $this->onQueue(config('ai_decisional.performance.queue'));
    }

    public function handle(ProductPerformanceAnalyzer $analyzer): void
    {
        \Log::info('[AI] Starting product performance analysis');

        try {
            $results = $analyzer->analyzeAllProducts();
            
            \Log::info('[AI] Product performance analysis completed', [
                'products_analyzed' => $results->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('[AI] Product performance analysis failed', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
}
```

---

## 8. PLANIFICATION (SCHEDULER)

### `app/Console/Kernel.php`

```php
protected function schedule(Schedule $schedule): void
{
    // IA Décisionnelle - Jobs planifiés
    if (config('ai_decisional.enabled')) {
        
        // Analyse de performance produits (quotidien à 2h)
        if (config('ai_decisional.modules.product_performance')) {
            $schedule->job(new \App\Jobs\AI\AnalyzeProductPerformanceJob)
                ->dailyAt('02:00')
                ->name('ai-product-performance')
                ->withoutOverlapping();
        }

        // Prédiction de ruptures de stock (quotidien à 3h)
        if (config('ai_decisional.modules.stock_prediction')) {
            $schedule->job(new \App\Jobs\AI\PredictStockRupturesJob)
                ->dailyAt('03:00')
                ->name('ai-stock-prediction')
                ->withoutOverlapping();
        }

        // Détection d'anomalies (toutes les heures)
        if (config('ai_decisional.modules.anomaly_detection')) {
            $schedule->job(new \App\Jobs\AI\DetectAnomaliesJob)
                ->hourly()
                ->name('ai-anomaly-detection')
                ->withoutOverlapping();
        }

        // Prédiction de churn (hebdomadaire, lundi à 1h)
        if (config('ai_decisional.modules.churn_prediction')) {
            $schedule->job(new \App\Jobs\AI\PredictChurnJob)
                ->weeklyOn(1, '01:00')
                ->name('ai-churn-prediction')
                ->withoutOverlapping();
        }
    }
}
```

---

## 9. DASHBOARD ADMIN (INTERNE UNIQUEMENT)

### Route

```php
// routes/web.php (section admin)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('/dashboard', [AIDecisionalController::class, 'dashboard'])->name('dashboard');
        Route::get('/recommendations', [AIDecisionalController::class, 'recommendations'])->name('recommendations');
        Route::get('/alerts', [AIDecisionalController::class, 'alerts'])->name('alerts');
        Route::post('/recommendations/{id}/review', [AIDecisionalController::class, 'reviewRecommendation'])->name('recommendations.review');
    });
});
```

### Controller

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AIRecommendation;
use App\Models\AIAlert;
use App\Models\AIMetric;
use Illuminate\Http\Request;

class AIDecisionalController extends Controller
{
    /**
     * Dashboard IA Décisionnelle (INTERNE UNIQUEMENT)
     */
    public function dashboard()
    {
        $activeRecommendations = AIRecommendation::where('status', 'pending')
            ->orderBy('priority')
            ->orderBy('created_at', 'desc')
            ->get();

        $unreadAlerts = AIAlert::where('is_read', false)
            ->orderBy('severity')
            ->orderBy('triggered_at', 'desc')
            ->get();

        $recentMetrics = AIMetric::where('calculated_for_date', '>=', now()->subDays(7))
            ->orderBy('calculated_for_date', 'desc')
            ->get()
            ->groupBy('metric_type');

        return view('admin.ai.dashboard', compact(
            'activeRecommendations',
            'unreadAlerts',
            'recentMetrics'
        ));
    }

    /**
     * Réviser une recommandation (accepter/rejeter)
     */
    public function reviewRecommendation(Request $request, int $id)
    {
        $recommendation = AIRecommendation::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:accepted,rejected',
        ]);

        $recommendation->update([
            'status' => $validated['status'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Recommandation mise à jour');
    }
}
```

---

## 10. TESTS

### Test unitaire : ProductPerformanceAnalyzer

```php
<?php

namespace Tests\Unit\Services\Decision;

use Tests\TestCase;
use App\Services\Decision\ProductPerformanceAnalyzer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductPerformanceAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    public function test_module_can_be_disabled()
    {
        config(['ai_decisional.modules.product_performance' => false]);

        $analyzer = new ProductPerformanceAnalyzer();
        $result = $analyzer->analyzeAllProducts();

        $this->assertNull($result);
    }

    public function test_calculates_performance_score()
    {
        $product = Product::factory()->create();

        $analyzer = new ProductPerformanceAnalyzer();
        $result = $analyzer->analyzeProduct($product);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_generates_recommendations_for_low_score()
    {
        config(['ai_decisional.thresholds.product_performance_min_score' => 40]);

        // Créer un produit avec faible performance
        $product = Product::factory()->create([
            'stock_quantity' => 0,
            'price' => 1000000, // Prix très élevé
        ]);

        $analyzer = new ProductPerformanceAnalyzer();
        $result = $analyzer->analyzeProduct($product);

        $this->assertLessThan(40, $result['score']);
        $this->assertNotEmpty($result['recommendations']);
    }
}
```

---

## 11. MONITORING ET OBSERVABILITÉ

### Métriques à suivre

| Métrique | Description | Alerte si |
|----------|-------------|-----------|
| **Temps d'exécution** | Durée des calculs | > 5 minutes |
| **Taux d'erreur** | % de calculs échoués | > 5% |
| **Recommandations générées** | Nombre par jour | < 5 ou > 100 |
| **Alertes critiques** | Nombre par jour | > 10 |
| **Taux d'acceptation** | % recommandations acceptées | < 30% |

### Logs structurés

```php
\Log::info('[AI] Module execution', [
    'module' => 'ProductPerformanceAnalyzer',
    'execution_time' => 45.2,
    'products_analyzed' => 150,
    'recommendations_generated' => 12,
    'alerts_triggered' => 3,
]);
```

---

## 12. DÉPLOIEMENT

### Checklist

- [ ] Migrations exécutées
- [ ] Configuration `.env` définie
- [ ] Modules activés selon priorité
- [ ] Seuils ajustés pour l'environnement
- [ ] Queue `ai-processing` configurée
- [ ] Scheduler activé
- [ ] Monitoring configuré
- [ ] Tests passés
- [ ] Documentation à jour

---

**Document technique — Architecture modulaire et désactivable**  
**Équipe Technique RACINE BY GANDA**
