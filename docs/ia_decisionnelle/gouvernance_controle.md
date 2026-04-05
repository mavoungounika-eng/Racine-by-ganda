# GOUVERNANCE & CONTRÔLE — IA DÉCISIONNELLE

**Référence** : [Cartographie](./cartographie_ia_decisionnelle.md) | [Architecture](./architecture_technique.md)  
**Statut** : `PRODUCTION-GRADE`

---

## 1. PRINCIPE FONDAMENTAL

> **Une IA qu'on ne peut pas éteindre est une bombe.**

Tout module d'IA décisionnelle doit pouvoir être :
- ✅ **Désactivé** instantanément
- ✅ **Tracé** dans ses calculs
- ✅ **Audité** dans ses recommandations
- ✅ **Ajusté** dans ses seuils

---

## 2. DÉSACTIVATION DES MODULES

### 2.1 Désactivation globale

```bash
# .env
AI_DECISIONAL_ENABLED=false
```

**Effet** : Tous les modules d'IA sont désactivés, le site continue de fonctionner normalement.

### 2.2 Désactivation par module

```bash
# .env
AI_MODULE_PRODUCT_PERFORMANCE=false
AI_MODULE_STOCK_PREDICTION=false
AI_MODULE_ANOMALY_DETECTION=false
AI_MODULE_CHURN_PREDICTION=false
AI_MODULE_CREATOR_SCORING=false
AI_MODULE_RECOMMENDATION=false
AI_MODULE_CONVERSION_OPT=false
```

**Effet** : Seul le module désactivé s'arrête, les autres continuent.

### 2.3 Vérification dans le code

Chaque service hérite de `BaseDecisionService` qui vérifie automatiquement :

```php
protected function isEnabled(): bool
{
    $moduleName = $this->getModuleName();
    return config('ai_decisional.enabled') && 
           config("ai_decisional.modules.{$moduleName}", false);
}
```

Si désactivé, le service retourne `null` sans erreur.

---

## 3. TRAÇABILITÉ DES CALCULS

### 3.1 Logging automatique

Chaque calcul est automatiquement enregistré dans `ai_calculation_logs` :

| Champ | Description |
|-------|-------------|
| `module` | Nom du module (ex: `ProductPerformanceAnalyzer`) |
| `calculation_type` | Type de calcul (ex: `calculate_performance_score`) |
| `input_data` | Données d'entrée (JSON) |
| `output_data` | Résultat (JSON) |
| `calculation_time` | Temps d'exécution (secondes) |
| `success` | Succès ou échec |
| `error_message` | Message d'erreur si échec |
| `calculated_at` | Timestamp |

### 3.2 Exemple de log

```json
{
  "id": 1523,
  "module": "ProductPerformanceAnalyzer",
  "calculation_type": "calculate_performance_score",
  "input_data": {
    "product_id": 123
  },
  "output_data": {
    "score": 72.5,
    "rotation_rate": 0.8,
    "conversion_rate": 0.04,
    "margin_rate": 0.35,
    "stock_health": 0.9
  },
  "calculation_time": 0.234,
  "success": true,
  "error_message": null,
  "calculated_at": "2026-01-04 02:15:30"
}
```

### 3.3 Consultation des logs

```php
// Logs des dernières 24h pour un module
$logs = AICalculationLog::where('module', 'ProductPerformanceAnalyzer')
    ->where('calculated_at', '>=', now()->subDay())
    ->orderBy('calculated_at', 'desc')
    ->get();

// Logs d'erreurs
$errors = AICalculationLog::where('success', false)
    ->where('calculated_at', '>=', now()->subWeek())
    ->get();
```

### 3.4 Rétention des logs

```bash
# config/ai_decisional.php
'logging' => [
    'retention_days' => 90, // Conservation 90 jours
],
```

Nettoyage automatique via command :

```php
// app/Console/Commands/AI/CleanOldLogsCommand.php
php artisan ai:clean-logs
```

---

## 4. TRAÇABILITÉ DES RECOMMANDATIONS

### 4.1 Cycle de vie d'une recommandation

```
┌─────────────────────────────────────────────────┐
│  1. CRÉATION (par module IA)                    │
│     status: pending                             │
│     reviewed_by: null                           │
└────────────────────┬────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────┐
│  2. RÉVISION (par humain)                       │
│     status: accepted | rejected                 │
│     reviewed_by: user_id                        │
│     reviewed_at: timestamp                      │
└────────────────────┬────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────┐
│  3. EXÉCUTION (si acceptée)                     │
│     status: executed                            │
└─────────────────────────────────────────────────┘
```

### 4.2 Audit trail complet

Chaque recommandation contient :

```php
[
    'id' => 42,
    'type' => 'recommendation',
    'priority' => 'high',
    'category' => 'product',
    'title' => 'Prioriser ce produit',
    'description' => 'Le produit #789 montre une croissance de 150%',
    'suggested_action' => 'Mettre en avant sur la page d\'accueil',
    'data' => [
        'product_id' => 789,
        'growth_rate' => 150,
        'current_stock' => 45,
    ],
    'created_by_module' => 'ProductPerformanceAnalyzer',
    'status' => 'accepted',
    'reviewed_by' => 1, // user_id de l'admin
    'reviewed_at' => '2026-01-04 10:30:00',
    'created_at' => '2026-01-04 02:15:00',
]
```

### 4.3 Rapports d'audit

```php
// Taux d'acceptation par module
$acceptanceRate = AIRecommendation::selectRaw('
        created_by_module,
        COUNT(*) as total,
        SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) as accepted,
        ROUND(SUM(CASE WHEN status = "accepted" THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as acceptance_rate
    ')
    ->groupBy('created_by_module')
    ->get();

// Recommandations non révisées depuis > 7 jours
$staleRecommendations = AIRecommendation::where('status', 'pending')
    ->where('created_at', '<', now()->subDays(7))
    ->get();
```

---

## 5. AJUSTEMENT DES SEUILS

### 5.1 Seuils configurables

Tous les seuils sont définis dans `.env` et peuvent être ajustés sans modification de code :

```bash
# Stock
AI_STOCK_CRITICAL_DAYS=7        # Alerte si rupture < 7 jours
AI_STOCK_WARNING_DAYS=14        # Avertissement si < 14 jours

# Créateurs
AI_CREATOR_PROCESSING_MAX=3     # Délai max traitement (jours)
AI_CREATOR_RETURN_RATE_MAX=15   # Taux retour max (%)
AI_CREATOR_DISPUTE_RATE_MAX=5   # Taux litiges max (%)

# Produits
AI_PRODUCT_MIN_SCORE=40         # Score min acceptable
AI_PRODUCT_ROTATION_MIN=0.5     # Rotation min acceptable

# Ventes
AI_SALES_ANOMALY_DROP=50        # % de baisse considéré comme anomalie
AI_CONVERSION_RATE_MIN=2        # Taux conversion min (%)

# Churn
AI_CHURN_RISK_THRESHOLD=0.7     # Seuil de risque de churn
```

### 5.2 Impact immédiat

Les seuils sont lus à chaque exécution :

```php
$minScore = config('ai_decisional.thresholds.product_performance_min_score');

if ($score < $minScore) {
    // Génération d'alerte
}
```

**Modification** → **Effet immédiat** au prochain calcul.

### 5.3 Historique des ajustements

Recommandé : logger les changements de seuils

```php
// app/Models/AIThresholdChange.php
AIThresholdChange::create([
    'threshold_name' => 'product_performance_min_score',
    'old_value' => 40,
    'new_value' => 35,
    'changed_by' => auth()->id(),
    'reason' => 'Ajustement après analyse des performances',
    'changed_at' => now(),
]);
```

---

## 6. ALERTES ET NOTIFICATIONS

### 6.1 Configuration des alertes

```bash
# .env
AI_ALERTS_ENABLED=true
AI_ALERT_CRITICAL_EMAILS="admin@racinebyganda.com,cto@racinebyganda.com"
AI_ALERT_WARNING_EMAILS="manager@racinebyganda.com"
```

### 6.2 Niveaux de sévérité

| Sévérité | Déclenchement | Notification |
|----------|---------------|--------------|
| **CRITICAL** | Anomalie majeure, chute > 50% | Email immédiat + Dashboard |
| **WARNING** | Seuil dépassé | Dashboard + Email quotidien |
| **INFO** | Performance exceptionnelle | Dashboard uniquement |

### 6.3 Exemple d'alerte critique

```php
AIAlert::create([
    'type' => 'alert',
    'severity' => 'critical',
    'category' => 'anomaly',
    'title' => 'Chute des ventes détectée',
    'message' => 'Ventes aujourd\'hui : 15 (moyenne 7j : 45)',
    'data' => [
        'sales_today' => 15,
        'avg_sales_7d' => 45,
        'drop_percentage' => -67,
    ],
    'triggered_by_module' => 'AnomalyDetectionService',
    'triggered_at' => now(),
]);

// Notification immédiate
Mail::to(config('ai_decisional.alerts.recipients.critical'))
    ->send(new CriticalAIAlert($alert));
```

---

## 7. CONTRÔLE D'ACCÈS

### 7.1 Qui peut voir quoi ?

| Rôle | Dashboard | Recommandations | Alertes | Logs | Config |
|------|-----------|-----------------|---------|------|--------|
| **Super Admin** | ✅ | ✅ | ✅ | ✅ | ✅ |
| **Admin** | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Manager** | ✅ (limité) | ✅ (son périmètre) | ✅ | ❌ | ❌ |
| **Créateur** | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Client** | ❌ | ❌ | ❌ | ❌ | ❌ |

### 7.2 Middleware de protection

```php
// routes/web.php
Route::middleware(['auth', 'role:super_admin,admin'])->prefix('admin/ai')->group(function () {
    Route::get('/dashboard', [AIDecisionalController::class, 'dashboard']);
    Route::get('/recommendations', [AIDecisionalController::class, 'recommendations']);
    Route::get('/alerts', [AIDecisionalController::class, 'alerts']);
});

// Logs et config : super admin uniquement
Route::middleware(['auth', 'role:super_admin'])->prefix('admin/ai')->group(function () {
    Route::get('/logs', [AIDecisionalController::class, 'logs']);
    Route::get('/config', [AIDecisionalController::class, 'config']);
    Route::post('/config', [AIDecisionalController::class, 'updateConfig']);
});
```

---

## 8. MONITORING DE L'IA ELLE-MÊME

### 8.1 Métriques de santé

| Métrique | Objectif | Alerte si |
|----------|----------|-----------|
| **Temps d'exécution moyen** | < 2s | > 5s |
| **Taux d'erreur** | < 1% | > 5% |
| **Recommandations/jour** | 10-50 | < 5 ou > 100 |
| **Alertes critiques/jour** | 0-3 | > 10 |
| **Taux d'acceptation recommandations** | > 50% | < 30% |
| **Utilisation cache** | > 80% | < 50% |

### 8.2 Dashboard de santé de l'IA

```php
// app/Http/Controllers/Admin/AIHealthController.php
public function health()
{
    $last24h = now()->subDay();

    $metrics = [
        'calculations' => [
            'total' => AICalculationLog::where('calculated_at', '>=', $last24h)->count(),
            'errors' => AICalculationLog::where('calculated_at', '>=', $last24h)
                ->where('success', false)->count(),
            'avg_time' => AICalculationLog::where('calculated_at', '>=', $last24h)
                ->avg('calculation_time'),
        ],
        'recommendations' => [
            'generated' => AIRecommendation::where('created_at', '>=', $last24h)->count(),
            'accepted' => AIRecommendation::where('reviewed_at', '>=', $last24h)
                ->where('status', 'accepted')->count(),
            'pending' => AIRecommendation::where('status', 'pending')->count(),
        ],
        'alerts' => [
            'critical' => AIAlert::where('triggered_at', '>=', $last24h)
                ->where('severity', 'critical')->count(),
            'warning' => AIAlert::where('triggered_at', '>=', $last24h)
                ->where('severity', 'warning')->count(),
            'unread' => AIAlert::where('is_read', false)->count(),
        ],
    ];

    return view('admin.ai.health', compact('metrics'));
}
```

---

## 9. PROCÉDURES D'URGENCE

### 9.1 Désactivation d'urgence

```bash
# Désactiver TOUTE l'IA immédiatement
php artisan config:set AI_DECISIONAL_ENABLED false
php artisan cache:clear
php artisan config:cache
```

### 9.2 Désactivation d'un module défaillant

```bash
# Exemple : module de prédiction de stock défaillant
php artisan config:set AI_MODULE_STOCK_PREDICTION false
php artisan cache:clear
```

### 9.3 Rollback d'une recommandation

```php
// Marquer une recommandation comme rejetée après exécution
$recommendation = AIRecommendation::find($id);
$recommendation->update([
    'status' => 'rejected',
    'reviewed_by' => auth()->id(),
    'reviewed_at' => now(),
]);

// Logger l'incident
\Log::critical('[AI] Recommendation rollback', [
    'recommendation_id' => $id,
    'reason' => 'Impact négatif détecté',
]);
```

---

## 10. AUDIT PÉRIODIQUE

### 10.1 Checklist mensuelle

- [ ] Vérifier le taux d'erreur des calculs (< 5%)
- [ ] Analyser le taux d'acceptation des recommandations (> 50%)
- [ ] Vérifier les alertes critiques non traitées
- [ ] Analyser les temps d'exécution (< 5s)
- [ ] Vérifier l'utilisation du cache (> 80%)
- [ ] Nettoyer les logs > 90 jours
- [ ] Réviser les seuils si nécessaire
- [ ] Vérifier les recommandations en attente > 7 jours

### 10.2 Rapport mensuel automatique

```php
// app/Console/Commands/AI/GenerateMonthlyReportCommand.php
php artisan ai:monthly-report
```

Génère un rapport PDF avec :
- Nombre de calculs effectués
- Taux de succès/erreur
- Recommandations générées/acceptées/rejetées
- Alertes déclenchées par sévérité
- Temps d'exécution moyen
- Suggestions d'amélioration

---

## 11. CONFORMITÉ ET ÉTHIQUE

### 11.1 Principes éthiques

1. **Transparence** : Toute décision influencée par l'IA doit être traçable
2. **Équité** : Aucun biais discriminatoire dans les algorithmes
3. **Contrôle humain** : Aucune action automatique sans validation
4. **Réversibilité** : Toute recommandation peut être annulée
5. **Explicabilité** : Les calculs doivent être compréhensibles

### 11.2 Audit de biais

```php
// Vérifier l'équité des recommandations par créateur
$creatorRecommendations = AIRecommendation::where('category', 'creator')
    ->selectRaw('
        JSON_EXTRACT(data, "$.creator_id") as creator_id,
        COUNT(*) as total,
        AVG(CASE WHEN priority = "high" THEN 1 ELSE 0 END) as high_priority_rate
    ')
    ->groupBy('creator_id')
    ->having('total', '>', 10)
    ->get();

// Détecter les créateurs sur-représentés dans les alertes
// → Vérifier si biais algorithmique ou problème réel
```

---

## 12. DOCUMENTATION OBLIGATOIRE

### 12.1 Pour chaque nouveau module

Avant de déployer un nouveau module d'IA, documenter :

1. **Objectif** : Quel problème résout-il ?
2. **Inputs** : Quelles données utilise-t-il ?
3. **Algorithme** : Comment calcule-t-il ?
4. **Outputs** : Que produit-il ?
5. **Seuils** : Quels seuils utilise-t-il ?
6. **Impact** : Quel est l'impact si désactivé ?
7. **Tests** : Comment le valider ?

### 12.2 Changelog des modules

```markdown
# CHANGELOG - IA Décisionnelle

## [1.2.0] - 2026-01-15
### Ajouté
- Module `ConversionOptimizationAnalyzer`
- Seuil `AI_CONVERSION_RATE_MIN`

### Modifié
- `ProductPerformanceAnalyzer` : ajout pondération marge
- Seuil `AI_PRODUCT_MIN_SCORE` : 40 → 35

### Corrigé
- Bug calcul rotation pour produits sans ventes
```

---

## 13. FORMATION DES ÉQUIPES

### 13.1 Formation Admin

**Objectif** : Comprendre et utiliser les recommandations

- Lire le dashboard
- Interpréter les recommandations
- Accepter/rejeter avec discernement
- Comprendre les alertes
- Ajuster les seuils si nécessaire

### 13.2 Formation Technique

**Objectif** : Maintenir et étendre l'IA

- Architecture des modules
- Création de nouveaux modules
- Debugging des calculs
- Optimisation des performances
- Gestion des logs

---

## VERDICT FINAL

### Règles d'or de la gouvernance

1. **Désactivable** : Tout module peut être éteint sans casser le site
2. **Tracé** : Chaque calcul est enregistré
3. **Contrôlé** : Aucune action automatique sans validation humaine
4. **Ajustable** : Tous les seuils sont configurables
5. **Auditable** : Rapports mensuels obligatoires

> **Une IA bien gouvernée est une IA utile. Une IA non gouvernée est un risque.**

---

**Document de gouvernance — Contrôle total, risque zéro**  
**Équipe Produit & Technique RACINE BY GANDA**
