# Performance Dashboard — Documentation Officielle

**Version**: 1.0  
**Date**: 2025-12-29  
**Phase**: 2.2 J3 — Visualisation  
**Status**: ✅ Production Ready

---

## 📋 Vue d'Ensemble

Le **Performance Dashboard** est un outil admin-only permettant de visualiser les métriques de performance backend collectées automatiquement par le middleware `RecordPerformanceMetrics`.

### Objectif
Rendre la performance observable et actionnable sans sur-ingénierie.

### Principe
**On ne surveille que ce qu'on est prêt à corriger.**

---

## 🔒 Accès

**URL Base**: `/admin/performance`  
**Middleware**: `admin` + `2fa`  
**Rôle requis**: Admin uniquement

### Routes Disponibles
- `GET /admin/performance` → Dashboard global
- `GET /admin/performance/routes` → Analyse par route
- `GET /admin/performance/alerts` → Alertes critiques

---

## 📊 Métriques Exposées

### 1. Métriques Requêtes / DB
| Métrique | Description | Utilité |
|----------|-------------|---------|
| `query_count` | Nombre total de requêtes SQL | Détecter N+1 & régressions |
| `db_time_ms` | Temps cumulé DB (ms) | Impact réel backend |
| `response_time_ms` | Temps total requête (ms) | Performance perçue |

### 2. Périodes d'Analyse
- **24 heures** : Détection rapide de régressions
- **7 jours** : Tendances et patterns

### 3. Agrégations
- Moyennes (`AVG`)
- Comptages (`COUNT`)
- Groupements par route (`GROUP BY`)

---

## 🎯 Seuils d'Alerte

### 🔴 Critique
- `query_count > 30`
- `response_time_ms > 500`

**Action**: Investigation immédiate requise

### 🟠 Alerte
- `query_count > 20` (mais ≤ 30)
- `response_time_ms ≤ 500`

**Action**: Surveillance renforcée

### 🟢 OK
- `query_count ≤ 15`
- `response_time_ms ≤ 300`

**Action**: Aucune

---

## 📖 Guide d'Utilisation

### Dashboard Global (`/admin/performance`)
**Affiche**:
- Statistiques 24h et 7j
- Top 5 routes les plus lentes
- Moyennes query_count, db_time, response_time

**Utilisation**:
1. Consulter les moyennes 24h pour détecter les régressions récentes
2. Comparer avec les moyennes 7j pour identifier les tendances
3. Examiner le Top 5 pour prioriser les optimisations

### Analyse par Route (`/admin/performance/routes`)
**Affiche**:
- Liste complète des routes avec stats
- Tri par colonne (route, appels, queries, temps)
- Pagination (20 routes/page)

**Utilisation**:
1. Trier par `avg_queries` pour identifier les N+1
2. Trier par `avg_response_time` pour trouver les routes lentes
3. Trier par `hits` pour prioriser les routes fréquentes

### Alertes (`/admin/performance/alerts`)
**Affiche**:
- Routes critiques (🔴)
- Routes en alerte (🟠)

**Utilisation**:
1. Traiter d'abord les alertes critiques
2. Planifier l'optimisation des alertes modérées
3. Documenter les actions correctives

---

## 🧪 Interprétation des Métriques

### Query Count Élevé
**Symptôme**: `query_count > 20`  
**Cause probable**: N+1 queries  
**Solution**: Eager loading (`->with()`, `->load()`)

**Exemple**:
```php
// ❌ N+1
$orders = Order::all();
foreach ($orders as $order) {
    echo $order->user->name; // 1 query par order
}

// ✅ Optimisé
$orders = Order::with('user')->get(); // 2 queries total
```

### Response Time Élevé
**Symptôme**: `response_time_ms > 500`  
**Causes probables**:
- Requêtes SQL lentes
- Calculs complexes
- Appels API externes

**Solutions**:
- Indexer les colonnes fréquemment filtrées
- Cacher les résultats coûteux
- Optimiser les algorithmes

### DB Time Élevé
**Symptôme**: `db_time_ms` proche de `response_time_ms`  
**Cause**: Temps passé en DB domine le temps total  
**Solution**: Optimiser les requêtes SQL (indexes, requêtes agrégées)

---

## ⚠️ Limites Connues

### 1. Debug Mode Only
**Collecte active uniquement si `APP_DEBUG=true`**

**Raison**: Éviter overhead en production  
**Impact**: Pas de métriques en prod (par design)

### 2. Pas de Temps Réel
**Données rafraîchies à chaque requête**

**Raison**: Simplicité, pas de WebSocket  
**Impact**: Rafraîchir manuellement la page

### 3. Rétention Courte
**Recommandation**: 7-14 jours

**Raison**: Table peut grossir rapidement  
**Solution**: Implémenter une commande de purge

### 4. Pas de Métriques Frontend
**Uniquement backend**

**Raison**: Périmètre Phase 2.2  
**Alternative**: Utiliser Google Analytics pour le frontend

---

## 🔧 Maintenance

### Purge des Anciennes Métriques
**Commande recommandée** (à créer):
```bash
php artisan performance:prune --days=14
```

**Implémentation suggérée**:
```php
PerformanceMetric::where('created_at', '<', now()->subDays(14))->delete();
```

### Monitoring de la Table
**Vérifier la taille**:
```sql
SELECT COUNT(*) FROM performance_metrics;
```

**Taille recommandée**: < 100,000 lignes

---

## 📈 Cas d'Usage

### 1. Détection de Régression
**Scénario**: Après un déploiement, les queries moyennes passent de 10 à 25

**Action**:
1. Consulter `/admin/performance`
2. Identifier la route affectée dans le Top 5
3. Comparer avec le code avant déploiement
4. Corriger le N+1 introduit

### 2. Optimisation Proactive
**Scénario**: Une route a 35 queries en moyenne

**Action**:
1. Aller sur `/admin/performance/alerts`
2. Identifier la route critique
3. Auditer le code avec `QueryLogger`
4. Implémenter eager loading
5. Vérifier la réduction dans le dashboard

### 3. Priorisation
**Scénario**: Plusieurs routes lentes, budget temps limité

**Action**:
1. Trier par `hits` sur `/admin/performance/routes`
2. Optimiser d'abord les routes fréquentes
3. Impact maximal pour effort minimal

---

## 🚀 Évolutions Futures (Hors Périmètre Phase 2.2)

### Phase 3 (Hypothétique)
- Graphiques temporels (Chart.js)
- Export CSV des métriques
- Alertes email automatiques
- Comparaison avant/après déploiement
- Métriques par utilisateur/rôle

### Non Prévu
- APM externe (NewRelic, Datadog)
- Temps réel (WebSocket)
- Métriques frontend
- Cache Redis pour dashboard

---

## 📝 Références

### Code Source
- Controller: `app/Http/Controllers/Admin/PerformanceController.php`
- Routes: `routes/web.php` (ligne ~540)
- Views: `resources/views/admin/performance/`
- Tests: `tests/Feature/Admin/PerformanceControllerTest.php`

### Documentation Liée
- `docs/PERFORMANCE_N_PLUS_ONE_AUDIT.md` - Audit Phase 2.1
- `phase_2_2_j1_completion.md` - Infrastructure
- `phase_2_2_j2_completion.md` - Collecte

### Commits
- `PERF: Add admin performance controller (Phase 2.2 J3)`
- `PERF: Register admin performance routes with RBAC`
- `PERF: Add admin performance dashboard views`
- `TEST/DOC: Add performance dashboard tests and documentation`

---

**Phase 2.2 J3**: ✅ **COMPLÈTE**  
**Dashboard**: ✅ **Production Ready**  
**Feature Freeze**: ✅ **Respecté**
