# 🎯 PLAN D'ACTION - SPRINT 1 P0

**Date :** 10 décembre 2025  
**Objectif :** Implémenter les tickets P0 pour Production Candidate

---

## ✅ ÉTAPE 1 : BASELINE & DIAGNOSTICS - TERMINÉE

- ✅ Migrations : **CORRIGÉES** (promo_code_id ajouté dans create_orders_table)
- ⚠️ Tests : 23 échecs identifiés (principalement commandes non créées)

---

## 🔧 ÉTAPE 2 : FIX MIGRATIONS SQLITE (RBG-P0-001 / RBG-P0-002)

### ✅ Correction 1 : promo_code_id (FAIT)
- **Fichier modifié :** `database/migrations/2025_11_23_000004_create_orders_table.php`
- **Action :** Ajout des colonnes directement dans create_orders_table
- **Statut :** ✅ Migrations passent maintenant

### ⏳ À FAIRE : Vérifier autres migrations sensibles SQLite

**Migrations à vérifier :**
1. `2025_12_10_105138_add_missing_indexes_for_orders_and_payments.php` (déjà corrigée avec try-catch)
2. `2025_12_08_000001_add_indexes_for_performance.php` (déjà corrigée avec try-catch)
3. `2025_01_27_000009_add_promo_code_to_orders_table.php` (protégée avec hasColumn)

**Action :** Vérifier que toutes les migrations passent en SQLite et documenter les workarounds.

---

## 🔒 ÉTAPE 3 : SÉCURITÉ STRIPE WEBHOOK (RBG-P0-010)

### ⏳ À FAIRE

**Fichiers à modifier :**
- `app/Http/Controllers/Front/CardPaymentController.php`
- `app/Services/Payments/StripePaymentService.php`
- `config/services.php` (ajouter STRIPE_WEBHOOK_SECRET dans .env.example)

**Tests à créer :**
- `tests/Feature/PaymentWebhookSecurityTest.php` (existe déjà mais à corriger)

**Actions :**
1. Activer vérification signature Stripe (actuellement commentée)
2. Rejeter webhooks sans signature → 401
3. Rejeter webhooks avec signature invalide → 401
4. Logger toutes les tentatives invalides
5. Ajouter variable STRIPE_WEBHOOK_SECRET dans .env.example

---

## 🔒 ÉTAPE 4 : SÉCURITÉ MOBILE MONEY (RBG-P0-011)

### ⏳ À FAIRE

**Fichiers à modifier :**
- `app/Http/Controllers/Front/MobileMoneyPaymentController.php`
- `app/Services/Payments/MobileMoneyPaymentService.php`

**Tests à créer :**
- `tests/Feature/MobileMoneyWebhookSecurityTest.php`

**Actions :**
1. Implémenter validation auth (token/signature selon provider)
2. Anti-replay via timestamp (rejet si > 5 min)
3. Idempotence via unique constraint (provider, provider_txn_id)
4. Logger toutes les tentatives invalides

---

## 🛒 ÉTAPE 5 : ANTI-OVERSELL STOCK (RBG-P0-020)

### ⏳ À FAIRE

**Fichiers à modifier :**
- `app/Services/OrderService.php`
- `app/Services/StockValidationService.php`

**Tests à créer :**
- `tests/Feature/StockConcurrencyTest.php`

**Actions :**
1. Encapsuler création commande + décrément dans `DB::transaction()`
2. Appliquer verrouillage pessimiste (`lockForUpdate`) sur produits
3. Tester concurrence (2 commandes simultanées sur même produit)

---

## 📊 PROBLÈMES IDENTIFIÉS (À CORRIGER EN PARALLÈLE)

### Problème 1 : Commandes non créées dans les tests

**Symptômes :**
- Tests CheckoutController : redirections vers `/` au lieu de routes attendues
- Tests CashOnDelivery : commandes null
- Tests OrderTest : table orders vide

**Cause probable :**
- Exception silencieuse dans `OrderService::createOrderFromCart()`
- Validation qui échoue
- Problème de données de test

**Action :** Analyser `OrderService::createOrderFromCart()` et les logs

### Problème 2 : Décrément stock ne fonctionne pas

**Symptômes :**
- Stock reste à 10 au lieu de 8 après commande de 2 unités

**Cause probable :**
- `OrderObserver@created()` ne s'exécute pas
- Logique de décrément incorrecte pour `cash_on_delivery`

**Action :** Vérifier `OrderObserver@created()` et la logique de décrément

---

## 🎯 ORDRE D'EXÉCUTION

1. ✅ **Étape 1** : Baseline (TERMINÉE)
2. ⏳ **Étape 2** : Fix migrations SQLite (EN COURS - promo_code_id corrigé)
3. ⏳ **Étape 3** : Sécurité Stripe webhook
4. ⏳ **Étape 4** : Sécurité Mobile Money
5. ⏳ **Étape 5** : Anti-oversell stock

---

## 📝 NOTES

- Les tests échouent principalement car les commandes ne sont pas créées
- Il faut d'abord comprendre pourquoi avant de continuer
- Les corrections de sécurité (étapes 3-4) peuvent être faites en parallèle
- L'anti-oversell (étape 5) nécessite que les commandes soient créées correctement

---

**Prochaine action immédiate :** Analyser pourquoi les commandes ne sont pas créées dans les tests.

