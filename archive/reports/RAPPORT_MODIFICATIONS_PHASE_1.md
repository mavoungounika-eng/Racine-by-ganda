# 📋 RAPPORT DE MODIFICATIONS — PHASE 1

**Date :** 2025-12-10  
**Phase :** Phase 1 — Corrections critiques (P1, P2, P3)  
**Objectif :** Sécuriser le tunnel d'achat (checkout → paiement → stock)

---

## ✅ RÉSUMÉ DES MODIFICATIONS

### Fichiers modifiés : 5
### Fichiers créés : 2
### Fichiers déplacés : 1

---

## 🔥 P1 — CORRECTION GESTION STOCK POUR CASH ON DELIVERY

### Problème résolu
Le stock n'était pas décrémenté pour les commandes `cash_on_delivery` car le décrément se faisait uniquement quand `payment_status='paid'`, mais pour cash on delivery, le paiement se fait à la livraison donc `payment_status` reste `'pending'`.

### Solution implémentée
**Option A choisie** : Décrémenter le stock immédiatement à la création de la commande pour `cash_on_delivery`.

### Fichiers modifiés

#### 1. `app/Observers/OrderObserver.php`
- **Méthode `created()`** : Ajout du décrément stock immédiat pour `cash_on_delivery`
- **Méthode `handlePaymentStatusChange()`** : Ajout de commentaires expliquant la logique
- **Protection** : Le `StockService` vérifie automatiquement si un mouvement existe déjà (évite double décrément)

#### 2. `modules/ERP/Services/StockService.php`
- **Méthode `decrementFromOrder()`** : Ajout d'une vérification pour éviter le double décrément
- **Logique** : Vérifie si un mouvement de stock existe déjà pour cette commande avant de décrémenter (idempotence)
- **Documentation** : Commentaires mis à jour pour expliquer la protection

#### 3. `app/Http/Controllers/Front/CheckoutController.php`
- **Méthode `placeOrder()`** : Commentaire mis à jour pour expliquer le nouveau comportement

### Nouveau comportement

#### Carte bancaire
1. Commande créée → `status='pending'`, `payment_status='pending'`
2. Stock **NON décrémenté** à ce stade
3. Paiement Stripe → Webhook → `payment_status='paid'`
4. `OrderObserver@handlePaymentStatusChange()` → Décrément stock
5. **Résultat** : Stock décrémenté après paiement confirmé ✅

#### Mobile Money
1. Commande créée → `status='pending'`, `payment_status='pending'`
2. Stock **NON décrémenté** à ce stade
3. Paiement Mobile Money → Callback → `payment_status='paid'`
4. `OrderObserver@handlePaymentStatusChange()` → Décrément stock
5. **Résultat** : Stock décrémenté après paiement confirmé ✅

#### Cash on delivery
1. Commande créée → `status='pending'`, `payment_status='pending'`
2. `OrderObserver@created()` détecte `payment_method='cash_on_delivery'`
3. **Décrément stock immédiatement** ✅
4. Protection double décrément : Si `payment_status` passe à `'paid'` plus tard, `StockService` vérifie qu'un mouvement existe déjà
5. **Résultat** : Stock décrémenté dès la création de commande ✅

---

## 🔁 P2 — UNIFICATION DU CHECKOUT

### Problème résolu
Double système de checkout avec deux vues différentes :
- `resources/views/checkout/index.blade.php` (utilisée par `CheckoutController`)
- `resources/views/frontend/checkout/index.blade.php` (utilisée par `OrderController` legacy)

### Solution implémentée
- **Vue officielle** : `resources/views/checkout/index.blade.php` (Bootstrap, layout frontend)
- **Vue legacy** : Déplacée dans `resources/views/_legacy/checkout/`

### Fichiers modifiés / créés

#### 1. `resources/views/_legacy/checkout/frontend-index-legacy.blade.php` (déplacé)
- **Ancien emplacement** : `resources/views/frontend/checkout/index.blade.php`
- **Nouvel emplacement** : `resources/views/_legacy/checkout/frontend-index-legacy.blade.php`
- **Raison** : Vue non utilisée (OrderController n'a pas de routes actives)

#### 2. `resources/views/_legacy/checkout/README.md` (créé)
- Documentation expliquant pourquoi la vue a été archivée

### Vues conservées (utilisées par les contrôleurs de paiement)
- `resources/views/frontend/checkout/card-success.blade.php` ✅
- `resources/views/frontend/checkout/card-cancel.blade.php` ✅
- `resources/views/frontend/checkout/mobile-money-*.blade.php` ✅

### Routes vérifiées
- ✅ `checkout.index` → `CheckoutController@index` → `view('checkout.index')`
- ✅ `checkout.place` → `CheckoutController@placeOrder`
- ✅ `checkout.success` → `CheckoutController@success` → `view('checkout.success')`
- ✅ `checkout.cancel` → `CheckoutController@cancel` → `view('checkout.cancel')`

### Résultat
- **Vue checkout officielle** : `resources/views/checkout/index.blade.php`
- **Vues mises en legacy** : `frontend-index-legacy.blade.php`
- **Contrôleurs** : Aucun changement (utilisaient déjà la bonne vue)

---

## 🧹 P3 — GESTION COMMANDES / PAIEMENTS ABANDONNÉS

### Problème résolu
Les commandes `payment_status='pending'` non payées s'accumulaient dans la base de données sans être nettoyées, encombrant les tables et faussant les statistiques.

### Solution implémentée
Création d'un job `CleanupAbandonedOrders` qui nettoie automatiquement les commandes abandonnées selon leur méthode de paiement.

### Fichiers créés

#### 1. `app/Jobs/CleanupAbandonedOrders.php` (NOUVEAU)
- **Critères de nettoyage** :
  - `cash_on_delivery` : > 7 jours
  - `card` : > 24 heures
  - `mobile_money` : > 48 heures
- **Actions** :
  - Marque la commande comme `status='cancelled'`
  - Pour `cash_on_delivery` : Réintègre le stock (car décrémenté à la création)
  - Log détaillé des actions
- **Idempotence** : Peut être exécuté plusieurs fois sans problème

### Fichiers modifiés

#### 1. `bootstrap/app.php`
- **Scheduler** : Ajout du job `CleanupAbandonedOrders` planifié quotidiennement à 2h du matin

### Comportement du job

#### Critères de sélection
- Commandes avec `payment_status='pending'` ET `status='pending'`
- Selon `payment_method` :
  - `cash_on_delivery` : Créées il y a plus de **7 jours**
  - `card` : Créées il y a plus de **24 heures**
  - `mobile_money` : Créées il y a plus de **48 heures**

#### Actions effectuées
1. Pour chaque commande abandonnée :
   - Si `cash_on_delivery` : Réintègre le stock via `StockService::restockFromOrder()`
   - Marque la commande : `status='cancelled'` (garde `payment_status='pending'` pour traçabilité)
   - Log l'action avec détails (order_id, payment_method, age)

2. Log récapitulatif :
   - Nombre de commandes nettoyées par méthode de paiement
   - Statistiques globales

#### Planification
- **Fréquence** : Quotidiennement à 2h du matin
- **Commande manuelle** : `php artisan queue:work` (si queue en background) ou exécution directe

---

## 📊 RÉSUMÉ DES FLUX AVANT / APRÈS

### Avant les modifications

#### Cash on delivery
```
Commande créée → payment_status='pending' → Stock JAMAIS décrémenté ❌
```

#### Carte / Mobile Money
```
Commande créée → Paiement → payment_status='paid' → Stock décrémenté ✅
```

#### Commandes abandonnées
```
Commandes pending → S'accumulent dans la DB → Jamais nettoyées ❌
```

### Après les modifications

#### Cash on delivery
```
Commande créée → Stock décrémenté IMMÉDIATEMENT ✅
→ Si abandonnée (> 7 jours) → Stock réintégré ✅
```

#### Carte / Mobile Money
```
Commande créée → Paiement → payment_status='paid' → Stock décrémenté ✅
→ Si abandonnée (> 24h/48h) → Commande annulée ✅
```

#### Commandes abandonnées
```
Commandes pending → Job quotidien → Nettoyage automatique ✅
→ Stock réintégré si cash_on_delivery ✅
```

---

## 🧪 POINTS DE VÉRIFICATION

### P1 — Stock cash on delivery
- [ ] Créer une commande cash on delivery
- [ ] Vérifier que le stock est décrémenté immédiatement
- [ ] Vérifier qu'un mouvement de stock est créé
- [ ] Vérifier qu'un double décrément n'est pas possible (tester si payment_status passe à 'paid')

### P2 — Unification checkout
- [ ] Accéder à `/checkout` → Vérifier que la vue `checkout/index.blade.php` s'affiche
- [ ] Vérifier que le formulaire fonctionne (validation, soumission)
- [ ] Vérifier que les redirections après soumission fonctionnent (success, card, mobile_money)

### P3 — Nettoyage commandes abandonnées
- [ ] Créer des commandes test avec différents payment_method
- [ ] Modifier manuellement `created_at` pour simuler l'âge
- [ ] Exécuter le job : `php artisan queue:work` ou appeler directement
- [ ] Vérifier que les commandes sont marquées comme `cancelled`
- [ ] Vérifier que le stock est réintégré pour cash_on_delivery
- [ ] Vérifier les logs

---

## 📝 NOTES IMPORTANTES

### Protection double décrément
Le `StockService` vérifie maintenant si un mouvement de stock existe déjà pour une commande avant de décrémenter. Cela garantit l'idempotence même si :
- `OrderObserver@created()` et `OrderObserver@handlePaymentStatusChange()` sont appelés
- Un webhook/callback est reçu plusieurs fois

### Job de nettoyage
Le job `CleanupAbandonedOrders` est planifié quotidiennement. Pour le tester manuellement :
```bash
php artisan queue:work
# OU
php artisan tinker
>>> \App\Jobs\CleanupAbandonedOrders::dispatch();
```

### Vues legacy
La vue `frontend-index-legacy.blade.php` a été archivée mais peut être restaurée si nécessaire. Elle contient un stepper plus complexe qui pourrait être réutilisé plus tard.

---

## ✅ VALIDATION FINALE

- [x] P1 : Stock décrémenté pour cash on delivery
- [x] P1 : Protection double décrément implémentée
- [x] P2 : Vue checkout unifiée
- [x] P2 : Vues legacy archivées
- [x] P3 : Job de nettoyage créé
- [x] P3 : Job planifié dans le scheduler
- [x] Documentation mise à jour
- [x] Commentaires ajoutés dans le code

---

**Fin du rapport de modifications**

