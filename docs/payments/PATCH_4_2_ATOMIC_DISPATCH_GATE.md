# 🔧 PATCH 4.2 — Atomic Dispatch Gate + Requeue Stuck

**Date :** 2025-12-15  
**Statut :** ✅ **TERMINÉ**

---

## 🎯 OBJECTIF

Renforcer le Patch 4.1 ("dispatched_at") en rendant la décision de dispatch **atomique** (éviter double-dispatch sous concurrence) et ajouter une commande Artisan pour **requeue** des events "stuck".

---

## ✅ MODIFICATIONS IMPLÉMENTÉES

### A) WebhookController — Atomic Dispatch Gate

**Fichier modifié :** `app/Http/Controllers/Api/WebhookController.php`

**Changements :**

#### Avant (Patch 4.1) : Vérification puis update (non atomique)
```php
if ($webhookEvent->dispatched_at === null) {
    ProcessStripeWebhookEventJob::dispatch($webhookEvent->id);
    $webhookEvent->update(['dispatched_at' => now()]);
}
```

#### Après (Patch 4.2) : UPDATE atomique conditionnel
```php
// Atomic claim 1 : dispatched_at IS NULL
$rowsAffected = DB::table('stripe_webhook_events')
    ->where('id', $webhookEvent->id)
    ->whereNull('dispatched_at')
    ->update([
        'dispatched_at' => now(),
        'updated_at' => now(),
    ]);

if ($rowsAffected === 1) {
    // Claim réussi : dispatch le job
    ProcessStripeWebhookEventJob::dispatch($webhookEvent->id);
}
```

**Logique implémentée (Stripe + Monetbil) :**

1. **Status final** → `already_processed` (pas de dispatch)
2. **Atomic claim 1** : `dispatched_at IS NULL` → UPDATE atomique → si `rowsAffected === 1` → dispatch
3. **Atomic claim 2** : `status = 'failed'` ET `dispatched_at < threshold` → UPDATE atomique → si `rowsAffected === 1` → redispatch
4. **Sinon** → `received` (pas de redispatch)

**Avantages :**
- ✅ Évite double-dispatch sous concurrence
- ✅ Compatible MySQL (prod) et SQLite (tests)
- ✅ Pas besoin de `lockForUpdate` : l'UPDATE atomique suffit

---

### B) Commande Artisan — Requeue Stuck Webhooks

**Fichier créé :** `app/Console/Commands/Payments/RequeueStuckWebhookEvents.php`

**Signature :**
```bash
php artisan payments:requeue-stuck-webhooks [--minutes=10] [--provider=all]
```

**Fonctionnalités :**

1. **Sélection des événements "stuck" :**
   - Status = `received` OU `failed`
   - ET (`dispatched_at IS NULL` OU `status = 'failed'` ET `dispatched_at < threshold`)
   - Limite : 7 jours maximum (basé sur `created_at`)

2. **Traitement atomique :**
   - Utilise la même stratégie atomic claim que le controller
   - Compte : `scanned` / `dispatched` / `skipped`

3. **Output console :**
   - Résumé par provider (Stripe / Monetbil)
   - Total général

**Exemples d'utilisation :**
```bash
# Tous providers, seuil 10 min
php artisan payments:requeue-stuck-webhooks

# Stripe uniquement, seuil 5 min
php artisan payments:requeue-stuck-webhooks --minutes=5 --provider=stripe

# Monetbil uniquement, seuil 15 min
php artisan payments:requeue-stuck-webhooks --minutes=15 --provider=monetbil
```

---

### C) Tests — Atomicité

**Fichier créé :** `tests/Feature/WebhookDispatchAtomicityTest.php`

**Tests ajoutés :**

1. **`test_stripe_atomic_claim_prevents_double_dispatch()`**
   - Simule 2 appels concurrents
   - Assert : exactement 1 dispatch
   - Assert : `rowsAffected2 === 0` (atomic claim échoué)

2. **`test_monetbil_atomic_claim_prevents_double_dispatch()`**
   - Même logique pour Monetbil

3. **`test_command_requeues_stuck_events_with_null_dispatched_at()`**
   - Event avec `dispatched_at = NULL`
   - Assert : job dispatché + `dispatched_at` set

4. **`test_command_requeues_failed_old_events()`**
   - Event `failed` avec `dispatched_at` ancien
   - Assert : job redispatched + `dispatched_at` mis à jour

5. **`test_command_skips_recent_events()`**
   - Event avec `dispatched_at` récent
   - Assert : pas de dispatch

6. **`test_command_requeues_monetbil_stuck_events()`**
   - Même logique pour Monetbil

---

### D) Documentation

**Fichier créé :** `docs/payments/ANTI_STUCK_WEBHOOKS.md`

**Contenu :**
- Utilisation de la commande
- Logique de sélection
- Planification (scheduler / cron)
- Recommandations

---

## 📊 FICHIERS MODIFIÉS/CRÉÉS (4 fichiers)

1. ✅ `app/Http/Controllers/Api/WebhookController.php` (modifié — atomic dispatch gate)
2. ✅ `app/Console/Commands/Payments/RequeueStuckWebhookEvents.php` (créé)
3. ✅ `tests/Feature/WebhookDispatchAtomicityTest.php` (créé)
4. ✅ `docs/payments/ANTI_STUCK_WEBHOOKS.md` (créé)

---

## 🧪 TESTS

**Commandes de vérification :**

```bash
# Migration SQLite
php artisan migrate:fresh --env=testing

# Tests nouveaux
php artisan test --filter WebhookDispatchAtomicityTest
# ✅ 6 tests passent (23 assertions)

# Tests existants (vérification non-régression)
php artisan test --filter WebhookEndpointsTest
# ✅ 7 tests passent (34 assertions)

php artisan test --filter WebhookSecurityTest
# ✅ 7 tests passent (15 assertions)

php artisan test --filter PaymentJobsIdempotenceTest
# ✅ 5 tests passent (8 assertions)

php artisan test --filter PaymentsHubRbacTest
# ✅ 5 tests passent (11 assertions)

# Tous ensemble
php artisan test --filter "WebhookDispatchAtomicityTest|WebhookEndpointsTest|WebhookSecurityTest|PaymentJobsIdempotenceTest|PaymentsHubRbacTest"
# ✅ 30 tests passent (91 assertions)
```

---

## ✅ CONFORMITÉ

- ✅ Atomic dispatch gate : évite double-dispatch sous concurrence
- ✅ Commande anti-stuck : requeue automatique des events bloqués
- ✅ Compatible MySQL (production) et SQLite (tests)
- ✅ Aucun secret exposé : logging strict maintenu
- ✅ Aucune régression : tous les tests existants passent
- ✅ Documentation complète : utilisation + planification

---

## 🔍 DÉTAILS TECHNIQUES

### Atomic Claim Pattern

**Principe :**
- Utiliser `UPDATE ... WHERE condition` avec condition stricte
- Si `rowsAffected === 1` → claim réussi → dispatch
- Si `rowsAffected === 0` → claim échoué (déjà pris) → skip

**Avantages :**
- Pas besoin de `lockForUpdate` (plus simple)
- Compatible MySQL et SQLite
- Performant (index sur `dispatched_at`)

### Commande Requeue

**Stratégie :**
1. Sélectionner events éligibles (status + dispatched_at)
2. Pour chaque event : atomic claim (même logique que controller)
3. Compter : scanned / dispatched / skipped
4. Afficher résumé

**Limites :**
- 7 jours maximum (basé sur `created_at`)
- Seuil configurable (défaut: 10 minutes)

---

**Patch 4.2 terminé le 2025-12-15**  
**Atomic Dispatch Gate + Requeue Stuck ✅**








