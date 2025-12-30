# 🔧 Anti-Stuck Webhooks — Commande de Requeue

**Date :** 2025-12-15  
**Statut :** ✅ **OPÉRATIONNEL**

---

## 🎯 OBJECTIF

La commande `payments:requeue-stuck-webhooks` permet de **requeue** automatiquement les événements webhook/callback qui sont "stuck" (bloqués) :

- Événements avec `dispatched_at = NULL` (jamais dispatchés)
- Événements `failed` avec `dispatched_at` ancien (> seuil configuré)

---

## 📋 UTILISATION

### Signature

```bash
php artisan payments:requeue-stuck-webhooks [--minutes=10] [--provider=all]
```

### Options

- `--minutes` : Seuil en minutes pour considérer un event comme "stuck" (défaut: 10)
- `--provider` : Provider à traiter (`stripe`, `monetbil`, ou `all` - défaut: `all`)

### Exemples

```bash
# Requeue tous les events stuck (tous providers, seuil 10 min)
php artisan payments:requeue-stuck-webhooks

# Requeue uniquement Stripe, seuil 5 minutes
php artisan payments:requeue-stuck-webhooks --minutes=5 --provider=stripe

# Requeue uniquement Monetbil, seuil 15 minutes
php artisan payments:requeue-stuck-webhooks --minutes=15 --provider=monetbil
```

---

## 🔍 LOGIQUE DE SÉLECTION

### Événements éligibles

Un événement est considéré "stuck" si :

1. **Status** = `received` OU `failed`
2. **ET** une des conditions suivantes :
   - `dispatched_at IS NULL` (jamais dispatché)
   - `status = 'failed'` ET `dispatched_at < now() - minutes` (failed ancien)

### Limite de temps

Les événements sont limités à **7 jours maximum** (basé sur `created_at`) pour éviter de traiter des événements très anciens.

---

## ⚙️ TRAITEMENT ATOMIQUE

La commande utilise la **même stratégie atomique** que le `WebhookController` :

1. **Atomic claim 1** : Si `dispatched_at IS NULL`
   - `UPDATE ... SET dispatched_at = NOW() WHERE id = ? AND dispatched_at IS NULL`
   - Si `rowsAffected === 1` → dispatch le job

2. **Atomic claim 2** : Si `status = 'failed'` ET `dispatched_at` ancien
   - `UPDATE ... SET dispatched_at = NOW() WHERE id = ? AND status = 'failed' AND dispatched_at < threshold`
   - Si `rowsAffected === 1` → redispatch le job

3. **Skip** : Si aucun claim n'a réussi (déjà dispatché récemment ou status final)

---

## 📊 STATISTIQUES

La commande affiche un résumé :

```
=== Résumé ===
stripe:
  Scannés: 5
  Dispatchés: 3
  Ignorés: 2
monetbil:
  Scannés: 2
  Dispatchés: 1
  Ignorés: 1

Total: 7 scannés, 4 dispatchés, 3 ignorés
```

---

## 🔄 PLANIFICATION (Scheduler)

Le requeue automatique peut être planifié via Scheduler Laravel.

### Variables .env
```bash
PAYMENTS_STUCK_REQUEUE_ENABLED=true
PAYMENTS_STUCK_REQUEUE_MINUTES=10
```

### Scheduler (exemple)

* Fréquence recommandée : toutes les 5 minutes
* Protection : `withoutOverlapping()` + `onOneServer()`

```php
if (config('payments.webhooks.stuck_requeue_enabled', true)) {
    $minutes = config('payments.webhooks.stuck_requeue_minutes', 10);

    $schedule->command("payments:requeue-stuck-webhooks --minutes={$minutes}")
        ->everyFiveMinutes()
        ->withoutOverlapping()
        ->onOneServer()
        ->description('Requeue automatique des événements webhook/callback stuck');
}
```

### Option alternative : Via Cron

```bash
# Toutes les 5 minutes
*/5 * * * * cd /path/to/project && php artisan payments:requeue-stuck-webhooks --minutes=10 >> /dev/null 2>&1
```

---

## ⚠️ RECOMMANDATIONS

1. **Fréquence** : Toutes les 5-10 minutes est généralement suffisant
2. **Seuil** : 10 minutes par défaut est raisonnable (évite de requeue trop tôt)
3. **Monitoring** : Surveiller les logs pour détecter des patterns de stuck events récurrents
4. **Performance** : La commande limite automatiquement à 7 jours pour éviter de scanner trop de données

---

## 🔍 LOGS

La commande logge les actions importantes :

```php
Log::info('RequeueStuckWebhookEvents: Stripe event requeued', [
    'event_id' => 'evt_...',
    'event_type' => 'payment_intent.succeeded',
    'reason' => 'dispatched_at_null', // ou 'failed_old'
]);
```

---

## ✅ TESTS

Les tests couvrent :

- ✅ Atomic claim empêche double-dispatch (Stripe + Monetbil)
- ✅ Commande requeue events avec `dispatched_at = NULL`
- ✅ Commande requeue events `failed` anciens
- ✅ Commande skip events récents

**Commande de test :**

```bash
php artisan test --filter WebhookDispatchAtomicityTest
```

---

**Documentation créée le 2025-12-15**  
**Anti-Stuck Webhooks ✅**
