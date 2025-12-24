# 📋 RAPPORT DE DIAGNOSTIC - SPRINT 1 P0

**Date :** 10 décembre 2025  
**Objectif :** Baseline & diagnostics pour Sprint 1 P0

---

## ✅ ÉTAPE 1 : BASELINE & DIAGNOSTICS

### Commandes exécutées

```bash
php artisan config:clear
php artisan cache:clear
php artisan migrate:fresh --env=testing
php artisan test
```

### Résultats

#### ✅ Migrations
- **Statut :** ✅ **SUCCÈS** (après correction)
- **Problème initial :** Colonne `promo_code_id` manquante dans `orders`
- **Correction appliquée :** Ajout des colonnes `promo_code_id`, `discount_amount`, `shipping_method`, `shipping_cost`, `payment_status` directement dans `create_orders_table.php`
- **Fichier modifié :** `database/migrations/2025_11_23_000004_create_orders_table.php`

#### ❌ Tests
- **Statut :** ❌ **23 tests échouent, 9 passent**
- **Total :** 32 tests (53 assertions)

---

## 🔍 ERREURS IDENTIFIÉES PAR CATÉGORIE

### 1. Erreurs de migration (CORRIGÉES)

#### RBG-P0-001 / RBG-P0-002 : Colonne `promo_code_id` manquante

**Erreur :**
```
SQLSTATE[HY000]: General error: 1 table orders has no column named promo_code_id
```

**Cause :**
- Migration `2025_01_27_000009_add_promo_code_to_orders_table.php` s'exécute AVANT `create_orders_table` (timestamps)
- Protection `if (!Schema::hasTable('orders'))` ne fonctionne pas correctement lors de `migrate:fresh`

**Correction appliquée :**
- Ajout des colonnes directement dans `create_orders_table.php` :
  - `promo_code_id`
  - `discount_amount`
  - `shipping_method`
  - `shipping_cost`
  - `payment_status`

**Fichier modifié :**
- `database/migrations/2025_11_23_000004_create_orders_table.php`

---

### 2. Erreurs de tests Feature (À CORRIGER)

#### 2.1 Tests CheckoutController (7 échecs)

**Problèmes identifiés :**

1. **Redirections incorrectes**
   - `it creates order with cash on delivery and redirects to success`
   - `it creates order with card payment and redirects to card payment`
   - `it creates order with mobile money payment and redirects to mobile money form`
   - **Erreur :** `Expected: http://localhost:8000 To contain: checkout/success`
   - **Cause probable :** Redirection vers `/` au lieu de la route attendue

2. **Validation errors**
   - `it handles validation errors when required fields are missing`
   - **Erreur :** Redirection vers `/` au lieu de `checkout.index`

3. **Panier vide**
   - `it redirects to cart when cart is empty on get checkout`
   - `it redirects to cart when cart is empty on post checkout`
   - **Erreur :** Message d'erreur différent de celui attendu

4. **Création commande**
   - `it creates order items correctly`
   - **Erreur :** `Failed asserting that null is not null` (commande non créée)

**Fichiers à vérifier :**
- `app/Http/Controllers/Front/CheckoutController.php`
- `tests/Feature/CheckoutControllerTest.php`

---

#### 2.2 Tests CashOnDeliveryTest (6 échecs)

**Problèmes identifiés :**

1. **Redirection**
   - `it creates order with cash on delivery`
   - **Erreur :** Redirection incorrecte

2. **Décrément stock**
   - `it decrements stock for cash on delivery`
   - **Erreur :** `Failed asserting that 10 matches expected 8` (stock non décrémenté)

3. **Vidage panier**
   - `it clears cart after order creation`
   - **Erreur :** Panier non vidé

4. **Events**
   - `it logs funnel events for cash on delivery`
   - **Erreur :** Event `OrderPlaced` non dispatché

5. **Payment record**
   - `it does not create payment record for cash on delivery`
   - **Erreur :** `Call to a member function payments() on null` (commande null)

6. **Double décrément**
   - `it prevents double stock decrement for cash on delivery`
   - **Erreur :** `Call to a member function update() on null` (commande null)

**Fichiers à vérifier :**
- `app/Http/Controllers/Front/CheckoutController.php`
- `app/Services/OrderService.php`
- `app/Observers/OrderObserver.php`
- `tests/Feature/CashOnDeliveryTest.php`

---

#### 2.3 Tests OrderTest (6 échecs)

**Problèmes identifiés :**

1. **Création commande**
   - `user can create order from cart`
   - **Erreur :** `The table is empty` (commande non créée)

2. **Décrément stock**
   - `order creation reduces product stock`
   - **Erreur :** Stock non décrémenté

3. **Stock insuffisant**
   - `cannot create order with insufficient stock`
   - **Erreur :** Session error manquante (validation échoue avant)

4. **Total commande**
   - `order total is calculated correctly`
   - **Erreur :** `Attempt to read property "total_amount" on null`

5. **Numéro commande**
   - `order has unique order number`
   - **Erreur :** `Attempt to read property "order_number" on null`

6. **QR token**
   - `order has qr token`
   - **Erreur :** `Attempt to read property "qr_token" on null`

**Fichiers à vérifier :**
- `app/Http/Controllers/Front/CheckoutController.php`
- `app/Services/OrderService.php`
- `tests/Feature/OrderTest.php`

---

#### 2.4 Tests PaymentWebhookSecurityTest (4 échecs)

**Problèmes identifiés :**

1. **Tous les tests échouent avec la même erreur :**
   - `it rejects webhook without signature in production`
   - `it rejects webhook with invalid signature`
   - `it logs structured information on webhook failure`
   - `it allows webhook without signature in development`
   - **Erreur :** `SQLSTATE[HY000]: General error: 1 table orders has no column named promo_code_id`
   - **Note :** Cette erreur devrait être résolue après la correction de migration

**Fichiers à vérifier :**
- `tests/Feature/PaymentWebhookSecurityTest.php` (existe-t-il ?)

---

## 📊 RÉSUMÉ DES ERREURS

| Catégorie | Nombre | Statut |
|-----------|--------|--------|
| **Migrations** | 1 | ✅ CORRIGÉ |
| **Tests CheckoutController** | 7 | ⚠️ À CORRIGER |
| **Tests CashOnDelivery** | 6 | ⚠️ À CORRIGER |
| **Tests OrderTest** | 6 | ⚠️ À CORRIGER |
| **Tests PaymentWebhookSecurity** | 4 | ⚠️ À VÉRIFIER (peut-être résolu) |
| **TOTAL** | **24** | |

---

## 🎯 PROCHAINES ÉTAPES

### Étape 2 : Fix migrations SQLite (RBG-P0-001 / RBG-P0-002)
- ✅ **FAIT** : Correction `promo_code_id` dans `create_orders_table`
- ⏳ **À FAIRE** : Vérifier autres migrations sensibles SQLite
- ⏳ **À FAIRE** : Documenter workarounds

### Étape 3 : Sécurité Stripe webhook (RBG-P0-010)
- ⏳ **À FAIRE** : Activer signature obligatoire
- ⏳ **À FAIRE** : Créer tests Feature

### Étape 4 : Sécurité Mobile Money (RBG-P0-011)
- ⏳ **À FAIRE** : Implémenter validation + anti-replay
- ⏳ **À FAIRE** : Créer tests Feature

### Étape 5 : Anti-oversell stock (RBG-P0-020)
- ⏳ **À FAIRE** : Verrouillage pessimiste
- ⏳ **À FAIRE** : Créer tests Feature

---

## ⚠️ NOTES IMPORTANTES

1. **Les tests échouent principalement car les commandes ne sont pas créées**
   - Problème probable dans `CheckoutController@placeOrder()`
   - Vérifier les redirections et la logique de création

2. **Le décrément stock ne fonctionne pas**
   - Vérifier `OrderObserver@created()`
   - Vérifier la logique pour `cash_on_delivery`

3. **Les tests PaymentWebhookSecurity existent déjà**
   - Ils échouent à cause de l'erreur de migration (maintenant corrigée)
   - À re-tester après correction

---

**Prochaine action :** Analyser `CheckoutController@placeOrder()` pour comprendre pourquoi les commandes ne sont pas créées.

