# 📋 Documentation d'Architecture - Circuit Checkout
## RACINE BY GANDA

**Date de création** : 10 décembre 2025  
**Version** : 1.0  
**Statut** : ✅ Tunnel officiel sanctuarisé

---

## 🎯 Vue d'Ensemble

Le circuit de checkout de RACINE BY GANDA est basé sur une architecture unifiée et sanctuarisée autour de **CheckoutController**, le seul contrôleur officiel pour le processus de commande.

**Principe fondamental** : Un seul tunnel officiel, un seul point d'entrée, une seule logique métier.

---

## ✅ Tunnel Officiel : CheckoutController

### Contrôleur

**Fichier** : `app/Http/Controllers/Front/CheckoutController.php`

**Statut** : ✅ **OFFICIEL - ACTIF**

### Routes

Toutes les routes checkout pointent vers `CheckoutController` :

| Route | Méthode | Contrôleur | Description |
|-------|---------|------------|-------------|
| `checkout.index` | GET | `CheckoutController@index()` | Affiche le formulaire de checkout |
| `checkout.place` | POST | `CheckoutController@placeOrder()` | Traite la soumission du formulaire |
| `checkout.success` | GET | `CheckoutController@success()` | Page de succès après commande |
| `checkout.cancel` | GET | `CheckoutController@cancel()` | Page d'annulation |
| `api.checkout.verify-stock` | POST | `CheckoutController@verifyStock()` | API validation stock temps réel |
| `api.checkout.validate-email` | POST | `CheckoutController@validateEmail()` | API validation email temps réel |
| `api.checkout.validate-phone` | POST | `CheckoutController@validatePhone()` | API validation téléphone temps réel |
| `api.checkout.apply-promo` | POST | `CheckoutController@applyPromo()` | API application code promo |

**Fichier de routes** : `routes/web.php` (lignes 385-405)

### Validation

**Form Request** : `app/Http/Requests/PlaceOrderRequest.php`

**Règles de validation** :
```php
[
    'full_name'       => 'required|string|max:255',
    'email'           => 'required|email',
    'phone'           => 'required|string|max:50',
    'address_line1'   => 'required|string|max:255',
    'city'            => 'required|string|max:255',
    'country'         => 'required|string|max:255',
    'shipping_method' => 'required|in:home_delivery,showroom_pickup',
    'payment_method'  => 'required|in:mobile_money,card,cash_on_delivery',
]
```

**Valeurs `payment_method` acceptées** :
- `'mobile_money'` - Paiement Mobile Money (MTN/Airtel)
- `'card'` - Paiement par carte bancaire (Stripe)
- `'cash_on_delivery'` - Paiement à la livraison

### Service Métier

**Service** : `app/Services/OrderService.php`

**Méthode principale** : `OrderService::createOrderFromCart(array $formData, Collection $cartItems, int $userId): Order`

**Responsabilités** :
- Validation du stock avec verrouillage (`StockValidationService`)
- Calcul des montants (sous-total, livraison, total)
- Création de la commande et des items dans une transaction DB
- Émission de l'événement `OrderPlaced` pour analytics

**Avantages** :
- Logique métier centralisée
- Réutilisabilité
- Testabilité
- Séparation des responsabilités

### Observer

**Observer** : `app/Observers/OrderObserver.php`

**Méthode** : `OrderObserver@created(Order $order)`

**Logique de décrément stock** :
- **Pour `cash_on_delivery`** : Décrément immédiat à la création de la commande
  - Raison : Le paiement se fera à la livraison, donc `payment_status` restera `'pending'`
  - Si on attendait `payment_status = 'paid'`, le stock ne serait jamais décrémenté

- **Pour `card` / `mobile_money`** : Décrément dans `OrderObserver@handlePaymentStatusChange()` quand `payment_status = 'paid'`
  - Raison : Le paiement est traité via webhook/callback, donc on attend la confirmation

**Protection double décrément** : `StockService` vérifie automatiquement si un mouvement existe déjà (idempotence)

### Route Model Binding

**Utilisation** : Route model binding pour sécurité et simplicité

**Exemple** :
```php
// Route
Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

// Méthode
public function success(Order $order)
{
    $this->authorize('view', $order); // Policy pour vérifier l'accès
    // ...
}
```

**Avantages** :
- Sécurité automatique (404 si commande n'existe pas)
- Pas besoin de récupérer manuellement l'ID
- Vérification d'autorisation via `OrderPolicy`

### Vues

**Vues officielles** :
- `resources/views/checkout/index.blade.php` - Formulaire de checkout
- `resources/views/checkout/success.blade.php` - Page de succès
- `resources/views/checkout/cancel.blade.php` - Page d'annulation

**Formulaire** :
- Action : `route('checkout.place')` (POST)
- CSRF : `@csrf`
- Validation : Messages d'erreur affichés via `@error` directives

### Flux Complet

1. **Utilisateur sur `/checkout`**
   - `CheckoutController@index()` affiche le formulaire
   - Vérifications : auth, rôle client, statut actif, panier non vide

2. **Soumission du formulaire**
   - POST vers `/checkout` → `CheckoutController@placeOrder(PlaceOrderRequest $request)`
   - Validation via `PlaceOrderRequest`
   - Création commande via `OrderService::createOrderFromCart()`
   - Décrément stock immédiat pour `cash_on_delivery` (via `OrderObserver@created()`)
   - Vidage du panier
   - Redirection selon `payment_method`

3. **Redirection selon paiement**
   - `cash_on_delivery` → `/checkout/success/{order}` avec message flash
   - `card` → `/checkout/card/pay` (Stripe)
   - `mobile_money` → `/checkout/mobile-money/{order}/form`

4. **Page de succès**
   - `CheckoutController@success(Order $order)` avec route model binding
   - Vérification d'autorisation via `OrderPolicy`
   - Affichage des détails de commande et message flash

---

## ⚠️ Tunnel Legacy : OrderController

### Contrôleur

**Fichier** : `app/Http/Controllers/Front/OrderController.php`

**Statut** : ⚠️ **LEGACY - DÉPRÉCIÉ**

**Annotation** : `@deprecated` (ligne 18-34)

### Routes

❌ **AUCUNE ROUTE ACTIVE** ne pointe vers `OrderController`

**Vérification** :
```bash
grep -r "OrderController" routes/
```
Résultat : Seulement `CreatorOrderController` et `AdminOrderController` (non concernés)

### Méthodes Obsolètes

1. **`checkout()`** (ligne 42)
   - Annotée `@deprecated`
   - Équivalent : `CheckoutController@index()`
   - Route : `checkout.index`

2. **`placeOrder(Request $request)`** (ligne 93)
   - Annotée `@deprecated`
   - Équivalent : `CheckoutController@placeOrder()`
   - Route : `checkout.place`
   - ⚠️ **Incompatibilités** :
     - Utilise `payment_method: 'cash'` au lieu de `'cash_on_delivery'`
     - Redirection avec `['order_id' => $order->id]` au lieu de route model binding
     - Logique inline au lieu d'utiliser `OrderService`

3. **`success(Request $request)`** (ligne 439)
   - Annotée `@deprecated`
   - Équivalent : `CheckoutController@success()`
   - Route : `checkout.success`
   - ⚠️ **Incompatibilités** :
     - Récupère `order_id` manuellement (pas de route model binding)
     - Logique de récupération complexe et fragile

### Incompatibilités Détaillées

#### 1. Valeurs `payment_method`

**OrderController** :
```php
'payment_method' => 'required|in:card,mobile_money,cash'
```

**CheckoutController** :
```php
'payment_method' => 'required|in:mobile_money,card,cash_on_delivery'
```

**Problème** : `OrderController` utilise `'cash'` alors que `CheckoutController` utilise `'cash_on_delivery'`

**Conséquence** : Si `OrderController` était utilisé, la validation échouerait avec le formulaire actuel

#### 2. Redirection

**OrderController** :
```php
return redirect()->route('checkout.success', ['order_id' => $order->id]);
```

**CheckoutController** :
```php
return redirect()->route('checkout.success', $order); // Route model binding
```

**Problème** : `OrderController` passe un array `['order_id' => $order->id]` alors que la route attend route model binding

**Conséquence** : Erreur 404 ou exception si `OrderController` était utilisé

#### 3. Architecture

**OrderController** :
- Logique inline dans le contrôleur
- Pas de service dédié
- Validation manuelle

**CheckoutController** :
- Utilise `OrderService` pour la logique métier
- Utilise `PlaceOrderRequest` pour la validation
- Séparation des responsabilités

**Conséquence** : Maintenance difficile, code dupliqué, bugs potentiels

#### 4. Décrément Stock

**OrderController** :
- Décrément dans `OrderObserver@updated()` quand `payment_status = 'paid'`
- Pour `cash`, met `payment_status = 'paid'` après création

**CheckoutController** :
- Décrément immédiat dans `OrderObserver@created()` pour `cash_on_delivery`
- Décrément dans `OrderObserver@handlePaymentStatusChange()` pour `card`/`mobile_money`

**Conséquence** : Comportement incohérent, risque de double décrément ou non-décrément

### Vues Legacy

**Vue** : `resources/views/_legacy/checkout/frontend-index-legacy.blade.php`

**Statut** : ⚠️ **ARCHIVÉE** dans `_legacy/checkout/`

**Documentation** : `resources/views/_legacy/checkout/README.md`

---

## 🏗️ Décision d'Architecture

### Principe : Un Seul Tunnel Officiel

**Décision** : `CheckoutController` est le **seul tunnel officiel** pour le checkout.

**Raisons** :
1. **Cohérence** : Une seule logique métier, une seule validation
2. **Maintenance** : Code centralisé, plus facile à maintenir
3. **Sécurité** : Route model binding, policies, validation centralisée
4. **Évolutivité** : Architecture modulaire (Service, Request, Observer)
5. **Tests** : Plus facile à tester avec services dédiés

### OrderController : Conservation Temporaire

**Décision** : `OrderController` est conservé temporairement pour référence historique.

**Raisons** :
1. **Référence** : Permet de comprendre l'évolution du code
2. **Migration** : Facilite la migration si nécessaire
3. **Documentation** : Sert d'exemple de ce qu'il ne faut pas faire

**Suppression future** : `OrderController` sera supprimé dans une future version après validation complète.

---

## 📋 Recommandations Futures

### Court Terme (1-2 semaines)

1. **Tests Feature**
   - Ajouter des tests Feature pour `CheckoutController`
   - Tester les 3 modes de paiement (`cash_on_delivery`, `card`, `mobile_money`)
   - Tester les redirections et messages flash

2. **Surveillance**
   - Vérifier les logs pour confirmer qu'aucun appel vers `OrderController` n'apparaît
   - Surveiller les erreurs 404/500 liées au checkout

### Moyen Terme (1-2 mois)

1. **Documentation**
   - Ajouter des exemples d'utilisation dans la documentation développeur
   - Créer un guide de migration si nécessaire

2. **Amélioration**
   - Centraliser toute la logique checkout dans `CheckoutController`
   - Améliorer les tests de non-régression

### Long Terme (3-6 mois)

1. **Suppression**
   - Supprimer complètement `OrderController` après validation
   - Supprimer les vues legacy si non nécessaires

2. **Évolution**
   - Améliorer l'architecture checkout si nécessaire
   - Ajouter de nouvelles fonctionnalités (codes promo, points fidélité, etc.)

---

## 🔍 Vérifications

### Routes

✅ **Confirmé** : Aucune route ne pointe vers `OrderController`

**Commande de vérification** :
```bash
php artisan route:list | grep checkout
```

**Résultat attendu** : Toutes les routes checkout pointent vers `CheckoutController`

### Vues

✅ **Confirmé** : Toutes les vues actives pointent vers `CheckoutController`

- `checkout/index.blade.php` → `route('checkout.place')` ✅
- `checkout/success.blade.php` → Utilisée par `CheckoutController@success()` ✅
- `checkout/cancel.blade.php` → Utilisée par `CheckoutController@cancel()` ✅

### Code

✅ **Confirmé** : `OrderController` est clairement marqué comme `@deprecated`

- Classe annotée ✅
- Méthodes annotées ✅
- Documentation des incompatibilités ✅
- Références vers `CheckoutController` ✅

---

## 📚 Références

### Fichiers Clés

- **Contrôleur officiel** : `app/Http/Controllers/Front/CheckoutController.php`
- **Contrôleur legacy** : `app/Http/Controllers/Front/OrderController.php` (déprécié)
- **Form Request** : `app/Http/Requests/PlaceOrderRequest.php`
- **Service** : `app/Services/OrderService.php`
- **Observer** : `app/Observers/OrderObserver.php`
- **Routes** : `routes/web.php` (lignes 385-405)

### Documentation Associée

- **Rapport d'analyse** : `RAPPORT_ANALYSE_PHASE1.md`
- **Rapport final** : `RAPPORT_FINAL_ASSainissement_CHECKOUT.md`

---

**Date de dernière mise à jour** : 10 décembre 2025  
**Auteur** : Architecte Laravel 12 + QA Senior  
**Statut** : ✅ Documentation complète et validée

