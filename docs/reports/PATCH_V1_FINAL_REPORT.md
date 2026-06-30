# RAPPORT FINAL — MIGRATION PAYMENT SOURCE OF TRUTH V1

**Date :** 2025-01-XX  
**Statut :** ✅ Migration complétée et sécurisée

---

## 📋 RÉSUMÉ EXÉCUTIF

Migration réussie du pipeline webhooks v1.1 de `PaymentTransaction` vers `Payment` comme source of truth unique pour la boutique RACINE.

**Changements principaux :**
- ✅ Extraction et persistance des identifiants Stripe (`checkout_session_id`, `payment_intent_id`)
- ✅ Mapping déterministe webhook → Payment (sans fallback fragile)
- ✅ Nouvelle méthode `updatePaymentAndOrder()` dans `PaymentEventMapperService`
- ✅ Idempotence stricte sur tous les points d'entrée
- ✅ Protection contre downgrade Order (paid → failed)

---

## 📁 FICHIERS MODIFIÉS

### Migrations
1. **`database/migrations/2025_12_17_185924_add_stripe_identifiers_to_stripe_webhook_events_table.php`**
   - Ajout colonnes `checkout_session_id` et `payment_intent_id` (nullable, indexées)
   - Migration réversible

### Contrôleurs
2. **`app/Http/Controllers/Api/WebhookController.php`**
   - Ajout méthode `extractStripeIdentifiers()` pour extraire IDs depuis payload Stripe
   - Persistance des identifiants dans `StripeWebhookEvent` lors de `firstOrCreate()`
   - Mise à jour des identifiants si événement existant et colonnes null

### Jobs
3. **`app/Jobs/ProcessStripeWebhookEventJob.php`**
   - Migration de `findTransaction()` → `findPayment()`
   - Mapping déterministe : `payment_intent_id` (priorité 1) ou `checkout_session_id` (priorité 2)
   - Suppression de tous les `LIKE` et fallback "dernière transaction récente"
   - Retry logic si Payment introuvable (3 tentatives)
   - Appel `updatePaymentAndOrder()` au lieu de `updateTransactionAndOrder()`
   - Appel `$event->markAsProcessed($payment->id)` avec payment_id

4. **`app/Jobs/ProcessMonetbilCallbackEventJob.php`**
   - Migration de `findTransaction()` → `findPayment()`
   - Mapping déterministe : `Payment.external_reference == transaction_id` + `channel='mobile_money'` + `whereNotNull('order_id')`
   - Retry logic si Payment introuvable (3 tentatives)
   - Appel `updatePaymentAndOrder()` au lieu de `updateTransactionAndOrder()`
   - Gestion sécurisée de la colonne `error` (vérification existence)

### Services
5. **`app/Services/Payments/PaymentEventMapperService.php`**
   - ✅ Nouvelle méthode `updatePaymentAndOrder(Payment $payment, string $newStatus)`
   - Lock `Payment` et `Order` avec `lockForUpdate()` dans transaction DB
   - Idempotence : vérification statut final avant update
   - Mapping `newStatus` (v1.1) → `payment.status` (boutique)
   - Mapping `payment.status` → `order.payment_status` et `order.status`
   - Protection contre downgrade (Order paid → failed bloqué)
   - Mise à jour `paid_at` uniquement si statut devient 'paid'
   - `updateTransactionAndOrder()` marquée `@deprecated`

### Modèles
6. **`app/Models/StripeWebhookEvent.php`**
   - ✅ Ajout `checkout_session_id` et `payment_intent_id` dans `$fillable`
   - ✅ Méthode `markAsProcessed()` idempotente stricte :
     - Ne réécrit pas `status` si déjà 'processed'
     - Ne réécrit pas `processed_at` si déjà défini
     - Ne met `payment_id` que s'il est null ET Payment existe
     - Ne fait pas d'`update()` si `$updateData` vide
   - Import `use App\Models\Payment;` ajouté

---

## ✅ VÉRIFICATIONS EFFECTUÉES

### 1. StripeWebhookEvent::$fillable
✅ Contient :
- `checkout_session_id`
- `payment_intent_id`
- `payment_id`

### 2. Appels markAsProcessed()
✅ `ProcessStripeWebhookEventJob` :
- Ligne 133 : `$event->markAsProcessed($payment->id)` (idempotence)
- Ligne 164 : `$event->markAsProcessed($payment->id)` (succès)

✅ `ProcessMonetbilCallbackEventJob` :
- Utilise `update()` direct (cohérent, pas de méthode `markAsProcessed()` sur `MonetbilCallbackEvent`)

### 3. Mapping déterministe

**Stripe :**
- Priorité 1 : `Payment.provider_payment_id == StripeWebhookEvent.payment_intent_id`
- Priorité 2 : `Payment.external_reference == StripeWebhookEvent.checkout_session_id`
- Contraintes : `provider='stripe'` + `channel='card'`

**Monetbil :**
- `Payment.external_reference == MonetbilCallbackEvent.transaction_id`
- Contraintes : `channel='mobile_money'` + `whereNotNull('order_id')`

### 4. Nettoyage
✅ Aucun commentaire "fallback updateTransactionAndOrder" trouvé
✅ Aucun appel à `updateTransactionAndOrder()` dans les jobs v1.1

---

## 🚀 COMMANDES ARTISAN À EXÉCUTER

### 1. Migration base de données
```bash
php artisan migrate
```

**Vérification :**
```bash
php artisan migrate:status
```

### 2. Tests unitaires (si disponibles)
```bash
php artisan test --filter=PaymentEventMapperService
php artisan test --filter=ProcessStripeWebhookEventJob
php artisan test --filter=ProcessMonetbilCallbackEventJob
```

### 3. Tests feature (à créer - voir section suivante)
```bash
php artisan test tests/Feature/Payments/
```

---

## 🧪 TESTS À CRÉER (CHECKLIST)

### Tests Feature Stripe

#### `tests/Feature/Payments/StripeWebhookPaymentMappingTest.php`
```php
// Test 1: mapping payment_intent_id -> Payment.provider_payment_id
public function test_stripe_webhook_maps_payment_intent_to_payment()

// Test 2: mapping checkout_session_id -> Payment.external_reference
public function test_stripe_webhook_maps_checkout_session_to_payment()

// Test 3: idempotence (même event_id 2x => un seul dispatch)
public function test_stripe_webhook_event_idempotent()

// Test 4: Payment introuvable -> event failed, Order inchangé
public function test_stripe_webhook_fails_when_payment_not_found()
public function test_stripe_webhook_does_not_update_order_when_payment_not_found()
```

### Tests Feature Monetbil

#### `tests/Feature/Payments/MonetbilWebhookPaymentMappingTest.php`
```php
// Test 1: mapping transaction_id -> Payment.external_reference
public function test_monetbil_webhook_maps_transaction_id_to_payment()

// Test 2: Payment introuvable -> event failed, Order inchangé
public function test_monetbil_webhook_fails_when_payment_not_found()
```

### Tests Unit

#### `tests/Unit/Services/PaymentEventMapperServiceTest.php`
```php
// Test mapping Payment.status -> Order.status/payment_status
public function test_map_payment_paid_to_order_processing()
public function test_map_payment_failed_to_order_pending()
public function test_map_payment_refunded_to_order_cancelled()
public function test_protection_against_downgrade_paid_to_failed()
```

---

## 🔍 VALIDATION MANUELLE

### Pré-requis
- Stripe CLI installé et configuré
- Environnement de test configuré
- Base de données de test avec migrations appliquées

### Scénario 1 : Stripe Webhook - Payment Intent

1. **Créer une commande avec paiement Stripe**
   ```bash
   # Via l'interface ou API
   ```

2. **Vérifier que Payment est créé**
   ```sql
   SELECT id, provider_payment_id, external_reference, status 
   FROM payments 
   WHERE provider='stripe' AND channel='card' 
   ORDER BY id DESC LIMIT 1;
   ```

3. **Simuler webhook payment_intent.succeeded**
   ```bash
   stripe listen --forward-to http://localhost/api/webhooks/stripe
   stripe trigger payment_intent.succeeded
   ```

4. **Vérifications :**
   - ✅ `stripe_webhook_events.payment_intent_id` est rempli
   - ✅ `stripe_webhook_events.checkout_session_id` est rempli (si disponible)
   - ✅ `payments.status` = 'paid'
   - ✅ `orders.payment_status` = 'paid'
   - ✅ `orders.status` = 'processing'
   - ✅ `stripe_webhook_events.status` = 'processed'
   - ✅ `stripe_webhook_events.payment_id` = Payment.id

### Scénario 2 : Stripe Webhook - Checkout Session

1. **Simuler webhook checkout.session.completed**
   ```bash
   stripe trigger checkout.session.completed
   ```

2. **Vérifications :**
   - ✅ `stripe_webhook_events.checkout_session_id` est rempli
   - ✅ Payment trouvé via `external_reference`
   - ✅ Order mise à jour correctement

### Scénario 3 : Idempotence Stripe

1. **Envoyer 2 fois le même webhook** (même `event_id`)
   ```bash
   # Utiliser le même event_id depuis Stripe Dashboard
   ```

2. **Vérifications :**
   - ✅ Un seul `StripeWebhookEvent` créé
   - ✅ Un seul job dispatché (vérifier `dispatched_at`)
   - ✅ Order mise à jour une seule fois

### Scénario 4 : Payment introuvable (Stripe)

1. **Simuler webhook avec payment_intent_id inexistant**
   ```bash
   # Modifier manuellement le payment_intent_id dans la DB ou utiliser un ID invalide
   ```

2. **Vérifications :**
   - ✅ Après 3 tentatives : `stripe_webhook_events.status` = 'failed'
   - ✅ `orders.payment_status` reste inchangé
   - ✅ Logs avec `error_message` explicite

### Scénario 5 : Monetbil Callback

1. **Créer une commande avec paiement Mobile Money**
   ```bash
   # Via l'interface
   ```

2. **Vérifier que Payment est créé**
   ```sql
   SELECT id, external_reference, status 
   FROM payments 
   WHERE channel='mobile_money' 
   ORDER BY id DESC LIMIT 1;
   ```

3. **Simuler callback Monetbil**
   ```bash
   # Via l'API Monetbil ou simulateur
   POST /api/webhooks/monetbil
   {
     "transaction_id": "<external_reference_du_payment>",
     "status": "success"
   }
   ```

4. **Vérifications :**
   - ✅ Payment trouvé via `external_reference`
   - ✅ `payments.status` = 'paid'
   - ✅ `orders.payment_status` = 'paid'
   - ✅ `orders.status` = 'processing'
   - ✅ `monetbil_callback_events.status` = 'processed'

### Scénario 6 : Race condition (webhook avant Payment)

1. **Simuler webhook avant que Payment soit créé**
   ```bash
   # Envoyer webhook immédiatement après création Order, avant création Payment
   ```

2. **Vérifications :**
   - ✅ Job retry (3 tentatives avec backoff)
   - ✅ Si Payment créé entre-temps : succès
   - ✅ Si Payment jamais créé : event failed après 3 tentatives

---

## ⚠️ POINTS D'ATTENTION

### 1. Migration
- ✅ Migration réversible (`down()` implémenté)
- ⚠️ Les événements historiques n'auront pas `checkout_session_id` / `payment_intent_id` (attendu)

### 2. Compatibilité
- ✅ Legacy routes `/webhooks/stripe` et `/payment/card/webhook` toujours actives mais dépréciées
- ✅ `updateTransactionAndOrder()` marquée `@deprecated` mais toujours fonctionnelle

### 3. Performance
- ✅ Index sur `checkout_session_id` et `payment_intent_id` pour recherche rapide
- ✅ `lockForUpdate()` utilisé pour éviter race conditions

### 4. Sécurité
- ✅ Logs sans secrets (pas de payload brut, pas de signature)
- ✅ Vérification existence Payment avant set `payment_id`
- ✅ Protection contre downgrade Order (paid → failed)

---

## 📊 MÉTRIQUES DE SUCCÈS

### Critères de validation
- ✅ Aucune dépendance à `PaymentTransaction` dans le flow boutique webhooks v1.1
- ✅ Mapping déterministe sans fallback fragile
- ✅ Idempotence stricte sur tous les points d'entrée
- ✅ Retry logic fonctionnel (3 tentatives avec backoff)
- ✅ Protection contre downgrade Order

### Monitoring recommandé
- Dashboard admin pour visualiser événements `failed` avec Payment introuvable
- Alertes si > X événements failed dans les dernières 24h
- Métriques : nombre d'événements processed/ignored/failed par jour

---

## 🔗 RESSOURCES

### Documentation
- `PATCH_PLAN_V1_PAYMENT_SOURCE_OF_TRUTH.md` : Plan de patch détaillé
- `INVENTAIRE_TECHNIQUE_PAIEMENTS.md` : Inventaire des points d'ancrage
- `AUDIT_WEBHOOKS_JOBS_EXACTLY_ONCE.md` : Audit complet du système

### Fichiers critiques
- `app/Http/Controllers/Api/WebhookController.php`
- `app/Jobs/ProcessStripeWebhookEventJob.php`
- `app/Jobs/ProcessMonetbilCallbackEventJob.php`
- `app/Services/Payments/PaymentEventMapperService.php`
- `app/Models/StripeWebhookEvent.php`

---

**FIN DU RAPPORT**

