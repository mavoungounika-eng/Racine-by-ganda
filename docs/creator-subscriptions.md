# 📚 DOCUMENTATION TECHNIQUE - ABONNEMENT CRÉATEUR

**Date :** 19 décembre 2025  
**Projet :** RACINE BY GANDA  
**Version :** 1.0

---

## 🎯 PHILOSOPHIE

### Règle Fondamentale

⚠️ **Ne jamais conditionner une feature par le nom du plan.**  
✅ **Toujours passer par `can()` ou `capability()`.**

**Exemple INCORRECT :**
```php
if ($user->activePlan()->code === 'premium') {
    // Feature premium
}
```

**Exemple CORRECT :**
```php
if ($user->can('can_use_api')) {
    // Feature API
}
```

**Pourquoi ?**
- Flexibilité : changer les capabilities sans modifier le code
- Testabilité : tester les capabilities indépendamment
- Évolutivité : ajouter de nouveaux plans facilement

---

## 📋 LISTE DES PLANS

| Code | Nom | Prix | Description |
|------|-----|------|-------------|
| `free` | Créateur Découverte | 0 XAF | Plan gratuit pour tester |
| `official` | Créateur Officiel | 5 000 XAF/mois | Plan minimum pour vendre sérieusement |
| `premium` | Créateur Premium | 15 000 XAF/mois | Pour marques ambitieuses |

---

## 🔑 LISTE DES CAPABILITIES

| Capability | Type | Description |
|------------|------|-------------|
| `can_add_products` | bool | Peut ajouter des produits |
| `max_products` | int | Nombre max de produits (-1 = illimité) |
| `can_manage_collections` | bool | Peut gérer des collections |
| `max_collections` | int | Nombre max de collections (-1 = illimité) |
| `can_view_advanced_stats` | bool | Accès aux statistiques avancées |
| `can_view_analytics` | bool | Accès aux analytics |
| `can_export_data` | bool | Peut exporter les données |
| `dashboard_layout` | string | Layout du dashboard (basic/advanced/premium) |
| `can_use_api` | bool | Accès à l'API |
| `support_level` | string | Niveau de support (community/priority/dedicated) |

---

## 🔄 FLUX PAIEMENT

### Étape 1 : Choix du plan

**Route :** `/createur/abonnement/upgrade`  
**Contrôleur :** `SubscriptionController@upgrade`

Affiche les 3 plans avec leurs features.

### Étape 2 : Sélection du plan

**Route :** `POST /createur/abonnement/plan/{plan}/select`  
**Contrôleur :** `SubscriptionController@select`

- Si plan FREE → Activation immédiate
- Si plan payant → Redirection vers paiement

### Étape 3 : Paiement

**Route :** `/createur/abonnement/plan/{plan}/paiement`  
**Contrôleur :** `SubscriptionController@payment`

Options :
- Carte bancaire (Stripe)
- Mobile Money (Monetbil/MTN/Airtel)

### Étape 4 : Callback

**Route :** `/createur/abonnement/plan/{plan}/success`  
**Contrôleur :** `SubscriptionController@handlePaymentSuccess`

Actions :
1. Vérifier le paiement (webhook Stripe ou callback Mobile Money)
2. Créer/mettre à jour `CreatorSubscription`
3. Associer `creator_plan_id`
4. `clearCache($creator)`
5. Tracker l'événement (analytics)
6. Redirection dashboard avec message de succès

---

## ⏰ GESTION EXPIRATION

### Downgrade Automatique

**Commande :** `php artisan creator:check-expired-subscriptions`  
**Job :** `DowngradeExpiredSubscriptions`  
**Planification :** Quotidien à 3h du matin

**Règles :**
- Abonnement expiré → Downgrade vers FREE
- Données conservées (pas de suppression)
- Features bloquées, pas supprimées
- Cache invalidé automatiquement

**Test :**
```bash
# Mode dry-run (pas de modification)
php artisan creator:check-expired-subscriptions --dry-run

# Mode normal
php artisan creator:check-expired-subscriptions
```

---

## 🐛 CAS D'ERREUR FRÉQUENTS

### 1. "Plan FREE non trouvé"

**Cause :** Seeders non exécutés  
**Solution :**
```bash
php artisan db:seed --class=CreatorPlanSeeder
```

### 2. "Capability non trouvée"

**Cause :** Capability manquante dans le seeder  
**Solution :** Vérifier `PlanCapabilitySeeder` et ajouter la capability

### 3. "Cache non invalidé"

**Cause :** Cache obsolète après changement de plan  
**Solution :**
```php
app(CreatorCapabilityService::class)->clearCache($user);
```

### 4. "Dashboard layout incorrect"

**Cause :** Vue manquante pour le layout  
**Solution :** Vérifier que `resources/views/creator/dashboard/{layout}.blade.php` existe

---

## 🛠️ COMMANDES ARTISAN

### Vérifier les abonnements expirés

```bash
# Dry-run (affiche sans modifier)
php artisan creator:check-expired-subscriptions --dry-run

# Exécution normale
php artisan creator:check-expired-subscriptions
```

### Analytics

```php
// Dans un contrôleur ou tinker
$analytics = app(SubscriptionAnalyticsService::class);

// MRR
$mrr = $analytics->calculateMRR('2025-12');

// Conversion
$conversion = $analytics->calculateConversionRate('2025-12');

// Churn
$churn = $analytics->calculateChurn('2025-12');

// Stats globales
$stats = $analytics->getGlobalStats();
```

---

## 👨‍💼 PROCÉDURE UPGRADE MANUEL ADMIN

### Via Interface Admin

1. Aller sur `/admin/creator-subscriptions/{creator}`
2. Cliquer sur "Changer de plan"
3. Sélectionner le nouveau plan
4. Confirmer

### Via Tinker

```php
php artisan tinker

$user = User::find(1); // ID du créateur
$plan = CreatorPlan::where('code', 'premium')->first();

$subscription = CreatorSubscription::updateOrCreate(
    ['creator_id' => $user->id],
    [
        'creator_profile_id' => $user->creatorProfile->id,
        'creator_plan_id' => $plan->id,
        'status' => 'active',
        'started_at' => now(),
        'ends_at' => now()->addMonth(),
    ]
);

app(CreatorCapabilityService::class)->clearCache($user);
```

---

## 📊 ANALYTICS

### Table `subscription_events`

Tracke tous les événements d'abonnement :
- `created` — Création d'abonnement
- `upgraded` — Upgrade de plan
- `downgraded` — Downgrade de plan
- `canceled` — Annulation
- `renewed` — Renouvellement

### Métriques Calculées

- **MRR** : Monthly Recurring Revenue
- **Conversion** : FREE → OFFICIEL
- **Churn** : Taux d'attrition mensuel
- **Revenu par plan** : Répartition des revenus

---

## 🔐 SÉCURITÉ

### Middleware

- `auth` — Authentification requise
- `role.creator` — Rôle créateur uniquement
- `capability:can_manage_collections` — Capability spécifique

### Vérifications

- Toujours vérifier `$user->isCreator()` avant d'utiliser les capabilities
- Ne jamais faire confiance au plan directement
- Toujours utiliser `$user->can($capability)` pour les vérifications

---

## 📝 NOTES IMPORTANTES

1. **Cache** : Toutes les capabilities sont cachées (60 minutes)
2. **Fallback** : Expiration → FREE automatique
3. **Type Safety** : Gestion des types (bool, int, string, json)
4. **Compatibilité** : Compatible avec système Stripe existant
5. **Migration** : Additive (ne supprime rien)

---

## 🚀 DÉMARRAGE RAPIDE

```bash
# 1. Migrations
php artisan migrate

# 2. Seeders
php artisan db:seed --class=CreatorPlanSeeder
php artisan db:seed --class=PlanCapabilitySeeder

# 3. Vérifier
php artisan tinker
>>> $user = User::whereHas('roleRelation', fn($q) => $q->where('slug', 'createur'))->first();
>>> $user->can('can_add_products');
```

---

**Dernière mise à jour :** 19 décembre 2025

