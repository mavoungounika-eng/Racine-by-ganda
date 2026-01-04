# DASHBOARD ADMIN CENTRAL — RACINE BY GANDA

## Principe Fondamental

**Le dashboard est un outil de DÉCISION, pas un rapport.**

Chaque donnée affichée doit répondre à la question : **"Quelle action humaine cela déclenche-t-il ?"**

---

## Structure (6 Blocs Obligatoires)

### 1. État Global
### 2. Alertes & Priorités
### 3. Activité Commerciale
### 4. Marketplace
### 5. Opérations & Logistique
### 6. Tendances Courtes

---

## 1️⃣ ÉTAT GLOBAL

**Objectif** : Vue d'ensemble en < 10 secondes.

| KPI | Formule | Seuils | Action si Rouge |
|-----|---------|--------|-----------------|
| **CA Aujourd'hui** | `SUM(orders.total_amount) WHERE status IN ('completed', 'processing') AND DATE(created_at) = TODAY` | 🟢 > 100k FCFA<br>🟠 50k-100k<br>🔴 < 50k | Vérifier campagnes marketing, contacter équipe commerciale |
| **Commandes Aujourd'hui** | `COUNT(orders) WHERE DATE(created_at) = TODAY` | 🟢 > 10<br>🟠 5-10<br>🔴 < 5 | Analyser trafic site, vérifier disponibilité produits |
| **Panier Moyen** | `AVG(orders.total_amount) WHERE status != 'cancelled' AND DATE(created_at) = TODAY` | 🟢 > 30k FCFA<br>🟠 20k-30k<br>🔴 < 20k | Proposer bundles, revoir stratégie upsell |
| **Taux Conversion** | `(COUNT(orders) / COUNT(sessions)) * 100` (simplifié) | 🟢 > 2%<br>🟠 1-2%<br>🔴 < 1% | Optimiser tunnel de vente, vérifier UX checkout |
| **Commandes En Attente** | `COUNT(orders) WHERE status = 'pending' AND created_at < NOW() - INTERVAL 24 HOUR` | 🟢 0<br>🟠 1-3<br>🔴 > 3 | Contacter clients, relancer paiements |

**Variation J-1** : Chaque KPI affiche la variation par rapport à hier (↗️ +15%, ↘️ -8%).

---

## 2️⃣ ALERTES & PRIORITÉS

**Objectif** : Identifier les problèmes critiques nécessitant une action immédiate.

**Règle** : Maximum 5 alertes affichées simultanément.

| Alerte | Condition | Gravité | Action |
|--------|-----------|---------|--------|
| **Commandes en Retard** | `COUNT(orders) WHERE status IN ('processing', 'confirmed') AND expected_delivery_date < NOW()` | 🔴 Critique | Rediriger vers liste filtrée → Contacter clients |
| **Stock Critique** | `COUNT(products) WHERE stock < 5` | 🟠 Urgent | Rediriger vers liste produits → Réapprovisionner |
| **Paiements Échoués** | `COUNT(payments) WHERE status = 'failed' AND created_at > NOW() - INTERVAL 24 HOUR` | 🔴 Critique | Rediriger vers transactions → Relancer paiements |
| **Créateurs à Risque** | `COUNT(creators) WHERE revenue_30d < threshold AND status = 'active'` | 🟠 Attention | Rediriger vers liste créateurs → Accompagner |
| **Taux Conversion Faible** | `conversion_rate < 1%` | 🟠 Attention | Analyser tunnel, vérifier UX |

**Affichage** : Si aucune alerte → Afficher "✅ Aucune alerte critique".

---

## 3️⃣ ACTIVITÉ COMMERCIALE

**Objectif** : Identifier les produits performants et les problèmes de rotation.

| Métrique | Formule | Action |
|----------|---------|--------|
| **Top 5 Produits Marque** | `SELECT products.title, COUNT(order_items.id) as sales_count FROM order_items JOIN products ON order_items.product_id = products.id WHERE products.user_id IS NULL AND DATE(orders.created_at) = TODAY GROUP BY products.id ORDER BY sales_count DESC LIMIT 5` | Mettre en avant, réapprovisionner |
| **Top 5 Produits Marketplace** | Même requête avec `products.user_id IS NOT NULL` | Féliciter créateurs, promouvoir |
| **Produits Faible Rotation** | `SELECT products WHERE last_sale_date < NOW() - INTERVAL 30 DAY AND stock > 0` | Lancer promotions, revoir prix |
| **Paniers Abandonnés (24h)** | `COUNT(carts) WHERE updated_at < NOW() - INTERVAL 24 HOUR AND status = 'active'` | Envoyer emails de relance |

---

## 4️⃣ MARKETPLACE (Vue Secondaire)

**Objectif** : Suivi des vendeurs partenaires, sans dominer visuellement.

| Métrique | Formule | Action |
|----------|---------|--------|
| **CA Marketplace Aujourd'hui** | `SUM(order_items.price * order_items.quantity) WHERE products.user_id IS NOT NULL AND DATE(orders.created_at) = TODAY` | Comparer avec CA Marque |
| **Commandes Marketplace** | `COUNT(DISTINCT orders.id) WHERE order_items.product_id IN (SELECT id FROM products WHERE user_id IS NOT NULL)` | Suivre performance globale |
| **Créateurs Actifs** | `COUNT(users) WHERE role = 'createur' AND last_sale_date > NOW() - INTERVAL 30 DAY` | Identifier inactifs |
| **Créateurs à Risque** | `COUNT(users) WHERE role = 'createur' AND revenue_30d < 10000` | Accompagner, former |

**Règle** : Isolation totale CA Marque ≠ CA Marketplace.

---

## 5️⃣ OPÉRATIONS & LOGISTIQUE

**Objectif** : Suivi des tâches opérationnelles pour l'équipe Staff.

| Métrique | Formule | Action |
|----------|---------|--------|
| **À Préparer** | `COUNT(orders) WHERE status = 'confirmed' AND prepared_at IS NULL` | Rediriger vers liste → Préparer commandes |
| **Prêtes Non Expédiées** | `COUNT(orders) WHERE status = 'prepared' AND shipped_at IS NULL AND prepared_at < NOW() - INTERVAL 24 HOUR` | Rediriger vers liste → Expédier |
| **Retours en Attente** | `COUNT(returns) WHERE status = 'pending'` | Traiter retours |
| **Incidents Signalés** | `COUNT(incidents) WHERE status = 'open'` | Résoudre incidents |

---

## 6️⃣ TENDANCES COURTES (7 Jours)

**Objectif** : Visualiser l'évolution récente via mini-graphiques.

| Graphique | Données | Technologie |
|-----------|---------|-------------|
| **CA 7 Jours** | `SELECT DATE(created_at) as date, SUM(total_amount) as revenue FROM orders WHERE created_at >= NOW() - INTERVAL 7 DAY GROUP BY DATE(created_at)` | Chart.js (line) |
| **Commandes 7 Jours** | Même requête avec `COUNT(*)` | Chart.js (bar) |
| **Conversion 7 Jours** | Calcul quotidien du taux | Chart.js (line) |

**Règle** : Graphiques minimalistes, pas de détails excessifs.

---

## Configuration Technique

### Cache
- **État Global** : 5 min (`config/dashboard.php`)
- **Alertes** : 3 min
- **Commercial** : 10 min
- **Marketplace** : 15 min
- **Opérations** : 5 min
- **Tendances** : 15 min

### Seuils
Tous les seuils sont configurables dans `config/dashboard.php` :

```php
'thresholds' => [
    'revenue' => ['green' => 100000, 'orange' => 50000],
    'orders' => ['green' => 10, 'orange' => 5],
    'conversion' => ['green' => 2.0, 'orange' => 1.0],
    // ...
]
```

### Performance
- **Temps de chargement** : < 2s
- **Aucun N+1** : Utilisation de `with()` et `join()`
- **Fallback** : Si erreur, afficher message générique

---

## Validation Finale

**Critère de validation** : Pour chaque widget, répondre à la question :

> "Si cette valeur est ROUGE, quelle action précise est déclenchée ?"

- ✅ **Réponse claire** → Widget validé
- ❌ **Pas de réponse** → Widget à supprimer

---

## Accès

**Rôles autorisés** : Super Admin, Admin  
**Rôles interdits** : Staff, Créateur, Client

**Route** : `/admin/dashboard`  
**Controller** : `AdminDashboardController`  
**Service** : `DashboardService`
