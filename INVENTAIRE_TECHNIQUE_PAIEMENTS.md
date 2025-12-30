# INVENTAIRE TECHNIQUE — POINTS D'ANCRAGE PAIEMENTS
**Projet :** racine-backend (Laravel 12)  
**Date :** 2025-01-XX  
**Type :** Inventaire technique ciblé (PASS A)

---

## 1. WEBHOOKS

### 1.1. Routes d'entrée

#### Stripe (officiel v1.1)
- **Route :** `POST /api/webhooks/stripe`
- **Fichier :** `routes/api.php` (ligne 20)
- **Contrôleur :** `App\Http\Controllers\Api\WebhookController@stripe()`
- **Middleware :** `api`, `throttle:webhooks` (60/min)

#### Stripe (legacy déprécié)
- **Route 1 :** `POST /webhooks/stripe`
- **Route 2 :** `POST /payment/card/webhook`
- **Fichier :** `routes/web.php` (lignes 453, 459)
- **Contrôleur :** `App\Http\Controllers\Front\CardPaymentController@webhook()`
- **Middleware :** `LegacyWebhookDeprecationHeaders`

#### Monetbil (officiel v1.1)
- **Route :** `POST /api/webhooks/monetbil`
- **Fichier :** `routes/api.php` (ligne 21)
- **Contrôleur :** `App\Http\Controllers\Api\WebhookController@monetbil()`
- **Middleware :** `api`, `throttle:webhooks` (60/min)

---

### 1.2. Modèles utilisés

#### Système v1.1 (officiel)
- **`StripeWebhookEvent`** : Persisté dans `WebhookController@stripe()`
- **`MonetbilCallbackEvent`** : Persisté dans `WebhookController@monetbil()`
- **`PaymentTransaction`** : Utilisé dans `ProcessStripeWebhookEventJob` et `ProcessMonetbilCallbackEventJob`
- **`Order`** : Mis à jour via `PaymentEventMapperService::updateTransactionAndOrder()`

#### Système legacy
- **`StripeWebhookEvent`** : Persisté dans `CardPaymentService::handleWebhook()`
- **`Payment`** : Utilisé dans `CardPaymentService::handleWebhook()`
- **`Order`** : Mis à jour directement dans `CardPaymentService::handleCheckoutSessionCompleted()`

---

### 1.3. Points de modification `Order.payment_status`

#### Système v1.1
**Fichier :** `app/Services/Payments/PaymentEventMapperService.php`

**Méthode :** `updateTransactionAndOrder(PaymentTransaction $transaction, string $newStatus)`

**Ligne 96 :** `'payment_status' => $orderPaymentStatus`

**Flux :**
```
ProcessStripeWebhookEventJob
  → PaymentEventMapperService::updateTransactionAndOrder()
  → Order.update(payment_status='paid')
```

```
ProcessMonetbilCallbackEventJob
  → PaymentEventMapperService::updateTransactionAndOrder()
  → Order.update(payment_status='paid')
```

#### Système legacy Stripe
**Fichier :** `app/Services/Payments/CardPaymentService.php`

**Méthodes :**
- `handleCheckoutSessionCompleted()` (ligne 477)
- `handlePaymentIntentSucceeded()` (ligne 516)

**Flux :**
```
CardPaymentService::handleWebhook()
  → handleCheckoutSessionCompleted()
  → Order.update(payment_status='paid')
```

#### Système legacy Mobile Money
**Fichier :** `app/Services/Payments/MobileMoneyPaymentService.php`

**Méthode :** `updatePaymentStatus()` (ligne 716)

**Flux :**
```
MobileMoneyPaymentService::handleCallback()
  → updatePaymentStatus()
  → Order.update(payment_status='paid')
```

#### Fallback Mobile Money
**Fichier :** `app/Http/Controllers/Front/MobileMoneyPaymentController.php`

**Méthode :** `success()` (ligne 151)

**Flux :**
```
MobileMoneyPaymentController::success()
  → Order.update(payment_status='paid') (si callback échoué)
```

---

## 2. CHECKOUT BOUTIQUE

### 2.1. Création `Payment`

#### Stripe (carte bancaire)
**Fichier :** `app/Services/Payments/CardPaymentService.php`

**Méthode :** `createCheckoutSession(Order $order)`

**Ligne 51 :** `Payment::create([...])`

**Champs utilisés :**
- `order_id` : `$order->id`
- `provider` : `'stripe'`
- `channel` : `'card'`
- `status` : `'initiated'`
- `amount` : `$order->total_amount`
- `currency` : `'XAF'`
- `external_reference` : `$session->id` (Stripe Checkout Session ID)
- `provider_payment_id` : `$session->payment_intent` (Stripe Payment Intent ID)

**Ligne 94 :** Mise à jour après création session Stripe

---

#### Mobile Money
**Fichier :** `app/Services/Payments/MobileMoneyPaymentService.php`

**Méthode :** `initiatePayment(Order $order, string $phone, string $provider)`

**Ligne 50 :** `Payment::create([...])`

**Champs utilisés :**
- `order_id` : `$order->id`
- `provider` : `$provider` ('mtn_momo' ou 'airtel_money')
- `channel` : `'mobile_money'`
- `status` : `'initiated'`
- `amount` : `$order->total_amount`
- `currency` : `'XAF'`
- `external_reference` : `$this->generateTransactionId($provider)` (transaction_id Monetbil)
- `customer_phone` : `$phone`

---

### 2.2. Champs de mapping provider

#### Stripe
- **`Payment.external_reference`** = `session_id` (Stripe Checkout Session ID, format `cs_...`)
- **`Payment.provider_payment_id`** = `payment_intent` (Stripe Payment Intent ID, format `pi_...`)

**Stockage :** `CardPaymentService::createCheckoutSession()` (lignes 94-95)

**Usage legacy :** `CardPaymentService::handleWebhook()` cherche par `external_reference` (session_id) ou `provider_payment_id` (payment_intent)

---

#### Monetbil (Mobile Money)
- **`Payment.external_reference`** = `transaction_id` (Monetbil transaction ID)

**Stockage :** `MobileMoneyPaymentService::initiatePayment()` (ligne 58)

**Usage legacy :** `MobileMoneyPaymentService::handleCallback()` cherche par `external_reference` (transaction_id)

---

### 2.3. Liaison Order ↔ Payment

#### Relation Eloquent
**Fichier :** `app/Models/Payment.php`

**Ligne 35 :** `public function order(): BelongsTo`

**Clé :** `Payment.order_id` → `Order.id` (FK, cascadeOnDelete)

#### Création
- **Stripe :** `Payment::create(['order_id' => $order->id])` dans `CardPaymentService::createCheckoutSession()`
- **Mobile Money :** `Payment::create(['order_id' => $order->id])` dans `MobileMoneyPaymentService::initiatePayment()`

#### Mise à jour Order depuis Payment
- **Legacy Stripe :** `CardPaymentService::handleCheckoutSessionCompleted()` → `$payment->order->update()`
- **Legacy Mobile Money :** `MobileMoneyPaymentService::updatePaymentStatus()` → `$payment->order->update()`
- **v1.1 :** `PaymentEventMapperService::updateTransactionAndOrder()` → Utilise `PaymentTransaction.order_id` (à migrer)

---

## 3. MODÈLES & SERVICES

### 3.1. Services impliqués

#### Services de paiement
- **`CardPaymentService`** : `app/Services/Payments/CardPaymentService.php`
  - `createCheckoutSession()` : Création Payment Stripe
  - `handleWebhook()` : Traitement webhook Stripe (legacy)

- **`MobileMoneyPaymentService`** : `app/Services/Payments/MobileMoneyPaymentService.php`
  - `initiatePayment()` : Création Payment Mobile Money
  - `handleCallback()` : Traitement callback Monetbil (legacy)
  - `updatePaymentStatus()` : Mise à jour Payment + Order

- **`PaymentEventMapperService`** : `app/Services/Payments/PaymentEventMapperService.php`
  - `mapStripeEventToStatus()` : Mapping event_type → statut
  - `mapMonetbilEventToStatus()` : Mapping payload → statut
  - `updateTransactionAndOrder()` : Mise à jour PaymentTransaction + Order (v1.1)

---

### 3.2. Jobs impliqués

#### Jobs webhooks v1.1
- **`ProcessStripeWebhookEventJob`** : `app/Jobs/ProcessStripeWebhookEventJob.php`
  - Utilise `PaymentTransaction` (à migrer vers `Payment`)
  - Appelle `PaymentEventMapperService::updateTransactionAndOrder()`

- **`ProcessMonetbilCallbackEventJob`** : `app/Jobs/ProcessMonetbilCallbackEventJob.php`
  - Utilise `PaymentTransaction` (à migrer vers `Payment`)
  - Appelle `PaymentEventMapperService::updateTransactionAndOrder()`

---

### 3.3. Tables utilisées en production

#### Tables principales
- **`payments`** : Utilisée par checkout boutique (Stripe + Mobile Money)
- **`payment_transactions`** : Utilisée par webhooks v1.1 (à migrer)
- **`orders`** : Source of truth commandes
- **`stripe_webhook_events`** : Événements Stripe persistés
- **`monetbil_callback_events`** : Événements Monetbil persistés

#### Tables de liaison
- **`order_items`** : Items de commande
- **`order_vendors`** : Répartition par vendeur (marketplace)

---

## 4. RÉSUMÉ DES POINTS D'ANCRAGE

### 4.1. Création Payment
- **Stripe :** `CardPaymentService::createCheckoutSession()` (ligne 51)
- **Mobile Money :** `MobileMoneyPaymentService::initiatePayment()` (ligne 50)

### 4.2. Mapping webhook → Payment
- **Stripe legacy :** `CardPaymentService::handleWebhook()` → Cherche par `external_reference` ou `provider_payment_id`
- **Stripe v1.1 :** `ProcessStripeWebhookEventJob@findTransaction()` → Cherche `PaymentTransaction` (à migrer)
- **Monetbil legacy :** `MobileMoneyPaymentService::handleCallback()` → Cherche par `external_reference`
- **Monetbil v1.1 :** `ProcessMonetbilCallbackEventJob@findTransaction()` → Cherche `PaymentTransaction` (à migrer)

### 4.3. Mise à jour Order.payment_status
- **v1.1 :** `PaymentEventMapperService::updateTransactionAndOrder()` (ligne 96)
- **Legacy Stripe :** `CardPaymentService::handleCheckoutSessionCompleted()` (ligne 477)
- **Legacy Mobile Money :** `MobileMoneyPaymentService::updatePaymentStatus()` (ligne 716)
- **Fallback Mobile Money :** `MobileMoneyPaymentController::success()` (ligne 151)

### 4.4. Champs de mapping
- **Stripe :** `Payment.external_reference` (session_id), `Payment.provider_payment_id` (payment_intent)
- **Monetbil :** `Payment.external_reference` (transaction_id)

---

**FIN DE L'INVENTAIRE — PASS A**

