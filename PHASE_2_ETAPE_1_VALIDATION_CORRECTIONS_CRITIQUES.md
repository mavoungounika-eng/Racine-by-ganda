# ✅ PHASE 2 — ÉTAPE 1/3 : VALIDATION CORRECTIONS CRITIQUES
## RACINE BY GANDA — MODULE CHECKOUT & PAIEMENT

**Date :** 2025-01-XX  
**Statut :** ✅ **VALIDÉ — LISTE FIGÉE**  
**Périmètre :** Gel définitif des corrections à implémenter

---

## 🎯 OBJECTIF ÉTAPE 1

Transformer les **12 points critiques** identifiés en Phase 1 en une **liste courte, fermée et priorisée** de corrections réellement nécessaires, sans sur-ingénierie.

**Résultat :** ✅ **7 corrections critiques définitives** validées et figées.

---

## 📊 CORRESPONDANCE CRITIQUES → CORRECTIONS

| Critique Phase 1 | Correction Phase 2 | Statut |
|------------------|-------------------|--------|
| #2 — Validation stock différée | **CORRECTION 1** — Lock produit | ✅ Couvert |
| #11 — Décrément stock différé | **CORRECTION 1** — Lock produit | ✅ Couvert |
| #12 — Pas de lock sur Produit | **CORRECTION 1** + **CORRECTION 6** | ✅ Couvert |
| #3 — Pas de vérification double paiement Stripe | **CORRECTION 2** + **CORRECTION 4** | ✅ Couvert |
| #6 — Pas de lock sur Order (Monetbil) | **CORRECTION 2** | ✅ Couvert |
| #7 — Double création Payment Monetbil | **CORRECTION 4** | ✅ Couvert |
| #4 — Webhook non idempotent au niveau Order | **CORRECTION 3** + **CORRECTION 7** | ✅ Couvert |
| #5 — Pas de rollback si échec webhook | **CORRECTION 3** | ✅ Couvert |
| #8 — Pas de transaction DB Order + Payment | **CORRECTION 3** | ✅ Couvert |
| #10 — Décrément stock hors transaction | **CORRECTION 5** | ✅ Couvert |
| #9 — Idempotence basée sur montant | **CORRECTION 7** (partiel) | ✅ Couvert |
| #1 — Race condition panier | ⚠️ Non-critique (UX, pas financier) | ℹ️ Exclu |

**Couverture :** ✅ **11/12 critiques couvertes** (92%)

---

## 🔴 LES 7 CORRECTIONS CRITIQUES DÉFINITIVES

### ✅ CORRECTION 1 — LOCK PRODUIT (ANTI OVERSELL)

**Priorité :** 🔴 **HAUTE**  
**Impact :** Oversell impossible  
**Couvre :** Critiques #2, #11, #12

#### Problème racine
Plusieurs clients peuvent acheter le dernier produit simultanément, créant des oversells.

#### Décision ferme
**Tout accès en écriture au stock produit doit passer par `lockForUpdate()`.**

#### Zones concernées
1. **Validation stock** (`OrderService::createOrderFromCart()`)
   - Lock produits avant validation
   - Lock produits avant décrément

2. **Décrément stock** (`StockService::decrementFromOrder()`)
   - Lock produit avant `decrement()`
   - Lock dans transaction DB

3. **Décrément post-paiement** (`OrderObserver::handlePaymentStatusChange()`)
   - Lock produit avant décrément (via StockService)

#### Résultat attendu
- ✅ Un seul flux peut modifier le stock à la fois
- ✅ Oversell impossible
- ✅ Atomicité garantie

#### Fichiers à modifier
- `app/Services/OrderService.php`
- `modules/ERP/Services/StockService.php`
- `app/Services/StockValidationService.php` (si existe)

---

### ✅ CORRECTION 2 — LOCK COMMANDE AVANT PAIEMENT

**Priorité :** 🔴 **HAUTE**  
**Impact :** Double paiement impossible  
**Couvre :** Critiques #3, #6

#### Problème racine
Plusieurs paiements ou transactions peuvent être initiés pour une même commande simultanément.

#### Décision ferme
**Toute lecture/écriture de `Order.payment_status` doit être protégée par un lock.**

#### Zones concernées
1. **Stripe checkout creation** (`CardPaymentService::createCheckoutSession()`)
   - Lock Order avant création Payment
   - Vérifier `payment_status='pending'` sous lock

2. **Monetbil start()** (`MonetbilController::start()`)
   - Lock Order avant vérification `payment_status`
   - Lock Order avant création PaymentTransaction

3. **Toute création de Payment liée à Order**
   - Vérifier Payment existant sous lock Order

#### Résultat attendu
- ✅ Une commande = un seul paiement actif
- ✅ Zéro double paiement logique
- ✅ Atomicité garantie

#### Fichiers à modifier
- `app/Services/Payments/CardPaymentService.php`
- `app/Http/Controllers/Payments/MonetbilController.php`

---

### ✅ CORRECTION 3 — TRANSACTION ORDER + PAYMENT (WEBHOOKS)

**Priorité :** 🔴 **HAUTE**  
**Impact :** Incohérence financière impossible  
**Couvre :** Critiques #4, #5, #8

#### Problème racine
Paiement et commande peuvent diverger en cas d'erreur partielle lors du traitement webhook.

#### Décision ferme
**Toute confirmation de paiement via webhook doit être transactionnelle.**

#### Règle absolue
```
Payment.status = 'paid'  ⇔  Order.payment_status = 'paid'
```

**Ces deux mises à jour doivent être atomiques.**

#### Zones concernées
1. **Stripe webhook handler** (`CardPaymentService::handleWebhook()`)
   - Transaction DB pour Payment update + Order update
   - Rollback automatique si échec

2. **Monetbil notify callback** (`MonetbilController::notify()`)
   - Transaction DB pour PaymentTransaction update + Order update + Payment creation
   - Rollback automatique si échec

#### Résultat attendu
- ✅ Aucune incohérence financière possible
- ✅ Rollback automatique en cas d'erreur
- ✅ Atomicité garantie

#### Fichiers à modifier
- `app/Services/Payments/CardPaymentService.php`
- `app/Http/Controllers/Payments/MonetbilController.php`

---

### ✅ CORRECTION 4 — IDÉMPOTENCE PAIEMENT (STRIPE & MONETBIL)

**Priorité :** 🟠 **MOYENNE**  
**Impact :** Duplication Payment impossible  
**Couvre :** Critiques #3, #7

#### Problème racine
Plusieurs Payment peuvent être créés pour une même commande si webhook reçu plusieurs fois ou double clic.

#### Décision ferme
**Avant toute création de Payment, vérifier s'il en existe déjà un actif.**

#### Zones concernées
1. **Stripe checkout creation** (`CardPaymentService::createCheckoutSession()`)
   - Vérifier Payment existant (status='initiated' ou 'paid') pour Order
   - Retourner Payment existant si trouvé

2. **Monetbil notify callback** (`MonetbilController::notify()`)
   - Vérifier Payment existant pour Order avant création
   - Utiliser Payment existant si trouvé

#### Résultat attendu
- ✅ Un Payment logique par commande
- ✅ Webhooks répétitifs sans effet de bord
- ✅ Idempotence garantie

#### Fichiers à modifier
- `app/Services/Payments/CardPaymentService.php`
- `app/Http/Controllers/Payments/MonetbilController.php`

---

### ✅ CORRECTION 5 — STRATÉGIE STOCK UNIQUE (DÉCISION MÉTIER)

**Priorité :** 🔴 **HAUTE**  
**Impact :** Cohérence métier  
**Couvre :** Critiques #10, #11

#### ⚠️ Décision bloquante (sera tranchée à l'ÉTAPE 3)

**Deux options possibles :**

#### Option A : Réservation de stock
```
1. Création commande → Réserver stock (status='reserved')
2. Paiement réussi → Confirmer réservation (status='confirmed')
3. Paiement échoué → Libérer réservation (status='available')
```

**Avantages :**
- ✅ Pas de décrément avant paiement
- ✅ Stock visible mais réservé
- ✅ Rollback propre si échec

**Inconvénients :**
- ⚠️ Complexité supplémentaire (nouveau statut)
- ⚠️ Gestion expiration réservations

#### Option B : Décrément immédiat + rollback
```
1. Création commande → Décrément stock immédiatement
2. Paiement réussi → Stock décrémenté (OK)
3. Paiement échoué → Réintégrer stock (rollback)
```

**Avantages :**
- ✅ Simplicité (pas de nouveau statut)
- ✅ Décrément immédiat (cohérent avec cash_on_delivery)

**Inconvénients :**
- ⚠️ Rollback nécessaire si paiement échoue
- ⚠️ Stock peut être négatif temporairement

#### 👉 Une seule sera retenue, l'autre éliminée.

**Décision à prendre :** Option A ou Option B (ÉTAPE 3)

---

### ✅ CORRECTION 6 — LOCK PRODUIT DANS StockService

**Priorité :** 🟠 **MOYENNE**  
**Impact :** Concurrence sécurisée  
**Couvre :** Critique #12

#### Décision ferme
**Toute opération `decrement()` sur Product doit être précédée d'un lock explicite.**

#### Zones concernées
1. **StockService::decrementFromOrder()**
   - Lock Product avant `decrement()`
   - Lock dans transaction DB

#### Résultat attendu
- ✅ Décrément sûr même sous forte concurrence
- ✅ Atomicité garantie
- ✅ Pas de race condition

#### Fichiers à modifier
- `modules/ERP/Services/StockService.php`

---

### ✅ CORRECTION 7 — ÉTATS TERMINAUX IMMUTABLES

**Priorité :** 🟠 **MOYENNE**  
**Impact :** Sécurité logique  
**Couvre :** Critiques #4, #9

#### Décision ferme
**Un état `paid`, `cancelled`, `completed` ne peut plus être modifié.**

#### Zones concernées
1. **Order**
   - États immuables : `paid`, `cancelled`, `completed`
   - Vérification avant update dans Observer

2. **Payment**
   - États immuables : `paid`, `cancelled`
   - Vérification avant update dans webhook handlers

#### Résultat attendu
- ✅ Aucun double traitement possible
- ✅ Webhooks tardifs ignorés proprement
- ✅ Sécurité logique garantie

#### Fichiers à modifier
- `app/Observers/OrderObserver.php`
- `app/Services/Payments/CardPaymentService.php`
- `app/Http/Controllers/Payments/MonetbilController.php`
- `app/Models/Order.php` (méthode helper `isTerminal()`)

---

## 📊 TABLEAU DE SYNTHÈSE

| # | Correction | Priorité | Impact | Critiques Couvertes |
|---|-----------|----------|--------|---------------------|
| 1 | Lock produit | 🔴 HAUTE | Oversell | #2, #11, #12 |
| 2 | Lock commande | 🔴 HAUTE | Double paiement | #3, #6 |
| 3 | Transaction webhook | 🔴 HAUTE | Incohérence finance | #4, #5, #8 |
| 4 | Idempotence paiement | 🟠 MOYENNE | Duplication | #3, #7 |
| 5 | Stratégie stock | 🔴 HAUTE | Cohérence métier | #10, #11 |
| 6 | Lock StockService | 🟠 MOYENNE | Concurrence | #12 |
| 7 | États immuables | 🟠 MOYENNE | Sécurité logique | #4, #9 |

**Total :** 7 corrections (3 HAUTE, 4 MOYENNE)

---

## ✅ VALIDATION FINALE

### Critères de validation

- ✅ **Couverture complète** : 11/12 critiques couvertes (92%)
- ✅ **Priorisation claire** : 3 HAUTE, 4 MOYENNE
- ✅ **Justification** : Chaque correction est justifiée
- ✅ **Indispensabilité** : Chaque correction est nécessaire à la clôture
- ✅ **Périmètre fermé** : Aucune correction supplémentaire requise

### Exclusions justifiées

- **Critique #1** (Race condition panier) : Non-critique (impact UX, pas financier)
  - Le panier est vidé après création commande
  - Si redirection échoue, commande existe quand même
  - Pas de perte financière

### Décisions bloquantes

- **CORRECTION 5** : Stratégie stock à trancher à l'ÉTAPE 3
  - Option A : Réservation de stock
  - Option B : Décrément immédiat + rollback

---

## 🎯 PROCHAINES ÉTAPES

### ÉTAPE 2/3 : IMPLÉMENTATION
- Implémenter les corrections 1, 2, 3, 4, 6, 7
- Préparer les deux options pour correction 5

### ÉTAPE 3/3 : DÉCISION MÉTIER + CLÔTURE
- Trancher correction 5 (Option A ou B)
- Implémenter correction 5
- Tests finaux
- Checklist production

---

## ✅ FIN ÉTAPE 1/3 — VALIDATION

**👉 Cette liste est la vérité finale du module Checkout/Paiement.**

**Aucun autre correctif n'est requis pour une clôture propre.**

**Statut :** ✅ **VALIDÉ — LISTE FIGÉE**

---

**Date de validation :** 2025-01-XX  
**Validé par :** Architecture Review  
**Prochaine étape :** ÉTAPE 2/3 — Implémentation



