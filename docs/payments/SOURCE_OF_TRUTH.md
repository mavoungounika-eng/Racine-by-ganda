# 🎯 Source of Truth — Payments Hub v1.1

**Date :** 2025-12-14  
**Sprint :** Sprint 1 — Audit  
**Ticket :** #PH1-005

---

## 🎯 OBJECTIF

Verrouiller la règle "une seule vérité" en production pour éviter les incohérences.

---

## ✅ RÈGLE VALIDÉE

### Source of truth = `payment_transactions` + `orders`

**Tables sources de vérité :**
1. **`payment_transactions`** : Vérité métier des transactions de paiement
2. **`orders`** : Vérité métier des commandes (incluant `payment_status`)

**Tables événements (support) :**
- `stripe_webhook_events` : Événements Stripe (idempotence)
- `monetbil_callback_events` : Événements Monetbil (idempotence)

**Table legacy (compatibilité uniquement) :**
- `payments` : Table legacy, **ne pas utiliser comme source de vérité**

---

## 📋 RÈGLES DO / DON'T

### ✅ DO (À FAIRE)

#### 1. Lecture des transactions

**Utiliser :**
```php
// ✅ CORRECT
$transaction = PaymentTransaction::where('payment_ref', $ref)->first();
$order = $transaction->order;
$status = $transaction->status; // Source of truth
```

**Ne pas utiliser :**
```php
// ❌ INCORRECT (legacy)
$payment = Payment::where('order_id', $orderId)->first();
$status = $payment->status; // Pas source of truth
```

#### 2. Mise à jour du statut

**Utiliser :**
```php
// ✅ CORRECT
$transaction->update(['status' => 'succeeded']);
$order->update(['payment_status' => 'paid']);
```

**Ne pas utiliser :**
```php
// ❌ INCORRECT
$payment->update(['status' => 'paid']); // Legacy uniquement
```

#### 3. Reporting / KPIs

**Utiliser :**
```php
// ✅ CORRECT
$total = PaymentTransaction::where('status', 'succeeded')->sum('amount');
$count = PaymentTransaction::where('status', 'pending')->count();
```

**Ne pas utiliser :**
```php
// ❌ INCORRECT
$total = Payment::where('status', 'paid')->sum('amount'); // Legacy
```

#### 4. Webhooks / Callbacks

**Utiliser :**
```php
// ✅ CORRECT
// 1. Persist event (idempotence)
$event = StripeWebhookEvent::firstOrCreate(['event_id' => $eventId], [...]);
// 2. Update transaction (source of truth)
$transaction = PaymentTransaction::where('payment_ref', $ref)->first();
$transaction->update(['status' => 'succeeded']);
// 3. Update order
$order->update(['payment_status' => 'paid']);
```

---

### ❌ DON'T (À NE PAS FAIRE)

#### 1. Ne pas utiliser `payments` comme source de vérité

```php
// ❌ INTERDIT
$payment = Payment::where('order_id', $orderId)->first();
if ($payment->status === 'paid') {
    // Ne pas utiliser pour décisions métier
}
```

**Raison :** Table legacy, peut contenir des données obsolètes ou incohérentes.

#### 2. Ne pas créer de logique métier basée sur `payments`

```php
// ❌ INTERDIT
if (Payment::where('order_id', $orderId)->where('status', 'paid')->exists()) {
    // Ne pas utiliser pour logique métier
}
```

**Raison :** `payment_transactions` est la source de vérité.

#### 3. Ne pas synchroniser `payments` depuis `payment_transactions`

**Pas nécessaire** : La table `payments` peut rester pour compatibilité, mais ne doit pas être mise à jour automatiquement depuis `payment_transactions`.

---

## 🔄 FLUX DE TRAITEMENT (Source of truth)

### Flux webhook/callback

```
1. Webhook/Callback reçu
   ↓
2. Verify signature/auth
   ↓
3. Persist event (stripe_webhook_events / monetbil_callback_events)
   ↓
4. Update payment_transactions (source of truth)
   ↓
5. Update orders.payment_status (source of truth)
   ↓
6. (Optionnel) Créer/mettre à jour payments pour compatibilité
```

### Flux checkout

```
1. Commande créée (orders)
   ↓
2. Initiation paiement
   ↓
3. Créer payment_transactions (status: pending)
   ↓
4. Redirection vers provider
   ↓
5. Webhook/Callback → Update payment_transactions + orders
```

---

## 📊 MAPPING STATUTS

### `payment_transactions.status` → `orders.payment_status`

| Transaction Status | Order Payment Status |
|-------------------|---------------------|
| `pending` | `pending` |
| `processing` | `pending` |
| `succeeded` | `paid` |
| `failed` | `failed` |
| `canceled` | `pending` (ou `failed` selon contexte) |
| `refunded` | `paid` (avec flag refund si nécessaire) |

---

## 🗄️ STATUT TABLE `payments` (Legacy)

### Décision : Conserver pour compatibilité

**Raison :** Code existant peut référencer cette table.

**Action :**
- ✅ Conserver la table
- ✅ Ne pas supprimer les données existantes
- ❌ Ne pas utiliser comme source de vérité
- ❌ Ne pas créer de nouvelles logiques basées sur cette table
- ⚠️ Documenter comme "legacy" dans le code

### Migration future (optionnel)

Si nécessaire, créer une vue ou un accessor pour compatibilité :

```php
// Dans Order model (optionnel)
public function legacyPayments()
{
    return $this->hasMany(Payment::class);
}
```

---

## ✅ CHECKLIST VALIDATION

- [x] Source of truth validée (`payment_transactions` + `orders`)
- [x] Table legacy identifiée (`payments`)
- [x] Règles DO/DON'T documentées
- [x] Flux de traitement documenté
- [x] Mapping statuts documenté
- [x] Statut table legacy décidé

---

## 📝 NOTES IMPORTANTES

1. **Cohérence** : Toujours mettre à jour `payment_transactions` ET `orders.payment_status` ensemble.

2. **Idempotence** : Les événements webhook/callback sont idempotents via `event_id` / `event_key`.

3. **Jobs** : Les jobs de traitement doivent lire depuis `payment_transactions`, pas depuis `payments`.

4. **UI Admin** : Les vues admin doivent afficher les données depuis `payment_transactions`, pas depuis `payments`.

5. **Tests** : Tous les tests doivent utiliser `payment_transactions` comme source de vérité.

---

**Document créé le :** 2025-12-14  
**Prochaine étape :** Appliquer cette règle dans tous les jobs/services (Sprint 4-6)







