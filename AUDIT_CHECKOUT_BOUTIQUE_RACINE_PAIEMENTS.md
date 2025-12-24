# AUDIT CHECKOUT BOUTIQUE RACINE + PAIEMENTS
**Projet :** racine-backend (Laravel 12)  
**Date :** 2025-01-XX  
**Phase :** PASS 2/3 — Analyse circuit d'achat client (Boutique RACINE uniquement)

---

## 1. ROUTES CHECKOUT

### 1.1. Fichier : `routes/web.php`

#### Routes principales checkout

| Route | Méthode | Contrôleur | Middleware | Nom de route |
|-------|---------|------------|------------|-------------|
| `/checkout` | GET | `CheckoutController@index` | `auth`, `throttle:120,1` | `checkout.index` |
| `/checkout` | POST | `CheckoutController@placeOrder` | `auth`, `throttle:10,1` | `checkout.place` |
| `/checkout/success/{order}` | GET | `CheckoutController@success` | `auth`, `throttle:120,1` | `checkout.success` |
| `/checkout/cancel/{order}` | GET | `CheckoutController@cancel` | `auth`, `throttle:120,1` | `checkout.cancel` |

#### Routes API validation temps réel

| Route | Méthode | Contrôleur | Middleware | Nom de route |
|-------|---------|------------|------------|-------------|
| `/api/checkout/verify-stock` | POST | `CheckoutController@verifyStock` | `auth`, `throttle:120,1` | `api.checkout.verify-stock` |
| `/api/checkout/validate-email` | POST | `CheckoutController@validateEmail` | `auth`, `throttle:120,1` | `api.checkout.validate-email` |
| `/api/checkout/validate-phone` | POST | `CheckoutController@validatePhone` | `auth`, `throttle:120,1` | `api.checkout.validate-phone` |
| `/api/checkout/apply-promo` | POST | `CheckoutController@applyPromo` | `auth`, `throttle:120,1` | `api.checkout.apply-promo` |

#### Routes paiement carte (Stripe)

| Route | Méthode | Contrôleur | Middleware | Nom de route |
|-------|---------|------------|------------|-------------|
| `/checkout/card/pay` | POST | `CardPaymentController@pay` | `auth` | `checkout.card.pay` |
| `/checkout/card/{order}/success` | GET | `CardPaymentController@success` | `auth` | `checkout.card.success` |
| `/checkout/card/{order}/cancel` | GET | `CardPaymentController@cancel` | `auth` | `checkout.card.cancel` |

#### Routes paiement Mobile Money

| Route | Méthode | Contrôleur | Middleware | Nom de route |
|-------|---------|------------|------------|-------------|
| `/checkout/mobile-money/{order}/form` | GET | `MobileMoneyPaymentController@form` | `auth` | `checkout.mobile-money.form` |
| `/checkout/mobile-money/{order}/pay` | POST | `MobileMoneyPaymentController@pay` | `auth`, `throttle:5,1` | `checkout.mobile-money.pay` |
| `/checkout/mobile-money/{order}/pending` | GET | `MobileMoneyPaymentController@pending` | `auth` | `checkout.mobile-money.pending` |
| `/checkout/mobile-money/{order}/status` | GET | `MobileMoneyPaymentController@checkStatus` | `auth` | `checkout.mobile-money.status` |
| `/checkout/mobile-money/{order}/success` | GET | `MobileMoneyPaymentController@success` | `auth` | `checkout.mobile-money.success` |
| `/checkout/mobile-money/{order}/cancel` | GET | `MobileMoneyPaymentController@cancel` | `auth` | `checkout.mobile-money.cancel` |

#### Routes webhooks (dépréciées, migration en cours)

| Route | Méthode | Contrôleur | Middleware | Statut |
|-------|---------|------------|------------|--------|
| `/webhooks/stripe` | POST | `CardPaymentController@webhook` | `LegacyWebhookDeprecationHeaders` | ⚠️ Déprécié |
| `/payment/card/webhook` | POST | `CardPaymentController@webhook` | `LegacyWebhookDeprecationHeaders` | ⚠️ Déprécié |

**Note :** Les webhooks Stripe officiels sont gérés via `/api/webhooks/stripe` (non analysé dans ce PASS).

---

## 2. CHECKOUT CONTROLLER (BOUTIQUE)

### 2.1. Contrôleur principal

**Fichier :** `app/Http/Controllers/Front/CheckoutController.php`

**Dépendances injectées :**
- `OrderService` : Création de commandes
- `StockValidationService` : Validation du stock

---

### 2.2. Méthodes du contrôleur

#### 2.2.1. `index()` — Affichage page checkout

**Rôle :** Affiche le formulaire de checkout avec les items du panier

**Entrées :**
- Aucune (utilise `Auth::user()` et panier)

**Sortie :**
- Vue `checkout.index` avec :
  - `items` : Items du panier
  - `subtotal` : Sous-total
  - `shipping_default` : 2000 FCFA
  - `addresses` : Adresses du client
  - `defaultAddress` : Adresse par défaut
  - `user` : Utilisateur connecté

**Vérifications :**
- ✅ Authentification requise
- ✅ Rôle client uniquement
- ✅ Statut utilisateur = 'active'
- ✅ Panier non vide

**Événements :**
- `CheckoutStarted` émis pour analytics

---

#### 2.2.2. `placeOrder(PlaceOrderRequest $request)` — Création commande

**Rôle :** Crée la commande depuis le panier et redirige vers le paiement

**Entrées :**
- `PlaceOrderRequest` (validation) :
  - `full_name`, `email`, `phone`
  - `address_line1`, `city`, `country`
  - `payment_method` : `cash_on_delivery`, `card`, `mobile_money`
  - `shipping_method` : `home_delivery`, `showroom_pickup`
  - `promo_code_id` (optionnel)

**Sortie :**
- Redirection selon `payment_method` :
  - `cash_on_delivery` → `checkout.success`
  - `card` → `checkout.card.pay`
  - `mobile_money` → `payment.monetbil.start` (route non trouvée, probablement `checkout.mobile-money.form`)

**Création de commande :**
- Déléguée à `OrderService::createOrderFromCart()`
- Transaction DB avec verrouillage produits (`lockForUpdate()`)
- Validation stock avant création
- Calcul montants (subtotal, shipping, total)
- Création `Order` + `OrderItem`
- Vidage panier après succès

**Gestion erreurs :**
- `OrderException` → Redirection checkout avec message utilisateur
- `StockException` → Redirection checkout avec message utilisateur
- `\Throwable` → Redirection checkout avec message générique + logs

**Rollback :**
- Transaction DB automatique en cas d'exception
- Panier non vidé si échec

---

#### 2.2.3. `success(Order $order)` — Page succès

**Rôle :** Affiche la page de confirmation après commande

**Entrées :**
- `Order $order` (route model binding)

**Sortie :**
- Vue `checkout.success` avec `$order` (items + address chargés)

**Autorisation :**
- `OrderPolicy::view()` (vérifie que l'utilisateur est propriétaire)

---

#### 2.2.4. `cancel(Order $order)` — Page annulation

**Rôle :** Affiche la page d'annulation de paiement

**Entrées :**
- `Order $order`

**Sortie :**
- Vue `checkout.cancel` avec `$order` et `$paymentMethod`

---

#### 2.2.5. `verifyStock(Request $request)` — API validation stock

**Rôle :** Vérifie le stock en temps réel (AJAX)

**Entrées :**
- Aucune (utilise panier session/DB)

**Sortie :**
- JSON avec `has_issues`, `issues[]`, `items[]`

**Délégation :**
- `StockValidationService::checkStockIssues()`

---

#### 2.2.6. `applyPromo(Request $request)` — API code promo

**Rôle :** Applique un code promo et calcule la réduction

**Entrées :**
- `code` : Code promo
- `total` : Montant total

**Sortie :**
- JSON avec `success`, `discount_amount`, `free_shipping`, `promo_code`

**Validation :**
- Code existe et actif
- Dates valides (`starts_at`, `expires_at`)
- `max_uses` non atteint
- `max_uses_per_user` respecté
- `min_amount` atteint

---

### 2.3. Happy Path (10 étapes)

```
1. Client accède à /checkout (GET)
   → CheckoutController@index
   → Vérifie auth, rôle, panier
   → Affiche formulaire checkout

2. Client remplit formulaire et soumet (POST /checkout)
   → CheckoutController@placeOrder
   → Validation PlaceOrderRequest

3. OrderService::createOrderFromCart()
   → Transaction DB commence
   → Validation stock avec lockForUpdate()
   → Calcul montants (subtotal, shipping, total)

4. Création Order (status='pending', payment_status='pending')
   → Order::create() avec withoutEvents()
   → Génération order_number et qr_token

5. Création OrderItem pour chaque produit
   → OrderItem::create() avec prix au moment de la commande

6. OrderObserver@created() déclenché manuellement
   → Si cash_on_delivery : décrément stock immédiat
   → Email confirmation
   → Notification client + équipe

7. Panier vidé (cartService->clear())

8. Redirection selon payment_method :
   a) cash_on_delivery → checkout.success
   b) card → checkout.card.pay
   c) mobile_money → checkout.mobile-money.form

9. Paiement (card ou mobile_money) :
   → Création Payment (status='initiated')
   → Redirection vers provider (Stripe/Monetbil)

10. Webhook/Callback reçu :
    → CardPaymentService::handleWebhook() OU
    → MobileMoneyPaymentService::handleCallback()
    → Payment::update(status='paid')
    → Order::update(payment_status='paid', status='processing')
    → OrderObserver@handlePaymentStatusChange()
    → Décrément stock (si card/mobile_money)
    → Points fidélité
    → Notification client
```

---

## 3. VUES CHECKOUT

### 3.1. Fichiers Blade identifiés

| Fichier | Route associée | Description |
|---------|----------------|-------------|
| `resources/views/checkout/index.blade.php` | `checkout.index` | Formulaire checkout principal |
| `resources/views/checkout/success.blade.php` | `checkout.success` | Page confirmation commande |
| `resources/views/checkout/cancel.blade.php` | `checkout.cancel` | Page annulation paiement |
| `resources/views/frontend/checkout/card-success.blade.php` | `checkout.card.success` | Succès paiement carte |
| `resources/views/frontend/checkout/card-cancel.blade.php` | `checkout.card.cancel` | Annulation paiement carte |
| `resources/views/frontend/checkout/mobile-money-form.blade.php` | `checkout.mobile-money.form` | Formulaire Mobile Money |
| `resources/views/frontend/checkout/mobile-money-pending.blade.php` | `checkout.mobile-money.pending` | Attente confirmation MM |
| `resources/views/frontend/checkout/mobile-money-success.blade.php` | `checkout.mobile-money.success` | Succès paiement MM |
| `resources/views/frontend/checkout/mobile-money-cancel.blade.php` | `checkout.mobile-money.cancel` | Annulation paiement MM |

---

### 3.2. Vue principale : `checkout/index.blade.php`

#### Choix méthode de paiement

Le formulaire contient un champ radio pour `payment_method` :
- `cash_on_delivery` : Paiement à la livraison
- `card` : Carte bancaire (Stripe)
- `mobile_money` : Mobile Money (Monetbil)

#### Construction identifiants

**`order_number` :**
- Généré dans `Order::booted()` via `OrderNumberService::generateOrderNumber()`
- Format non visible dans le code analysé (probablement séquentiel ou UUID)

**`qr_token` :**
- Généré dans `Order::booted()` via `Order::generateUniqueQrToken()`
- UUID v4 (`Str::uuid()->toString()`)

**`payment_ref` :**
- Pour Stripe : `external_reference` = `session_id` (Stripe Checkout Session)
- Pour Mobile Money : `external_reference` = `transaction_id` (Monetbil)

**`external_reference` :**
- Stocké dans `Payment.external_reference`
- Clé de liaison avec le provider (Stripe session_id ou Monetbil transaction_id)

---

### 3.3. Endpoints API appelés depuis la vue

D'après les routes identifiées, les endpoints suivants sont disponibles :

| Endpoint | Méthode | Usage |
|----------|---------|-------|
| `/api/checkout/verify-stock` | POST | Vérification stock avant soumission |
| `/api/checkout/validate-email` | POST | Validation email en temps réel |
| `/api/checkout/validate-phone` | POST | Validation téléphone en temps réel |
| `/api/checkout/apply-promo` | POST | Application code promo |

**Note :** Le code JavaScript dans les vues n'a pas été analysé en détail (hors scope PASS 2).

---

## 4. STATUTS : SOURCE OF TRUTH

### 4.1. Définition des statuts

#### `orders.status`

**Valeurs possibles :**
- `pending` : Commande créée, en attente
- `processing` : Commande en préparation
- `shipped` : Commande expédiée
- `completed` : Commande livrée
- `cancelled` : Commande annulée

**Défini dans :**
- Migration : `2025_11_23_000004_create_orders_table.php` (default: `'pending'`)
- Modèle : `app/Models/Order.php` (fillable, pas de cast enum)

#### `orders.payment_status`

**Valeurs possibles :**
- `pending` : Paiement en attente
- `paid` : Paiement reçu
- `failed` : Paiement échoué
- `refunded` : Paiement remboursé

**Défini dans :**
- Migration : `2025_11_23_000007_add_payment_status_to_orders_table.php` (default: `'pending'`)
- Modèle : `app/Models/Order.php` (fillable, pas de cast enum)

---

### 4.2. Où et quand `orders.payment_status` change

#### Au moment de `placeOrder` (CheckoutController)

**Valeur initiale :**
```php
// OrderService::createOrderFromCart()
Order::create([
    'payment_status' => 'pending',  // Toujours 'pending' à la création
    'status' => 'pending',
]);
```

**Conclusion :** `payment_status` reste `'pending'` à la création de commande.

---

#### Au moment du retour success/cancel (CardPaymentController)

**`CardPaymentController@success()` :**
- Ne modifie PAS `payment_status`
- Affiche uniquement la vue de succès

**`CardPaymentController@cancel()` :**
- Ne modifie PAS `payment_status`
- Affiche uniquement la vue d'annulation

**Conclusion :** Les pages success/cancel ne modifient PAS `payment_status`.

---

#### Au moment du webhook Stripe (CardPaymentService)

**Fichier :** `app/Services/Payments/CardPaymentService.php`

**Méthode :** `handleCheckoutSessionCompleted()` et `handlePaymentIntentSucceeded()`

```php
// CardPaymentService::handleCheckoutSessionCompleted()
$order->update([
    'payment_status' => 'paid',      // ✅ MODIFIÉ ICI
    'status' => 'processing',
]);
```

**Événements Stripe traités :**
- `checkout.session.completed` → `handleCheckoutSessionCompleted()`
- `payment_intent.succeeded` → `handlePaymentIntentSucceeded()`

**Conclusion :** Le webhook Stripe modifie `payment_status` à `'paid'`.

---

#### Au moment du callback Mobile Money (MobileMoneyPaymentService)

**Fichier :** `app/Services/Payments/MobileMoneyPaymentService.php`

**Méthode :** `updatePaymentStatus()`

```php
// MobileMoneyPaymentService::updatePaymentStatus()
if ($order && $order->payment_status !== 'paid') {
    $order->update([
        'payment_status' => 'paid',   // ✅ MODIFIÉ ICI
        'status' => 'processing',
    ]);
}
```

**Conclusion :** Le callback Mobile Money modifie `payment_status` à `'paid'`.

---

#### Fallback dans MobileMoneyPaymentController@success()

**Fichier :** `app/Http/Controllers/Front/MobileMoneyPaymentController.php`

```php
// MobileMoneyPaymentController::success()
if ($order->payment_status !== 'paid') {
    $order->update([
        'payment_status' => 'paid',   // ⚠️ FALLBACK (si callback échoue)
        'status' => 'processing',
    ]);
}
```

**Conclusion :** Un fallback existe dans la page success Mobile Money (si callback n'a pas fonctionné).

---

### 4.3. Source of truth actuelle

#### ✅ WEBHOOK = VÉRITÉ (pour Stripe)

**Raison :**
- Le webhook Stripe (`CardPaymentService::handleWebhook()`) est la seule source qui modifie `payment_status` à `'paid'` pour les paiements carte
- Idempotence garantie via `StripeWebhookEvent.event_id` (unique)
- Transaction DB avec `lockForUpdate()` pour éviter race conditions
- Vérification signature en production

**Flux :**
```
Stripe → Webhook → CardPaymentService::handleWebhook()
                → Payment::update(status='paid')
                → Order::update(payment_status='paid')
                → OrderObserver@handlePaymentStatusChange()
                → Décrément stock + Points fidélité
```

---

#### ✅ CALLBACK = VÉRITÉ (pour Mobile Money)

**Raison :**
- Le callback Mobile Money (`MobileMoneyPaymentService::handleCallback()`) est la source principale
- Idempotence garantie via vérification `payment.status === 'paid'` avant update
- Transaction DB avec `lockForUpdate()`
- Vérification signature (si configurée)

**Flux :**
```
Monetbil → Callback → MobileMoneyPaymentService::handleCallback()
                    → Payment::update(status='paid')
                    → Order::update(payment_status='paid')
                    → OrderObserver@handlePaymentStatusChange()
                    → Décrément stock + Points fidélité
```

**⚠️ Fallback :** La page `checkout.mobile-money.success` peut mettre à jour `payment_status` si le callback n'a pas fonctionné (cas limite).

---

#### ❌ SUCCESS PAGE ≠ VÉRITÉ (sauf fallback Mobile Money)

**Raison :**
- `CardPaymentController@success()` ne modifie PAS `payment_status`
- L'utilisateur peut accéder à la page success même si le webhook n'a pas encore été reçu
- La page success est uniquement informative

**Risque :**
- Si le webhook est retardé, l'utilisateur voit "succès" mais `payment_status` reste `'pending'`
- Le décrément stock et les points fidélité ne sont pas attribués jusqu'à réception du webhook

---

### 4.4. Conclusion source of truth

| Provider | Source of truth | Fallback | Risque |
|----------|-----------------|----------|--------|
| **Stripe (card)** | ✅ Webhook | ❌ Aucun | ⚠️ Délai possible entre success page et webhook |
| **Mobile Money** | ✅ Callback | ✅ Success page (si callback échoue) | ⚠️ Fallback peut masquer un problème callback |
| **Cash on delivery** | ✅ Commande créée | ❌ Aucun | ✅ Pas de risque (pas de paiement en ligne) |

**Recommandation :**
- Pour Stripe : Ajouter un polling ou un fallback dans `CardPaymentController@success()` si `payment_status !== 'paid'` après X secondes
- Pour Mobile Money : Le fallback existe déjà, mais il faudrait logger quand il est utilisé pour détecter les problèmes de callback

---

## 5. LIEN AVEC TABLES PAIEMENT

### 5.1. Usage de la table `payments`

#### Création

**Stripe (CardPaymentService) :**
```php
// CardPaymentService::createCheckoutSession()
Payment::create([
    'order_id' => $order->id,
    'provider' => 'stripe',
    'channel' => 'card',
    'status' => 'initiated',
    'amount' => $order->total_amount,
    'currency' => 'XAF',
    'external_reference' => $session->id,  // Stripe session_id
    'provider_payment_id' => $session->payment_intent,
]);
```

**Mobile Money (MobileMoneyPaymentService) :**
```php
// MobileMoneyPaymentService::initiatePayment()
Payment::create([
    'order_id' => $order->id,
    'provider' => $provider,  // 'mtn_momo' ou 'airtel_money'
    'channel' => 'mobile_money',
    'status' => 'initiated',
    'amount' => $order->total_amount,
    'currency' => 'XAF',
    'external_reference' => $transactionId,  // Monetbil transaction_id
]);
```

#### Mise à jour statut

**Stripe (via webhook) :**
```php
// CardPaymentService::handleCheckoutSessionCompleted()
$payment->update([
    'status' => 'paid',
    'paid_at' => now(),
]);
```

**Mobile Money (via callback) :**
```php
// MobileMoneyPaymentService::updatePaymentStatus()
$payment->update([
    'status' => 'paid',
    'paid_at' => now(),
]);
```

---

### 5.2. Usage de la table `payment_transactions`

#### ⚠️ NON UTILISÉE PAR LA BOUTIQUE RACINE

**Observation :**
- La table `payment_transactions` existe et est utilisée pour Monetbil dans d'autres parties du code
- **MAIS** le checkout boutique RACINE utilise uniquement la table `payments`
- `PaymentTransaction` est probablement utilisé pour un ancien système ou pour d'autres flux

**Preuve :**
- `CheckoutController` ne crée jamais de `PaymentTransaction`
- `CardPaymentService` ne crée jamais de `PaymentTransaction`
- `MobileMoneyPaymentService` ne crée jamais de `PaymentTransaction` (utilise `Payment`)

**Conclusion :** La boutique RACINE utilise **UNIQUEMENT** la table `payments`.

---

### 5.3. Relation Order ↔ Payment

#### Clé de liaison

**Foreign Key :**
- `payments.order_id` → `orders.id` (cascadeOnDelete)

**Relation Eloquent :**
```php
// Order.php
public function payments(): HasMany
{
    return $this->hasMany(Payment::class);
}

// Payment.php
public function order(): BelongsTo
{
    return $this->belongsTo(Order::class);
}
```

#### Champs de liaison avec providers

**Stripe :**
- `Payment.external_reference` = `session_id` (Stripe Checkout Session)
- `Payment.provider_payment_id` = `payment_intent` (Stripe Payment Intent)
- Recherche dans webhook : `Payment::where('external_reference', $sessionId)`

**Mobile Money :**
- `Payment.external_reference` = `transaction_id` (Monetbil)
- Recherche dans callback : `Payment::where('external_reference', $transactionId)`

---

### 5.4. Réponse aux questions

#### La boutique RACINE utilise-t-elle les deux tables ?

**Réponse :** ❌ NON

- ✅ **`payments`** : Utilisée pour Stripe ET Mobile Money
- ❌ **`payment_transactions`** : NON utilisée par le checkout boutique

**Note :** `payment_transactions` est probablement utilisée ailleurs (ancien système Monetbil ou autres flux).

---

#### Pour quels providers ?

**Réponse :**
- **Stripe** : Utilise `payments` uniquement
- **Mobile Money (Monetbil)** : Utilise `payments` uniquement
- **Cash on delivery** : Aucune table paiement (pas de paiement en ligne)

---

#### Comment une order se relie aux transactions ?

**Réponse :**
- Via `payments.order_id` (Foreign Key)
- Une commande peut avoir plusieurs `Payment` (en cas de tentatives multiples)
- Le Payment actif est celui avec `status='paid'` et `channel` correspondant

**Exemple :**
```php
$order = Order::find(123);
$paidPayment = $order->payments()
    ->where('status', 'paid')
    ->where('channel', 'card')
    ->first();
```

---

#### Quels champs sont la clé ?

**Réponse :**

| Champ | Usage | Exemple |
|-------|-------|---------|
| `payments.order_id` | ✅ Clé principale (FK) | `123` |
| `payments.external_reference` | ✅ Clé provider (Stripe session_id, Monetbil transaction_id) | `cs_test_...` ou `TXN_...` |
| `payments.provider_payment_id` | ✅ ID paiement provider (Stripe payment_intent) | `pi_...` |
| `payment_transactions.payment_ref` | ❌ Non utilisé par checkout boutique | - |
| `payment_transactions.order_id` | ❌ Non utilisé par checkout boutique | - |

**Conclusion :** Les clés principales sont `payments.order_id` (FK) et `payments.external_reference` (liaison provider).

---

## A. RÉSUMÉ EXÉCUTIF

Le checkout boutique RACINE utilise un flux standard : création commande (`Order`) → initiation paiement (`Payment`) → webhook/callback → mise à jour statuts. La source de vérité pour `payment_status` est le **webhook Stripe** ou le **callback Mobile Money**, pas les pages success. La table `payments` est utilisée pour tous les paiements en ligne, tandis que `payment_transactions` n'est pas utilisée par le checkout. Un fallback existe pour Mobile Money dans la page success, mais pas pour Stripe, ce qui peut créer un délai entre l'affichage "succès" et la confirmation réelle du paiement.

---

## B. FICHIERS CRITIQUES CHECKOUT

### Contrôleurs
- `app/Http/Controllers/Front/CheckoutController.php`
- `app/Http/Controllers/Front/CardPaymentController.php`
- `app/Http/Controllers/Front/MobileMoneyPaymentController.php`

### Services
- `app/Services/OrderService.php`
- `app/Services/Payments/CardPaymentService.php`
- `app/Services/Payments/MobileMoneyPaymentService.php`
- `app/Services/StockValidationService.php`

### Observers
- `app/Observers/OrderObserver.php`

### Modèles
- `app/Models/Order.php`
- `app/Models/Payment.php`
- `app/Models/OrderItem.php`

### Vues
- `resources/views/checkout/index.blade.php`
- `resources/views/checkout/success.blade.php`
- `resources/views/frontend/checkout/card-success.blade.php`
- `resources/views/frontend/checkout/mobile-money-form.blade.php`

### Routes
- `routes/web.php` (lignes 410-449)

### Requests
- `app/Http/Requests/PlaceOrderRequest.php`

---

## C. POINTS DE FRICTION / RISQUES

### 🔴 Critique

1. **Pas de fallback pour Stripe** : Si le webhook est retardé, l'utilisateur voit "succès" mais `payment_status` reste `'pending'`, le stock n'est pas décrémenté et les points fidélité ne sont pas attribués.

2. **Double table paiement** : `payments` et `payment_transactions` coexistent, créant confusion sur quelle table utiliser. Le checkout utilise uniquement `payments`, mais `payment_transactions` existe toujours.

### 🟡 Moyen

3. **Route Mobile Money incorrecte** : Dans `CheckoutController@redirectToPayment()`, la redirection pour `mobile_money` pointe vers `payment.monetbil.start` qui n'existe pas. Devrait être `checkout.mobile-money.form`.

4. **Fallback Mobile Money masque les problèmes** : Le fallback dans `MobileMoneyPaymentController@success()` peut masquer des échecs de callback sans logging approprié.

5. **Pas de vérification idempotence côté client** : Les pages success ne vérifient pas si le paiement est déjà traité avant d'afficher le message.

### 🟢 Mineur

6. **Logs verbeux en production** : `CheckoutController@placeOrder()` contient beaucoup de logs qui pourraient être réduits en production.

7. **Validation stock côté client optionnelle** : L'endpoint `/api/checkout/verify-stock` existe mais n'est peut-être pas utilisé systématiquement dans la vue.

---

## D. RECOMMANDATIONS IMMÉDIATES

### 1. Ajouter fallback pour Stripe

Dans `CardPaymentController@success()`, vérifier `payment_status` et si `'pending'` après X secondes, faire un polling ou afficher un message "Vérification en cours".

### 2. Corriger route Mobile Money

Dans `CheckoutController@redirectToPayment()`, remplacer `payment.monetbil.start` par `checkout.mobile-money.form`.

### 3. Logger les fallbacks Mobile Money

Dans `MobileMoneyPaymentController@success()`, logger quand le fallback est utilisé pour détecter les problèmes de callback.

### 4. Clarifier usage `payment_transactions`

Documenter ou supprimer `payment_transactions` si elle n'est plus utilisée, ou créer une migration pour migrer les données vers `payments`.

### 5. Ajouter vérification idempotence

Dans les pages success, vérifier si `payment_status === 'paid'` avant d'afficher le message de succès, pour éviter les doublons.

### 6. Réduire logs en production

Utiliser `Log::debug()` au lieu de `Log::info()` pour les logs de traçage dans `CheckoutController@placeOrder()`.

---

**FIN DU RAPPORT — PASS 2/3**

