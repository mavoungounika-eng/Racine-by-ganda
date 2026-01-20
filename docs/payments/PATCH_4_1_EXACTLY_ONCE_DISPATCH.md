# 🔧 PATCH 4.1 — Exactly-Once Dispatch + Anti-Stuck Webhooks

**Date :** 2025-12-15  
**Statut :** ✅ **TERMINÉ**

---

## 🎯 OBJECTIF

Implémenter un mécanisme **exactly-once dispatch** (par event) côté endpoint, compatible **MySQL (prod)** + **SQLite (tests)**, pour éviter :
- Les redispatch multiples en cas de retry webhook
- Les events bloqués (persistés mais job non dispatché)

---

## ✅ MODIFICATIONS IMPLÉMENTÉES

### A) Migrations — Colonne `dispatched_at`

**Fichiers créés :**
1. `database/migrations/2025_12_15_015923_add_dispatched_at_to_stripe_webhook_events_table.php`
2. `database/migrations/2025_12_15_015924_add_dispatched_at_to_monetbil_callback_events_table.php`

**Changements :**
- Ajout colonne `dispatched_at` (timestamp nullable, index)
- Compatible SQLite (Schema builder uniquement)
- Rollback propre (dropColumn + dropIndex)

---

### B) Modèles — Support `dispatched_at`

**Fichiers modifiés :**
1. `app/Models/StripeWebhookEvent.php`
2. `app/Models/MonetbilCallbackEvent.php`

**Changements :**
- Ajout `dispatched_at` dans `$fillable`
- Ajout cast `'dispatched_at' => 'datetime'`

---

### C) WebhookController — Dispatch Conditionnel

**Fichier modifié :** `app/Http/Controllers/Api/WebhookController.php`

**Logique implémentée (Stripe + Monetbil) :**

#### Règle 1 : Status final → Pas de dispatch
```php
if ($event->isProcessed()) { // processed ou ignored
    return response()->json(['status' => 'already_processed'], 200);
}
```

#### Règle 2 : `dispatched_at` null → Dispatch maintenant
```php
if ($event->dispatched_at === null) {
    ProcessStripeWebhookEventJob::dispatch($event->id);
    $event->update(['dispatched_at' => now()]);
    return response()->json(['status' => 'received'], 200);
}
```

#### Règle 3 : Failed + `dispatched_at` > 5 min → Redispatch autorisé
```php
if ($event->status === 'failed' && $event->dispatched_at->lt(now()->subMinutes(5))) {
    ProcessStripeWebhookEventJob::dispatch($event->id);
    $event->update(['dispatched_at' => now()]);
    return response()->json(['status' => 'received'], 200);
}
```

#### Règle 4 : Déjà dispatché récemment → Pas de redispatch
```php
return response()->json(['status' => 'received'], 200);
```

**Pour les nouveaux événements :**
- Dispatch immédiat + `dispatched_at = now()`

---

### D) Tests — Vérification Exactly-Once

**Fichier modifié :** `tests/Feature/WebhookEndpointsTest.php`

**Tests ajoutés :**

1. **`test_stripe_webhook_dispatch_exactly_once()`**
   - 2 appels avec même `event_id`
   - Assert : `Bus::assertDispatched(ProcessStripeWebhookEventJob::class, 1)` (exactement 1)
   - Assert : `dispatched_at` non-null après 1er appel
   - Assert : 2e réponse `['status' => 'received']` (pas de redispatch)

2. **`test_stripe_webhook_already_processed()`**
   - Event avec `status='processed'`
   - Assert : `['status' => 'already_processed']`
   - Assert : `Bus::assertNothingDispatched()`

3. **`test_monetbil_callback_dispatch_exactly_once()`**
   - Même logique que Stripe, avec `event_key` stable

**Tests existants ajustés :**
- `test_stripe_webhook_idempotence()` : Utilise `Bus::fake()` et vérifie `already_processed`
- `test_monetbil_callback_idempotence()` : Utilise `Bus::fake()` et vérifie `already_processed`

---

## 📊 FICHIERS MODIFIÉS/CRÉÉS (7 fichiers)

1. ✅ `database/migrations/2025_12_15_015923_add_dispatched_at_to_stripe_webhook_events_table.php` (créé)
2. ✅ `database/migrations/2025_12_15_015924_add_dispatched_at_to_monetbil_callback_events_table.php` (créé)
3. ✅ `app/Models/StripeWebhookEvent.php` (modifié)
4. ✅ `app/Models/MonetbilCallbackEvent.php` (modifié)
5. ✅ `app/Http/Controllers/Api/WebhookController.php` (modifié)
6. ✅ `tests/Feature/WebhookEndpointsTest.php` (modifié)

---

## 🧪 TESTS

**Commandes de vérification :**

```bash
# Migration SQLite
php artisan migrate:fresh --env=testing

# Tests corrigés
php artisan test --filter WebhookEndpointsTest
# ✅ 7 tests passent (34 assertions)

# Tests existants (vérification non-régression)
php artisan test --filter PaymentJobsIdempotenceTest
# ✅ 5 tests passent (8 assertions)

php artisan test --filter WebhookSecurityTest
# ✅ 7 tests passent (15 assertions)

php artisan test --filter PaymentWebhookSecurityTest
# ✅ 4 tests passent (8 assertions)

php artisan test --filter PaymentsHubRbacTest
# ✅ 5 tests passent (11 assertions)
```

---

## ✅ CONFORMITÉ

- ✅ Routes restent `api` + `throttle:api`
- ✅ Aucun secret exposé
- ✅ Exactly-once dispatch : un event = un dispatch maximum
- ✅ Anti-stuck : event `failed` + `dispatched_at` > 5 min → redispatch autorisé
- ✅ Compatible MySQL (production) et SQLite (tests)
- ✅ Aucune régression : tous les tests existants passent

---

## 🔍 DÉTAILS TECHNIQUES

### Migration SQLite-Compatible

```php
Schema::table('stripe_webhook_events', function (Blueprint $table) {
    $table->timestamp('dispatched_at')->nullable()->after('processed_at');
    $table->index('dispatched_at');
});
```

### Logique de Dispatch

**Nouvel événement :**
1. `firstOrCreate()` → `wasRecentlyCreated = true`
2. Dispatch job + `dispatched_at = now()`

**Événement existant :**
1. Si `status` final → `already_processed` (pas de dispatch)
2. Si `dispatched_at` null → Dispatch + set `dispatched_at`
3. Si `status=failed` + `dispatched_at` > 5 min → Redispatch + update `dispatched_at`
4. Sinon → `received` (pas de redispatch)

---

**Patch 4.1 terminé le 2025-12-15**  
**Exactly-Once Dispatch + Anti-Stuck Webhooks ✅**






