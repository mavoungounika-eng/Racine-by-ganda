# 📊 PHASE 1 — RAPPORT D'ANALYSE CHECKOUT & PAIEMENT
## RACINE BY GANDA (Laravel 12)

**Date :** 2025-01-XX  
**Niveau :** CTO / Architecture Review  
**Objectif :** Audit complet du circuit Checkout & Paiement avant sécurisation

---

## 🎯 RÉSUMÉ EXÉCUTIF

### État Actuel
- ✅ **Checkout** : Opérationnel avec protection double soumission
- ✅ **Stripe** : Intégration complète avec webhooks idempotents
- ✅ **Mobile Money (Monetbil)** : Infrastructure en place, webhooks sécurisés
- ✅ **Stock** : Décrément conditionnel (cash = immédiat, card/MM = au paiement)
- ✅ **Webhooks** : Pattern idempotent avec jobs asynchrones

### Points Critiques Identifiés
1. ⚠️ **Race conditions** : Protection partielle (lockForUpdate présent mais pas partout)
2. ⚠️ **Double paiement** : Protection au niveau checkout, mais pas au niveau webhook
3. ⚠️ **Stock** : Décrément différé pour card/MM peut créer des oversells
4. ⚠️ **Webhooks** : Idempotence OK, mais pas de rollback automatique si échec

---

## 1️⃣ CHECKOUT — ANALYSE DÉTAILLÉE

### 1.1. CheckoutController@index()

**Fichier :** `app/Http/Controllers/Front/CheckoutController.php` (lignes 42-85)

**Fonctionnalités :**
- ✅ Vérification authentification
- ✅ Vérification rôle client (`isClient()`)
- ✅ Vérification statut actif
- ✅ Chargement panier (DB ou Session)
- ✅ Génération token unique pour protection double soumission
- ✅ Émission événement `CheckoutStarted` (analytics)

**Points Positifs :**
- Séparation claire DB/Session cart
- Protection CSRF via token unique
- Logging structuré

**Points d'Attention :**
- Pas de validation stock en temps réel à l'affichage (seulement au submit)
- Token stocké en session (peut expirer si session timeout)

**Verdict :** ✅ **FONCTIONNEL**

---

### 1.2. CheckoutController@placeOrder()

**Fichier :** `app/Http/Controllers/Front/CheckoutController.php` (lignes 102-268)

**Flux Complet :**
```
1. Vérification token unique (_checkout_token)
2. Validation données (PlaceOrderRequest)
3. Chargement panier
4. Vérification ownership panier
5. Appel OrderService::createOrderFromCart()
6. Vidage panier
7. Suppression token
8. Redirection selon payment_method
```

**Protections Implémentées :**
- ✅ **Double soumission** : Token unique vérifié avant création commande
- ✅ **Ownership panier** : Vérification user_id sur cart et items
- ✅ **Idempotence** : OrderService vérifie commande existante (5 min, même montant)
- ✅ **Transactions DB** : OrderService utilise DB::transaction()

**Points Critiques :**

#### 🔴 CRITIQUE 1 : Race Condition Panier
```php
// Ligne 142 : Chargement panier
$items = $cartService->getItems();

// Ligne 199 : Création commande (dans transaction)
$order = $this->orderService->createOrderFromCart(...);

// Ligne 221 : Vidage panier (HORS transaction)
$cartService->clear();
```

**Problème :** Le panier est vidé APRÈS la transaction. Si la redirection échoue, le panier est vidé mais la commande peut ne pas être visible.

**Impact :** Moyen (UX dégradée, pas de perte financière)

**Recommandation :** Déplacer `clear()` dans la transaction ou après confirmation de redirection.

---

#### 🔴 CRITIQUE 2 : Validation Stock Différée
```php
// OrderService::createOrderFromCart() valide le stock AVANT création
// Mais si 2 clients achètent le dernier produit en même temps :
// - Client A : Validation OK → Création commande
// - Client B : Validation OK → Création commande (RACE CONDITION)
```

**Impact :** Élevé (oversell possible)

**Recommandation :** Utiliser `lockForUpdate()` sur les produits lors de la validation stock (déjà partiellement implémenté dans StockValidationService).

---

### 1.3. CheckoutController@redirectToPayment()

**Fichier :** `app/Http/Controllers/Front/CheckoutController.php` (lignes 277-364)

**Fonctionnalités :**
- Redirection selon `payment_method` :
  - `cash_on_delivery` → `checkout.success`
  - `card` → `checkout.card.pay`
  - `mobile_money` / `monetbil` → `payment.monetbil.start`

**Points Positifs :**
- Gestion d'erreurs avec fallback
- Logging détaillé

**Points d'Attention :**
- Pas de vérification que la commande a bien un `payment_status='pending'` avant redirection
- Pas de protection contre double redirection

**Verdict :** ✅ **FONCTIONNEL** (améliorable)

---

## 2️⃣ PAIEMENT STRIPE — ANALYSE DÉTAILLÉE

### 2.1. CardPaymentService@createCheckoutSession()

**Fichier :** `app/Services/Payments/CardPaymentService.php` (lignes 32-130)

**Flux :**
```
1. Vérification configuration Stripe
2. Calcul montant en centimes
3. Création Payment (status='initiated')
4. Création session Stripe Checkout
5. Mise à jour Payment avec session_id
```

**Points Positifs :**
- ✅ Création Payment AVANT session Stripe (traçabilité)
- ✅ Gestion erreurs avec rollback (mise à jour Payment en 'failed')
- ✅ Métadonnées complètes (order_id, payment_id)

**Points Critiques :**

#### 🔴 CRITIQUE 3 : Pas de Vérification Double Paiement
```php
// Ligne 51 : Création Payment sans vérifier si un Payment existe déjà
$payment = Payment::create([
    'order_id' => $order->id,
    'status' => 'initiated',
    ...
]);
```

**Problème :** Si l'utilisateur clique 2 fois sur "Payer", 2 Payments sont créés pour la même commande.

**Impact :** Moyen (pas de double débit, mais confusion)

**Recommandation :** Vérifier si un Payment 'initiated' ou 'paid' existe déjà pour cette commande.

---

### 2.2. CardPaymentService@handleWebhook()

**Fichier :** `app/Services/Payments/CardPaymentService.php` (lignes 153-448)

**Flux :**
```
1. Vérification signature (OBLIGATOIRE en production)
2. Extraction event_id et event_type
3. Insert-first StripeWebhookEvent (idempotence)
4. Recherche Payment par session_id ou payment_intent_id
5. Lock Payment (lockForUpdate)
6. Vérification si déjà payé
7. Traitement événement (checkout.session.completed, payment_intent.succeeded)
8. Mise à jour Order (payment_status='paid', status='processing')
```

**Points Positifs :**
- ✅ **Idempotence** : Insert-first avec vérification duplicate
- ✅ **Race condition** : Lock Payment avant traitement
- ✅ **Signature** : Vérification obligatoire en production
- ✅ **Logging** : Structuré avec IP, route, reason

**Points Critiques :**

#### 🔴 CRITIQUE 4 : Webhook Non Idempotent au Niveau Payment
```php
// Ligne 390 : Vérification si déjà payé
if ($payment->status === 'paid') {
    // Retourne le Payment mais ne vérifie pas si Order est déjà payé
    return $payment;
}
```

**Problème :** Si 2 webhooks arrivent en même temps :
- Webhook 1 : Payment.status='paid' → Order.payment_status='paid'
- Webhook 2 : Payment.status='paid' → Order.payment_status='paid' (double update)

**Impact :** Faible (pas de double débit, mais double update inutile)

**Recommandation :** Vérifier `Order.payment_status` avant mise à jour.

---

#### 🔴 CRITIQUE 5 : Pas de Rollback si Échec
```php
// Si le webhook échoue après mise à jour Payment mais avant Order
// Le Payment est 'paid' mais l'Order reste 'pending'
```

**Impact :** Élevé (incohérence données)

**Recommandation :** Utiliser transaction DB pour Payment + Order update.

---

### 2.3. WebhookController@stripe()

**Fichier :** `app/Http/Controllers/Api/WebhookController.php` (lignes 32-293)

**Pattern :** Verify → Persist Event → Dispatch Job → Return 200

**Points Positifs :**
- ✅ **Idempotence** : `firstOrCreate` sur `event_id`
- ✅ **Atomic claim** : `dispatched_at` pour éviter double dispatch
- ✅ **Retry** : Redispatch si `status='failed'` et `dispatched_at < 5 min`

**Points d'Attention :**
- Job asynchrone : Le webhook retourne 200 avant traitement complet
- Pas de garantie de traitement (si job échoue, pas de retry automatique au-delà de 5 min)

**Verdict :** ✅ **FONCTIONNEL** (pattern moderne, mais dépend de la queue)

---

## 3️⃣ PAIEMENT MOBILE MONEY (MONETBIL) — ANALYSE DÉTAILLÉE

### 3.1. MonetbilController@start()

**Fichier :** `app/Http/Controllers/Payments/MonetbilController.php` (lignes 37-141)

**Flux :**
```
1. Vérification accès commande (authorize)
2. Protection double paiement (vérification payment_status)
3. Vérification transaction existante (pending)
4. Création/mise à jour PaymentTransaction
5. Création URL paiement Monetbil
6. Redirection vers Monetbil
```

**Points Positifs :**
- ✅ Protection double paiement au niveau Order
- ✅ Réutilisation transaction existante (idempotence)
- ✅ Logging structuré

**Points Critiques :**

#### 🔴 CRITIQUE 6 : Pas de Lock sur Order
```php
// Ligne 43 : Vérification payment_status sans lock
if ($order->payment_status === 'paid') {
    return redirect()->route('checkout.success');
}
```

**Problème :** Race condition si 2 requêtes simultanées :
- Requête 1 : payment_status='pending' → Création transaction
- Requête 2 : payment_status='pending' → Création transaction (DOUBLE)

**Impact :** Moyen (2 transactions pour 1 commande)

**Recommandation :** Utiliser `lockForUpdate()` sur Order avant vérification.

---

### 3.2. MonetbilController@notify()

**Fichier :** `app/Http/Controllers/Payments/MonetbilController.php` (lignes 162-448)

**Flux :**
```
1. Vérification IP (whitelist si configurée)
2. Vérification signature (OBLIGATOIRE en production)
3. Récupération payment_ref
4. Recherche PaymentTransaction
5. Vérification idempotence (isAlreadySuccessful)
6. Transaction DB + lock
7. Mise à jour PaymentTransaction
8. Si success : Mise à jour Order + Création Payment
```

**Points Positifs :**
- ✅ **Idempotence** : Vérification `isAlreadySuccessful()`
- ✅ **Race condition** : Lock Transaction avant traitement
- ✅ **Signature** : Vérification obligatoire en production
- ✅ **Codes HTTP stricts** : 401, 403, 404, 500 selon erreur

**Points Critiques :**

#### 🔴 CRITIQUE 7 : Double Création Payment Possible
```php
// Ligne 342 : Création Payment sans vérifier si existe déjà
$order->payments()->create([
    'provider' => 'monetbil',
    'status' => 'paid',
    ...
]);
```

**Problème :** Si le webhook est reçu 2 fois (retry), 2 Payments sont créés.

**Impact :** Faible (pas de double débit, mais confusion)

**Recommandation :** Vérifier si un Payment existe déjà pour cette transaction.

---

#### 🔴 CRITIQUE 8 : Pas de Transaction DB pour Order + Payment
```php
// Ligne 336 : Mise à jour Order
$order->update(['payment_status' => 'paid']);

// Ligne 342 : Création Payment (dans try/catch mais pas dans transaction)
$order->payments()->create([...]);
```

**Problème :** Si la création Payment échoue, l'Order est payé mais pas de trace Payment.

**Impact :** Moyen (incohérence données)

**Recommandation :** Envelopper Order update + Payment creation dans une transaction DB.

---

## 4️⃣ COMMANDES — ANALYSE DÉTAILLÉE

### 4.1. OrderService@createOrderFromCart()

**Fichier :** `app/Services/OrderService.php` (lignes 63-173)

**Flux :**
```
1. Vérification panier non vide
2. Idempotence : Vérification commande existante (5 min, même montant)
3. Calcul montants
4. Transaction DB :
   a. Validation stock avec lockForUpdate
   b. Création Order (withoutEvents)
   c. Création OrderItems
   d. Déclenchement OrderObserver@created
```

**Points Positifs :**
- ✅ **Transaction DB** : Atomicité garantie
- ✅ **Idempotence** : Vérification commande existante
- ✅ **Validation stock** : Avec lockForUpdate (protection race condition)

**Points Critiques :**

#### 🔴 CRITIQUE 9 : Idempotence Basée sur Montant (Approximatif)
```php
// Ligne 80 : Vérification idempotence basée sur total_amount
->where('total_amount', $this->calculateAmounts(...)['total'])
```

**Problème :** Si 2 commandes ont le même montant mais produits différents, la 2ème est ignorée.

**Impact :** Faible (cas rare)

**Recommandation :** Vérifier aussi les produits (déjà fait ligne 88-96, mais peut être amélioré).

---

### 4.2. OrderObserver@created()

**Fichier :** `app/Observers/OrderObserver.php` (lignes 33-90)

**Flux :**
```
1. Si payment_method='cash_on_delivery' :
   → Décrément stock immédiatement
2. Envoi email confirmation
3. Notification client
4. Notification équipe
5. Invalidation cache
```

**Points Positifs :**
- ✅ Décrément stock conditionnel (cash = immédiat, card/MM = au paiement)
- ✅ Gestion erreurs (continue même si décrément échoue)

**Points Critiques :**

#### 🔴 CRITIQUE 10 : Décrément Stock Hors Transaction
```php
// Ligne 44 : Décrément stock (dans try/catch mais pas dans transaction Order)
$stockService->decrementFromOrder($order);
```

**Problème :** Si le décrément échoue, la commande est créée mais le stock n'est pas décrémenté.

**Impact :** Élevé (oversell possible pour cash_on_delivery)

**Recommandation :** Déjà géré par StockService (vérification mouvement existant), mais peut être amélioré.

---

### 4.3. OrderObserver@handlePaymentStatusChange()

**Fichier :** `app/Observers/OrderObserver.php` (lignes 182-233)

**Flux :**
```
1. Si payment_status='paid' :
   → Décrément stock (pour card/mobile_money)
   → Attribution points fidélité
   → Notification client
   → Invalidation cache
```

**Points Positifs :**
- ✅ Décrément stock conditionnel (seulement si pas déjà fait)
- ✅ Gestion erreurs (continue même si décrément échoue)

**Points Critiques :**

#### 🔴 CRITIQUE 11 : Décrément Stock Différé = Risque Oversell
```php
// Pour card/mobile_money, le stock est décrémenté APRÈS paiement
// Si le paiement prend du temps, 2 clients peuvent acheter le dernier produit
```

**Impact :** Élevé (oversell possible)

**Recommandation :** 
- Option 1 : Réserver le stock à la création commande (status='reserved')
- Option 2 : Décrémenter immédiatement et réintégrer si paiement échoue

---

## 5️⃣ STOCK — ANALYSE DÉTAILLÉE

### 5.1. StockService@decrementFromOrder()

**Fichier :** `modules/ERP/Services/StockService.php` (lignes 32-86)

**Flux :**
```
1. Vérification items non vide
2. Protection double décrément (vérification mouvement existant)
3. Transaction DB :
   a. Pour chaque item :
      - Vérification stock disponible
      - Décrément stock
      - Création mouvement stock
```

**Points Positifs :**
- ✅ **Protection double décrément** : Vérification mouvement existant
- ✅ **Transaction DB** : Atomicité garantie
- ✅ **Logging** : Avertissement si stock insuffisant (backorder)

**Points Critiques :**

#### 🔴 CRITIQUE 12 : Pas de Lock sur Produit
```php
// Ligne 67 : Décrément stock sans lock
$product->decrement('stock', $item->quantity);
```

**Problème :** Si 2 commandes décrémentent en même temps, race condition possible.

**Impact :** Moyen (déjà protégé par transaction, mais lock explicite serait mieux)

**Recommandation :** Utiliser `lockForUpdate()` sur Product avant décrément.

---

## 6️⃣ WEBHOOKS — ANALYSE DÉTAILLÉE

### 6.1. Pattern Idempotence

**Stripe :**
- ✅ `StripeWebhookEvent` avec `event_id` unique
- ✅ `firstOrCreate` pour idempotence
- ✅ Atomic claim via `dispatched_at`
- ✅ Retry automatique si `status='failed'`

**Monetbil :**
- ✅ `MonetbilCallbackEvent` avec `event_key` unique (hash)
- ✅ `firstOrCreate` pour idempotence
- ✅ Atomic claim via `dispatched_at`
- ✅ Retry automatique si `status='failed'`

**Points Positifs :**
- ✅ Pattern moderne (persist → dispatch → return 200)
- ✅ Idempotence garantie
- ✅ Retry automatique

**Points d'Attention :**
- ⚠️ Dépendance queue (si queue down, pas de traitement)
- ⚠️ Pas de rollback automatique si job échoue définitivement

---

## 7️⃣ ÉTATS DE COMMANDE — ANALYSE

### États Order
- `pending` : Commande créée, paiement en attente
- `processing` : Commande payée, en préparation
- `shipped` : Commande expédiée
- `completed` : Commande livrée
- `cancelled` : Commande annulée

### États Payment
- `initiated` : Paiement initié (session créée)
- `paid` : Paiement confirmé
- `failed` : Paiement échoué
- `cancelled` : Paiement annulé

### États Order.payment_status
- `pending` : Paiement en attente
- `paid` : Paiement confirmé
- `failed` : Paiement échoué

**Points Positifs :**
- ✅ Séparation claire Order.status / Order.payment_status
- ✅ États cohérents

**Points d'Attention :**
- ⚠️ Pas de statut 'refunded' pour les remboursements
- ⚠️ Pas de statut 'expired' pour les paiements expirés

---

## 8️⃣ TRACABILITÉ COMPTABLE — ANALYSE

### Enregistrements Créés

**Order :**
- ✅ `order_number` : Numéro unique
- ✅ `qr_token` : Token QR unique
- ✅ `total_amount` : Montant total
- ✅ `payment_method` : Méthode paiement
- ✅ `payment_status` : Statut paiement

**Payment :**
- ✅ `provider` : Stripe / Monetbil
- ✅ `channel` : card / mobile_money
- ✅ `external_reference` : Référence externe (session_id, transaction_id)
- ✅ `provider_payment_id` : ID paiement provider
- ✅ `metadata` : Métadonnées complètes
- ✅ `paid_at` : Date paiement

**PaymentTransaction (Monetbil) :**
- ✅ `payment_ref` : Référence unique
- ✅ `transaction_id` : ID transaction Monetbil
- ✅ `transaction_uuid` : UUID transaction
- ✅ `raw_payload` : Payload complet

**StripeWebhookEvent :**
- ✅ `event_id` : ID événement Stripe
- ✅ `event_type` : Type événement
- ✅ `payload_hash` : Hash payload
- ✅ `checkout_session_id` : ID session
- ✅ `payment_intent_id` : ID payment intent

**Points Positifs :**
- ✅ Traçabilité complète (Order → Payment → Webhook)
- ✅ Métadonnées riches
- ✅ Logs structurés

**Points d'Attention :**
- ⚠️ Pas de table de réconciliation (Order vs Payment)
- ⚠️ Pas de table d'audit pour les changements de statut

---

## 9️⃣ MARKETPLACE — COMPATIBILITÉ CRÉATEURS

### Checkout Créateurs

**Fichier :** `app/Services/Payments/CreatorSubscriptionCheckoutService.php`

**Fonctionnalités :**
- ✅ Checkout Stripe pour abonnements créateurs
- ✅ Vérification `canCreatorReceivePayments()`
- ✅ Création session Stripe Checkout (mode subscription)
- ✅ Métadonnées complètes (creator_id, plan_id)

**Points Positifs :**
- ✅ Séparation claire (checkout clients vs créateurs)
- ✅ Vérification éligibilité avant checkout

**Points d'Attention :**
- ⚠️ Pas de gestion remboursements créateurs
- ⚠️ Pas de gestion échec paiement abonnement

**Verdict :** ✅ **FONCTIONNEL** (scope limité aux abonnements)

---

## 🔟 RÉSUMÉ DES POINTS CRITIQUES

| # | Critère | Impact | Priorité | Fichier |
|---|---------|--------|----------|---------|
| 1 | Race condition panier (clear hors transaction) | Moyen | Moyenne | CheckoutController |
| 2 | Validation stock différée (oversell possible) | Élevé | Haute | OrderService |
| 3 | Pas de vérification double paiement Stripe | Moyen | Moyenne | CardPaymentService |
| 4 | Webhook non idempotent au niveau Order | Faible | Basse | CardPaymentService |
| 5 | Pas de rollback si échec webhook | Élevé | Haute | CardPaymentService |
| 6 | Pas de lock sur Order (Monetbil start) | Moyen | Moyenne | MonetbilController |
| 7 | Double création Payment Monetbil | Faible | Basse | MonetbilController |
| 8 | Pas de transaction DB Order + Payment | Moyen | Moyenne | MonetbilController |
| 9 | Idempotence basée sur montant (approximatif) | Faible | Basse | OrderService |
| 10 | Décrément stock hors transaction | Élevé | Haute | OrderObserver |
| 11 | Décrément stock différé = risque oversell | Élevé | Haute | OrderObserver |
| 12 | Pas de lock sur Produit (décrément) | Moyen | Moyenne | StockService |

---

## ✅ RECOMMANDATIONS PRIORITAIRES

### Priorité HAUTE
1. **Protection oversell** : Lock produits lors validation stock
2. **Rollback webhook** : Transaction DB pour Payment + Order update
3. **Décrément stock** : Lock produit avant décrément

### Priorité MOYENNE
4. **Double paiement** : Vérification Payment existant avant création
5. **Lock Order** : Lock Order avant vérification payment_status (Monetbil)
6. **Transaction Order + Payment** : Envelopper dans transaction DB

### Priorité BASSE
7. **Idempotence webhook Order** : Vérifier Order.payment_status avant update
8. **Idempotence commande** : Améliorer vérification produits

---

## 📋 CHECKLIST PHASE 2 — POINTS CRITIQUES À CORRIGER

- [ ] **Double paiement** : Vérification Payment existant (Stripe + Monetbil)
- [ ] **Paiement sans commande** : Vérification Order existe avant création Payment
- [ ] **Commande sans paiement** : Vérification Payment existe avant update Order
- [ ] **Race conditions** : Lock Order + Product partout nécessaire
- [ ] **Webhooks non idempotents** : Vérification Order.payment_status avant update
- [ ] **Perte de stock** : Lock Product avant décrément + transaction DB

---

## 🎯 CONCLUSION

Le système est **globalement fonctionnel** avec une architecture solide :
- ✅ Idempotence webhooks
- ✅ Protection double soumission checkout
- ✅ Traçabilité complète
- ✅ Séparation responsabilités (Services, Observers)

**Points à améliorer :**
- ⚠️ Protection race conditions (locks manquants)
- ⚠️ Rollback transactions (webhooks)
- ⚠️ Protection oversell (locks produits)

**Recommandation :** Procéder à la **Phase 2** pour corriger les points critiques identifiés.

---

**Fin du rapport Phase 1**



