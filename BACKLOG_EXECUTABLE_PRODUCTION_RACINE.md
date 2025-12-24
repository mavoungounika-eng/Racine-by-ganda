# 📋 BACKLOG EXÉCUTABLE - PRODUCTION RACINE BY GANDA

**Date :** 10 décembre 2025  
**Version :** 1.0  
**Format :** Jira/Trello compatible  
**Statut :** Prêt pour intégration dans outil de gestion

---

## 1️⃣ CADRE D'EXÉCUTION

### Colonnes Trello / Statuts Jira

**Backlog → Ready → In Progress → Code Review → QA/Staging → Done**

### Definition of Done (DoD) commune à tous les tickets

✅ Code + tests associés (Feature/Unit) ajoutés ou mis à jour  
✅ `php artisan test` OK  
✅ Pas de régression checkout (par tests)  
✅ Changelog/notes de version internes (1–5 lignes)  
✅ Si impact DB : migration réversible + testée  
✅ Code review validé  
✅ Documentation mise à jour si nécessaire

### Branching minimal

* `main` (prod)
* `develop` (intégration)
* `fix/*` (P0)
* `feat/*` (P1/P2)

### Conventions de nommage

* **P0** = Bloquant production (critique)
* **P1** = Haute priorité (important)
* **P2** = Amélioration structurante (nice to have)

---

## 2️⃣ BACKLOG PRIORISÉ

---

# 🎯 EPIC E1 — Stabilité DB & Tests (P0)

**Objectif :** Garantir que l'environnement `testing` (SQLite) + la prod (MySQL/PostgreSQL) convergent sans surprises.

**Valeur métier :** Éviter les bugs en production dus à des différences d'environnement.

---

### RBG-P0-001 — Pipeline local "migrations + tests" en environnement testing

**Type :** Bug / Technical Debt  
**Priorité :** P0 (Bloquant)  
**Estimation :** M (3-5 jours)

**Description :**  
Vérifier que `migrate:fresh` + tests passent sur SQLite sans erreurs. Actuellement, certaines migrations peuvent échouer silencieusement ou nécessiter des workarounds.

**Acceptance Criteria (AC) :**

* ✅ `php artisan migrate:fresh --env=testing` s'exécute sans erreur
* ✅ `php artisan test` passe à 100% (tous les tests)
* ✅ Aucune erreur "index already exists / foreign key / alter table" sur SQLite
* ✅ Les migrations sont idempotentes (peuvent être exécutées plusieurs fois)
* ✅ Documentation des workarounds SQLite si nécessaire

**Commandes de validation :**
```bash
# Nettoyer l'environnement
php artisan config:clear
php artisan cache:clear

# Réinitialiser la base de test
php artisan migrate:fresh --env=testing

# Exécuter tous les tests
php artisan test

# Vérifier les logs d'erreur
tail -f storage/logs/laravel.log
```

**Fichiers impactés :**
- `database/migrations/2025_12_10_105138_add_missing_indexes_for_orders_and_payments.php`
- `database/migrations/2025_12_08_000001_add_indexes_for_performance.php`
- `phpunit.xml`

**Tests à ajouter :**
- Test de migration complète sur SQLite
- Test de migration complète sur MySQL (staging)

**Notes :**
- Les migrations avec `try-catch` pour indexes doivent être documentées
- Vérifier que les migrations sont réversibles (`down()` fonctionne)

---

### RBG-P0-002 — Normaliser les migrations sensibles SQLite

**Type :** Technical Debt  
**Priorité :** P0 (Bloquant)  
**Estimation :** L (5-8 jours)

**Description :**  
Identifier et corriger toutes les migrations qui utilisent des patterns non compatibles SQLite (indexes conditionnels, alter table complexes, `information_schema`).

**Acceptance Criteria (AC) :**

* ✅ Toutes les migrations passent en SQLite sans try/catch "aveugle" non documenté
* ✅ Chaque workaround SQLite est commenté (raison + lien vers ticket)
* ✅ Les migrations sont testées sur SQLite ET MySQL
* ✅ Aucune utilisation de `information_schema.statistics` (non supporté SQLite)
* ✅ Les `Schema::hasIndex()` sont remplacés par try-catch documentés

**Commandes de validation :**
```bash
# Test SQLite
php artisan migrate:fresh --env=testing
php artisan test

# Test MySQL (staging)
php artisan migrate:fresh --env=staging
php artisan test --env=staging
```

**Fichiers impactés :**
- Toutes les migrations dans `database/migrations/`
- Focus sur :
  - `2025_12_10_105138_add_missing_indexes_for_orders_and_payments.php`
  - `2025_12_08_000001_add_indexes_for_performance.php`
  - `2025_01_27_000009_add_promo_code_to_orders_table.php`
  - `2025_01_27_000010_add_payment_method_to_orders_table.php`

**Pattern à utiliser :**
```php
// Workaround SQLite : SQLite ne supporte pas information_schema.statistics
// Utilisation de try-catch pour gérer les erreurs "index already exists"
// Voir ticket RBG-P0-002
try {
    $table->index('column_name', 'index_name');
} catch (\Exception $e) {
    // Index existe déjà, ignorer l'erreur
    if (!str_contains($e->getMessage(), 'Duplicate key name') && 
        !str_contains($e->getMessage(), 'already exists')) {
        throw $e;
    }
}
```

**Tests à ajouter :**
- Test de migration sur SQLite
- Test de migration sur MySQL
- Test de rollback (`migrate:rollback`)

---

### RBG-P1-003 — Rapport "DB Compatibility Matrix"

**Type :** Documentation  
**Priorité :** P1 (Haute)  
**Estimation :** S (1-2 jours)

**Description :**  
Documenter ce qui est garanti cross-DB vs prod-only. Créer une matrice de compatibilité claire pour les développeurs.

**Acceptance Criteria (AC) :**

* ✅ Document Markdown créé : `docs/DATABASE_COMPATIBILITY.md`
* ✅ Liste des fonctionnalités : "OK MySQL/PostgreSQL/SQLite", "Prod only" + justification
* ✅ Exemples de code cross-DB vs prod-only
* ✅ Guide de migration cross-DB
* ✅ Workarounds SQLite documentés

**Contenu du document :**

```markdown
# Database Compatibility Matrix

## Support par SGBD

| Fonctionnalité | MySQL | PostgreSQL | SQLite | Notes |
|----------------|-------|------------|--------|-------|
| Indexes conditionnels | ✅ | ✅ | ⚠️ Workaround | Try-catch nécessaire |
| Foreign keys | ✅ | ✅ | ✅ | |
| Transactions | ✅ | ✅ | ✅ | |
| information_schema | ✅ | ✅ | ❌ | Non supporté SQLite |
| ...

## Workarounds SQLite

### Indexes
- Utiliser try-catch au lieu de hasIndex()
- Voir ticket RBG-P0-002

## Tests
- Tests unitaires : SQLite (rapide)
- Tests staging : MySQL (prod-like)
```

**Fichiers à créer :**
- `docs/DATABASE_COMPATIBILITY.md`

---

# 🔒 EPIC E2 — Sécurité Paiements & Webhooks (P0)

**Objectif :** Zéro callback non authentifié / zero paiement spoofable.

**Valeur métier :** Prévenir la fraude et les paiements non autorisés.

---

### RBG-P0-010 — Stripe : activer et imposer la signature en production

**Type :** Security / Bug  
**Priorité :** P0 (Bloquant)  
**Estimation :** M (3-5 jours)

**Description :**  
Vérifier que la vérification de signature Stripe n'est pas commentée / contournée. Actuellement, certains webhooks peuvent être acceptés sans vérification de signature.

**Acceptance Criteria (AC) :**

* ✅ Signature requise en production (vérification active)
* ✅ Requête non signée → 4xx + log structuré
* ✅ Test (Unit ou Feature) sur webhook "invalid signature"
* ✅ Test sur webhook "missing signature"
* ✅ Documentation de la configuration Stripe webhook secret
* ✅ Logs de sécurité pour tentatives invalides

**Commandes de validation :**
```bash
# Test webhook invalide
curl -X POST http://localhost/payment/card/webhook \
  -H "Content-Type: application/json" \
  -d '{"type":"payment_intent.succeeded","data":{}}'
# Doit retourner 401/403

# Test webhook valide (nécessite signature réelle)
# Utiliser Stripe CLI pour générer une signature valide
```

**Fichiers impactés :**
- `app/Http/Controllers/Front/CardPaymentController.php`
- `app/Services/Payments/StripePaymentService.php`
- `config/services.php` (webhook secret)

**Code à vérifier/corriger :**
```php
// AVANT (DANGEREUX)
// $signature = $request->header('Stripe-Signature');
// $event = \Stripe\Webhook::constructEvent(...); // Commenté

// APRÈS (SÉCURISÉ)
$signature = $request->header('Stripe-Signature');
if (!$signature) {
    Log::warning('Stripe webhook: Missing signature', [
        'ip' => $request->ip(),
        'url' => $request->fullUrl(),
    ]);
    abort(401, 'Missing signature');
}

$event = \Stripe\Webhook::constructEvent(
    $payload,
    $signature,
    config('services.stripe.webhook_secret')
);
```

**Tests à ajouter :**
- `tests/Feature/PaymentWebhookSecurityTest.php`
  - Test webhook sans signature → 401
  - Test webhook signature invalide → 401
  - Test webhook signature valide → 200

**Variables d'environnement :**
- `STRIPE_WEBHOOK_SECRET` (à documenter dans `.env.example`)

---

### RBG-P0-011 — Mobile Money : durcir la validation callback (MTN/Airtel)

**Type :** Security  
**Priorité :** P0 (Bloquant)  
**Estimation :** L (5-8 jours)

**Description :**  
Renforcer l'authenticité/anti-replay des callbacks Mobile Money selon le provider (HMAC, token, timestamp, idempotency).

**Acceptance Criteria (AC) :**

* ✅ Callback invalide rejeté (signature/token invalide)
* ✅ Double callback même transaction = idempotent (pas de double "paid")
* ✅ Logs complets (provider, txn_id, status, signature_ok, timestamp)
* ✅ Vérification timestamp (rejet si trop ancien, ex: > 5 min)
* ✅ Test de replay attack (même callback 2x)
* ✅ Documentation de la configuration par provider

**Commandes de validation :**
```bash
# Test callback invalide
curl -X POST http://localhost/payment/mobile-money/callback \
  -H "Content-Type: application/json" \
  -d '{"txn_id":"test","status":"success"}'
# Doit retourner 401/403

# Test replay (même callback 2x)
# Le deuxième doit être ignoré (idempotent)
```

**Fichiers impactés :**
- `app/Http/Controllers/Front/MobileMoneyPaymentController.php`
- `app/Services/Payments/MobileMoneyPaymentService.php`
- `app/Models/Payment.php` (ajouter `txn_id` unique si nécessaire)

**Code à implémenter :**
```php
// Vérification signature/token
if (!$this->validateCallbackSignature($request, $provider)) {
    Log::warning('Mobile Money callback: Invalid signature', [
        'provider' => $provider,
        'txn_id' => $request->input('txn_id'),
        'ip' => $request->ip(),
    ]);
    abort(401, 'Invalid signature');
}

// Vérification timestamp (anti-replay)
$timestamp = $request->input('timestamp');
if (abs(time() - $timestamp) > 300) { // 5 minutes
    Log::warning('Mobile Money callback: Timestamp too old', [
        'provider' => $provider,
        'txn_id' => $request->input('txn_id'),
        'timestamp' => $timestamp,
    ]);
    abort(401, 'Timestamp too old');
}

// Idempotence (vérifier si déjà traité)
$payment = Payment::where('provider_txn_id', $request->input('txn_id'))
    ->where('provider', $provider)
    ->first();

if ($payment && $payment->status === 'paid') {
    Log::info('Mobile Money callback: Already processed (idempotent)', [
        'provider' => $provider,
        'txn_id' => $request->input('txn_id'),
        'payment_id' => $payment->id,
    ]);
    return response()->json(['status' => 'already_processed'], 200);
}
```

**Tests à ajouter :**
- `tests/Feature/MobileMoneyWebhookSecurityTest.php`
  - Test callback sans signature → 401
  - Test callback signature invalide → 401
  - Test callback timestamp trop ancien → 401
  - Test replay (même callback 2x) → idempotent
  - Test callback valide → 200

**Variables d'environnement :**
- `MTN_MOMO_SECRET_KEY` (à documenter)
- `AIRTEL_MONEY_SECRET_KEY` (à documenter)

---

### RBG-P1-012 — Rate limiting sur endpoints sensibles (paiement + checkout verify stock)

**Type :** Security / Performance  
**Priorité :** P1 (Haute)  
**Estimation :** S (1-2 jours)

**Description :**  
Ajouter rate limiting sur les endpoints critiques (paiements, vérification stock, checkout) pour prévenir les abus.

**Acceptance Criteria (AC) :**

* ✅ Rate limiting configuré sur routes critiques
* ✅ Limites définies et testées (ex: 10 req/min pour checkout, 5 req/min pour paiement)
* ✅ Pas de dégradation UX (limites raisonnables)
* ✅ Messages d'erreur clairs (429 avec retry-after)
* ✅ Logs des rate limits dépassés

**Routes à protéger :**
- `POST /checkout` (création commande)
- `POST /api/checkout/verify-stock` (vérification stock)
- `POST /payment/card/webhook` (webhook Stripe)
- `POST /payment/mobile-money/callback` (callback Mobile Money)
- `POST /checkout/card/pay` (initiation paiement carte)

**Configuration :**
```php
// routes/web.php ou bootstrap/app.php
Route::middleware(['throttle:checkout'])->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'placeOrder']);
});

// config/services.php ou config/rate-limiting.php
'checkout' => [
    'max_attempts' => 10,
    'decay_minutes' => 1,
],
'payment' => [
    'max_attempts' => 5,
    'decay_minutes' => 1,
],
```

**Tests à ajouter :**
- Test rate limit dépassé → 429
- Test rate limit reset après délai
- Test rate limit par IP (pas global)

**Fichiers impactés :**
- `routes/web.php`
- `bootstrap/app.php` (middleware throttle)
- `config/services.php` (ou nouveau fichier `config/rate-limiting.php`)

---

# 🛒 EPIC E3 — Checkout & Concurrence Stock (P0/P1)

**Objectif :** Empêcher l'oversell et garantir l'intégrité commande/stock.

**Valeur métier :** Éviter les commandes impossibles à honorer (survente).

---

### RBG-P0-020 — Verrouillage stock "anti-oversell" (transactions + lock)

**Type :** Bug / Security  
**Priorité :** P0 (Bloquant)  
**Estimation :** L (5-8 jours)

**Description :**  
Mettre en place une stratégie fiable (pessimistic locking ou optimistic) dans `OrderService::createOrderFromCart()` + observer pour empêcher la survente en cas de commandes simultanées.

**Acceptance Criteria (AC) :**

* ✅ Impossible de commander plus que stock disponible en concurrence
* ✅ Test dédié qui simule 2 commandes simultanées (au minimum via transactions / double requête)
* ✅ Lock pessimiste ou version optimistic sur `products.stock`
* ✅ Transaction DB pour atomicité (création commande + décrément stock)
* ✅ Message d'erreur clair si stock insuffisant ("Stock insuffisant, il reste X unités")
* ✅ Logs des tentatives de survente

**Scénario de test :**
```
Produit A : stock = 5
Commande 1 (simultanée) : quantité = 3 → OK (stock devient 2)
Commande 2 (simultanée) : quantité = 4 → ÉCHEC (stock insuffisant)
```

**Code à implémenter :**
```php
// app/Services/OrderService.php
public function createOrderFromCart(array $formData, Collection $cartItems, int $userId): Order
{
    return DB::transaction(function () use ($formData, $cartItems, $userId) {
        // Verrouillage pessimiste sur les produits
        $productIds = $cartItems->pluck('product_id')->toArray();
        $products = Product::lockForUpdate()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        // Validation stock avec lock
        foreach ($cartItems as $item) {
            $product = $products->get($item->product_id);
            
            if (!$product || $product->stock < $item->quantity) {
                throw new StockException(
                    "Stock insuffisant pour {$product->title}. " .
                    "Stock disponible : {$product->stock}, " .
                    "Quantité demandée : {$item->quantity}"
                );
            }
        }

        // Création commande
        $order = Order::create([...]);

        // Décrément stock (dans la même transaction)
        foreach ($cartItems as $item) {
            $product = $products->get($item->product_id);
            $product->decrement('stock', $item->quantity);
        }

        return $order;
    });
}
```

**Tests à ajouter :**
- `tests/Feature/StockConcurrencyTest.php`
  - Test 2 commandes simultanées sur même produit
  - Test commande avec stock exact
  - Test commande avec stock insuffisant
  - Test rollback si erreur (stock non décrémenté)

**Fichiers impactés :**
- `app/Services/OrderService.php`
- `app/Services/StockValidationService.php`
- `app/Observers/OrderObserver.php` (vérifier cohérence)

---

### RBG-P1-021 — Audit cohérence Observer / décrément stock

**Type :** Technical Debt / Bug  
**Priorité :** P1 (Haute)  
**Estimation :** M (3-5 jours)

**Description :**  
Vérifier que le décrément stock se fait une seule fois, au bon moment (paid vs placed selon logique), et documenter la stratégie.

**Acceptance Criteria (AC) :**

* ✅ Le décrément se fait une seule fois (pas de double décrément)
* ✅ Statuts commandes documentés et alignés avec décrément
* ✅ Logique claire : `cash_on_delivery` → décrément à `created`, `card/mobile_money` → décrément à `paid`
* ✅ Test de non-double-décrément
* ✅ Documentation de la stratégie dans `docs/architecture/checkout-audit.md`

**Logique actuelle (à vérifier) :**
- `cash_on_delivery` : Décrément dans `OrderObserver@created()` (immédiat)
- `card` / `mobile_money` : Décrément dans `OrderObserver@handlePaymentStatusChange()` quand `payment_status = 'paid'`

**Code à auditer :**
- `app/Observers/OrderObserver.php`
- `app/Services/OrderService.php`
- `app/Services/StockValidationService.php`

**Tests à ajouter :**
- Test décrément `cash_on_delivery` (immédiat)
- Test décrément `card` (après paiement)
- Test non-double-décrément (même commande 2x)
- Test rollback si annulation (stock restauré)

**Documentation à mettre à jour :**
- `docs/architecture/checkout-audit.md` (section "Observer")

---

# ⚡ EPIC E4 — Performance & N+1 (P1)

**Objectif :** Stabiliser les dashboards et pages critiques en temps de réponse.

**Valeur métier :** Améliorer l'expérience utilisateur et réduire la charge serveur.

---

### RBG-P1-030 — Audit N+1 sur pages critiques

**Type :** Performance / Technical Debt  
**Priorité :** P1 (Haute)  
**Estimation :** L (5-8 jours)

**Description :**  
Identifier et corriger les requêtes N+1 sur les pages critiques (boutique, checkout, dashboards).

**Cibles prioritaires :**

* ✅ Boutique : listing + show produit
* ✅ Checkout : récap + success
* ✅ Admin dashboard(s)
* ✅ Creator dashboard
* ✅ Liste commandes (admin + créateur)

**Acceptance Criteria (AC) :**

* ✅ Liste des requêtes avant/après (documentée)
* ✅ Eager loading ajouté là où nécessaire (`with()`, `load()`)
* ✅ Aucun changement fonctionnel (même résultat)
* ✅ Réduction du nombre de requêtes DB (objectif : -50% minimum)
* ✅ Tests de performance (temps de réponse)

**Outils d'audit :**
```php
// Activer query log
DB::enableQueryLog();

// ... code à auditer ...

$queries = DB::getQueryLog();
Log::info('Queries executed', ['count' => count($queries), 'queries' => $queries]);
```

**Pages à auditer :**

1. **Boutique (`/shop`)**
   - Fichier : `app/Http/Controllers/Front/FrontendController.php@shop()`
   - Vérifier : `Product::with(['category', 'creator'])`

2. **Détail produit (`/products/{slug}`)**
   - Vérifier : Relations `category`, `creator`, `reviews`, `orderItems`

3. **Checkout (`/checkout`)**
   - Fichier : `app/Http/Controllers/Front/CheckoutController.php@index()`
   - Vérifier : `CartItem::with(['product.category', 'product.creator'])`

4. **Admin Dashboard**
   - Fichier : `app/Http/Controllers/Admin/AdminDashboardController.php`
   - Vérifier : Toutes les requêtes (orders, products, users, payments)

5. **Creator Dashboard**
   - Fichier : `app/Http/Controllers/Creator/CreatorDashboardController.php`
   - Vérifier : Orders, products, finances

**Tests à ajouter :**
- Test de nombre de requêtes (max 10-15 par page)
- Test de temps de réponse (max 500ms pour pages publiques)

**Fichiers impactés :**
- Tous les contrôleurs listés ci-dessus
- Focus sur les méthodes `index()`, `show()`, `dashboard()`

---

### RBG-P1-031 — Cache invalidation (produits/catégories/CMS)

**Type :** Performance / Bug  
**Priorité :** P1 (Haute)  
**Estimation :** M (3-5 jours)

**Description :**  
Mettre en place une invalidation automatique du cache après CRUD sur produits, catégories, CMS.

**Acceptance Criteria (AC) :**

* ✅ Après CRUD produit, cache produits invalidé
* ✅ Après CRUD catégorie, cache catégories invalidé
* ✅ Après CRUD CMS, cache CMS invalidé
* ✅ Pas d'affichage de contenu obsolète sur pages publiques
* ✅ Tests de cache invalidation

**Stratégie d'invalidation :**

```php
// Observer ou Event Listener
class ProductObserver
{
    public function saved(Product $product)
    {
        Cache::forget('shop_categories_hierarchical');
        Cache::forget('shop_products_*'); // Pattern matching
        Cache::tags(['products', 'categories'])->flush();
    }
}
```

**Clés de cache à invalider :**

- Produits : `shop_products_*` (pattern)
- Catégories : `shop_categories_hierarchical`
- CMS : `cms_page_*`, `cms_section_*`

**Tests à ajouter :**
- Test cache invalidation après création produit
- Test cache invalidation après mise à jour catégorie
- Test cache invalidation après suppression CMS

**Fichiers impactés :**
- `app/Observers/ProductObserver.php`
- `app/Http/Controllers/Admin/AdminCategoryController.php`
- `modules/CMS/Http/Controllers/CmsPageController.php`
- `app/Services/CmsContentService.php`

---

### RBG-P2-032 — Mise en queue des emails/notifications lourdes

**Type :** Performance  
**Priorité :** P2 (Amélioration)  
**Estimation :** M (3-5 jours)

**Description :**  
Déplacer l'envoi d'emails et notifications lourdes vers une queue pour ne pas bloquer les requêtes utilisateur.

**Acceptance Criteria (AC) :**

* ✅ Envoi via queue (config documentée)
* ✅ Pas de régression des notifications (toujours envoyées)
* ✅ Retry automatique en cas d'échec
* ✅ Monitoring de la queue (logs, échecs)
* ✅ Tests de queue

**Emails à mettre en queue :**

- `OrderConfirmationMail`
- `OrderStatusUpdateMail`
- `NewMessageMail`
- `MessageReplyMail`
- `WelcomeMail`
- `SecurityAlertMail`

**Code à modifier :**
```php
// AVANT
Mail::to($user)->send(new OrderConfirmationMail($order));

// APRÈS
Mail::to($user)->queue(new OrderConfirmationMail($order));
// ou
dispatch(new SendOrderConfirmationMail($order));
```

**Configuration :**
```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'database'), // ou 'redis'
```

**Tests à ajouter :**
- Test email mis en queue (pas envoyé immédiatement)
- Test email envoyé après traitement queue
- Test retry en cas d'échec

**Fichiers impactés :**
- Tous les contrôleurs/services qui envoient des emails
- `app/Mail/*` (vérifier que les classes sont queueable)

---

# 🔄 EPIC E5 — Simplification Auth + Dashboards (P2)

**Objectif :** Réduire complexité sans casser (stratégie progressive).

**Valeur métier :** Faciliter la maintenance et réduire les bugs.

---

### RBG-P2-040 — Cartographie Auth (4 systèmes → cible)

**Type :** Documentation / Technical Debt  
**Priorité :** P2 (Amélioration)  
**Estimation :** S (1-2 jours)

**Description :**  
Créer une cartographie complète des 4 systèmes d'authentification existants et proposer une architecture cible unifiée.

**Acceptance Criteria (AC) :**

* ✅ Diagramme de flux d'authentification (1 diagramme)
* ✅ Mapping routes/guards/middlewares (tableau)
* ✅ Chaque flux décrit : Public (client/créateur), ERP (admin/staff), 2FA, reset password, OAuth
* ✅ Points de duplication listés
* ✅ Architecture cible proposée (1 diagramme)

**Livrable :**
- Document : `docs/architecture/auth-mapping.md`
- Diagrammes : Mermaid ou PlantUML

**Contenu du document :**

```markdown
# Cartographie Authentification

## Systèmes actuels

1. PublicAuthController (`/login`)
2. AdminAuthController (`/admin/login`)
3. ErpAuthController (`/erp/login`)
4. AuthHubController (`/auth`)

## Flux par type d'utilisateur

### Client
- Route : `/login`
- Controller : `PublicAuthController`
- Redirection : `/compte`
- 2FA : Optionnel

### Créateur
- Route : `/createur/login`
- Controller : `CreatorAuthController`
- Redirection : `/createur/dashboard` (si actif)
- 2FA : Optionnel

### Admin
- Route : `/admin/login`
- Controller : `AdminAuthController`
- Redirection : `/admin/dashboard`
- 2FA : Optionnel

### Staff
- Route : `/erp/login`
- Controller : `ErpAuthController`
- Redirection : `/erp/dashboard`
- 2FA : Optionnel

## Architecture cible

Un seul point d'entrée `/auth` avec redirection intelligente selon le rôle.
```

**Fichiers à créer :**
- `docs/architecture/auth-mapping.md`

---

### RBG-P2-041 — Unification progressive via "AuthHub" + stratégie Strangler

**Type :** Refactoring  
**Priorité :** P2 (Amélioration)  
**Estimation :** L (5-8 jours)

**Description :**  
Unifier progressivement les 4 systèmes d'authentification via `AuthHubController` en utilisant une stratégie Strangler (les anciens contrôleurs deviennent des wrappers).

**Acceptance Criteria (AC) :**

* ✅ Un point d'entrée stable `/auth`
* ✅ Redirections déterministes par rôle
* ✅ Les anciens contrôleurs restent mais deviennent "thin wrappers" avant suppression
* ✅ Pas de régression (tous les tests passent)
* ✅ Documentation de la migration

**Stratégie Strangler :**

```php
// Étape 1 : AuthHub devient le point d'entrée principal
// Étape 2 : Les anciens contrôleurs redirigent vers AuthHub
// Étape 3 : Après validation, suppression des anciens contrôleurs

// Exemple : PublicAuthController devient un wrapper
class PublicAuthController extends Controller
{
    public function showLoginForm()
    {
        // Rediriger vers AuthHub avec contexte
        return redirect()->route('auth.hub', ['type' => 'public']);
    }
}
```

**Tests à ajouter :**
- Test redirection depuis anciennes routes
- Test redirection selon rôle après login
- Test 2FA toujours fonctionnel

**Fichiers impactés :**
- `app/Http/Controllers/Auth/AuthHubController.php`
- `app/Http/Controllers/Auth/PublicAuthController.php`
- `app/Http/Controllers/Admin/AdminAuthController.php`
- `app/Http/Controllers/Auth/ErpAuthController.php`

---

### RBG-P2-042 — Rationalisation des 7 dashboards (inventaire + cible)

**Type :** Documentation / Technical Debt  
**Priorité :** P2 (Amélioration)  
**Estimation :** M (3-5 jours)

**Description :**  
Faire l'inventaire des 7 dashboards existants et proposer une cible : 1 Admin, 1 Creator, 1 ERP (+ pages spécialisées si besoin).

**Acceptance Criteria (AC) :**

* ✅ Liste des dashboards existants (tableau)
* ✅ Cible : 1 Admin, 1 Creator, 1 ERP (+ pages spécialisées si besoin)
* ✅ Plan de migration écran par écran
* ✅ Documentation des fonctionnalités par dashboard

**Dashboards identifiés :**

1. Admin Dashboard (`/admin/dashboard`)
2. ERP Dashboard (`/erp/dashboard`)
3. Creator Dashboard (`/createur/dashboard`)
4. Analytics Dashboard (`/admin/analytics`)
5. CRM Dashboard (`/crm/dashboard`)
6. CMS Dashboard (`/cms/dashboard`)
7. Client Dashboard (`/compte`)

**Livrable :**
- Document : `docs/architecture/dashboards-inventory.md`
- Plan de migration

**Fichiers à créer :**
- `docs/architecture/dashboards-inventory.md`

---

# 📚 EPIC E6 — Documentation Production (P1/P2)

**Objectif :** Rendre le projet déployable et maintenable par n'importe quel dev.

**Valeur métier :** Faciliter l'onboarding et réduire les erreurs de déploiement.

---

### RBG-P1-050 — INSTALL.md + .env.example complet

**Type :** Documentation  
**Priorité :** P1 (Haute)  
**Estimation :** M (3-5 jours)

**Description :**  
Créer un guide d'installation complet et un fichier `.env.example` avec toutes les variables nécessaires.

**Acceptance Criteria (AC) :**

* ✅ Document `INSTALL.md` créé
* ✅ Setup local décrit (étapes claires)
* ✅ Setup staging décrit
* ✅ Variables obligatoires listées (Stripe/MM/Google OAuth/2FA/Cache/Queue)
* ✅ `.env.example` complet avec toutes les variables
* ✅ Commandes de vérification (tests, migrations)

**Contenu de `INSTALL.md` :**

```markdown
# Guide d'Installation - RACINE BY GANDA

## Prérequis

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8+ ou PostgreSQL 13+
- Redis (recommandé pour cache/queue)

## Installation locale

1. Cloner le projet
2. Installer les dépendances
3. Configurer `.env`
4. Lancer les migrations
5. Créer les comptes de test
6. Lancer les tests

## Variables d'environnement

### Obligatoires
- `APP_NAME`, `APP_URL`, `APP_KEY`
- `DB_*` (connexion base de données)
- `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- `MTN_MOMO_SECRET_KEY`, `AIRTEL_MONEY_SECRET_KEY`

### Optionnelles
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` (OAuth)
- `REDIS_HOST`, `REDIS_PORT` (Cache/Queue)
- `MAIL_*` (Configuration email)
```

**Fichiers à créer :**
- `INSTALL.md`
- `.env.example` (vérifier qu'il est complet)

---

### RBG-P2-051 — Swagger/OpenAPI (minimum vital)

**Type :** Documentation  
**Priorité :** P2 (Amélioration)  
**Estimation :** L (5-8 jours)

**Description :**  
Créer une documentation API Swagger/OpenAPI pour les endpoints critiques.

**Acceptance Criteria (AC) :**

* ✅ Endpoints critiques documentés : auth, checkout verify stock, payments webhooks, orders status
* ✅ Swagger UI accessible (`/api/docs`)
* ✅ Exemples de requêtes/réponses
* ✅ Schémas de données (Request/Response)

**Endpoints à documenter :**

- `POST /api/auth/login`
- `POST /api/checkout/verify-stock`
- `POST /payment/card/webhook`
- `POST /payment/mobile-money/callback`
- `GET /api/orders/{id}/status`

**Outils recommandés :**
- `darkaonline/l5-swagger` (Laravel + Swagger)

**Fichiers à créer :**
- `app/Http/Controllers/Api/*` (si API dédiée)
- `swagger.yaml` ou annotations dans contrôleurs

---

## 3️⃣ GO / NO-GO PRODUCTION

### ✅ GO (tous obligatoires)

- [ ] **Tous les tests passent** : `php artisan test` (sur env prod-like + env testing)
- [ ] **Webhooks Stripe** : signature activée et testée (ticket RBG-P0-010)
- [ ] **Mobile Money** : validation + idempotence en place (ticket RBG-P0-011)
- [ ] **Checkout** : anti-oversell validé (test concurrence) (ticket RBG-P0-020)
- [ ] **Migration prod** : `php artisan migrate --force` OK sur staging
- [ ] **Logs** : rotation/config OK + pas de secrets en clair
- [ ] **Plan rollback** : version taguée + procédure documentée
- [ ] **Rate limiting** : configuré sur endpoints critiques (ticket RBG-P1-012)
- [ ] **Cache** : configuré (Redis recommandé)
- [ ] **Queue** : configurée (Redis/Beanstalkd)
- [ ] **Monitoring** : configuré (Sentry, logs)
- [ ] **Backup DB** : configuré et testé
- [ ] **SSL/TLS** : configuré (HTTPS obligatoire)

### ❌ NO-GO (un seul suffit)

- [ ] Un paiement peut passer en "paid" sans preuve cryptographique (signature)
- [ ] Un oversell est possible (stock < quantité commandée)
- [ ] Les migrations échouent sur staging
- [ ] Les tests critiques checkout/auth échouent
- [ ] Les secrets sont en clair dans les logs
- [ ] Pas de plan de rollback
- [ ] Rate limiting non configuré sur endpoints critiques

---

## 4️⃣ RISK REGISTER

| ID  | Risque                                     | Impact | Probabilité | Mitigation                              | Owner          | Ticket associé |
| --- | ------------------------------------------ | ------ | ----------- | --------------------------------------- | -------------- | -------------- |
| R1  | Callback paiement spoofé                   | 🔴 Très élevé | 🟡 Moyen | Signatures + idempotence + logs         | Backend        | RBG-P0-010, RBG-P0-011 |
| R2  | Oversell stock en concurrence              | 🔴 Très élevé | 🟡 Moyen | Lock/transaction + test concurrence     | Backend        | RBG-P0-020 |
| R3  | Régressions auth multi-rôle                | 🟠 Élevé | 🟡 Moyen | Cartographie + tests redirection        | Backend        | RBG-P2-040, RBG-P2-041 |
| R4  | N+1 sur dashboards                         | 🟡 Moyen | 🔴 Élevé | Audit requêtes + eager loading          | Backend        | RBG-P1-030 |
| R5  | Cache obsolète après CRUD                  | 🟡 Moyen | 🟡 Moyen | Invalidation automatique par events     | Backend        | RBG-P1-031 |
| R6  | Divergences SQLite vs MySQL                | 🟠 Élevé | 🟡 Moyen | Compatibility matrix + fixes migrations | Backend        | RBG-P0-001, RBG-P0-002 |
| R7  | Signature Stripe "commentée" en prod       | 🔴 Très élevé | 🟢 Faible-Moyen | Ticket P0 + test webhook                | Backend        | RBG-P0-010 |
| R8  | Double exécution webhook (replay)          | 🟠 Élevé | 🟡 Moyen | Idempotency keys + unique constraints   | Backend        | RBG-P0-011 |
| R9  | Dette legacy non supprimée                 | 🟡 Moyen | 🔴 Élevé | Strangler + critères de suppression     | Lead Dev       | RBG-P2-041 |
| R10 | Charge email/notifications bloque requêtes | 🟡 Moyen | 🟡 Moyen | Queue + monitoring                      | Backend/DevOps | RBG-P2-032 |
| R11 | Rate limiting manquant                     | 🟠 Élevé | 🟡 Moyen | Configuration + tests                    | Backend        | RBG-P1-012 |
| R12 | Migration prod échoue                      | 🔴 Très élevé | 🟢 Faible | Tests staging + rollback plan           | Backend        | RBG-P0-001 |

**Légende :**
- 🔴 Très élevé
- 🟠 Élevé
- 🟡 Moyen
- 🟢 Faible

---

## 5️⃣ ORDRE D'EXÉCUTION RECOMMANDÉ

### Sprint 1 (2 semaines) - Stabilité & Sécurité

1. **E1 (DB & tests)** - RBG-P0-001, RBG-P0-002, RBG-P1-003
2. **E2 (webhooks)** - RBG-P0-010, RBG-P0-011, RBG-P1-012

**Objectif :** Pipeline stable + webhooks sécurisés

---

### Sprint 2 (2 semaines) - Checkout & Performance

3. **E3 (stock concurrence)** - RBG-P0-020, RBG-P1-021
4. **E4 (N+1 + cache)** - RBG-P1-030, RBG-P1-031

**Objectif :** Anti-oversell + performance optimisée

---

### Sprint 3 (2 semaines) - Documentation & Améliorations

5. **E6 (INSTALL)** - RBG-P1-050, RBG-P2-051
6. **E5 (simplification)** - RBG-P2-040, RBG-P2-041, RBG-P2-042

**Objectif :** Documentation complète + simplification progressive

---

### Sprint 4 (optionnel) - Queue & Optimisations

7. **E4 (queue)** - RBG-P2-032

**Objectif :** Performance finale

---

## 📊 MÉTRIQUES DE SUCCÈS

### Avant Production

- ✅ 100% des tests P0 passent
- ✅ Couverture tests ≥ 60%
- ✅ 0 vulnérabilité critique (webhooks sécurisés)
- ✅ 0 risque oversell (tests concurrence OK)
- ✅ Documentation complète (INSTALL.md)

### Après Production

- ✅ Temps de réponse < 500ms (pages publiques)
- ✅ 0 incident sécurité (webhooks)
- ✅ 0 oversell (stock)
- ✅ Uptime ≥ 99.5%

---

## 📝 NOTES

- **Estimation totale :** ~60-80 jours (3-4 sprints de 2 semaines)
- **Priorité absolue :** E1, E2, E3 (P0)
- **Flexibilité :** E5 peut être reporté si nécessaire
- **Review :** Chaque ticket doit être revu avant merge

---

**Date de création :** 10 décembre 2025  
**Dernière mise à jour :** 10 décembre 2025  
**Version :** 1.0

