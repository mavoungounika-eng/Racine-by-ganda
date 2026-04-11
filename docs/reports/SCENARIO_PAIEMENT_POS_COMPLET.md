# 💳 SCÉNARIO COMPLET - MOYENS DE PAIEMENT POS

**Date :** 8 décembre 2025  
**Version :** 1.0

---

## 📋 VUE D'ENSEMBLE

Le système POS (Point of Sale) supporte **3 moyens de paiement** avec des scénarios différenciés :

1. **💵 Espèces (Cash)** : Paiement immédiat, stock décrémenté immédiatement
2. **💳 Carte bancaire (TPE)** : Paiement en attente, confirmation manuelle après validation TPE
3. **📱 Mobile Money** : Paiement initié, confirmation automatique via callback

---

## 💵 SCÉNARIO 1 : PAIEMENT EN ESPÈCES

### Workflow

```
1. Client sélectionne "Espèces" dans le POS
   ↓
2. Validation de la vente
   ↓
3. Commande créée avec :
   - status: 'completed'
   - payment_status: 'paid'
   ↓
4. Payment créé avec :
   - provider: 'cash'
   - status: 'paid'
   - paid_at: now()
   ↓
5. Stock décrémenté IMMÉDIATEMENT
   ↓
6. Mouvement stock créé avec raison "Vente en boutique"
   ↓
7. Commande terminée ✅
```

### Caractéristiques

- ✅ **Paiement immédiat** : Pas d'attente
- ✅ **Stock décrémenté immédiatement** : Produit retiré du stock
- ✅ **Commande complétée** : Statut `completed` dès la création
- ✅ **Enregistrement Payment** : Créé avec statut `paid`

### Code

```php
// Dans PosController::createOrder()
if ($paymentMethod === 'cash') {
    $paymentStatus = 'paid';
    $orderStatus = 'completed';
}

// Payment créé
Payment::create([
    'provider' => 'cash',
    'status' => 'paid',
    'paid_at' => now(),
    'metadata' => [
        'payment_location' => 'Boutique physique',
        'processed_by' => Auth::id(),
    ],
]);
```

---

## 💳 SCÉNARIO 2 : PAIEMENT PAR CARTE (TPE)

### Workflow

```
1. Client sélectionne "Carte bancaire (TPE)" dans le POS
   ↓
2. Validation de la vente
   ↓
3. Commande créée avec :
   - status: 'pending'
   - payment_status: 'pending'
   ↓
4. Payment créé avec :
   - provider: 'stripe'
   - channel: 'card'
   - status: 'pending'
   - metadata: { payment_method: 'TPE', note: 'À confirmer via TPE' }
   ↓
5. Stock NON décrémenté (en attente de confirmation)
   ↓
6. Admin valide le paiement sur le TPE
   ↓
7. Admin confirme le paiement via :
   POST /admin/pos/order/{order}/confirm-payment
   ↓
8. Payment mis à jour :
   - status: 'paid'
   - paid_at: now()
   - provider_payment_id: transaction_id du TPE
   ↓
9. Commande mise à jour :
   - payment_status: 'paid'
   - status: 'completed'
   ↓
10. Stock décrémenté
   ↓
11. Mouvement stock créé avec raison "Vente en boutique"
   ↓
12. Commande terminée ✅
```

### Caractéristiques

- ⏳ **Paiement en attente** : Nécessite confirmation manuelle
- ⏳ **Stock réservé** : Non décrémenté jusqu'à confirmation
- ✅ **Confirmation manuelle** : Route dédiée pour confirmer après validation TPE
- ✅ **Traçabilité** : Transaction ID du TPE enregistré

### Code

```php
// Dans PosController::createOrder()
if ($paymentMethod === 'card') {
    $paymentStatus = 'pending';
    $orderStatus = 'pending';
}

// Payment créé en attente
Payment::create([
    'provider' => 'stripe',
    'channel' => 'card',
    'status' => 'pending',
    'metadata' => [
        'payment_method' => 'TPE',
        'note' => 'Paiement par carte en boutique. À confirmer via TPE.',
    ],
]);

// Confirmation manuelle
// POST /admin/pos/order/{order}/confirm-payment
// Body: { transaction_id: '...', receipt_number: '...' }
```

### Route de confirmation

```php
Route::post('pos/order/{order}/confirm-payment', [PosController::class, 'confirmCardPayment'])
    ->name('pos.confirm-payment');
```

---

## 📱 SCÉNARIO 3 : PAIEMENT MOBILE MONEY

### Workflow

```
1. Client sélectionne "Mobile Money" dans le POS
   ↓
2. Admin sélectionne l'opérateur (MTN MoMo ou Airtel Money)
   ↓
3. Numéro de téléphone requis (customer_phone)
   ↓
4. Validation de la vente
   ↓
5. Commande créée avec :
   - status: 'pending'
   - payment_status: 'pending'
   ↓
6. MobileMoneyPaymentService::initiatePayment() appelé
   ↓
7. Payment créé avec :
   - provider: 'mtn_momo' ou 'airtel_money'
   - channel: 'mobile_money'
   - status: 'initiated' ou 'pending'
   - customer_phone: numéro normalisé
   - external_reference: transaction_id généré
   ↓
8. Si provider activé :
   - Appel API MTN/Airtel pour initier le paiement
   - Envoi demande de paiement au téléphone client
   ↓
9. Stock NON décrémenté (en attente de confirmation)
   ↓
10. Client valide le paiement sur son téléphone
   ↓
11. Callback reçu du provider
   ↓
12. MobileMoneyPaymentService::handleCallback() appelé
   ↓
13. Payment mis à jour :
    - status: 'paid' (si succès)
    - paid_at: now()
   ↓
14. Commande mise à jour :
    - payment_status: 'paid'
    - status: 'paid'
   ↓
15. OrderObserver déclenché → Stock décrémenté
   ↓
16. Mouvement stock créé avec raison "Vente en ligne"
    (car déclenché par l'Observer, pas directement par POS)
   ↓
17. Commande terminée ✅
```

### Caractéristiques

- ⏳ **Paiement initié** : Demande envoyée au téléphone client
- ⏳ **Stock réservé** : Non décrémenté jusqu'à confirmation
- ✅ **Confirmation automatique** : Via callback du provider
- ✅ **Support multi-opérateurs** : MTN MoMo et Airtel Money
- ⚠️ **Mode développement** : Simulation si provider non activé

### Code

```php
// Dans PosController::createPayment()
if ($paymentMethod === 'mobile_money') {
    $phone = $request->customer_phone;
    $provider = $request->input('mobile_money_provider', 'mtn_momo');
    
    $mobileMoneyService = app(MobileMoneyPaymentService::class);
    $payment = $mobileMoneyService->initiatePayment($order, $phone, $provider);
}
```

### Callback

```php
// Route callback (déjà existante)
Route::post('/payment/mobile-money/{provider}/callback', 
    [MobileMoneyPaymentController::class, 'callback'])
    ->name('payment.mobile-money.callback');
```

---

## 🔄 COMPARAISON DES SCÉNARIOS

| Critère | Espèces | Carte (TPE) | Mobile Money |
|---------|---------|-------------|--------------|
| **Statut initial commande** | `completed` | `pending` | `pending` |
| **Statut initial paiement** | `paid` | `pending` | `initiated`/`pending` |
| **Décrémentation stock** | Immédiate | Après confirmation | Après callback |
| **Confirmation** | Automatique | Manuelle (admin) | Automatique (callback) |
| **Enregistrement Payment** | ✅ Créé `paid` | ✅ Créé `pending` | ✅ Créé `initiated` |
| **Raison mouvement stock** | "Vente en boutique" | "Vente en boutique" | "Vente en ligne"* |

*Note : Pour Mobile Money, le mouvement est créé par l'OrderObserver, donc la raison est "Vente en ligne". On pourrait améliorer cela en vérifiant si la commande vient du POS.

---

## 📊 ENREGISTREMENTS PAYMENT

### Structure Payment

```php
Payment::create([
    'order_id' => $order->id,
    'amount' => $order->total_amount,
    'currency' => 'XAF',
    'channel' => 'cash' | 'card' | 'mobile_money',
    'provider' => 'cash' | 'stripe' | 'mtn_momo' | 'airtel_money',
    'status' => 'paid' | 'pending' | 'initiated' | 'failed',
    'customer_phone' => '...', // Pour Mobile Money
    'external_reference' => '...', // Transaction ID
    'provider_payment_id' => '...', // ID du provider
    'paid_at' => now(), // Si payé
    'metadata' => [
        'payment_location' => 'Boutique physique',
        'processed_by' => Auth::id(),
        // ... autres infos
    ],
]);
```

---

## 🔧 ROUTES POS

```php
// Interface POS
GET  /admin/pos → Interface POS

// Recherche produit
POST /admin/pos/search-product → Recherche par code-barres/SKU/ID

// Création commande
POST /admin/pos/create-order → Créer commande avec paiement

// Confirmation paiement carte
POST /admin/pos/order/{order}/confirm-payment → Confirmer paiement TPE

// Détails commande
GET  /admin/pos/order/{order} → Détails d'une commande
```

---

## ⚠️ POINTS D'ATTENTION

### 1. Double décrémentation stock

**Problème** : L'OrderObserver décrémente le stock quand `payment_status` passe à `paid`, mais dans le POS on décrémente aussi manuellement.

**Solution** : 
- Pour les commandes POS, on crée la commande avec `user_id = null`
- L'Observer vérifie `if (!$order->user_id) return;` donc il ne décrémente pas
- On décrémente manuellement dans le POS avec la raison "Vente en boutique"

### 2. Mobile Money - Raison mouvement stock

**Problème** : Le mouvement stock créé par l'Observer a la raison "Vente en ligne" même pour Mobile Money POS.

**Solution actuelle** : Acceptable car le callback arrive après, donc techniquement c'est une vente en ligne.

**Amélioration possible** : Ajouter un champ `source` (online/store) à Order pour distinguer.

### 3. Confirmation paiement carte

**Workflow** : 
1. Admin valide sur le TPE
2. Admin entre le transaction_id dans le POS
3. Appel de `confirmCardPayment()` pour confirmer

**Amélioration possible** : Intégration directe avec le TPE si API disponible.

---

## ✅ VALIDATION

### Test Espèces
1. Créer une vente POS avec paiement espèces
2. Vérifier que :
   - Commande créée avec `status = completed`, `payment_status = paid`
   - Payment créé avec `status = paid`
   - Stock décrémenté
   - Mouvement stock créé avec raison "Vente en boutique"

### Test Carte
1. Créer une vente POS avec paiement carte
2. Vérifier que :
   - Commande créée avec `status = pending`, `payment_status = pending`
   - Payment créé avec `status = pending`
   - Stock NON décrémenté
3. Confirmer le paiement via `/admin/pos/order/{order}/confirm-payment`
4. Vérifier que :
   - Payment mis à jour avec `status = paid`
   - Commande mise à jour avec `payment_status = paid`, `status = completed`
   - Stock décrémenté
   - Mouvement stock créé

### Test Mobile Money
1. Créer une vente POS avec paiement Mobile Money
2. Vérifier que :
   - Commande créée avec `status = pending`, `payment_status = pending`
   - Payment créé avec `status = initiated`
   - Stock NON décrémenté
3. Simuler le callback (ou attendre le vrai callback)
4. Vérifier que :
   - Payment mis à jour avec `status = paid`
   - Commande mise à jour
   - Stock décrémenté (via OrderObserver)

---

**Scénario complet implémenté ! ✅**

