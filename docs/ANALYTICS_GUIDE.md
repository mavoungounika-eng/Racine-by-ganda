# 📊 GUIDE ANALYTICS - RACINE BY GANDA

**Date de création** : 10 décembre 2025  
**Version** : Phase 4-5

---

## 🎯 Vue d'ensemble

Le module Analytics de RACINE BY GANDA permet de suivre :
- **Funnel d'achat** : Conversion depuis l'ajout au panier jusqu'au paiement
- **Ventes & CA** : Chiffres d'affaires, commandes, top produits
- **Statistiques créateur** : Performance des vendeurs sur la marketplace

---

## 📈 1. FONCTIONNEMENT DU FUNNEL

### 1.1. Événements trackés

Le système enregistre automatiquement les événements suivants dans la table `funnel_events` :

1. **`product_added_to_cart`** : Produit ajouté au panier
2. **`checkout_started`** : Utilisateur a démarré le checkout
3. **`order_placed`** : Commande créée
4. **`payment_completed`** : Paiement réussi
5. **`payment_failed`** : Paiement échoué

### 1.2. Enregistrement des événements

Les événements sont émis via le système d'Events/Listeners Laravel :

- **Event** : `ProductAddedToCart`, `CheckoutStarted`, `OrderPlaced`, `PaymentCompleted`, `PaymentFailed`
- **Listener** : `LogFunnelEvent` (enregistre dans DB + log fichier)

**Fichiers clés** :
- `app/Events/*.php` : Définition des événements
- `app/Listeners/LogFunnelEvent.php` : Enregistrement des événements
- `app/Models/FunnelEvent.php` : Modèle de données

### 1.3. Structure de la table `funnel_events`

```sql
- id (bigint)
- event_type (string) : Type d'événement
- user_id (bigint, nullable) : ID utilisateur
- order_id (bigint, nullable) : ID commande
- product_id (bigint, nullable) : ID produit
- metadata (json) : Données supplémentaires
- ip_address (string, nullable)
- user_agent (string, nullable)
- occurred_at (timestamp) : Date/heure de l'événement
```

---

## 🎛️ 2. DASHBOARD ADMIN

### 2.1. Accès

**URL** : `/admin/analytics`

**Permissions** : Rôle `admin`, `moderator` ou `super_admin`

### 2.2. Pages disponibles

#### Vue d'ensemble (`/admin/analytics`)

Affiche :
- KPIs Funnel (7 derniers jours) : Produits ajoutés, checkouts, commandes, paiements
- KPIs Ventes : CA total, commandes payées, panier moyen, clients uniques

#### Dashboard Funnel (`/admin/analytics/funnel`)

**Fonctionnalités** :
- Statistiques par type d'événement
- Taux de conversion :
  - Cart → Checkout
  - Checkout → Order
  - Order → Payment
  - Global (Cart → Payment)
- Évolution temporelle (timeline)
- Filtres :
  - Période : 7j, 30j, ce mois, personnalisée
  - Méthode de paiement (optionnel)

**Exemple d'URL** :
```
/admin/analytics/funnel?period=30days&payment_method=card
```

#### Dashboard Ventes (`/admin/analytics/sales`)

**Fonctionnalités** :
- KPIs : CA total, commandes, panier moyen, clients uniques
- Répartition par méthode de paiement
- Top 10 produits (par quantité vendue)
- Évolution journalière (timeline)

**Filtres** :
- Période : 7j, 30j, ce mois, personnalisée

### 2.3. Cache & Performance

**Cache** : TTL 1 heure (3600 secondes)

**Forcer le refresh** :
- Ajouter `?refresh=1` à l'URL
- Exemple : `/admin/analytics/funnel?refresh=1`

**Clé de cache** :
- Format : `analytics:funnel:YYYY-MM-DD:YYYY-MM-DD[:payment_method]`
- Format : `analytics:sales:YYYY-MM-DD:YYYY-MM-DD`

---

## 👨‍🎨 3. DASHBOARD CRÉATEUR

### 3.1. Accès

**URL** : `/createur/analytics`

**Permissions** : Rôle `createur` ou `creator`, compte actif

### 3.2. Pages disponibles

#### Vue d'ensemble (`/createur/analytics`)

Affiche :
- **KPIs** :
  - CA total (somme des OrderItems de ses produits)
  - Nombre de commandes contenant ses produits
  - Panier moyen
- **Top 10 produits** : Par quantité vendue
- **Évolution temporelle** : Timeline journalière

#### Détails ventes (`/createur/analytics/sales`)

**Fonctionnalités** :
- KPIs détaillés
- Top produits avec CA généré
- Évolution journalière complète
- Filtres par période

### 3.3. Filtrage des données

Les statistiques créateur **filtrent automatiquement** :
- Seulement les commandes contenant au moins un produit du créateur
- Seulement les OrderItems dont le produit appartient au créateur
- Seulement les commandes avec `payment_status='paid'`

**Exemple de requête** :
```php
Order::whereHas('items.product', function ($q) use ($creatorId) {
    $q->where('user_id', $creatorId);
})
->where('payment_status', 'paid')
```

### 3.4. Cache & Performance

**Cache** : TTL 1 heure

**Clé de cache** :
- Format : `analytics:creator:{creator_id}:YYYY-MM-DD:YYYY-MM-DD`

**Forcer le refresh** :
- Ajouter `?refresh=1` à l'URL

---

## 🔧 4. UTILISATION TECHNIQUE

### 4.1. Service Analytics

**Fichier** : `app/Services/AnalyticsService.php`

**Méthodes principales** :

```php
// Funnel stats
$stats = $analyticsService->getFunnelStats($startDate, $endDate, $paymentMethod, $forceRefresh);

// Sales stats
$stats = $analyticsService->getSalesStats($startDate, $endDate, $forceRefresh);

// Creator stats
$stats = $analyticsService->getCreatorStats($creatorId, $startDate, $endDate, $forceRefresh);

// Clear cache
$analyticsService->clearCache();
```

### 4.2. Invalidation du cache

**Automatique** : Le cache expire après 1 heure

**Manuelle** :
```php
// Via service
app(\App\Services\AnalyticsService::class)->clearCache();

// Via cache directement
Cache::forget('analytics:funnel:...');
Cache::forget('analytics:sales:...');
Cache::forget('analytics:creator:...');
```

**Recommandation** : Invalider le cache après événements importants (commandes, paiements) si besoin de données en temps réel.

### 4.3. Logs Funnel

**Fichier** : `storage/logs/funnel.log`

**Configuration** : `config/logging.php` → canal `funnel`

**Rotation** : Quotidienne, conservation 30 jours (configurable via `LOG_FUNNEL_DAYS`)

---

## 📊 5. INTERPRÉTATION DES DONNÉES

### 5.1. Taux de conversion

**Cart → Checkout** :
- Nombre de checkouts / Nombre d'ajouts au panier
- Indique l'intérêt des utilisateurs pour finaliser l'achat

**Checkout → Order** :
- Nombre de commandes / Nombre de checkouts
- Indique la complétion du formulaire

**Order → Payment** :
- Nombre de paiements réussis / Nombre de commandes
- Indique le taux de réussite des paiements

**Global (Cart → Payment)** :
- Nombre de paiements / Nombre d'ajouts au panier
- Taux de conversion global du tunnel

### 5.2. KPIs Ventes

**CA total** : Somme de tous les `total_amount` des commandes payées

**Panier moyen** : CA total / Nombre de commandes

**Clients uniques** : Nombre de `user_id` distincts ayant passé commande

### 5.3. Top produits

Tri par **quantité vendue** (pas par CA)

Pour voir par CA, utiliser la colonne `total_revenue` dans les données retournées.

---

## ⚠️ 6. LIMITATIONS & OPTIMISATIONS

### 6.1. Performance

**Cache** : Les statistiques sont mises en cache 1h pour réduire les requêtes DB

**Requêtes** : 
- `getFunnelStats()` : 2-3 requêtes (optimisé)
- `getSalesStats()` : 3-4 requêtes (optimisé)
- `getCreatorStats()` : 5-6 requêtes (peut être optimisé avec jointures)

**Recommandation** : Pour de grandes quantités de données, envisager :
- Indexes supplémentaires sur `funnel_events.occurred_at`
- Indexes sur `orders.created_at`, `orders.payment_status`
- Pagination pour les top produits si > 1000 produits

### 6.2. Précision des données

**Funnel** : Les événements sont enregistrés en temps réel, mais le cache peut retarder l'affichage de 1h maximum.

**Ventes** : Basées sur `payment_status='paid'`, donc incluent tous les paiements réussis.

**Créateur** : Basées sur les OrderItems, donc un créateur peut voir le CA de ses produits même si la commande contient d'autres produits.

---

## 🐛 7. DÉPANNAGE

### 7.1. Aucune donnée affichée

**Vérifier** :
- Des événements funnel sont-ils enregistrés ? (`SELECT * FROM funnel_events LIMIT 10`)
- Des commandes payées existent-elles ? (`SELECT * FROM orders WHERE payment_status='paid' LIMIT 10`)
- La période sélectionnée contient-elle des données ?

### 7.2. Cache ne se met pas à jour

**Solution** :
- Ajouter `?refresh=1` à l'URL
- Ou vider le cache : `php artisan cache:clear`

### 7.3. Statistiques créateur incorrectes

**Vérifier** :
- Le créateur a-t-il des produits ? (`SELECT * FROM products WHERE user_id = ?`)
- Y a-t-il des commandes contenant ces produits ?
- Les commandes sont-elles payées ?

---

## 📚 8. RESSOURCES

### 8.1. Fichiers clés

- `app/Services/AnalyticsService.php` : Service principal
- `app/Http/Controllers/Admin/AnalyticsController.php` : Contrôleur admin
- `app/Http/Controllers/Creator/AnalyticsController.php` : Contrôleur créateur
- `app/Events/*.php` : Événements funnel
- `app/Listeners/LogFunnelEvent.php` : Listener
- `app/Models/FunnelEvent.php` : Modèle

### 8.2. Vues

- `resources/views/admin/analytics/*.blade.php` : Vues admin
- `resources/views/creator/analytics/*.blade.php` : Vues créateur

### 8.3. Routes

- Admin : `/admin/analytics`, `/admin/analytics/funnel`, `/admin/analytics/sales`
- Créateur : `/createur/analytics`, `/createur/analytics/sales`

---

**Fin du guide**

