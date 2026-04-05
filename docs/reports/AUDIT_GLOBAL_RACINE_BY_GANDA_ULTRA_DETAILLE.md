# 🧠 AUDIT GLOBAL RACINE BY GANDA — RAPPORT ULTRA DÉTAILLÉ

**Date :** {{ date('Y-m-d') }}  
**Type :** Audit architectural et fonctionnel complet  
**Projet :** RACINE BY GANDA — Plateforme e-commerce + ERP/CRM + Marketplace créateurs  
**Framework :** Laravel 12+ / PHP moderne

---

## 📋 TABLE DES MATIÈRES

1. [Vue d'ensemble](#1-vue-densemble)
2. [Analyse par domaine](#2-analyse-par-domaine)
3. [Liste des problèmes priorisés](#3-liste-des-problèmes-priorisés)
4. [Plan d'action structuré](#4-plan-daction-structuré)

---

## 1. VUE D'ENSEMBLE

### 1.1. Schéma d'architecture actuelle

#### Frontend E-commerce (Public)

```
Client (non connecté)
  ↓
/ → FrontendController@home
  ↓
/boutique → FrontendController@shop (catalogue produits)
  ↓
/produit/{id} → FrontendController@product (fiche produit)
  ↓
/cart → CartController@index (panier session)
  ↓
/cart/add → CartController@add (ajout produit)
  ↓
/checkout → CheckoutController@index (authentification requise)
  ↓
/checkout (POST) → CheckoutController@placeOrder
  ├─→ Création Order (status='pending', payment_status='pending')
  ├─→ Vider panier
  └─→ Redirection selon payment_method:
      ├─ cash_on_delivery → /checkout/success/{order}
      ├─ card → /checkout/card/pay → Stripe Checkout
      └─ mobile_money → /checkout/mobile-money/{order}/form
  ↓
Paiement (selon méthode)
  ├─ Carte: Stripe webhook → OrderObserver → StockService.decrementFromOrder()
  ├─ Mobile Money: Callback → OrderObserver → StockService.decrementFromOrder()
  └─ Cash: Pas de décrément automatique (⚠️ PROBLÈME)
  ↓
/checkout/success/{order} → CheckoutController@success
```

#### Back-office / Admin

```
Admin (authentifié + middleware 'admin')
  ↓
/admin/dashboard → AdminDashboardController@index
  ↓
/admin/products → AdminProductController (CRUD produits)
/admin/orders → AdminOrderController (liste, détails, scan QR)
/admin/users → AdminUserController (gestion utilisateurs)
/admin/creators → AdminCreatorController (validation créateurs)
/admin/pos → PosController (Point of Sale boutique physique)
```

#### Espace Créateur

```
Créateur (authentifié + middleware 'role.creator' + 'creator.active')
  ↓
/createur/dashboard → CreatorDashboardController@index
  ↓
/createur/produits → CreatorProductController (CRUD produits créateur)
/createur/commandes → CreatorOrderController (commandes liées)
/createur/finances → CreatorFinanceController (revenus)
```

#### Espace Client

```
Client (authentifié)
  ↓
/compte → ClientAccountController@index (dashboard client)
  ↓
/profil → ProfileController (profil, commandes, adresses, favoris)
```

### 1.2. Photo globale

#### ✅ Forces principales

1. **Architecture modulaire** : Séparation claire entre modules (ERP, CRM, CMS, Frontend, etc.)
2. **Services bien structurés** : CartService (DB/Session), PaymentServices (Card/MobileMoney), StockService
3. **Observers utilisés** : OrderObserver pour décrément stock, notifications, emails
4. **Sécurité de base** : Middlewares (admin, creator, auth), Policies (Order, Product, User)
5. **Gestion multi-paiements** : Stripe (carte), Mobile Money (MTN/Airtel), Cash on delivery
6. **Système de rôles** : Client, Créateur, Admin, Staff
7. **Marketplace créateurs** : Isolation des données, validation workflow

#### ⚠️ Faiblesses principales

1. **Double système de checkout** : 
   - `resources/views/checkout/index.blade.php` (Bootstrap, layout `frontend`)
   - `resources/views/frontend/checkout/index.blade.php` (Bootstrap aussi mais plus complexe, layout `frontend`)
   - Les deux utilisent le même layout mais ont des structures différentes

2. **Layouts multiples** :
   - `layouts/frontend.blade.php` (Bootstrap 4, utilisé actuellement)
   - `layouts/master.blade.php` (Vite/Tailwind, probablement legacy)

3. **Paiement à la livraison non décrémenté** :
   - Le stock n'est décrémenté que quand `payment_status='paid'`
   - Pour `cash_on_delivery`, le paiement est à la livraison, donc le stock n'est jamais décrémenté automatiquement
   - Risque : Vendre le même produit plusieurs fois

4. **Gestion des paiements abandonnés** :
   - Pas de nettoyage automatique des commandes `pending` non payées après X jours
   - Job `CleanupPendingMobileMoneyPayments` existe mais pas de job pour les commandes cash/card abandonnées

5. **Index manquants** :
   - Certaines colonnes critiques n'ont pas d'index (ex: `orders.payment_method`, `payments.provider`)

6. **Code legacy non nettoyé** :
   - Vues avec suffixe `-old`, `-improved` (ex: `index-old.blade.php`, `index-improved.blade.php`)
   - Routes commentées (ex: routes ERP désactivées)

#### 🚨 Risques globaux

1. **Risque financier** : 
   - Paiement à la livraison → stock non décrémenté → risque de survente
   - Webhooks Stripe/Mobile Money peuvent échouer → commande payée mais stock non décrémenté

2. **Risque UX** :
   - Double système checkout → confusion utilisateur
   - Layouts différents → incohérence visuelle

3. **Risque technique** :
   - Code legacy non nettoyé → dette technique
   - Index manquants → performance dégradée à grande échelle

4. **Risque sécurité** :
   - Middlewares `role` et `permission` désactivés dans `bootstrap/app.php` (lignes 22-23)
   - Webhooks non sécurisés en développement (signature vérifiée seulement en production)

---

## 2. ANALYSE PAR DOMAINE

### 2.1. Front E-commerce

#### ✅ Forces

- **Contrôleurs bien structurés** : `FrontendController`, `CartController`, `CheckoutController`
- **Services de panier** : `DatabaseCartService` et `SessionCartService` avec interface commune
- **Vérification stock** : Vérification avant ajout panier et avant checkout
- **Rate limiting** : Frontend (60 req/min), Cart/Checkout (120 req/min)

#### ⚠️ Faiblesses

1. **Double système checkout** :
   - `resources/views/checkout/index.blade.php` (simple, Bootstrap)
   - `resources/views/frontend/checkout/index.blade.php` (complexe, stepper, Bootstrap)
   - Les deux utilisent `layouts.frontend` mais ont des structures différentes
   - **Référence** : `routes/web.php:375` → `CheckoutController@index` → retourne `view('checkout.index')`
   - **Problème** : La vue `frontend/checkout/index.blade.php` n'est jamais utilisée dans les routes actives

2. **Vues checkout multiples** :
   - `checkout/index.blade.php` (utilisée)
   - `checkout/success.blade.php` (utilisée)
   - `checkout/cancel.blade.php` (utilisée)
   - `frontend/checkout/index.blade.php` (NON utilisée)
   - `frontend/checkout/card-success.blade.php` (utilisée par `CardPaymentController@success`)
   - `frontend/checkout/card-cancel.blade.php` (utilisée)
   - `frontend/checkout/mobile-money-*.blade.php` (utilisées)
   - **Problème** : Cohérence visuelle entre `checkout/success` et `frontend/checkout/card-success`

3. **Layout master.blade.php non utilisé** :
   - `resources/views/layouts/master.blade.php` utilise Vite/Tailwind
   - Aucune vue ne l'utilise actuellement
   - **Action** : Supprimer ou archiver

#### 🚨 Risques

- **Confusion utilisateur** : Si les deux vues checkout sont accessibles, expérience incohérente
- **Maintenance** : Code dupliqué, modifications à faire en deux endroits

---

### 2.2. Paiements

#### ✅ Forces

- **Services séparés** : `CardPaymentService`, `MobileMoneyPaymentService`
- **Webhooks sécurisés** : Vérification signature Stripe (en production)
- **Gestion erreurs** : Try/catch, logging, exceptions personnalisées
- **Rate limiting** : 5 tentatives/minute pour Mobile Money
- **Protection double paiement** : Vérification `payment_status === 'paid'` avant initiation

#### ⚠️ Faiblesses

1. **Paiement à la livraison non décrémenté** :
   - **Référence** : `app/Http/Controllers/Front/CheckoutController.php:196-200`
   - Quand `payment_method='cash_on_delivery'`, redirection vers `checkout.success` sans passer par paiement
   - Le stock n'est décrémenté que quand `payment_status='paid'` (voir `OrderObserver:147-167`)
   - **Problème** : Pour cash on delivery, `payment_status` reste `'pending'` → stock jamais décrémenté
   - **Risque** : Vendre le même produit plusieurs fois avant livraison

2. **Gestion paiements abandonnés** :
   - Job `CleanupPendingMobileMoneyPayments` existe mais :
     - Pas de job pour commandes cash abandonnées
     - Pas de job pour commandes card abandonnées (si webhook échoue)
   - **Référence** : `app/Jobs/CleanupPendingMobileMoneyPayments.php`

3. **Webhook Stripe en développement** :
   - **Référence** : `app/Services/Payments/CardPaymentService.php:165-174`
   - En développement, signature non vérifiée
   - **Risque** : En production, si secret non configuré, warning loggé mais webhook accepté

4. **Mobile Money : vérification signature faible** :
   - **Référence** : `app/Http/Controllers/Front/MobileMoneyPaymentController.php:231-260`
   - En développement, signature acceptée automatiquement
   - En production, si secret non configuré, retourne `true` (ligne 238)

#### 🚨 Risques

- **Risque financier critique** : Paiement cash → stock non décrémenté → survente
- **Risque sécurité** : Webhooks non sécurisés si secrets mal configurés
- **Risque données** : Commandes abandonnées non nettoyées → base de données encombrée

---

### 2.3. Stock & ERP

#### ✅ Forces

- **Service dédié** : `modules/ERP/Services/StockService`
- **Mouvements traçables** : `ErpStockMovement` pour chaque décrément/réintégration
- **Observer automatique** : `OrderObserver` décrémente quand `payment_status='paid'`
- **Réintégration** : `restockFromOrder()` si commande annulée après paiement

#### ⚠️ Faiblesses

1. **Décrément seulement si payé** :
   - **Référence** : `app/Observers/OrderObserver.php:147-167`
   - Le stock n'est décrémenté que si `payment_status='paid'`
   - **Problème** : Cash on delivery → `payment_status='pending'` → stock jamais décrémenté
   - **Solution** : Décrémenter aussi pour `cash_on_delivery` OU marquer comme réservé

2. **Gestion backorder** :
   - **Référence** : `modules/ERP/Services/StockService.php:46-50`
   - Si stock insuffisant, warning loggé mais décrément quand même
   - **Risque** : Stock peut devenir négatif

3. **Pas de réservation de stock** :
   - Le stock n'est pas réservé lors de la création de commande
   - **Risque** : Race condition si deux commandes simultanées pour le même produit

4. **Transaction DB dans StockService** :
   - **Référence** : `modules/ERP/Services/StockService.php:37`
   - Transaction DB mais pas de verrouillage au niveau commande
   - **Risque** : Si deux commandes payées simultanément, décréments peuvent se chevaucher

#### 🚨 Risques

- **Risque critique** : Stock non décrémenté pour cash on delivery → survente
- **Risque performance** : Pas de réservation → vérifications stock à chaque étape
- **Risque données** : Stock peut devenir négatif (backorder non géré)

---

### 2.4. Back-office / Admin

#### ✅ Forces

- **Contrôleurs bien organisés** : `AdminProductController`, `AdminOrderController`, etc.
- **Policies** : `OrderPolicy`, `ProductPolicy`, `UserPolicy`
- **POS intégré** : Point of Sale pour boutique physique
- **Scan QR codes** : Vérification commandes via QR

#### ⚠️ Faiblesses

1. **Vues avec suffixes** :
   - `index.blade.php`, `index-old.blade.php`, `index-improved.blade.php`
   - **Référence** : `resources/views/admin/orders/`, `resources/views/admin/categories/`
   - **Problème** : Code legacy non nettoyé, confusion sur quelle vue est utilisée

2. **Middlewares désactivés** :
   - **Référence** : `bootstrap/app.php:22-23`
   - Middlewares `role` et `permission` commentés
   - **Risque** : Si réactivés, certaines routes peuvent casser

3. **Routes ERP désactivées** :
   - **Référence** : `routes/web.php:114-123`
   - Routes ERP commentées, redirection vers `/login`
   - **Action** : Nettoyer ou documenter pourquoi désactivées

#### 🚨 Risques

- **Risque maintenance** : Code legacy → confusion, modifications au mauvais endroit
- **Risque sécurité** : Middlewares désactivés → si réactivés, tests nécessaires

---

### 2.5. Créateurs / Marketplace

#### ✅ Forces

- **Isolation données** : Filtrage par `user_id` dans tous les contrôleurs créateur
- **Workflow validation** : `CreatorValidationChecklist`, `CreatorValidationStep`
- **Documents traçables** : `CreatorDocument` avec observer
- **Finances séparées** : `CreatorFinanceController` pour revenus créateurs

#### ⚠️ Faiblesses

1. **Pas de vérification propriété dans certaines routes** :
   - **Référence** : `routes/web.php:57-63` (routes produits créateur)
   - Route Model Binding mais pas de Policy vérifiant que le produit appartient au créateur
   - **Risque** : Si un créateur devine un ID produit d'un autre créateur, peut modifier

2. **Gestion commandes créateurs** :
   - **Référence** : `app/Http/Controllers/Creator/CreatorOrderController.php`
   - Filtrage via `whereHas('items.product', ...)` mais pas de Policy
   - **Risque** : Si logique de filtrage bug, exposition données

#### 🚨 Risques

- **Risque sécurité** : Pas de Policy sur produits créateurs → accès non autorisé possible
- **Risque données** : Isolation dépend de la logique contrôleur → si bug, fuite données

---

### 2.6. Architecture / Code

#### ✅ Forces

- **Services bien séparés** : Cart, Payment, Stock, Notification, Loyalty
- **Exceptions personnalisées** : `OrderException`, `PaymentException`, `StockException`
- **Observers utilisés** : `OrderObserver`, `ProductObserver`, `CreatorProfileObserver`
- **Form Requests** : Validation centralisée

#### ⚠️ Faiblesses

1. **Contrôleurs parfois gras** :
   - `CheckoutController` : 410 lignes (validation, logique métier, API endpoints)
   - **Référence** : `app/Http/Controllers/Front/CheckoutController.php`
   - **Action** : Extraire logique dans `CheckoutService`

2. **Logique métier dans contrôleurs** :
   - Calculs montants, vérifications stock, création commande dans `CheckoutController`
   - **Action** : Créer `OrderService` pour logique création commande

3. **Requêtes N+1 potentielles** :
   - **Référence** : `app/Http/Controllers/Front/CheckoutController.php:228`
   - `$order->load(['items.product', 'address'])` → bon
   - Mais dans d'autres endroits, pas de `with()` ou `load()`
   - **Exemple** : `app/Http/Controllers/Creator/CreatorOrderController.php` → vérifier eager loading

4. **Code dupliqué** :
   - Vérification stock dans `CartController` et `CheckoutController`
   - **Action** : Créer `StockValidationService`

#### 🚨 Risques

- **Risque performance** : Requêtes N+1 → lenteur à grande échelle
- **Risque maintenance** : Code dupliqué → bugs à corriger en plusieurs endroits

---

### 2.7. Sécurité / Performance

#### ✅ Forces

- **Rate limiting** : Frontend (60), Cart/Checkout (120), Mobile Money (5)
- **CSRF protection** : Active sauf webhooks
- **Policies** : Order, Product, User, Category
- **Headers sécurité** : `SecurityHeaders` middleware
- **Index sur colonnes critiques** : Migration `2025_12_08_000001_add_indexes_for_performance.php`

#### ⚠️ Faiblesses

1. **Index manquants** :
   - `orders.payment_method` : Pas d'index (filtrage fréquent)
   - `payments.provider` : Pas d'index (filtrage par provider)
   - `payments.channel` : Pas d'index (filtrage par channel)
   - **Référence** : `database/migrations/2025_12_08_000001_add_indexes_for_performance.php`
   - Cette migration ajoute des index mais pas sur toutes les colonnes critiques

2. **Middlewares désactivés** :
   - **Référence** : `bootstrap/app.php:22-23`
   - `role` et `permission` middlewares commentés
   - **Risque** : Si réactivés, certaines routes peuvent casser

3. **Vérification propriété commande** :
   - **Référence** : `app/Http/Controllers/Front/CheckoutController.php:223-226`
   - Vérification manuelle `$order->user_id !== Auth::id()`
   - **Action** : Utiliser `OrderPolicy` avec `authorize()`

4. **Webhooks non sécurisés en dev** :
   - **Référence** : `app/Services/Payments/CardPaymentService.php:165-174`
   - En développement, signature non vérifiée
   - **Risque** : Si oubli de configurer secret en production, webhook accepté

5. **Pas de cache** :
   - Pas de cache sur catalogue produits (liste produits consultée souvent)
   - Pas de cache sur catégories
   - **Risque** : Performance dégradée à grande échelle

#### 🚨 Risques

- **Risque performance** : Index manquants → requêtes lentes
- **Risque sécurité** : Middlewares désactivés → si réactivés, tests nécessaires
- **Risque sécurité** : Webhooks non sécurisés si secrets mal configurés

---

## 3. LISTE DES PROBLÈMES PRIORISÉS

### [P1] Paiement à la livraison : stock non décrémenté

- **Impact** : Critique (risque financier, survente)
- **Description** :
  - Quand `payment_method='cash_on_delivery'`, la commande est créée avec `payment_status='pending'`
  - Le stock n'est décrémenté que quand `payment_status='paid'` (voir `OrderObserver:147-167`)
  - Pour cash on delivery, le paiement est à la livraison, donc `payment_status` reste `'pending'` → stock jamais décrémenté
  - **Référence** :
    - `app/Http/Controllers/Front/CheckoutController.php:196-200` (redirection cash on delivery)
    - `app/Observers/OrderObserver.php:147-167` (décrément seulement si `payment_status='paid'`)
- **Risques** :
  - Vendre le même produit plusieurs fois avant livraison
  - Stock incorrect → commandes impossibles à honorer
- **Piste d'amélioration** :
  - Option 1 : Décrémenter le stock dès création commande pour `cash_on_delivery`
  - Option 2 : Marquer le stock comme "réservé" et décrémenter à la livraison
  - Option 3 : Décrémenter le stock quand la commande passe en `status='processing'` (pas seulement `payment_status='paid'`)

---

### [P2] Double système de checkout (vues redondantes)

- **Impact** : Majeur (confusion UX, maintenance)
- **Description** :
  - Vue A : `resources/views/checkout/index.blade.php` (simple, Bootstrap, utilisée)
  - Vue B : `resources/views/frontend/checkout/index.blade.php` (complexe, stepper, Bootstrap, NON utilisée)
  - Les deux utilisent `layouts.frontend` mais ont des structures différentes
  - **Référence** : `routes/web.php:375` → `CheckoutController@index` → retourne `view('checkout.index')`
- **Risques** :
  - Confusion si les deux vues sont accessibles
  - Code dupliqué → modifications à faire en deux endroits
  - Maintenance difficile
- **Piste d'amélioration** :
  - Unifier sur une seule vue (garder la plus complète : `frontend/checkout/index.blade.php`)
  - Déplacer l'autre dans un dossier `_legacy` ou supprimer
  - Vérifier toutes les routes utilisant `checkout.*` et `frontend.checkout.*`

---

### [P3] Gestion paiements abandonnés incomplète

- **Impact** : Majeur (données, performance)
- **Description** :
  - Job `CleanupPendingMobileMoneyPayments` existe mais :
    - Pas de job pour commandes cash abandonnées
    - Pas de job pour commandes card abandonnées (si webhook échoue)
  - **Référence** : `app/Jobs/CleanupPendingMobileMoneyPayments.php`
- **Risques** :
  - Base de données encombrée de commandes `pending` non payées
  - Performance dégradée (requêtes sur `orders` avec `payment_status='pending'`)
  - Stock "bloqué" si réservation implémentée
- **Piste d'amélioration** :
  - Créer un job `CleanupAbandonedOrders` qui nettoie :
    - Commandes `cash_on_delivery` avec `payment_status='pending'` depuis > 7 jours
    - Commandes `card` avec `payment_status='pending'` depuis > 24h (webhook devrait arriver rapidement)
    - Commandes `mobile_money` avec `payment_status='pending'` depuis > 48h
  - Planifier le job quotidiennement

---

### [P4] Index manquants sur colonnes critiques

- **Impact** : Moyen (performance à grande échelle)
- **Description** :
  - `orders.payment_method` : Pas d'index (filtrage fréquent dans admin)
  - `payments.provider` : Pas d'index (filtrage par provider)
  - `payments.channel` : Pas d'index (filtrage par channel)
  - **Référence** : `database/migrations/2025_12_08_000001_add_indexes_for_performance.php`
  - Cette migration ajoute des index mais pas sur toutes les colonnes critiques
- **Risques** :
  - Requêtes lentes sur grandes tables
  - Performance dégradée dans back-office (filtrage commandes par méthode paiement)
- **Piste d'amélioration** :
  - Créer une migration ajoutant les index manquants :
    ```php
    $table->index('payment_method', 'orders_payment_method_index');
    $table->index('provider', 'payments_provider_index');
    $table->index('channel', 'payments_channel_index');
    ```

---

### [P5] Code legacy non nettoyé (vues avec suffixes)

- **Impact** : Moyen (maintenance, confusion)
- **Description** :
  - Vues avec suffixe `-old`, `-improved` :
    - `resources/views/admin/orders/index-old.blade.php`
    - `resources/views/admin/orders/index-improved.blade.php`
    - `resources/views/admin/categories/index-old.blade.php`
    - `resources/views/admin/categories/index-improved.blade.php`
    - Etc.
  - **Référence** : `resources/views/admin/`
- **Risques** :
  - Confusion sur quelle vue est utilisée
  - Modifications au mauvais endroit
  - Dette technique
- **Piste d'amélioration** :
  - Identifier les vues réellement utilisées (grep dans contrôleurs)
  - Déplacer les vues non utilisées dans `resources/views/_legacy/`
  - Ou supprimer si vraiment obsolètes

---

### [P6] Middlewares `role` et `permission` désactivés

- **Impact** : Moyen (sécurité, maintenabilité)
- **Description** :
  - Middlewares `role` et `permission` commentés dans `bootstrap/app.php:22-23`
  - **Référence** : `bootstrap/app.php:22-23`
- **Risques** :
  - Si réactivés, certaines routes peuvent casser
  - Confusion sur quel système d'autorisation utiliser (middlewares vs Policies)
- **Piste d'amélioration** :
  - Décider : utiliser middlewares OU Policies (pas les deux)
  - Si middlewares : réactiver et tester toutes les routes
  - Si Policies : supprimer les middlewares commentés

---

### [P7] Layout `master.blade.php` non utilisé (Tailwind legacy)

- **Impact** : Faible (dette technique)
- **Description** :
  - `resources/views/layouts/master.blade.php` utilise Vite/Tailwind
  - Aucune vue ne l'utilise actuellement
  - **Référence** : `resources/views/layouts/master.blade.php`
- **Risques** :
  - Dette technique
  - Confusion si quelqu'un l'utilise par erreur
- **Piste d'amélioration** :
  - Vérifier qu'aucune vue ne l'utilise (grep `@extends('layouts.master')`)
  - Supprimer ou déplacer dans `_legacy`

---

### [P8] Vérification propriété commande manuelle (pas de Policy)

- **Impact** : Moyen (sécurité, maintenabilité)
- **Description** :
  - Vérification manuelle `$order->user_id !== Auth::id()` dans plusieurs contrôleurs
  - **Référence** :
    - `app/Http/Controllers/Front/CheckoutController.php:223-226`
    - `app/Http/Controllers/Front/MobileMoneyPaymentController.php:139-141`
- **Risques** :
  - Code dupliqué
  - Oubli de vérification dans un contrôleur → faille sécurité
- **Piste d'amélioration** :
  - Utiliser `OrderPolicy` avec `authorize('view', $order)` dans tous les contrôleurs
  - Supprimer les vérifications manuelles

---

### [P9] Contrôleurs gras (logique métier dans contrôleurs)

- **Impact** : Moyen (maintenabilité, testabilité)
- **Description** :
  - `CheckoutController` : 410 lignes (validation, logique métier, API endpoints)
  - Calculs montants, vérifications stock, création commande dans contrôleur
  - **Référence** : `app/Http/Controllers/Front/CheckoutController.php`
- **Risques** :
  - Difficile à tester
  - Code dupliqué si logique réutilisée ailleurs
- **Piste d'amélioration** :
  - Créer `OrderService` pour logique création commande
  - Créer `StockValidationService` pour vérifications stock
  - Garder contrôleurs minces (validation, appel services, réponse)

---

### [P10] Pas de cache sur catalogue produits

- **Impact** : Moyen (performance à grande échelle)
- **Description** :
  - Pas de cache sur liste produits (page `/boutique`)
  - Pas de cache sur catégories
  - **Référence** : `app/Http/Controllers/Front/FrontendController.php`
- **Risques** :
  - Performance dégradée si beaucoup de produits
  - Charge DB inutile
- **Piste d'amélioration** :
  - Cache liste produits (TTL 1h)
  - Cache catégories (TTL 24h)
  - Invalider cache lors modification produit/catégorie

---

## 4. PLAN D'ACTION STRUCTURÉ

### Phase 1 – Critique (tunnel d'achat & paiement & stock)

**Objectif** : Corriger les problèmes critiques liés au revenu et à la cohérence des données.

#### Chantier 1.1 : Corriger décrément stock pour cash on delivery

- **Objectif** : Décrémenter le stock pour les commandes cash on delivery
- **Fichiers concernés** :
  - `app/Observers/OrderObserver.php` (méthode `handlePaymentStatusChange`)
  - `app/Http/Controllers/Front/CheckoutController.php` (méthode `placeOrder`)
- **Actions** :
  1. Modifier `OrderObserver` pour décrémenter aussi si `payment_method='cash_on_delivery'` ET `status='processing'`
  2. OU : Décrémenter dès création commande pour cash on delivery
  3. Tester : Créer commande cash → vérifier stock décrémenté
- **Gain attendu** : Éviter survente, cohérence stock

#### Chantier 1.2 : Unifier le système de checkout

- **Objectif** : Une seule vue checkout, supprimer les redondances
- **Fichiers concernés** :
  - `resources/views/checkout/index.blade.php`
  - `resources/views/frontend/checkout/index.blade.php`
  - `app/Http/Controllers/Front/CheckoutController.php`
- **Actions** :
  1. Identifier quelle vue est la plus complète (probablement `frontend/checkout/index.blade.php`)
  2. Migrer les fonctionnalités manquantes si nécessaire
  3. Modifier `CheckoutController@index` pour utiliser la vue unifiée
  4. Déplacer l'autre vue dans `_legacy` ou supprimer
  5. Vérifier toutes les routes utilisant `checkout.*`
- **Gain attendu** : Cohérence UX, maintenance simplifiée

#### Chantier 1.3 : Gestion paiements abandonnés

- **Objectif** : Nettoyer automatiquement les commandes abandonnées
- **Fichiers concernés** :
  - `app/Jobs/CleanupAbandonedOrders.php` (à créer)
  - `app/Console/Kernel.php` (planification)
- **Actions** :
  1. Créer job `CleanupAbandonedOrders` qui nettoie :
     - Commandes cash `pending` > 7 jours
     - Commandes card `pending` > 24h
     - Commandes mobile money `pending` > 48h
  2. Planifier le job quotidiennement
  3. Tester : Créer commandes test, attendre délai, vérifier nettoyage
- **Gain attendu** : Base de données propre, performance améliorée

---

### Phase 2 – Majeur (architecture & performance)

**Objectif** : Améliorer l'architecture, la performance et la maintenabilité.

#### Chantier 2.1 : Ajouter index manquants

- **Objectif** : Améliorer les performances des requêtes
- **Fichiers concernés** :
  - `database/migrations/YYYY_MM_DD_add_missing_indexes.php` (à créer)
- **Actions** :
  1. Créer migration ajoutant index sur :
     - `orders.payment_method`
     - `payments.provider`
     - `payments.channel`
  2. Exécuter migration
  3. Vérifier amélioration performance (EXPLAIN sur requêtes)
- **Gain attendu** : Requêtes plus rapides, meilleure performance back-office

#### Chantier 2.2 : Refactoriser contrôleurs (extraire logique métier)

- **Objectif** : Contrôleurs minces, logique dans services
- **Fichiers concernés** :
  - `app/Services/OrderService.php` (à créer)
  - `app/Services/StockValidationService.php` (à créer)
  - `app/Http/Controllers/Front/CheckoutController.php` (refactoriser)
- **Actions** :
  1. Créer `OrderService` avec méthode `createOrderFromCart()`
  2. Créer `StockValidationService` avec méthode `validateStockForItems()`
  3. Refactoriser `CheckoutController` pour utiliser ces services
  4. Tester : Vérifier que le comportement est identique
- **Gain attendu** : Code plus testable, réutilisable, maintenable

#### Chantier 2.3 : Utiliser Policies pour vérification propriété

- **Objectif** : Centraliser vérifications propriété dans Policies
- **Fichiers concernés** :
  - `app/Policies/OrderPolicy.php` (vérifier méthodes)
  - `app/Http/Controllers/Front/CheckoutController.php`
  - `app/Http/Controllers/Front/MobileMoneyPaymentController.php`
- **Actions** :
  1. Vérifier que `OrderPolicy@view` vérifie `user_id`
  2. Remplacer vérifications manuelles par `authorize('view', $order)`
  3. Tester : Vérifier qu'un utilisateur ne peut pas accéder aux commandes d'un autre
- **Gain attendu** : Sécurité renforcée, code plus propre

---

### Phase 3 – UX & Design

**Objectif** : Uniformiser l'expérience utilisateur et nettoyer le code legacy.

#### Chantier 3.1 : Nettoyer code legacy (vues avec suffixes)

- **Objectif** : Supprimer ou archiver les vues obsolètes
- **Fichiers concernés** :
  - `resources/views/admin/orders/index-old.blade.php`
  - `resources/views/admin/orders/index-improved.blade.php`
  - `resources/views/admin/categories/index-old.blade.php`
  - Etc.
- **Actions** :
  1. Grep dans contrôleurs pour identifier vues utilisées
  2. Déplacer vues non utilisées dans `resources/views/_legacy/`
  3. Ou supprimer si vraiment obsolètes
  4. Documenter dans README pourquoi certaines vues sont en legacy
- **Gain attendu** : Code plus propre, moins de confusion

#### Chantier 3.2 : Supprimer layout master.blade.php non utilisé

- **Objectif** : Nettoyer layouts legacy
- **Fichiers concernés** :
  - `resources/views/layouts/master.blade.php`
- **Actions** :
  1. Grep `@extends('layouts.master')` pour vérifier qu'aucune vue ne l'utilise
  2. Supprimer ou déplacer dans `_legacy`
- **Gain attendu** : Dette technique réduite

#### Chantier 3.3 : Implémenter cache sur catalogue produits

- **Objectif** : Améliorer performance page boutique
- **Fichiers concernés** :
  - `app/Http/Controllers/Front/FrontendController.php`
  - `app/Observers/ProductObserver.php` (invalider cache)
- **Actions** :
  1. Ajouter cache sur `FrontendController@shop` (TTL 1h)
  2. Ajouter cache sur catégories (TTL 24h)
  3. Invalider cache dans `ProductObserver` lors modification
  4. Tester : Vérifier amélioration performance
- **Gain attendu** : Performance améliorée, charge DB réduite

---

### Phase 4 – Sécurité & Robustesse

**Objectif** : Renforcer la sécurité et la robustesse du système.

#### Chantier 4.1 : Sécuriser webhooks en production

- **Objectif** : S'assurer que les webhooks sont sécurisés en production
- **Fichiers concernés** :
  - `app/Services/Payments/CardPaymentService.php`
  - `app/Http/Controllers/Front/MobileMoneyPaymentController.php`
- **Actions** :
  1. Vérifier que les secrets sont bien configurés en production
  2. Ajouter validation stricte : si secret manquant en production, refuser webhook
  3. Tester : Vérifier que webhooks refusés si signature invalide
- **Gain attendu** : Sécurité renforcée

#### Chantier 4.2 : Décider système autorisation (middlewares vs Policies)

- **Objectif** : Unifier système autorisation
- **Fichiers concernés** :
  - `bootstrap/app.php`
  - `app/Http/Middleware/CheckRole.php`
  - `app/Http/Middleware/CheckPermission.php`
  - `app/Policies/`
- **Actions** :
  1. Décider : utiliser middlewares OU Policies (recommandation : Policies)
  2. Si Policies : supprimer middlewares commentés
  3. Si middlewares : réactiver et tester toutes les routes
  4. Documenter choix dans README
- **Gain attendu** : Système cohérent, moins de confusion

---

## 📊 RÉSUMÉ EXÉCUTIF

### Problèmes critiques à corriger immédiatement

1. **P1** : Paiement cash on delivery → stock non décrémenté → risque survente
2. **P2** : Double système checkout → confusion UX
3. **P3** : Paiements abandonnés non nettoyés → performance dégradée

### Points forts à conserver

- Architecture modulaire bien structurée
- Services séparés (Cart, Payment, Stock)
- Observers pour automatisation
- Sécurité de base (middlewares, Policies)

### Audit et Planification

- [x] Audit technique complet (Modules, Code, Dette)
- [x] Audit financier approfondi (Comptabilité, Payouts, KYC)
- [x] Identification des incohérences SaaS vs Marketplace
- [ ] Rédaction du rapport d'audit final et recommandations
- [ ] Présentation du plan de remédiation (Phase 2 & 3)

### Dette technique à planifier

- Code legacy (vues avec suffixes)
- Layouts non utilisés
- Contrôleurs gras (logique métier à extraire)
- Index manquants (performance)

### Recommandations prioritaires

1. **Immédiat** : Corriger P1 (décrément stock cash on delivery)
2. **Court terme** : Unifier checkout (P2), nettoyer paiements abandonnés (P3)
3. **Moyen terme** : Refactoriser contrôleurs, ajouter index, implémenter cache
4. **Long terme** : Nettoyer code legacy, uniformiser système autorisation

---

**Fin du rapport d'audit**

