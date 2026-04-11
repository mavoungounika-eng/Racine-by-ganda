# 🔍 AUDIT GLOBAL STRIPE — RACINE BY GANDA

**Date :** 2025-01-27  
**Projet :** RACINE BY GANDA (Laravel 12)  
**Type :** Audit complet sans modifications  
**Statut :** ✅ Rapport complet

---

## 📋 TABLE DES MATIÈRES

1. [Cartographie de l'intégration](#1-cartographie-de-lintégration)
2. [Constat actuel (factuel)](#2-constat-actuel-factuel)
3. [Risques classés par sévérité](#3-risques-classés-par-sévérité)
4. [Plan d'actions proposé](#4-plan-dactions-proposé)
5. [Liste de changements candidats](#5-liste-de-changements-candidats)

---

## 1. CARTOGRAPHIE DE L'INTÉGRATION

### 1.1 Fichiers et rôles

#### Routes (`routes/web.php`)

```310:434:routes/web.php
// Routes Paiement
Route::middleware(['auth'])->group(function () {
    Route::post('/orders/{order}/pay', [\App\Http\Controllers\Front\PaymentController::class, 'pay'])->name('payment.pay');
    Route::get('/orders/{order}/payment/success', [\App\Http\Controllers\Front\PaymentController::class, 'success'])->name('payment.success');
    Route::get('/orders/{order}/payment/cancel', [\App\Http\Controllers\Front\PaymentController::class, 'cancel'])->name('payment.cancel');
    
    // Paiement par Carte Bancaire (Stripe)
    Route::post('/checkout/card/pay', [\App\Http\Controllers\Front\CardPaymentController::class, 'pay'])->name('checkout.card.pay');
    Route::get('/checkout/card/{order}/success', [\App\Http\Controllers\Front\CardPaymentController::class, 'success'])->name('checkout.card.success');
    Route::get('/checkout/card/{order}/cancel', [\App\Http\Controllers\Front\CardPaymentController::class, 'cancel'])->name('checkout.card.cancel');
});

// Webhook Stripe (Pas de middleware auth, pas de CSRF - géré dans bootstrap/app.php ou middleware)
Route::post('/webhooks/stripe', [\App\Http\Controllers\Front\PaymentController::class, 'webhook'])->name('payment.webhook');

// Webhook Stripe pour paiement par carte (sans auth, sans CSRF)
Route::post('/payment/card/webhook', [\App\Http\Controllers\Front\CardPaymentController::class, 'webhook'])->name('payment.card.webhook');
```

**Constats :**
- ✅ Deux routes webhook : `/webhooks/stripe` (legacy) et `/payment/card/webhook` (actuelle)
- ✅ CSRF exemption configurée dans `bootstrap/app.php` (lignes 17-21)
- ⚠️ Route legacy `/webhooks/stripe` toujours active (risque de confusion)

#### Controllers

**1. `app/Http/Controllers/Front/CardPaymentController.php`** (Principal — Actif)
- `pay()` : Initie le paiement Stripe Checkout
- `success()` : Page de succès après paiement
- `cancel()` : Page d'annulation
- `webhook()` : Traite les webhooks Stripe (utilise `CardPaymentService`)

**2. `app/Http/Controllers/Front/PaymentController.php`** (Legacy — Partiellement utilisé)
- `pay()` : Initie le paiement (utilise `StripePaymentService` legacy)
- `success()` : Page de succès
- `cancel()` : Page d'annulation
- `webhook()` : Traite les webhooks (utilise `StripePaymentService` legacy)

**Constats :**
- ⚠️ **Duplication** : Deux controllers gèrent Stripe
- ⚠️ **Incohérence** : `PaymentController` utilise `StripePaymentService` (legacy), `CardPaymentController` utilise `CardPaymentService` (actuel)

#### Services

**1. `app/Services/Payments/CardPaymentService.php`** (Principal — Actif)
- `createCheckoutSession(Order $order)` : Crée une session Stripe Checkout
- `handleWebhook(string $payload, ?string $signature)` : Traite les webhooks
- `handleCheckoutSessionCompleted()` : Gère `checkout.session.completed`
- `handlePaymentIntentSucceeded()` : Gère `payment_intent.succeeded`
- `handlePaymentIntentFailed()` : Gère `payment_intent.payment_failed`

**2. `app/Services/Payments/StripePaymentService.php`** (Legacy — Utilisé par `PaymentController`)
- `createCheckoutSession(Order $order)` : Crée une session Stripe Checkout (simplifié)
- `markOrderAsPaid()` : Marque la commande comme payée

**Constats :**
- ⚠️ **Duplication** : Deux services Stripe
- ✅ `CardPaymentService` est plus complet (gestion webhook, événements)
- ❌ `StripePaymentService` est minimaliste (pas de gestion webhook complète)

#### Configuration

**1. `config/services.php`** (Principal)
```php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'currency' => env('STRIPE_CURRENCY', 'XOF'),  // ⚠️ XOF par défaut
],
```

**2. `config/stripe.php`** (Alternatif — Non utilisé)
```php
'currency' => env('STRIPE_CURRENCY', 'XAF'),  // ⚠️ XAF par défaut (incohérence)
```

**Constats :**
- ⚠️ **Incohérence devise** : `config/services.php` utilise XOF, `config/stripe.php` utilise XAF
- ✅ `CardPaymentService` utilise `config('services.stripe.currency', 'XAF')` (fallback XAF)
- ❌ Migration utilise XOF par défaut (`database/migrations/2025_11_23_000006_create_payments_table.php`)

#### Modèles

**1. `app/Models/Payment.php`**
- Champs : `order_id`, `provider`, `provider_payment_id`, `status`, `amount`, `currency`, `channel`, `external_reference`, `metadata`, `payload`, `paid_at`
- Relations : `order()` (BelongsTo)

**2. `app/Models/Order.php`**
- Champs : `user_id`, `status`, `payment_status`, `payment_method`, `total_amount`, etc.
- Relations : `payments()` (HasMany)

**Constats :**
- ✅ Mapping Stripe → Payment : `external_reference` (session_id), `provider_payment_id` (payment_intent_id)
- ✅ Statuts cohérents : `pending` → `initiated` → `paid` / `failed`

### 1.2 Diagramme logique du flux

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │ 1. POST /checkout/card/pay
       ▼
┌─────────────────────────────┐
│ CardPaymentController::pay()│
└──────┬──────────────────────┘
       │ 2. CardPaymentService::createCheckoutSession()
       ▼
┌─────────────────────────────┐
│  Stripe Checkout Session     │
│  - session_id (cs_xxx)       │
│  - payment_intent (pi_xxx)    │
└──────┬──────────────────────┘
       │ 3. Redirection vers Stripe
       ▼
┌─────────────────────────────┐
│   Stripe Hosted Checkout     │
│   (Client saisit CB)         │
└──────┬──────────────────────┘
       │ 4. Paiement traité
       ▼
┌─────────────────────────────┐
│   Stripe Webhook             │
│   POST /payment/card/webhook │
└──────┬──────────────────────┘
       │ 5. CardPaymentController::webhook()
       │    → CardPaymentService::handleWebhook()
       ▼
┌─────────────────────────────┐
│   Vérification signature     │
│   (Webhook::constructEvent)  │
└──────┬──────────────────────┘
       │ 6. Traitement événement
       │    - checkout.session.completed
       │    - payment_intent.succeeded
       │    - payment_intent.payment_failed
       ▼
┌─────────────────────────────┐
│   Mise à jour Payment        │
│   - status = 'paid'          │
│   - paid_at = now()          │
└──────┬──────────────────────┘
       │ 7. Mise à jour Order
       │    - payment_status = 'paid'
       │    - status = 'processing'
       ▼
┌─────────────────────────────┐
│   Émission événement         │
│   PaymentCompleted($order)  │
└──────┬──────────────────────┘
       │ 8. Redirection client
       │    GET /checkout/card/{order}/success
       ▼
┌─────────────────────────────┐
│   Page succès                │
└─────────────────────────────┘
```

---

## 2. CONSTAT ACTUEL (FACTUEL)

### 2.1 Ce qui est en place ✅

#### Sécurité Webhook
- ✅ **Vérification de signature** : Utilise `Stripe\Webhook::constructEvent()` (méthode officielle)
- ✅ **Raw payload** : Utilise `$request->getContent()` pour le payload brut
- ✅ **CSRF exemption** : Configurée dans `bootstrap/app.php` (lignes 17-21)
- ✅ **Gestion d'erreurs** : Codes HTTP corrects (401 pour signature invalide, 400 pour payload invalide, 500 pour erreur serveur)
- ✅ **Logs structurés** : IP, route, user_agent, reason, error

#### Architecture
- ✅ **Service principal** : `CardPaymentService` bien structuré
- ✅ **Mapping données** : `external_reference` (session_id) et `provider_payment_id` (payment_intent_id)
- ✅ **Événements** : `PaymentCompleted` et `PaymentFailed` émis
- ✅ **Tests** : Tests de sécurité webhook présents (`PaymentWebhookSecurityTest`)

#### Configuration
- ✅ **Secrets** : Stockés dans `.env` (pas de hardcoding)
- ✅ **SDK Stripe** : `stripe/stripe-php` v19.0 (à jour)
- ✅ **Documentation** : `docs/payments/stripe.md` et `ENV_VARIABLES_STRIPE.md`

### 2.2 Ce qui manque ❌

#### Idempotency
- ❌ **Pas de protection contre double traitement** : Aucune vérification de `event.id` Stripe
- ❌ **Pas de table `stripe_webhook_events`** : Aucun historique des événements traités
- ⚠️ **Protection partielle** : Vérifie `if ($payment->status === 'paid')` mais pas au niveau événement

#### Race Conditions
- ❌ **Pas de verrouillage DB** : Aucun `lockForUpdate()` dans le traitement webhook
- ⚠️ **Risque** : Si deux webhooks arrivent simultanément pour le même paiement, double traitement possible

#### Tests
- ❌ **Pas de tests d'idempotency** : Aucun test pour vérifier qu'un même événement n'est pas traité deux fois
- ❌ **Pas de tests de race conditions** : Aucun test pour vérifier le comportement avec webhooks simultanés
- ⚠️ **Tests incomplets** : `PaymentTest` vérifie seulement la signature, pas le traitement complet

#### Monitoring
- ❌ **Pas de métriques** : Aucun tracking des webhooks reçus/traités/échoués
- ❌ **Pas d'alerting** : Aucune alerte en cas d'échec webhook répété

### 2.3 Ce qui est incorrect / fragile ⚠️

#### Duplication de code
- ⚠️ **Deux services Stripe** : `CardPaymentService` (actuel) et `StripePaymentService` (legacy)
- ⚠️ **Deux controllers** : `CardPaymentController` (actuel) et `PaymentController` (legacy)
- ⚠️ **Deux routes webhook** : `/webhooks/stripe` (legacy) et `/payment/card/webhook` (actuelle)
- **Impact** : Confusion, maintenance difficile, risque de bugs

#### Incohérence devise
- ⚠️ **`config/services.php`** : XOF par défaut
- ⚠️ **`config/stripe.php`** : XAF par défaut
- ⚠️ **`CardPaymentService`** : Fallback XAF (ligne 52)
- ⚠️ **Migration** : XOF par défaut
- **Impact** : Risque d'erreur de conversion (XOF vs XAF = même valeur mais codes différents)

#### Gestion d'erreurs legacy
- ⚠️ **`PaymentController::webhook()`** : Utilise `@file_get_contents('php://input')` (déprécié)
- ⚠️ **Codes HTTP** : Retourne 400 pour signature invalide (devrait être 401)
- **Impact** : Comportement incohérent avec `CardPaymentController`

#### Recherche de paiement
- ⚠️ **`CardPaymentService::handleWebhook()`** : Cherche par `external_reference` puis `provider_payment_id`
- ⚠️ **`PaymentController::webhook()`** : Cherche seulement par `provider_payment_id`
- **Impact** : `PaymentController` peut ne pas trouver le paiement si créé par `CardPaymentService`

---

## 3. RISQUES CLASSÉS PAR SÉVÉRITÉ

### 🔴 CRITICAL

#### R1 : Pas d'idempotency basée sur `event.id`
- **Fichier** : `app/Services/Payments/CardPaymentService.php` (lignes 151-332)
- **Preuve** : Aucune vérification de `event.id` avant traitement
- **Impact** : Si Stripe renvoie le même événement deux fois (retry), double traitement possible → double mise à jour de commande, double émission d'événement
- **Recommandation** : Créer une table `stripe_webhook_events` avec `event_id` unique, vérifier avant traitement

#### R2 : Race condition dans traitement webhook
- **Fichier** : `app/Services/Payments/CardPaymentService.php` (lignes 313-332)
- **Preuve** : Pas de `lockForUpdate()` ou transaction DB
- **Impact** : Si deux webhooks arrivent simultanément pour le même paiement, double traitement possible
- **Recommandation** : Utiliser `DB::transaction()` + `lockForUpdate()` sur le Payment

#### R3 : Duplication de code (services/controllers/routes)
- **Fichiers** : 
  - `app/Services/Payments/CardPaymentService.php` (actuel)
  - `app/Services/Payments/StripePaymentService.php` (legacy)
  - `app/Http/Controllers/Front/CardPaymentController.php` (actuel)
  - `app/Http/Controllers/Front/PaymentController.php` (legacy)
  - Routes : `/webhooks/stripe` (legacy) et `/payment/card/webhook` (actuelle)
- **Preuve** : Deux implémentations parallèles
- **Impact** : Confusion, maintenance difficile, risque de bugs (ex: `PaymentController` ne trouve pas les paiements créés par `CardPaymentService`)
- **Recommandation** : Supprimer le code legacy, utiliser uniquement `CardPaymentService` et `CardPaymentController`

### 🟠 HIGH

#### R4 : Incohérence devise (XOF vs XAF)
- **Fichiers** :
  - `config/services.php` (ligne 35) : XOF par défaut
  - `config/stripe.php` (ligne 42) : XAF par défaut
  - `app/Services/Payments/CardPaymentService.php` (ligne 52) : Fallback XAF
  - `database/migrations/2025_11_23_000006_create_payments_table.php` (ligne 21) : XOF par défaut
- **Preuve** : Valeurs par défaut différentes selon les fichiers
- **Impact** : Risque d'erreur de conversion (XOF et XAF ont la même valeur mais codes différents), confusion pour le marché Congo (XAF)
- **Recommandation** : Standardiser sur XAF (marché Congo), mettre à jour tous les fichiers

#### R5 : `PaymentController::webhook()` utilise méthode dépréciée
- **Fichier** : `app/Http/Controllers/Front/PaymentController.php` (ligne 64)
- **Preuve** : `@file_get_contents('php://input')` au lieu de `$request->getContent()`
- **Impact** : Comportement imprévisible, peut échouer avec certains middlewares Laravel
- **Recommandation** : Utiliser `$request->getContent()` ou supprimer le controller legacy

#### R6 : Codes HTTP incorrects dans `PaymentController::webhook()`
- **Fichier** : `app/Http/Controllers/Front/PaymentController.php` (lignes 74, 77)
- **Preuve** : Retourne 400 pour signature invalide (devrait être 401)
- **Impact** : Comportement incohérent avec `CardPaymentController`, confusion pour monitoring
- **Recommandation** : Utiliser 401 pour signature invalide, 400 pour payload invalide

### 🟡 MEDIUM

#### R7 : Pas de tests d'idempotency
- **Fichier** : `tests/Feature/PaymentWebhookSecurityTest.php`
- **Preuve** : Aucun test pour vérifier qu'un même `event.id` n'est pas traité deux fois
- **Impact** : Risque non détecté en développement
- **Recommandation** : Ajouter test `test_webhook_is_idempotent()` qui envoie le même événement deux fois

#### R8 : Pas de tests de race conditions
- **Fichier** : `tests/Feature/PaymentWebhookSecurityTest.php`
- **Preuve** : Aucun test pour vérifier le comportement avec webhooks simultanés
- **Impact** : Risque non détecté en développement
- **Recommandation** : Ajouter test avec deux webhooks simultanés pour le même paiement

#### R9 : Recherche de paiement incomplète dans `PaymentController`
- **Fichier** : `app/Http/Controllers/Front/PaymentController.php` (ligne 85)
- **Preuve** : Cherche seulement par `provider_payment_id`, pas par `external_reference`
- **Impact** : Si paiement créé par `CardPaymentService` (qui utilise `external_reference`), `PaymentController` ne le trouve pas
- **Recommandation** : Chercher par `external_reference` ET `provider_payment_id`, ou supprimer le controller legacy

#### R10 : Pas de monitoring/métriques webhook
- **Fichiers** : Tous les fichiers webhook
- **Preuve** : Aucun tracking des webhooks reçus/traités/échoués
- **Impact** : Difficile de diagnostiquer les problèmes en production
- **Recommandation** : Ajouter métriques (ex: Laravel Telescope, Sentry, ou table `webhook_logs`)

### 🟢 LOW

#### R11 : Documentation incomplète sur idempotency
- **Fichier** : `docs/payments/stripe.md`
- **Preuve** : Pas de mention de l'idempotency ou des risques de double traitement
- **Impact** : Développeurs futurs peuvent ne pas être conscients du risque
- **Recommandation** : Ajouter section sur idempotency dans la documentation

#### R12 : Pas de retry policy explicite
- **Fichier** : `app/Services/Payments/CardPaymentService.php`
- **Preuve** : Aucune gestion explicite des retries Stripe
- **Impact** : Si traitement échoue, Stripe retry automatiquement, mais pas de log explicite
- **Recommandation** : Documenter le comportement de retry Stripe

---

## 4. PLAN D'ACTIONS PROPOSÉ

### 4.1 Quick Wins (≤ 30 min)

#### QW1 : Standardiser la devise sur XAF
- **Fichiers** : `config/services.php`, `config/stripe.php`, migration
- **Action** : Changer toutes les valeurs par défaut de XOF à XAF
- **Temps estimé** : 15 min

#### QW2 : Corriger codes HTTP dans `PaymentController::webhook()`
- **Fichier** : `app/Http/Controllers/Front/PaymentController.php`
- **Action** : Changer 400 → 401 pour signature invalide
- **Temps estimé** : 5 min

#### QW3 : Corriger méthode dépréciée dans `PaymentController::webhook()`
- **Fichier** : `app/Http/Controllers/Front/PaymentController.php`
- **Action** : Remplacer `@file_get_contents('php://input')` par `$request->getContent()`
- **Temps estimé** : 5 min

#### QW4 : Améliorer recherche de paiement dans `PaymentController::webhook()`
- **Fichier** : `app/Http/Controllers/Front/PaymentController.php`
- **Action** : Chercher par `external_reference` ET `provider_payment_id`
- **Temps estimé** : 10 min

### 4.2 Correctifs structurels (1–2 jours)

#### CS1 : Implémenter idempotency basée sur `event.id`
- **Fichiers** :
  - Créer migration : `create_stripe_webhook_events_table.php`
  - Modifier : `app/Services/Payments/CardPaymentService.php`
- **Action** :
  1. Créer table `stripe_webhook_events` avec colonnes : `id`, `event_id` (unique), `event_type`, `processed_at`, `payment_id`, `created_at`, `updated_at`
  2. Dans `handleWebhook()`, vérifier si `event.id` existe déjà avant traitement
  3. Si existe, retourner le Payment existant (idempotent)
  4. Si n'existe pas, créer l'enregistrement et traiter
- **Temps estimé** : 4-6 heures

#### CS2 : Ajouter protection contre race conditions
- **Fichier** : `app/Services/Payments/CardPaymentService.php`
- **Action** :
  1. Envelopper le traitement webhook dans `DB::transaction()`
  2. Utiliser `lockForUpdate()` sur le Payment avant mise à jour
  3. Vérifier `status === 'paid'` AVANT la mise à jour (déjà fait, mais dans transaction)
- **Temps estimé** : 2-3 heures

#### CS3 : Supprimer code legacy (services/controllers/routes)
- **Fichiers** :
  - Supprimer : `app/Services/Payments/StripePaymentService.php`
  - Supprimer : `app/Http/Controllers/Front/PaymentController.php` (ou garder seulement les méthodes non-Stripe)
  - Supprimer route : `/webhooks/stripe` (ou rediriger vers `/payment/card/webhook`)
- **Action** :
  1. Vérifier que toutes les routes utilisent `CardPaymentController`
  2. Supprimer les fichiers legacy
  3. Mettre à jour la documentation
- **Temps estimé** : 2-3 heures

#### CS4 : Ajouter tests d'idempotency et race conditions
- **Fichier** : `tests/Feature/PaymentWebhookSecurityTest.php`
- **Action** :
  1. Ajouter test `test_webhook_is_idempotent()` : Envoyer le même événement deux fois, vérifier qu'il n'est traité qu'une fois
  2. Ajouter test `test_webhook_handles_concurrent_requests()` : Envoyer deux webhooks simultanés pour le même paiement, vérifier qu'un seul traitement réussit
- **Temps estimé** : 3-4 heures

### 4.3 Améliorations (monitoring, alerting, observabilité)

#### AM1 : Ajouter table `webhook_logs` pour monitoring
- **Fichiers** :
  - Créer migration : `create_webhook_logs_table.php`
  - Modifier : `app/Services/Payments/CardPaymentService.php`
- **Action** :
  1. Créer table `webhook_logs` avec colonnes : `id`, `event_id`, `event_type`, `status` (received/processed/failed), `payment_id`, `error_message`, `ip`, `user_agent`, `created_at`
  2. Logger chaque webhook reçu (même si déjà traité)
  3. Logger les erreurs avec détails
- **Temps estimé** : 3-4 heures

#### AM2 : Ajouter métriques Laravel Telescope (optionnel)
- **Fichier** : Configuration Telescope
- **Action** : Activer tracking des webhooks Stripe dans Telescope
- **Temps estimé** : 1-2 heures

#### AM3 : Ajouter alerting Sentry (optionnel)
- **Fichier** : Configuration Sentry
- **Action** : Configurer alertes pour échecs webhook répétés (> 5 en 1h)
- **Temps estimé** : 1-2 heures

#### AM4 : Documenter idempotency et retry policy
- **Fichier** : `docs/payments/stripe.md`
- **Action** : Ajouter sections :
  - "Idempotency et protection contre double traitement"
  - "Retry policy Stripe"
  - "Monitoring et alerting"
- **Temps estimé** : 1-2 heures

---

## 5. LISTE DE CHANGEMENTS CANDIDATS

### 5.1 Migration : Table `stripe_webhook_events`

```php
<?php
// database/migrations/YYYY_MM_DD_HHMMSS_create_stripe_webhook_events_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique(); // Stripe event.id (evt_xxx)
            $table->string('event_type'); // checkout.session.completed, etc.
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('processed_at');
            $table->json('payload')->nullable(); // Optionnel : stocker le payload complet
            $table->timestamps();
            
            $table->index('event_id');
            $table->index('payment_id');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
    }
};
```

### 5.2 Modification : `CardPaymentService::handleWebhook()` avec idempotency

```php
// app/Services/Payments/CardPaymentService.php

public function handleWebhook(string $payload, ?string $signature = null): ?Payment
{
    // ... code existant de vérification signature ...
    
    // Extraire event.id et event.type
    $eventId = is_object($event) ? $event->id : ($event['id'] ?? null);
    $eventType = is_object($event) ? $event->type : ($event['type'] ?? null);
    
    if (!$eventId || !$eventType) {
        Log::warning('Invalid webhook payload: missing event.id or event.type');
        return null;
    }
    
    // IDEMPOTENCY : Vérifier si l'événement a déjà été traité
    $processedEvent = \App\Models\StripeWebhookEvent::where('event_id', $eventId)->first();
    if ($processedEvent) {
        Log::info('Stripe webhook event already processed (idempotent)', [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payment_id' => $processedEvent->payment_id,
            'processed_at' => $processedEvent->processed_at,
        ]);
        
        // Retourner le Payment associé si existe
        return $processedEvent->payment_id 
            ? Payment::find($processedEvent->payment_id) 
            : null;
    }
    
    // ... code existant de recherche Payment ...
    
    if (!$payment) {
        Log::warning('Payment not found for webhook', [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'session_id' => $sessionId,
        ]);
        return null;
    }
    
    // PROTECTION RACE CONDITION : Transaction + lock
    return DB::transaction(function () use ($payment, $eventId, $eventType, $object, $eventType) {
        // Verrouiller le Payment pour éviter race condition
        $payment = Payment::where('id', $payment->id)
            ->lockForUpdate()
            ->first();
        
        // Vérifier à nouveau si déjà payé (double protection)
        if ($payment->status === 'paid') {
            Log::info('Payment already paid (race condition protection)', [
                'payment_id' => $payment->id,
                'event_id' => $eventId,
            ]);
            
            // Enregistrer l'événement comme traité quand même
            \App\Models\StripeWebhookEvent::create([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'payment_id' => $payment->id,
                'processed_at' => now(),
            ]);
            
            return $payment;
        }
        
        // Traiter l'événement
        switch ($eventType) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($payment, $object);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($payment, $object);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($payment, $object);
                break;
        }
        
        // Enregistrer l'événement comme traité
        \App\Models\StripeWebhookEvent::create([
            'event_id' => $eventId,
            'event_type' => $eventType,
            'payment_id' => $payment->id,
            'processed_at' => now(),
        ]);
        
        return $payment;
    });
}
```

### 5.3 Modification : Standardiser devise sur XAF

```php
// config/services.php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'currency' => env('STRIPE_CURRENCY', 'XAF'), // ✅ XAF au lieu de XOF
],

// config/stripe.php
'currency' => env('STRIPE_CURRENCY', 'XAF'), // ✅ Déjà XAF

// database/migrations/2025_11_23_000006_create_payments_table.php
$table->string('currency')->default('XAF'); // ✅ XAF au lieu de XOF
```

### 5.4 Test : Idempotency

```php
// tests/Feature/PaymentWebhookSecurityTest.php

#[Test]
public function test_webhook_is_idempotent(): void
{
    // Créer un événement Stripe mock
    $eventId = 'evt_test_1234567890';
    $sessionId = 'cs_test_1234567890';
    
    $payload = json_encode([
        'id' => $eventId,
        'type' => 'checkout.session.completed',
        'data' => [
            'object' => [
                'id' => $sessionId,
                'payment_status' => 'paid',
                'payment_intent' => 'pi_test_1234567890',
            ],
        ],
    ]);
    
    // Premier traitement
    $response1 = $this->call('POST', '/payment/card/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
    
    $response1->assertStatus(200);
    
    // Vérifier que le Payment est bien payé
    $payment = Payment::where('external_reference', $sessionId)->first();
    $this->assertNotNull($payment);
    $this->assertEquals('paid', $payment->status);
    
    // Vérifier que l'événement est enregistré
    $this->assertDatabaseHas('stripe_webhook_events', [
        'event_id' => $eventId,
        'payment_id' => $payment->id,
    ]);
    
    // Deuxième traitement (même événement)
    $response2 = $this->call('POST', '/payment/card/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $payload);
    
    $response2->assertStatus(200);
    
    // Vérifier que le Payment n'a pas été modifié deux fois
    $payment->refresh();
    $this->assertEquals('paid', $payment->status);
    
    // Vérifier qu'il n'y a qu'un seul enregistrement d'événement
    $this->assertDatabaseCount('stripe_webhook_events', 1);
}
```

### 5.5 Test : Race condition

```php
// tests/Feature/PaymentWebhookSecurityTest.php

#[Test]
public function test_webhook_handles_concurrent_requests(): void
{
    $eventId1 = 'evt_test_1111111111';
    $eventId2 = 'evt_test_2222222222';
    $sessionId = 'cs_test_1234567890';
    
    $payload1 = json_encode([
        'id' => $eventId1,
        'type' => 'checkout.session.completed',
        'data' => ['object' => ['id' => $sessionId, 'payment_status' => 'paid']],
    ]);
    
    $payload2 = json_encode([
        'id' => $eventId2,
        'type' => 'checkout.session.completed',
        'data' => ['object' => ['id' => $sessionId, 'payment_status' => 'paid']],
    ]);
    
    // Envoyer deux webhooks simultanément (simulation avec threads/processus parallèles)
    // En PHP, on peut utiliser des processus parallèles ou simplement vérifier le comportement séquentiel
    
    $response1 = $this->call('POST', '/payment/card/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $payload1);
    
    $response2 = $this->call('POST', '/payment/card/webhook', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
    ], $payload2);
    
    $response1->assertStatus(200);
    $response2->assertStatus(200);
    
    // Vérifier que le Payment n'a été mis à jour qu'une fois
    $payment = Payment::where('external_reference', $sessionId)->first();
    $this->assertNotNull($payment);
    $this->assertEquals('paid', $payment->status);
    
    // Vérifier qu'un seul événement a réellement traité le paiement
    $processedEvents = \App\Models\StripeWebhookEvent::where('payment_id', $payment->id)
        ->where('event_type', 'checkout.session.completed')
        ->get();
    
    // Les deux événements doivent être enregistrés, mais seul le premier doit avoir réellement traité
    $this->assertCount(2, $processedEvents);
}
```

---

## 6. RÉSUMÉ EXÉCUTIF

### Points forts ✅
- Architecture bien structurée avec `CardPaymentService`
- Sécurité webhook implémentée (signature vérifiée)
- Tests de sécurité présents
- Documentation existante

### Points critiques 🔴
- **Pas d'idempotency** : Risque de double traitement si Stripe retry
- **Race conditions** : Pas de verrouillage DB dans traitement webhook
- **Duplication code** : Deux services/controllers/routes Stripe

### Actions prioritaires
1. **Immédiat** : Implémenter idempotency basée sur `event.id` (R1)
2. **Court terme** : Ajouter protection race conditions (R2)
3. **Court terme** : Supprimer code legacy (R3)
4. **Moyen terme** : Standardiser devise XAF (R4)
5. **Moyen terme** : Ajouter tests idempotency/race conditions (R7, R8)

---

**Fin du rapport d'audit**

