# AUDIT WEBHOOKS + JOBS + EXACTLY-ONCE (Stripe + Monetbil)
**Projet :** racine-backend (Laravel 12)  
**Date :** 2025-01-XX  
**Phase :** PASS 3/3 — Analyse système exactly-once et webhooks

---

## 1. ROUTES WEBHOOKS

### 1.1. Routes officielles (API v1.1)

**Fichier :** `routes/api.php`

| Route | Méthode | Contrôleur | Middleware | Nom de route |
|-------|---------|------------|------------|-------------|
| `/api/webhooks/stripe` | POST | `WebhookController@stripe` | `api`, `throttle:webhooks` (60/min) | `api.webhooks.stripe` |
| `/api/webhooks/monetbil` | POST | `WebhookController@monetbil` | `api`, `throttle:webhooks` (60/min) | `api.webhooks.monetbil` |

**Pattern :** v1.1 (persist event → dispatch job → return 200 vite)

**Throttling :**
- Rate limiter `webhooks` : 60 requêtes par minute par IP
- Configuré dans `app/Http/Kernel.php` ou `bootstrap/app.php`

---

### 1.2. Routes legacy (dépréciées)

**Fichier :** `routes/web.php`

| Route | Méthode | Contrôleur | Middleware | Statut |
|-------|---------|------------|------------|--------|
| `/webhooks/stripe` | POST | `CardPaymentController@webhook` | `LegacyWebhookDeprecationHeaders` | ⚠️ Déprécié |
| `/payment/card/webhook` | POST | `CardPaymentController@webhook` | `LegacyWebhookDeprecationHeaders` | ⚠️ Déprécié |

**Note :** Les routes legacy utilisent `CardPaymentService::handleWebhook()` qui traite directement (pas de job). Elles sont marquées pour suppression après migration complète.

---

### 1.3. Middlewares

#### `throttle:webhooks`
- **Limite :** 60 requêtes par minute par IP
- **Scope :** Global (toutes les routes webhooks)
- **Config :** Rate limiter nommé `webhooks`

#### `LegacyWebhookDeprecationHeaders`
- **Rôle :** Ajoute des headers de dépréciation aux réponses
- **Usage :** Routes legacy uniquement
- **Headers :** `X-Webhook-Deprecated: true`, `X-Webhook-New-Endpoint: /api/webhooks/stripe`

---

## 2. STRIPE PIPELINE

### 2.1. Fichier controller/handler d'entrée

**Fichier :** `app/Http/Controllers/Api/WebhookController.php`

**Méthode :** `stripe(Request $request)`

**Pattern :** v1.1 (verify → persist → dispatch → 200)

---

### 2.2. Vérification signature

**Localisation :** `WebhookController@stripe()` (lignes 40-101)

**Processus :**
```php
// 1. Récupérer payload brut et signature
$payload = $request->getContent();
$signature = $request->header('Stripe-Signature');
$webhookSecret = config('services.stripe.webhook_secret');

// 2. Vérifier signature (production obligatoire)
if ($isProduction && empty($signature)) {
    return response()->json(['error' => 'Missing signature'], 401);
}

// 3. Vérifier avec Stripe SDK
$event = Webhook::constructEvent($payload, $signature, $webhookSecret);
```

**Méthode :** `Stripe\Webhook::constructEvent()` (SDK officiel)

**Sécurité :**
- ✅ Vérifie timestamp (évite replay attacks)
- ✅ Vérifie signature HMAC
- ✅ Production : signature obligatoire
- ✅ Dev : signature optionnelle (pour tests)

---

### 2.3. Création/mise à jour StripeWebhookEvent

**Localisation :** `WebhookController@stripe()` (lignes 111-194)

**Processus :**

#### Nouvel événement
```php
$webhookEvent = StripeWebhookEvent::firstOrCreate(
    ['event_id' => $eventId],  // Clé unique pour idempotence
    [
        'event_type' => $eventType,
        'status' => 'received',
        'payload_hash' => hash('sha256', $payload),
    ]
);
```

#### Événement existant (idempotence)
```php
if ($webhookEvent->wasRecentlyCreated === false) {
    // Règle 1 : Si déjà traité, ne pas redispatch
    if ($webhookEvent->isProcessed()) {
        return response()->json(['status' => 'already_processed'], 200);
    }
    
    // Règle 2 : Atomic claim "first dispatch"
    $rowsAffected = DB::table('stripe_webhook_events')
        ->where('id', $webhookEvent->id)
        ->whereNull('dispatched_at')  // Pas encore dispatché
        ->update(['dispatched_at' => now()]);
    
    if ($rowsAffected === 1) {
        ProcessStripeWebhookEventJob::dispatch($webhookEvent->id);
    }
    
    // Règle 3 : Atomic claim "redispatch failed old" (> 5 min)
    $threshold = now()->subMinutes(5);
    $rowsAffected = DB::table('stripe_webhook_events')
        ->where('id', $webhookEvent->id)
        ->where('status', 'failed')
        ->whereNotNull('dispatched_at')
        ->where('dispatched_at', '<', $threshold)
        ->update(['dispatched_at' => now()]);
    
    if ($rowsAffected === 1) {
        ProcessStripeWebhookEventJob::dispatch($webhookEvent->id);
    }
}
```

**Idempotence :**
- ✅ `event_id` unique (contrainte DB)
- ✅ `firstOrCreate()` évite doublons
- ✅ Atomic claim via `dispatched_at` (UPDATE WHERE NULL)

---

### 2.4. Mapping event_id → Payment → Order

**Localisation :** `ProcessStripeWebhookEventJob@findTransaction()`

**Processus :**

#### Étape 1 : Trouver PaymentTransaction
```php
// Option 1 : Via payment_id (legacy Payment table)
if ($event->payment_id) {
    $payment = Payment::find($event->payment_id);
    if ($payment && $payment->order_id) {
        $transaction = PaymentTransaction::where('order_id', $payment->order_id)
            ->where('provider', 'stripe')
            ->latest()
            ->first();
    }
}

// Option 2 : Via event_id dans transaction_id/uuid
$transaction = PaymentTransaction::where('provider', 'stripe')
    ->where(function ($query) use ($event) {
        $query->where('transaction_id', 'like', '%' . $event->event_id . '%')
              ->orWhere('transaction_uuid', 'like', '%' . $event->event_id . '%');
    })
    ->latest()
    ->first();

// Option 3 : Via event_type payment_intent (fallback)
if (str_contains($event->event_type, 'payment_intent')) {
    $transaction = PaymentTransaction::where('provider', 'stripe')
        ->where('created_at', '>=', now()->subDay())
        ->where('status', '!=', 'succeeded')
        ->orderBy('created_at', 'desc')
        ->first();
}
```

#### Étape 2 : Mettre à jour PaymentTransaction et Order
```php
// PaymentEventMapperService::updateTransactionAndOrder()
DB::transaction(function () use ($transaction, $newStatus) {
    // 1. Mettre à jour transaction (source of truth)
    $transaction->update(['status' => $newStatus]);
    
    // 2. Mettre à jour commande si liée
    if ($transaction->order_id) {
        $order = Order::lockForUpdate()->find($transaction->order_id);
        if ($order) {
            $order->update([
                'status' => mapPaymentStatusToOrderStatus($newStatus),
                'payment_status' => mapPaymentStatusToOrderPaymentStatus($newStatus),
            ]);
        }
    }
});
```

**Clés de liaison :**
- `StripeWebhookEvent.payment_id` → `Payment.id` (legacy)
- `Payment.order_id` → `Order.id`
- `PaymentTransaction.order_id` → `Order.id` (direct)
- `PaymentTransaction.transaction_id` / `transaction_uuid` (recherche par pattern)

---

### 2.5. Événements supportés

**Fichier :** `app/Services/Payments/PaymentEventMapperService.php`

**Méthode :** `mapStripeEventToStatus(string $eventType)`

| Event Type Stripe | Statut mappé | Description |
|-------------------|--------------|-------------|
| `payment_intent.succeeded` | `succeeded` | Paiement réussi |
| `checkout.session.completed` | `succeeded` | Session checkout complétée |
| `payment_intent.payment_failed` | `failed` | Paiement échoué |
| `charge.failed` | `failed` | Charge échouée |
| `payment_intent.canceled` | `canceled` | Paiement annulé |
| `checkout.session.expired` | `canceled` | Session expirée |
| `payment_intent.processing` | `processing` | Paiement en cours |
| `charge.pending` | `processing` | Charge en attente |
| `charge.refunded` | `refunded` | Remboursement effectué |
| `refund.created` | `refunded` | Remboursement créé |
| Autres | `null` (ignoré) | Événements non pertinents |

**Note :** Les événements non mappés sont marqués comme `ignored` dans `StripeWebhookEvent`.

---

### 2.6. Transactions DB et lockForUpdate

**Localisation :** `ProcessStripeWebhookEventJob@handle()`

**Processus :**

#### Lock sur StripeWebhookEvent
```php
$event = DB::transaction(function () {
    return StripeWebhookEvent::lockForUpdate()
        ->find($this->stripeWebhookEventId);
});
```

#### Lock sur Order
```php
// PaymentEventMapperService::updateTransactionAndOrder()
$order = Order::lockForUpdate()->find($transaction->order_id);
```

**Protection :**
- ✅ `lockForUpdate()` évite race conditions
- ✅ Transaction DB garantit atomicité
- ✅ Vérification idempotence avant update

---

## 3. MONETBIL PIPELINE

### 3.1. Endpoint callback principal

**Fichier :** `app/Http/Controllers/Api/WebhookController.php`

**Méthode :** `monetbil(Request $request)`

**Pattern :** v1.1 (verify → persist → dispatch → 200)

---

### 3.2. Calcul event_key

**Localisation :** `WebhookController@generateEventKey()` (lignes 382-391)

**Processus :**
```php
private function generateEventKey(array $payload): string
{
    // Construire clé stable depuis payload
    $key = ($payload['transaction_id'] ?? '') 
         . '|' . ($payload['transaction_uuid'] ?? '')
         . '|' . ($payload['payment_ref'] ?? '')
         . '|' . ($payload['timestamp'] ?? now()->timestamp);
    
    // Hash SHA256 pour idempotence
    return hash('sha256', $key);
}
```

**Stabilité :**
- ✅ Utilise `transaction_id`, `transaction_uuid`, `payment_ref`, `timestamp`
- ✅ Hash SHA256 garantit unicité
- ✅ Même payload = même `event_key` (idempotence)

---

### 3.3. Mapping payment_ref/transaction_id → PaymentTransaction

**Localisation :** `ProcessMonetbilCallbackEventJob@findTransaction()`

**Processus :**
```php
// Option 1 : Via payment_ref
if ($event->payment_ref) {
    $transaction = PaymentTransaction::where('payment_ref', $event->payment_ref)
        ->where('provider', 'monetbil')
        ->latest()
        ->first();
}

// Option 2 : Via transaction_id
if ($event->transaction_id) {
    $transaction = PaymentTransaction::where('transaction_id', $event->transaction_id)
        ->where('provider', 'monetbil')
        ->latest()
        ->first();
}

// Option 3 : Via transaction_uuid
if ($event->transaction_uuid) {
    $transaction = PaymentTransaction::where('transaction_uuid', $event->transaction_uuid)
        ->where('provider', 'monetbil')
        ->latest()
        ->first();
}
```

**Clés de liaison :**
- `MonetbilCallbackEvent.payment_ref` → `PaymentTransaction.payment_ref`
- `MonetbilCallbackEvent.transaction_id` → `PaymentTransaction.transaction_id`
- `MonetbilCallbackEvent.transaction_uuid` → `PaymentTransaction.transaction_uuid`
- `PaymentTransaction.order_id` → `Order.id` (direct)

**Note :** Le système utilise `PaymentTransaction`, pas `Payment` (contrairement au legacy MobileMoneyPaymentService).

---

### 3.4. Création/mise à jour MonetbilCallbackEvent

**Localisation :** `WebhookController@monetbil()` (lignes 259-346)

**Processus :**

#### Nouvel événement
```php
$callbackEvent = MonetbilCallbackEvent::firstOrCreate(
    ['event_key' => $eventKey],  // Clé unique pour idempotence
    [
        'payment_ref' => $payload['payment_ref'] ?? $payload['item_ref'] ?? null,
        'transaction_id' => $payload['transaction_id'] ?? null,
        'transaction_uuid' => $payload['transaction_uuid'] ?? null,
        'event_type' => $payload['event_type'] ?? $payload['status'] ?? null,
        'status' => 'received',
        'payload' => $payload,
        'received_at' => now(),
    ]
);
```

#### Événement existant (idempotence)
```php
if ($callbackEvent->wasRecentlyCreated === false) {
    // Règle 1 : Si déjà traité, ne pas redispatch
    if (in_array($callbackEvent->status, ['processed', 'ignored'])) {
        return response()->json(['status' => 'already_processed'], 200);
    }
    
    // Règle 2 : Atomic claim "first dispatch"
    $rowsAffected = DB::table('monetbil_callback_events')
        ->where('id', $callbackEvent->id)
        ->whereNull('dispatched_at')
        ->update(['dispatched_at' => now()]);
    
    if ($rowsAffected === 1) {
        ProcessMonetbilCallbackEventJob::dispatch($callbackEvent->id);
    }
    
    // Règle 3 : Atomic claim "redispatch failed old" (> 5 min)
    // (même logique que Stripe)
}
```

**Idempotence :**
- ✅ `event_key` unique (contrainte DB)
- ✅ `firstOrCreate()` évite doublons
- ✅ Atomic claim via `dispatched_at` (UPDATE WHERE NULL)

---

### 3.5. Locks et idempotence

**Localisation :** `ProcessMonetbilCallbackEventJob@handle()`

**Processus :**

#### Lock sur MonetbilCallbackEvent
```php
$event = DB::transaction(function () {
    return MonetbilCallbackEvent::lockForUpdate()
        ->find($this->monetbilCallbackEventId);
});
```

#### Vérification idempotence
```php
// Si déjà traité, ne pas retraiter
if (in_array($event->status, ['processed', 'ignored'])) {
    return;
}

// Si transaction déjà en succès, ignorer
if ($transaction->isAlreadySuccessful() && $status === 'succeeded') {
    $event->update(['status' => 'processed']);
    return;
}
```

#### Lock sur Order
```php
// PaymentEventMapperService::updateTransactionAndOrder()
$order = Order::lockForUpdate()->find($transaction->order_id);
```

**Protection :**
- ✅ `lockForUpdate()` évite race conditions
- ✅ Transaction DB garantit atomicité
- ✅ Vérification idempotence avant update

---

## 4. JOBS / QUEUES

### 4.1. Jobs dispatchés

#### ProcessStripeWebhookEventJob

**Fichier :** `app/Jobs/ProcessStripeWebhookEventJob.php`

**Dispatch :**
- `WebhookController@stripe()` (nouvel événement ou redispatch)
- `WebhookStuckController@requeue()` (requeue manuel)
- `RequeueStuckWebhookEvents` (commande artisan)

**Paramètres :**
- `tries` : 3 tentatives
- `timeout` : 60 secondes
- `backoff` : [10, 30, 60] secondes

**Rôle :**
- Mapper `event_type` → statut standardisé
- Trouver `PaymentTransaction` associée
- Mettre à jour `PaymentTransaction` et `Order`
- Marquer `StripeWebhookEvent` comme `processed`

---

#### ProcessMonetbilCallbackEventJob

**Fichier :** `app/Jobs/ProcessMonetbilCallbackEventJob.php`

**Dispatch :**
- `WebhookController@monetbil()` (nouvel événement ou redispatch)
- `WebhookStuckController@requeue()` (requeue manuel)
- `RequeueStuckWebhookEvents` (commande artisan)

**Paramètres :**
- `tries` : 3 tentatives
- `timeout` : 60 secondes
- `backoff` : [10, 30, 60] secondes

**Rôle :**
- Mapper payload → statut standardisé
- Trouver `PaymentTransaction` associée
- Mettre à jour `PaymentTransaction` et `Order`
- Marquer `MonetbilCallbackEvent` comme `processed`

---

### 4.2. Retry/backoff

**Configuration :**
```php
public $tries = 3;  // 3 tentatives maximum
public $backoff = [10, 30, 60];  // Délais entre tentatives (secondes)
```

**Comportement :**
- Tentative 1 : Immédiate
- Tentative 2 : Après 10 secondes
- Tentative 3 : Après 30 secondes
- Échec final : Job marqué `failed`, événement marqué `failed`

**Gestion échec :**
```php
catch (\Throwable $e) {
    $event->markAsFailed();
    throw $e;  // Relancer pour que le job soit marqué comme failed
}
```

---

### 4.3. Utilisation dispatched_at

**Rôle :** Garantir exactly-once dispatch (un seul job par événement)

**Processus :**

#### Atomic claim (nouvel événement)
```php
$rowsAffected = DB::table('stripe_webhook_events')
    ->where('id', $webhookEvent->id)
    ->whereNull('dispatched_at')  // Pas encore dispatché
    ->update(['dispatched_at' => now()]);

if ($rowsAffected === 1) {
    // Claim réussi : un seul worker peut dispatcher
    ProcessStripeWebhookEventJob::dispatch($webhookEvent->id);
}
```

#### Atomic claim (redispatch failed old)
```php
$threshold = now()->subMinutes(5);
$rowsAffected = DB::table('stripe_webhook_events')
    ->where('id', $webhookEvent->id)
    ->where('status', 'failed')
    ->whereNotNull('dispatched_at')
    ->where('dispatched_at', '<', $threshold)  // > 5 min depuis dernier dispatch
    ->update(['dispatched_at' => now()]);

if ($rowsAffected === 1) {
    // Claim réussi : redispatch autorisé
    ProcessStripeWebhookEventJob::dispatch($webhookEvent->id);
}
```

**Protection :**
- ✅ `dispatched_at IS NULL` = événement jamais dispatché
- ✅ `dispatched_at < threshold` = événement échoué et ancien (> 5 min)
- ✅ Atomic UPDATE garantit exactly-once dispatch

---

### 4.4. Mécanisme requeue_count/blocked

**Fichier :** `app/Services/Payments/WebhookRequeueGuard.php`

**Limite :** 5 requeue par heure par événement

**Processus :**

#### Vérification canRequeue
```php
private static function canRequeue(int $requeueCount, ?Carbon $lastRequeueAt): bool
{
    // Si requeue_count < 5, toujours autorisé
    if ($requeueCount < 5) {
        return true;
    }
    
    // Si requeue_count >= 5, vérifier cooldown (1 heure)
    $oneHourAgo = now()->subHour();
    if ($lastRequeueAt === null || $lastRequeueAt->lte($oneHourAgo)) {
        return true;  // Cooldown expiré
    }
    
    return false;  // Limite atteinte et cooldown actif
}
```

#### Auto-block si limite atteinte
```php
public static function markStripeAsBlockedIfNeeded(StripeWebhookEvent $event): bool
{
    if (!$canRequeue && $event->requeue_count >= 5) {
        $oneHourAgo = now()->subHour();
        if ($event->last_requeue_at && $event->last_requeue_at->gt($oneHourAgo)) {
            $event->markAsBlocked();  // Status = 'blocked'
            // Audit log automatique
            PaymentAuditLog::create([...]);
            return true;
        }
    }
    return false;
}
```

**Mise à jour requeue_count :**
- Incrémenté lors du requeue manuel (admin)
- Incrémenté lors du requeue automatique (commande artisan)
- Reset après 1 heure (cooldown)

**Statut blocked :**
- Événement marqué `status = 'blocked'`
- Ne peut plus être requeued automatiquement
- Peut être débloqué manuellement (admin)

---

## 5. CONCLUSION

### 5.1. Source of truth finale

#### Stripe

**Source of truth :** `PaymentTransaction` (via `ProcessStripeWebhookEventJob`)

**Flux :**
```
Stripe Webhook
  → WebhookController@stripe()
  → StripeWebhookEvent (persist, idempotent)
  → ProcessStripeWebhookEventJob (dispatch)
  → PaymentEventMapperService::updateTransactionAndOrder()
  → PaymentTransaction.update(status='succeeded')
  → Order.update(payment_status='paid', status='processing')
```

**Idempotence :**
- ✅ `StripeWebhookEvent.event_id` unique
- ✅ `firstOrCreate()` évite doublons
- ✅ Atomic claim `dispatched_at` garantit exactly-once dispatch
- ✅ Vérification `isProcessed()` avant traitement

**Note :** Le système legacy (`CardPaymentService::handleWebhook()`) utilise `Payment` au lieu de `PaymentTransaction`, mais il est déprécié.

---

#### Monetbil

**Source of truth :** `PaymentTransaction` (via `ProcessMonetbilCallbackEventJob`)

**Flux :**
```
Monetbil Callback
  → WebhookController@monetbil()
  → MonetbilCallbackEvent (persist, idempotent)
  → ProcessMonetbilCallbackEventJob (dispatch)
  → PaymentEventMapperService::updateTransactionAndOrder()
  → PaymentTransaction.update(status='succeeded')
  → Order.update(payment_status='paid', status='processing')
```

**Idempotence :**
- ✅ `MonetbilCallbackEvent.event_key` unique (hash stable)
- ✅ `firstOrCreate()` évite doublons
- ✅ Atomic claim `dispatched_at` garantit exactly-once dispatch
- ✅ Vérification `status IN ('processed', 'ignored')` avant traitement

**Note :** Le système legacy (`MobileMoneyPaymentService::handleCallback()`) utilise `Payment` au lieu de `PaymentTransaction`, mais il n'est pas utilisé par le nouveau système.

---

### 5.2. Points faibles

#### 🔴 Critique

1. **Double système (legacy + nouveau)**
   - Legacy : `CardPaymentService::handleWebhook()` → `Payment`
   - Nouveau : `ProcessStripeWebhookEventJob` → `PaymentTransaction`
   - Risque : Confusion sur quelle table est la source of truth

2. **Mapping Stripe fragile**
   - `ProcessStripeWebhookEventJob@findTransaction()` utilise des `LIKE` pour chercher `transaction_id`
   - Fallback sur "dernière transaction récente" si pas trouvée (risque de mismatch)

3. **Pas de payload stocké dans StripeWebhookEvent**
   - Le payload n'est pas stocké (seulement `payload_hash`)
   - Impossible de rejouer un événement si besoin
   - `ProcessStripeWebhookEventJob` ne peut pas extraire `payment_intent.id` du payload

#### 🟡 Moyen

4. **Requeue_count non incrémenté automatiquement**
   - `requeue_count` n'est incrémenté que lors du requeue manuel/admin
   - Les redispatch automatiques (failed old) n'incrémentent pas `requeue_count`
   - Risque : Limite de 5/heure contournable

5. **Cooldown 1 heure fixe**
   - Cooldown de 1 heure est fixe (pas configurable)
   - Pas de backoff exponentiel pour requeue_count élevé

6. **Pas de monitoring des blocked events**
   - Événements `blocked` ne sont pas monitorés automatiquement
   - Pas d'alerte si trop d'événements bloqués

#### 🟢 Mineur

7. **Logs verbeux en production**
   - Beaucoup de logs `Log::info()` qui pourraient être `Log::debug()`

8. **Pas de métriques**
   - Pas de métriques sur le taux de succès/échec des webhooks
   - Pas de dashboard temps réel

---

### 5.3. Recommandations (sans coder)

#### 1. Unifier les systèmes
- Migrer complètement vers `PaymentTransaction` pour Stripe et Monetbil
- Supprimer les routes legacy après migration
- Documenter clairement que `PaymentTransaction` est la source of truth

#### 2. Améliorer le mapping Stripe
- Stocker le payload complet dans `StripeWebhookEvent` (ou au moins `payment_intent.id`)
- Extraire `payment_intent.id` du payload pour mapping direct
- Supprimer le fallback "dernière transaction récente"

#### 3. Incrémenter requeue_count automatiquement
- Incrémenter `requeue_count` lors de chaque redispatch (automatique ou manuel)
- Mettre à jour `last_requeue_at` à chaque requeue
- Respecter la limite de 5/heure pour tous les types de requeue

#### 4. Monitoring des blocked events
- Créer une alerte si > X événements bloqués dans les dernières 24h
- Dashboard admin pour visualiser les événements bloqués
- Commande artisan pour débloquer manuellement si besoin

#### 5. Métriques et observabilité
- Ajouter des métriques (taux de succès, latence, erreurs)
- Dashboard temps réel pour les webhooks
- Alertes proactives sur les anomalies

---

## A. DIAGRAMMES TEXTE

### A.1. Pipeline Stripe (v1.1)

```
┌─────────────────┐
│ Stripe Webhook  │
│  (POST request) │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────┐
│ WebhookController@stripe()  │
│ 1. Verify signature         │
│ 2. Extract event_id/type   │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ StripeWebhookEvent          │
│ firstOrCreate(event_id)     │
│ - status='received'         │
│ - payload_hash              │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ Atomic Claim                │
│ UPDATE dispatched_at        │
│ WHERE dispatched_at IS NULL │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ ProcessStripeWebhookEventJob │
│ (dispatch to queue)         │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ Job Handler                 │
│ 1. Lock event (lockForUpdate)│
│ 2. Check isProcessed()      │
│ 3. Map event_type → status  │
│ 4. Find PaymentTransaction  │
│ 5. Update transaction+order │
│ 6. Mark event 'processed'   │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ PaymentTransaction          │
│ status='succeeded'          │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ Order                       │
│ payment_status='paid'      │
│ status='processing'         │
└─────────────────────────────┘
```

---

### A.2. Pipeline Monetbil (v1.1)

```
┌─────────────────┐
│ Monetbil Callback│
│  (POST request) │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────┐
│ WebhookController@monetbil()│
│ 1. Verify signature         │
│ 2. Generate event_key        │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ MonetbilCallbackEvent       │
│ firstOrCreate(event_key)    │
│ - status='received'         │
│ - payload (JSON)            │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ Atomic Claim                │
│ UPDATE dispatched_at        │
│ WHERE dispatched_at IS NULL │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ ProcessMonetbilCallbackEventJob│
│ (dispatch to queue)         │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ Job Handler                 │
│ 1. Lock event (lockForUpdate)│
│ 2. Check status             │
│ 3. Map payload → status     │
│ 4. Find PaymentTransaction  │
│ 5. Update transaction+order │
│ 6. Mark event 'processed'   │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ PaymentTransaction          │
│ status='succeeded'          │
└────────┬────────────────────┘
         │
         ▼
┌─────────────────────────────┐
│ Order                       │
│ payment_status='paid'       │
│ status='processing'         │
└─────────────────────────────┘
```

---

## B. FICHIERS CRITIQUES

### Contrôleurs
- `app/Http/Controllers/Api/WebhookController.php` (nouveau système)
- `app/Http/Controllers/Front/CardPaymentController.php` (legacy Stripe)
- `app/Http/Controllers/Front/MobileMoneyPaymentController.php` (legacy Monetbil)

### Jobs
- `app/Jobs/ProcessStripeWebhookEventJob.php`
- `app/Jobs/ProcessMonetbilCallbackEventJob.php`

### Services
- `app/Services/Payments/PaymentEventMapperService.php`
- `app/Services/Payments/WebhookRequeueGuard.php`
- `app/Services/Payments/CardPaymentService.php` (legacy)
- `app/Services/Payments/MobileMoneyPaymentService.php` (legacy)

### Modèles
- `app/Models/StripeWebhookEvent.php`
- `app/Models/MonetbilCallbackEvent.php`
- `app/Models/PaymentTransaction.php`
- `app/Models/Payment.php` (legacy)
- `app/Models/Order.php`

### Routes
- `routes/api.php` (lignes 19-22)
- `routes/web.php` (lignes 451-461, legacy)

### Migrations
- `database/migrations/2025_12_13_225153_create_stripe_webhook_events_table.php`
- `database/migrations/2025_12_14_000003_create_monetbil_callback_events_table.php`
- `database/migrations/2025_12_15_160000_add_requeue_tracking_to_webhook_events.php`
- `database/migrations/2025_12_15_170000_add_blocked_status_to_webhook_events.php`

---

## C. RISQUES + CORRECTIFS PRIORITAIRES

### 🔴 Critique — À corriger immédiatement

#### 1. Double système legacy/nouveau

**Risque :** Confusion sur source of truth, doublons possibles

**Correctif :**
- Migrer toutes les routes vers `/api/webhooks/*`
- Supprimer routes legacy après migration
- Documenter que `PaymentTransaction` est la source of truth

**Priorité :** P0

---

#### 2. Mapping Stripe fragile

**Risque :** Mismatch entre événement et transaction, paiement non traité

**Correctif :**
- Stocker `payment_intent.id` dans `StripeWebhookEvent` (ou payload complet)
- Extraire `payment_intent.id` du payload pour mapping direct
- Supprimer fallback "dernière transaction récente"

**Priorité :** P0

---

### 🟡 Moyen — À corriger rapidement

#### 3. Requeue_count non incrémenté automatiquement

**Risque :** Limite de 5/heure contournable, boucles infinies possibles

**Correctif :**
- Incrémenter `requeue_count` à chaque redispatch (automatique ou manuel)
- Mettre à jour `last_requeue_at` à chaque requeue
- Respecter limite pour tous les types de requeue

**Priorité :** P1

---

#### 4. Pas de monitoring blocked events

**Risque :** Événements bloqués non détectés, paiements non traités

**Correctif :**
- Alerte si > X événements bloqués dans 24h
- Dashboard admin pour visualiser blocked events
- Commande artisan pour débloquer manuellement

**Priorité :** P1

---

### 🟢 Mineur — À améliorer

#### 5. Logs verbeux

**Risque :** Performance, coût stockage logs

**Correctif :**
- Utiliser `Log::debug()` au lieu de `Log::info()` pour traçage
- Garder `Log::info()` pour événements importants uniquement

**Priorité :** P2

---

#### 6. Pas de métriques

**Risque :** Pas de visibilité sur la santé du système

**Correctif :**
- Ajouter métriques (taux succès, latence, erreurs)
- Dashboard temps réel
- Alertes proactives

**Priorité :** P2

---

**FIN DU RAPPORT — PASS 3/3**

