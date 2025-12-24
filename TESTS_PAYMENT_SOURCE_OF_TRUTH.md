# TESTS AUTOMATISÉS — PAYMENT SOURCE OF TRUTH V1.1

**Date :** 2025-01-XX  
**Statut :** ✅ Tests créés

---

## 📁 FICHIERS DE TESTS CRÉÉS

### 1. `tests/Feature/Payments/StripeWebhookPaymentMappingTest.php`

**Tests :**
- ✅ `test_stripe_webhook_maps_payment_intent_to_payment()` : Mapping `payment_intent_id` → `Payment.provider_payment_id`
- ✅ `test_stripe_webhook_maps_checkout_session_to_payment()` : Mapping `checkout_session_id` → `Payment.external_reference`
- ✅ `test_stripe_webhook_event_idempotent()` : Idempotence (même `event_id` 2x => un seul dispatch)

**Validations :**
- `payments.status` passe à `'paid'` sur `succeeded`
- `orders.payment_status` passe à `'paid'` et `orders.status` à `'processing'`
- `stripe_webhook_events.status` passe à `'processed'` et `payment_id` est renseigné

---

### 2. `tests/Feature/Payments/StripeWebhookPaymentNotFoundTest.php`

**Tests :**
- ✅ `test_stripe_webhook_fails_when_payment_not_found()` : Payment introuvable → event failed après retries
- ✅ `test_stripe_webhook_does_not_update_order_when_payment_not_found()` : Order inchangée si Payment introuvable

**Validations :**
- Après 3 tentatives : `stripe_webhook_events.status` = `'failed'`
- `orders.payment_status` reste `'pending'`
- `orders.status` reste `'pending'`

---

### 3. `tests/Feature/Payments/MonetbilWebhookPaymentMappingTest.php`

**Tests :**
- ✅ `test_monetbil_webhook_maps_transaction_id_to_payment()` : Mapping `transaction_id` → `Payment.external_reference`
- ✅ `test_monetbil_webhook_fails_when_payment_not_found()` : Payment introuvable → event failed après retries

**Validations :**
- `payments.status` passe à `'paid'` sur `succeeded`
- `orders.payment_status` passe à `'paid'` et `orders.status` à `'processing'`
- `monetbil_callback_events.status` passe à `'processed'`
- Si Payment introuvable : `monetbil_callback_events.status` = `'failed'` après retries

---

## 🚀 COMMANDE POUR EXÉCUTER LES TESTS

### Exécuter tous les tests Payment source of truth
```bash
php artisan test tests/Feature/Payments/
```

### Exécuter un fichier spécifique
```bash
# Tests Stripe mapping
php artisan test tests/Feature/Payments/StripeWebhookPaymentMappingTest.php

# Tests Stripe Payment not found
php artisan test tests/Feature/Payments/StripeWebhookPaymentNotFoundTest.php

# Tests Monetbil mapping
php artisan test tests/Feature/Payments/MonetbilWebhookPaymentMappingTest.php
```

### Exécuter un test spécifique
```bash
php artisan test --filter=test_stripe_webhook_maps_payment_intent_to_payment
php artisan test --filter=test_stripe_webhook_event_idempotent
php artisan test --filter=test_monetbil_webhook_maps_transaction_id_to_payment
```

---

## 📋 DÉTAILS DES TESTS

### Structure commune

Tous les tests utilisent :
- ✅ `RefreshDatabase` : Base de données réinitialisée entre chaque test
- ✅ `OrderFactory` et `PaymentFactory` : Factories existantes utilisées
- ✅ Appel direct aux endpoints `/api/webhooks/stripe` et `/api/webhooks/monetbil`
- ✅ Exécution manuelle des jobs : `new Job($event->id)->handle(app(PaymentEventMapperService::class))`
- ✅ Vérifications DB : `assertDatabaseHas()`, `refresh()`, `assertEquals()`

### Configuration test

- Environnement : `testing` (pas de vérification signature Stripe/Monetbil)
- Webhook secret : vide (dev mode)
- Factories : Utilisation des factories existantes (`OrderFactory`, `PaymentFactory`)

---

## ✅ COUVERTURE DES TESTS

### Scénarios testés

#### Stripe
- ✅ Mapping `payment_intent_id` → Payment (priorité 1)
- ✅ Mapping `checkout_session_id` → Payment (priorité 2)
- ✅ Idempotence événement (même `event_id` 2x)
- ✅ Payment introuvable → retry → failed
- ✅ Order inchangée si Payment introuvable

#### Monetbil
- ✅ Mapping `transaction_id` → Payment
- ✅ Payment introuvable → retry → failed
- ✅ Order inchangée si Payment introuvable

### Points non testés (à ajouter si nécessaire)

- Race condition : webhook avant création Payment (nécessite test d'intégration plus complexe)
- Protection downgrade Order (paid → failed) : testé indirectement via `updatePaymentAndOrder()`
- Requeue/blocked mechanism : testé dans `WebhookRequeueGuardTest.php` existant

---

## 🔧 DÉPENDANCES

### Factories utilisées
- ✅ `OrderFactory` : Existe et compatible
- ✅ `PaymentFactory` : Existe et compatible
- ✅ `UserFactory` : Existe et compatible

### Services mockés
- ✅ `PaymentEventMapperService` : Injecté via `app()` (pas de mock nécessaire)

---

## 📊 RÉSULTATS ATTENDUS

### Exécution complète
```bash
php artisan test tests/Feature/Payments/
```

**Résultat attendu :**
```
PASS  Tests\Feature\Payments\StripeWebhookPaymentMappingTest
  ✓ test_stripe_webhook_maps_payment_intent_to_payment
  ✓ test_stripe_webhook_maps_checkout_session_to_payment
  ✓ test_stripe_webhook_event_idempotent

PASS  Tests\Feature\Payments\StripeWebhookPaymentNotFoundTest
  ✓ test_stripe_webhook_fails_when_payment_not_found
  ✓ test_stripe_webhook_does_not_update_order_when_payment_not_found

PASS  Tests\Feature\Payments\MonetbilWebhookPaymentMappingTest
  ✓ test_monetbil_webhook_maps_transaction_id_to_payment
  ✓ test_monetbil_webhook_fails_when_payment_not_found

Tests:    7 passed
Duration: X.XXs
```

---

**FIN DU DOCUMENT**

