# CARTOGRAPHIE OFFICIELLE — IA DÉCISIONNELLE

**Statut** : `INTERNE` · `INVISIBLE` · `NON INTERACTIVE` · `PRODUCTION-GRADE`

---

## 1. RÔLE FONDAMENTAL (À GRAVER DANS LE MARBRE)

### Principe absolu

**L'IA décisionnelle n'agit jamais directement.**

Elle :
- **Observe** les données
- **Calcule** des insights
- **Recommande** des actions

Les **humains** ou les **règles métier** exécutent.

> **⚠️ Si cette règle est violée, tu perds le contrôle du système.**

---

## 2. POSITION DANS L'ARCHITECTURE

### ❌ Ce qu'elle N'EST PAS

- Pas de page dédiée
- Pas de chatbot
- Pas d'avatar
- Pas de "dashboard IA"

### ✅ Où elle VIT

Elle vit **derrière** :
- Les services métier
- Les jobs
- Les alertes
- Les rapports

> **Elle n'a pas de visage.**

### Architecture visuelle

```
┌─────────────────────────────────────────────────────────────┐
│                    COUCHE PRÉSENTATION                      │
│                  (Ce que voient les humains)                │
│                                                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  Dashboard   │  │   Alertes    │  │   Rapports   │     │
│  │    Admin     │  │              │  │              │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└────────────────────────────┬────────────────────────────────┘
                             │
                             │ Recommandations / Alertes
                             │
┌────────────────────────────▼────────────────────────────────┐
│                  COUCHE IA DÉCISIONNELLE                    │
│                        (Invisible)                          │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  MODULES D'INTELLIGENCE                             │   │
│  │                                                     │   │
│  │  • ProductPerformanceAnalyzer                       │   │
│  │  • CreatorMonitoringService                         │   │
│  │  • StockPredictionEngine                            │   │
│  │  • AnomalyDetectionService                          │   │
│  │  • ConversionOptimizationAnalyzer                   │   │
│  └─────────────────────────────────────────────────────┘   │
└────────────────────────────┬────────────────────────────────┘
                             │
                             │ Lecture seule
                             │
┌────────────────────────────▼────────────────────────────────┐
│                      COUCHE DONNÉES                         │
│                                                             │
│  Ventes · Produits · Clients · Créateurs · Stock · Logs    │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. INPUTS — CE QU'ELLE A LE DROIT D'OBSERVER

### A. Données commerciales (noyau)

| Donnée | Source | Fréquence |
|--------|--------|-----------|
| **Ventes** | `orders` | Temps réel |
| - Quantité | `order_items.quantity` | - |
| - Fréquence | Agrégation par période | - |
| - Panier moyen | `orders.total_amount` | - |
| **Produits** | `products` | Quotidien |
| - Rotation | Calcul ventes/stock | - |
| - Marge | `products.price - cost` | - |
| - Rupture | `products.stock_quantity` | - |
| **Clients** | `users` | Quotidien |
| - Récurrence | Comptage commandes | - |
| - Panier moyen | Moyenne `total_amount` | - |
| - Abandon | `cart_abandonments` | - |

### B. Données marketplace

| Donnée | Source | Fréquence |
|--------|--------|-----------|
| **Performances créateurs** | `creators` | Quotidien |
| - Délais traitement | `orders.processing_time` | - |
| - Taux de retour | `returns / orders` | - |
| - Taux de litiges | `disputes / orders` | - |
| - Volume ventes | Agrégation | - |

### C. Données opérationnelles

| Donnée | Source | Fréquence |
|--------|--------|-----------|
| **Stock** | `products.stock_quantity` | Temps réel |
| **Logistique** | `shipments` | Quotidien |
| **Délais** | `orders.delivery_time` | Quotidien |
| **Incidents** | `logs`, `exceptions` | Temps réel |

### D. Données comportementales (agrégées uniquement)

| Donnée | Source | Fréquence |
|--------|--------|-----------|
| **Pages vues** | Analytics | Quotidien |
| **Produits consultés** | `product_views` | Quotidien |
| **Abandons de panier** | `cart_abandonments` | Quotidien |

> **⚠️ RÈGLE CRITIQUE**  
> Aucune donnée brute sensible exposée directement.  
> Tout est **agrégé**, **normalisé**, **contextualisé**.

---

## 4. TRAITEMENTS — CE QU'ELLE FAIT RÉELLEMENT

### A. Calculs

#### 1. Scores (0-100)

```php
// Exemple : Score de performance produit
ProductPerformanceScore = (
    (rotation_rate * 0.4) +
    (conversion_rate * 0.3) +
    (margin_rate * 0.2) +
    (stock_health * 0.1)
) * 100
```

#### 2. Tendances

```php
// Exemple : Tendance de ventes
SalesTrend = (
    (sales_current_period - sales_previous_period) / 
    sales_previous_period
) * 100
```

#### 3. Évolutions

```php
// Exemple : Évolution du panier moyen
CartEvolution = [
    'week_1' => avg_cart_week_1,
    'week_2' => avg_cart_week_2,
    'week_3' => avg_cart_week_3,
    'week_4' => avg_cart_week_4,
    'trend' => 'increasing|stable|decreasing'
]
```

#### 4. Détections d'anomalies

```php
// Exemple : Détection de baisse anormale
if (sales_today < (avg_sales_last_7_days * 0.5)) {
    trigger_alert('ANOMALY_SALES_DROP');
}
```

### B. Comparaisons

| Type | Formule | Usage |
|------|---------|-------|
| **Période N vs N-1** | `(N - N-1) / N-1 * 100` | Tendances |
| **Produit vs moyenne** | `product_metric / category_avg` | Classement |
| **Créateur vs seuils** | `creator_metric >= threshold` | Alertes |

### C. Classements INTERNES

#### Produits à surveiller

```php
[
    'low_stock' => [
        // Produits avec stock < seuil
        ['id' => 123, 'stock' => 2, 'avg_daily_sales' => 5],
    ],
    'low_performance' => [
        // Produits avec score < 40
        ['id' => 456, 'score' => 35, 'reason' => 'low_conversion'],
    ],
    'high_potential' => [
        // Produits avec forte croissance
        ['id' => 789, 'growth' => 150, 'trend' => 'increasing'],
    ],
]
```

#### Créateurs à encadrer

```php
[
    'slow_processing' => [
        // Créateurs avec délais > seuil
        ['id' => 10, 'avg_processing_time' => 5.2, 'threshold' => 3],
    ],
    'high_return_rate' => [
        // Créateurs avec taux retour > 15%
        ['id' => 15, 'return_rate' => 18, 'threshold' => 15],
    ],
]
```

#### Commandes à risque

```php
[
    'payment_pending_long' => [
        // Paiement en attente > 24h
        ['order_id' => 5001, 'pending_hours' => 36],
    ],
    'delivery_delayed' => [
        // Livraison en retard
        ['order_id' => 5002, 'delay_days' => 3],
    ],
]
```

#### Stocks critiques

```php
[
    'rupture_imminent' => [
        // Rupture prévue sous 7 jours
        ['product_id' => 123, 'days_remaining' => 4],
    ],
]
```

> **👉 RÈGLE ABSOLUE : Jamais de classement public.**

---

## 5. OUTPUTS — CE QU'ELLE A LE DROIT DE PRODUIRE

### A. Recommandations INTERNES

#### Format standardisé

```php
[
    'type' => 'recommendation',
    'priority' => 'high|medium|low',
    'category' => 'product|creator|stock|sales',
    'title' => 'Titre court',
    'description' => 'Description factuelle',
    'suggested_action' => 'Action concrète',
    'data' => [...], // Données de support
    'created_at' => '2026-01-04 10:30:00',
]
```

#### Exemples concrets

**1. Produit à prioriser**
```php
[
    'type' => 'recommendation',
    'priority' => 'high',
    'category' => 'product',
    'title' => 'Prioriser ce produit',
    'description' => 'Le produit #789 montre une croissance de 150% sur 7 jours',
    'suggested_action' => 'Mettre en avant sur la page d\'accueil',
    'data' => [
        'product_id' => 789,
        'growth_rate' => 150,
        'current_stock' => 45,
        'avg_daily_sales' => 8,
    ],
]
```

**2. Créateur à vérifier**
```php
[
    'type' => 'recommendation',
    'priority' => 'medium',
    'category' => 'creator',
    'title' => 'Vérifier ce créateur',
    'description' => 'Taux de retour de 18% (seuil : 15%)',
    'suggested_action' => 'Contacter le créateur pour comprendre les causes',
    'data' => [
        'creator_id' => 15,
        'return_rate' => 18,
        'threshold' => 15,
        'total_orders' => 120,
        'total_returns' => 22,
    ],
]
```

**3. Risque de rupture**
```php
[
    'type' => 'recommendation',
    'priority' => 'high',
    'category' => 'stock',
    'title' => 'Risque de rupture sous 7 jours',
    'description' => 'Le produit #123 sera en rupture dans 4 jours',
    'suggested_action' => 'Réapprovisionner ou retirer temporairement de la vente',
    'data' => [
        'product_id' => 123,
        'current_stock' => 12,
        'avg_daily_sales' => 3,
        'days_remaining' => 4,
    ],
]
```

**4. Baisse anormale de conversion**
```php
[
    'type' => 'recommendation',
    'priority' => 'high',
    'category' => 'sales',
    'title' => 'Baisse anormale de conversion',
    'description' => 'Conversion page produit : 2.1% (moyenne : 4.5%)',
    'suggested_action' => 'Vérifier les images, descriptions, prix',
    'data' => [
        'current_conversion' => 2.1,
        'avg_conversion' => 4.5,
        'period' => 'last_7_days',
    ],
]
```

### B. Alertes

#### Format standardisé

```php
[
    'type' => 'alert',
    'severity' => 'critical|warning|info',
    'category' => 'threshold|anomaly|performance',
    'title' => 'Titre court',
    'message' => 'Message clair',
    'data' => [...],
    'triggered_at' => '2026-01-04 10:30:00',
]
```

#### Exemples concrets

**1. Seuil dépassé**
```php
[
    'type' => 'alert',
    'severity' => 'warning',
    'category' => 'threshold',
    'title' => 'Délai de traitement élevé',
    'message' => 'Créateur #10 : délai moyen de 5.2 jours (seuil : 3 jours)',
    'data' => [
        'creator_id' => 10,
        'avg_processing_time' => 5.2,
        'threshold' => 3,
    ],
]
```

**2. Anomalie détectée**
```php
[
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
]
```

**3. Performance hors norme**
```php
[
    'type' => 'alert',
    'severity' => 'info',
    'category' => 'performance',
    'title' => 'Performance exceptionnelle',
    'message' => 'Produit #789 : +150% de ventes sur 7 jours',
    'data' => [
        'product_id' => 789,
        'growth_rate' => 150,
    ],
]
```

### C. Indicateurs synthétiques

#### 1. Scores (0–100)

```php
[
    'product_health_score' => 85,      // Santé globale du produit
    'creator_performance_score' => 72, // Performance du créateur
    'stock_health_score' => 60,        // Santé du stock
    'conversion_score' => 78,          // Performance de conversion
]
```

#### 2. États (OK / À SURVEILLER / CRITIQUE)

```php
[
    'stock_status' => 'OK',              // Stock suffisant
    'creator_status' => 'À SURVEILLER',  // Métriques limites
    'sales_status' => 'CRITIQUE',        // Baisse anormale
]
```

> **👉 RÈGLE ABSOLUE : Jamais de décisions exécutées automatiquement sans règle humaine.**

---

## 6. CE QU'ELLE N'A ABSOLUMENT PAS LE DROIT DE FAIRE

### Interdictions critiques

| Action | Statut | Raison |
|--------|--------|--------|
| **Modifier des prix seule** | ❌ INTERDIT | Impact commercial direct |
| **Modifier une mise en avant seule** | ❌ INTERDIT | Décision stratégique |
| **Bloquer un créateur seule** | ❌ INTERDIT | Impact juridique/humain |
| **Déclencher une action client** | ❌ INTERDIT | Expérience utilisateur |
| **Parler à Amira** | ❌ INTERDIT | Séparation des couches |
| **Être mentionnée dans l'UX publique** | ❌ INTERDIT | Invisibilité obligatoire |

> **⚠️ Si elle agit directement → danger stratégique.**

### Exemple de violation

```php
// ❌ INTERDIT - Action automatique
if ($product->performance_score < 40) {
    $product->update(['is_featured' => false]); // DANGER !
}

// ✅ CORRECT - Recommandation
if ($product->performance_score < 40) {
    AIRecommendation::create([
        'type' => 'recommendation',
        'suggested_action' => 'Retirer de la mise en avant',
        'data' => ['product_id' => $product->id, 'score' => $product->performance_score],
    ]);
    // L'humain décide ensuite
}
```

---

## 7. RELATION AVEC LES HUMAINS (TRÈS IMPORTANT)

### Qui voit ses outputs ?

| Rôle | Accès | Niveau de détail |
|------|-------|------------------|
| **Super Admin** | Complet | Tous les modules, tous les détails |
| **Admin** | Étendu | Recommandations, alertes, rapports |
| **Managers autorisés** | Limité | Leur périmètre uniquement |

### Sous quelle forme ?

#### 1. Tableaux synthétiques

```
┌─────────────────────────────────────────────────┐
│  RECOMMANDATIONS ACTIVES (5)                    │
├─────────────────────────────────────────────────┤
│  🔴 HAUTE   Risque rupture produit #123         │
│  🟠 MOYENNE Vérifier créateur #15               │
│  🟠 MOYENNE Prioriser produit #789              │
│  🟢 BASSE   Optimiser page catégorie X          │
│  🟢 BASSE   Analyser abandon panier             │
└─────────────────────────────────────────────────┘
```

#### 2. Alertes sobres

```
⚠️ ALERTE : Chute des ventes détectée
Ventes aujourd'hui : 15 (moyenne : 45)
Action suggérée : Vérifier le site et les campagnes
```

#### 3. Rapports périodiques

```
📊 RAPPORT HEBDOMADAIRE IA DÉCISIONNELLE

Période : 30 déc 2025 - 5 jan 2026

PRODUITS
• 3 produits à fort potentiel identifiés
• 2 risques de rupture détectés
• 1 produit sous-performant

CRÉATEURS
• 1 créateur à surveiller (délais)
• 2 créateurs performants

STOCK
• Santé globale : 75/100
• 5 réapprovisionnements suggérés
```

### ❌ Ce qu'elle ne fait PAS

- Pas de "conseils bavards"
- Pas de storytelling
- Pas d'explications longues
- Pas de jargon technique

> **👉 L'IA suggère, l'humain décide.**

---

## 8. RELATION AVEC AMIRA (ZÉRO CONTACT)

### Règle absolue

**Amira ignore l'existence de l'IA décisionnelle.**

### Formulations interdites

| ❌ INTERDIT | ✅ CORRECT |
|-------------|------------|
| "Le système a détecté que ce produit vous plaira" | "Vous pourriez aimer ce produit" |
| "L'IA recommande ces articles" | "Ces articles pourraient vous intéresser" |
| "Notre algorithme a analysé vos préférences" | "Basé sur vos achats précédents" |
| "Optimisé par notre intelligence artificielle" | "Sélection personnalisée" |

### Si une logique influence le front

```php
// ❌ INTERDIT - Mention de l'IA
$message = "Notre algorithme a détecté que vous aimez ce style";

// ✅ CORRECT - Wording neutre et humain
$message = "Basé sur vos achats récents";
```

### Flux correct

```
IA Décisionnelle
    ↓ (calcul interne)
Recommandation produit
    ↓ (règle métier)
Affichage front
    ↓ (wording neutre)
"Vous pourriez aimer"
```

---

## 9. GOUVERNANCE & CONTRÔLE

### Règles obligatoires

#### 1. Logs de calculs

```php
// Chaque calcul doit être tracé
AICalculationLog::create([
    'module' => 'ProductPerformanceAnalyzer',
    'input_data' => [...],
    'output_data' => [...],
    'calculation_time' => 0.45, // secondes
    'timestamp' => now(),
]);
```

#### 2. Traçabilité des recommandations

```php
// Chaque recommandation doit être traçable
AIRecommendation::create([
    'type' => 'recommendation',
    'category' => 'product',
    'data' => [...],
    'created_by_module' => 'ProductPerformanceAnalyzer',
    'status' => 'pending', // pending|accepted|rejected|executed
    'reviewed_by' => null, // user_id
    'reviewed_at' => null,
]);
```

#### 3. Possibilité de désactiver chaque module IA

```php
// config/ai_decisional.php
return [
    'modules' => [
        'product_performance' => env('AI_MODULE_PRODUCT_PERFORMANCE', true),
        'creator_monitoring' => env('AI_MODULE_CREATOR_MONITORING', true),
        'stock_prediction' => env('AI_MODULE_STOCK_PREDICTION', true),
        'anomaly_detection' => env('AI_MODULE_ANOMALY_DETECTION', true),
        'conversion_optimization' => env('AI_MODULE_CONVERSION_OPT', true),
    ],
];
```

#### 4. Seuils ajustables manuellement

```php
// config/ai_decisional.php
return [
    'thresholds' => [
        'stock_critical_days' => env('AI_STOCK_CRITICAL_DAYS', 7),
        'creator_processing_time_max' => env('AI_CREATOR_PROCESSING_MAX', 3),
        'creator_return_rate_max' => env('AI_CREATOR_RETURN_RATE_MAX', 15),
        'sales_anomaly_drop_percent' => env('AI_SALES_ANOMALY_DROP', 50),
        'product_performance_min_score' => env('AI_PRODUCT_MIN_SCORE', 40),
    ],
];
```

> **👉 Une IA qu'on ne peut pas éteindre est une bombe.**

---

## 10. TEST DE MATURITÉ (IMPITOYABLE)

### Question critique

> **Si l'IA décisionnelle est coupée demain, le site peut-il continuer à vendre ?**

### Réponse attendue : OUI

| Fonctionnalité | Sans IA | Avec IA |
|----------------|---------|---------|
| **Vente de produits** | ✅ Fonctionne | ✅ Optimisée |
| **Gestion commandes** | ✅ Fonctionne | ✅ Alertes proactives |
| **Gestion stock** | ✅ Fonctionne | ✅ Prédictions |
| **Suivi créateurs** | ✅ Fonctionne | ✅ Monitoring automatique |
| **Support client** | ✅ Fonctionne | ✅ Détection anomalies |

### Verdict

- ✅ **OUI** → Architecture saine (IA = optimisation, pas dépendance)
- ❌ **NON** → Dépendance toxique (refonte nécessaire)

---

## VERDICT FINAL (RADICALEMENT HONNÊTE)

### L'IA décisionnelle doit être :

| Pour qui | Comment | Pourquoi |
|----------|---------|----------|
| **Client** | Ennuyeuse (invisible) | Ne doit pas savoir qu'elle existe |
| **Admin** | Passionnante (utile) | Insights actionnables |
| **Reste du monde** | Invisible (cachée) | Avantage concurrentiel |

> **C'est exactement ce qui distingue un produit sérieux d'un jouet technologique.**

---

**Document figé — Intelligence invisible, puissance réelle**  
**Toute modification nécessite validation formelle**
