# 📋 RAPPORT — PHASE 2.2 : WEBHOOKS STRIPE BILLING (ABONNEMENTS)

**Date :** 19 décembre 2025  
**Projet :** RACINE BY GANDA  
**Version :** 1.0  
**Phase :** 2.2 — Webhooks Stripe Billing

---

## 🎯 OBJECTIF DE L'ÉTAPE 2.2

Mettre en place un Stripe Billing Webhook Controller pour :
- ✅ Suivre les abonnements créateurs
- ✅ Mettre à jour `CreatorSubscription`
- ✅ Bloquer automatiquement les créateurs non payants

---

## ✅ LIVRABLES

### 1. Contrôleur Billing Webhook

**Fichier :** `app/Http/Controllers/Webhooks/StripeBillingWebhookController.php`

**Fonctionnalités :**
- ✅ Vérification signature Stripe (obligatoire en production)
- ✅ Gestion des 5 événements Billing (STRICT)
- ✅ Mapping événements → `CreatorSubscription`
- ✅ Invalidation automatique du cache des capabilities
- ✅ Logging complet pour traçabilité

**Événements gérés :**
1. `customer.subscription.created` → Créer/synchroniser l'abonnement
2. `customer.subscription.updated` → Mettre à jour le statut
3. `customer.subscription.deleted` → Désactiver l'abonnement
4. `invoice.payment_failed` → Marquer l'abonnement non actif
5. `invoice.paid` → Confirmer l'abonnement actif

---

## 🔄 MAPPING ÉVÉNEMENTS → CREATORSUBSCRIPTION

### Événement : `customer.subscription.created`

**Action :**
- Cherche l'abonnement existant par `stripe_subscription_id` ou `stripe_customer_id`
- Si trouvé → Met à jour avec les données Stripe
- Si non trouvé → Crée un nouvel abonnement via `createSubscriptionFromStripe()`
- Invalide le cache du créateur

**Données extraites :**
- `stripe_subscription_id` (obligatoire)
- `stripe_customer_id` (obligatoire)
- `stripe_price_id` (depuis `items.data[0].price.id`)
- `status` (mappé via `mapStripeStatusToLocal()`)
- `current_period_start` / `current_period_end`
- `trial_start` / `trial_end` (si présent)
- `cancel_at_period_end` / `canceled_at` (si présent)
- `metadata` (si présent)

**Création de l'abonnement :**
- Requiert `creator_id` dans les métadonnées Stripe (`metadata.creator_id`)
- Vérifie que le créateur existe et est valide
- Crée l'abonnement avec toutes les données nécessaires

---

### Événement : `customer.subscription.updated`

**Action :**
- Trouve l'abonnement par `stripe_subscription_id`
- Met à jour via `updateSubscriptionFromStripe()`
- Invalide le cache du créateur

**Données mises à jour :**
- `status` (mappé)
- `current_period_start` / `current_period_end`
- `ends_at` (synchronisé avec `current_period_end`)
- `cancel_at_period_end` / `canceled_at`
- `trial_start` / `trial_end`
- `stripe_customer_id` (si changé)
- `stripe_price_id` (si changé)
- `metadata` (si présent)

---

### Événement : `customer.subscription.deleted`

**Action :**
- Trouve l'abonnement par `stripe_subscription_id`
- Met à jour le statut vers `canceled`
- Définit `canceled_at` à maintenant
- Invalide le cache du créateur

**Résultat :**
- L'abonnement est marqué comme annulé
- Le créateur perd ses capabilities premium (fallback vers FREE)

---

### Événement : `invoice.payment_failed`

**Action :**
- Trouve l'abonnement via `invoice.subscription`
- Détermine le statut selon le nombre d'échecs :
  - `attempt_count >= 3` → `unpaid`
  - `attempt_count < 3` → `past_due`
- Met à jour le statut
- Invalide le cache du créateur

**Résultat :**
- L'abonnement passe en `past_due` (période de grâce) ou `unpaid` (bloqué)
- Le créateur est automatiquement bloqué (via système de capabilities)

---

### Événement : `invoice.paid`

**Action :**
- Trouve l'abonnement via `invoice.subscription`
- Si le statut n'est pas déjà `active`, le met à jour vers `active`
- Invalide le cache du créateur

**Résultat :**
- L'abonnement est confirmé comme actif
- Le créateur récupère ses capabilities premium

---

## 📊 RÈGLES EXACTES DE STATUT

### Mapping Stripe → Local

| Statut Stripe | Statut Local | Description |
|---------------|--------------|-------------|
| `incomplete` | `incomplete` | Créé mais premier paiement non effectué |
| `incomplete_expired` | `incomplete_expired` | Premier paiement expiré |
| `trialing` | `trialing` | Période d'essai active |
| `active` | `active` | Abonnement actif et payé |
| `past_due` | `past_due` | Paiement en retard (période de grâce) |
| `canceled` | `canceled` | Annulé (peut encore être actif jusqu'à fin période) |
| `unpaid` | `unpaid` | Impayé (doit suspendre le créateur) |

### Statuts considérés comme "actifs"

Seuls les statuts suivants sont considérés comme actifs :
- `active`
- `trialing`

**Méthode :** `CreatorSubscription::isActive()`

```php
public function isActive(): bool
{
    return in_array($this->status, ['active', 'trialing']) 
        && ($this->ends_at === null || $this->ends_at->isFuture());
}
```

### Statuts bloquants

Les statuts suivants bloquent automatiquement le créateur :
- `unpaid` → Blocage immédiat
- `past_due` → Blocage après période de grâce
- `canceled` → Blocage à la fin de la période
- `incomplete` / `incomplete_expired` → Blocage (pas d'abonnement valide)

**Mécanisme :**
- Le service `CreatorCapabilityService::getActiveSubscription()` filtre uniquement les statuts `active` ou `trialing`
- Si aucun abonnement actif → Fallback automatique vers plan FREE
- Le créateur perd toutes ses capabilities premium

---

## 🚫 CAS DE BLOCAGE CRÉATEUR

### 1. Paiement échoué (invoice.payment_failed)

**Scénario :**
- Stripe envoie `invoice.payment_failed`
- Le contrôleur met à jour le statut vers `past_due` (1-2 échecs) ou `unpaid` (3+ échecs)
- Le cache est invalidé
- Le système de capabilities détecte que l'abonnement n'est plus actif
- **Résultat :** Downgrade automatique vers FREE → Créateur bloqué

### 2. Abonnement annulé (customer.subscription.deleted)

**Scénario :**
- Stripe envoie `customer.subscription.deleted`
- Le contrôleur met à jour le statut vers `canceled`
- Le cache est invalidé
- **Résultat :** Downgrade automatique vers FREE → Créateur bloqué

### 3. Abonnement expiré (customer.subscription.updated avec ends_at passé)

**Scénario :**
- Stripe envoie `customer.subscription.updated` avec `current_period_end` dans le passé
- Le contrôleur met à jour `ends_at`
- Le cache est invalidé
- La méthode `isActive()` retourne `false` (car `ends_at` est dans le passé)
- **Résultat :** Downgrade automatique vers FREE → Créateur bloqué

### 4. Statut passé en unpaid/past_due (customer.subscription.updated)

**Scénario :**
- Stripe envoie `customer.subscription.updated` avec `status: 'unpaid'` ou `'past_due'`
- Le contrôleur met à jour le statut
- Le cache est invalidé
- **Résultat :** Downgrade automatique vers FREE → Créateur bloqué

---

## 🔐 SÉCURITÉ

### Vérification signature Stripe

**Obligatoire en production :**
- ✅ Vérification de la présence du header `Stripe-Signature`
- ✅ Vérification de la configuration `services.stripe.webhook_secret`
- ✅ Utilisation de `Stripe\Webhook::constructEvent()` pour valider la signature
- ✅ Rejet avec code 400 si signature invalide

**Mode développement :**
- ⚠️ En dev, la signature peut être ignorée pour faciliter les tests
- ⚠️ Le payload est parsé directement si la signature est absente
- ⚠️ **ATTENTION :** Ne jamais désactiver la vérification en production

### Rate Limiting

**Configuration :**
- Route protégée par middleware `throttle:webhooks`
- Limite : 60 requêtes par minute par IP
- Défini dans `routes/api.php`

### Logging

**Logs générés :**
- `received_stripe_billing_webhook` → Réception du webhook (safe, sans payload)
- `received_stripe_billing_webhook_parsed` → Événement parsé (avec `event_type`)
- `Stripe Billing webhook: Subscription created/updated/deleted` → Actions réussies
- `Stripe Billing webhook: Payment failed/confirmed` → Événements de paiement
- `Stripe Billing webhook: Processing error` → Erreurs de traitement

**Informations loggées (sécurisées) :**
- ✅ `event_type`
- ✅ `stripe_subscription_id`
- ✅ `creator_subscription_id`
- ✅ `creator_id` (si disponible)
- ✅ `ip` (adresse IP de la requête)
- ❌ **Jamais** le payload complet
- ❌ **Jamais** les données sensibles (cartes, tokens, etc.)

---

## ❌ CE QUI EST EXCLU

### ❌ Aucun checkout ici

Le contrôleur ne gère **PAS** :
- La création de sessions checkout
- La redirection vers Stripe Checkout
- La gestion des sessions checkout

**Raison :** Les checkouts sont gérés par le `SubscriptionController` lors de l'upgrade.

---

### ❌ Aucun Stripe Connect ici

Le contrôleur ne gère **PAS** :
- Les comptes Connect
- Les onboarding Connect
- Les payouts Connect

**Raison :** Les webhooks Connect sont gérés par `StripeConnectWebhookController`.

---

### ❌ Aucun appel Stripe inutile

Le contrôleur ne fait **PAS** :
- D'appel API Stripe pour récupérer des données
- De synchronisation manuelle
- De requêtes supplémentaires

**Raison :** Toutes les données nécessaires sont dans le payload du webhook.

---

### ❌ Aucune notification

Le contrôleur ne fait **PAS** :
- D'envoi d'email
- De notification push
- D'alerte admin

**Raison :** Les notifications sont gérées par d'autres services (jobs, listeners, etc.).

---

## 📁 FICHIERS CRÉÉS/MODIFIÉS

### Fichiers créés

1. **`app/Http/Controllers/Webhooks/StripeBillingWebhookController.php`**
   - Contrôleur principal pour les webhooks Billing
   - 600+ lignes de code
   - Gestion complète des 5 événements

### Fichiers modifiés

1. **`routes/api.php`**
   - Ajout de la route `/api/webhooks/stripe/billing`
   - Middleware : `api` + `throttle:webhooks`
   - Nom de route : `api.webhooks.stripe.billing`

---

## 🔗 ROUTE WEBHOOK

**URL :** `POST /api/webhooks/stripe/billing`

**Middleware :**
- `api` (groupe de middleware API)
- `throttle:webhooks` (60 requêtes/minute par IP)

**Nom de route :** `api.webhooks.stripe.billing`

**Configuration Stripe :**
- Dans le dashboard Stripe, configurer l'endpoint webhook avec cette URL
- Sélectionner les événements suivants :
  - `customer.subscription.created`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`
  - `invoice.payment_failed`
  - `invoice.paid`

---

## 🧪 TESTS RECOMMANDÉS

### Tests unitaires

1. **Vérification signature**
   - Test avec signature valide → 200 OK
   - Test avec signature invalide → 400 Bad Request
   - Test sans signature en production → 400 Bad Request
   - Test sans signature en dev → 200 OK (parsing direct)

2. **Événement customer.subscription.created**
   - Test création nouvel abonnement
   - Test synchronisation abonnement existant
   - Test avec creator_id manquant → Warning loggé

3. **Événement customer.subscription.updated**
   - Test mise à jour statut
   - Test mise à jour périodes
   - Test avec abonnement non trouvé → Warning loggé

4. **Événement customer.subscription.deleted**
   - Test désactivation abonnement
   - Test invalidation cache

5. **Événement invoice.payment_failed**
   - Test avec 1-2 échecs → `past_due`
   - Test avec 3+ échecs → `unpaid`
   - Test invalidation cache

6. **Événement invoice.paid**
   - Test activation abonnement
   - Test invalidation cache

### Tests d'intégration

1. **Flux complet**
   - Création abonnement → Webhook `created` → Vérification DB
   - Paiement réussi → Webhook `invoice.paid` → Vérification statut `active`
   - Paiement échoué → Webhook `payment_failed` → Vérification statut `unpaid`
   - Vérification blocage créateur (capabilities downgradées vers FREE)

2. **Idempotence**
   - Envoi du même webhook plusieurs fois → Pas de doublon
   - Vérification que le cache est invalidé correctement

---

## 📝 NOTES IMPORTANTES

### 1. Métadonnées Stripe

**Important :** Lors de la création d'un abonnement via checkout, il faut s'assurer que `metadata.creator_id` est défini dans la session checkout.

**Exemple :**
```php
$checkoutSession = \Stripe\Checkout\Session::create([
    // ... autres paramètres
    'metadata' => [
        'creator_id' => $creator->id,
    ],
]);
```

### 2. Cache des capabilities

**Important :** Le cache est invalidé à chaque mise à jour d'abonnement pour garantir que les capabilities sont à jour.

**Méthode :** `CreatorCapabilityService::clearCache($creator)`

### 3. Statuts non actifs

**Important :** Les statuts `unpaid`, `past_due`, `canceled`, `incomplete`, `incomplete_expired` ne sont **PAS** considérés comme actifs.

**Conséquence :** Le créateur est automatiquement downgradé vers FREE via le système de capabilities.

### 4. Gestion des erreurs

**Stratégie :** Les erreurs sont loggées mais ne retournent **PAS** d'erreur HTTP pour éviter les retries Stripe inutiles.

**Exception :** Erreurs de signature → 400 Bad Request (pas de retry)

---

## 🚀 PROCHAINES ÉTAPES

### Phase 2.2 — ✅ COMPLÉTÉE

- ✅ Contrôleur Billing webhook créé
- ✅ Mapping événements → CreatorSubscription
- ✅ Règles de statut implémentées
- ✅ Cas de blocage créateur gérés
- ✅ Sécurité (signature) implémentée
- ✅ Route webhook configurée

### Phase 3 — Checkout sécurisé (⏳ EN ATTENTE)

- ⏳ Création de sessions checkout Stripe
- ⏳ Redirection vers Stripe Checkout
- ⏳ Gestion des callbacks checkout
- ⏳ Association checkout → CreatorSubscription

### Phase 4 — Tests (⏳ EN ATTENTE)

- ⏳ Tests unitaires
- ⏳ Tests d'intégration
- ⏳ Tests de charge

### Phase 5 — Production (⏳ EN ATTENTE)

- ⏳ Configuration webhook Stripe
- ⏳ Monitoring
- ⏳ Documentation utilisateur

---

## 📊 RÉCAPITULATIF DU CHEMIN

| Phase | Étape | Statut |
|-------|-------|--------|
| Phase 1 | StripeConnectService | ✅ |
| Phase 2 | Webhook Connect | ✅ |
| Phase 2 | Webhook Billing | ✅ **COMPLÉTÉ** |
| Phase 3 | Checkout sécurisé | ⏳ |
| Phase 4 | Tests | ⏳ |
| Phase 5 | Production | ⏳ |

---

**Dernière mise à jour :** 19 décembre 2025  
**Auteur :** Auto (Cursor AI)  
**Version :** 1.0

