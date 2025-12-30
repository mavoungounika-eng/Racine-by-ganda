# 🚨 Runbook Incidents — Webhooks Payments Hub

**Date :** 2025-12-15  
**Version :** 1.0  
**Périmètre :** Stripe webhooks + Monetbil callbacks (Payments Hub)

---

## 1) Objectif

Ce runbook fournit des procédures opérationnelles pour diagnostiquer et résoudre les incidents liés aux webhooks/callbacks du Payments Hub, notamment :
- Paiements bloqués (transactions `pending` / `processing`)
- Events persistés en `received` non traités
- Jobs en échec (`failed_jobs`)
- Queue worker arrêté / instable
- Erreurs de signature (401) Stripe/Monetbil

**Endpoints officiels Payments Hub :**
- Stripe : `POST /api/webhooks/stripe` (routes/api.php)
- Monetbil : `POST /api/webhooks/monetbil` (routes/api.php)

**Note :** Les routes legacy `/payment/card/webhook` et `/webhooks/stripe` (routes/web.php) sont dépréciées et seront supprimées dans une future version.

---

## 2) Symptômes courants

### 2.1 Symptômes principaux
1. **Paiements bloqués** : transactions `pending` / `processing` qui ne finalisent pas
2. **Events "received" persistés** : `stripe_webhook_events` / `monetbil_callback_events` restent en `received`
3. **Hausse de jobs échoués** : augmentation de `failed_jobs`
4. **Queue down** : worker arrêté ou non fonctionnel
5. **Erreurs 401 répétées** : signatures invalides Stripe/Monetbil

### 2.2 Impacts typiques
- Retard de confirmation de commande
- Incohérences statut paiement/commande
- Dégradation UX et support client

---

## 3) Diagnostic rapide (check initial)

### 3.1 Vérifier le Queue Worker
```bash
# Linux
ps aux | grep "queue:work"

# Supervisor
supervisorctl status laravel-worker

# Redémarrer proprement
php artisan queue:restart
php artisan queue:work --tries=3 --timeout=60
```

### 3.2 Vérifier les failed jobs

```bash
php artisan queue:failed
php artisan queue:failed:show {id}
php artisan queue:retry {id}
php artisan queue:retry all
```

### 3.3 Vérifier l'état via l'Admin

1. `/admin/payments` → section **Webhooks Health / Observability**

   * ratio `received` vs `processed`
   * `stuck` total (par provider)
   * dernier event reçu par provider

2. `/admin/payments/webhooks/stuck`

   * events avec `dispatched_at = NULL`
   * events `failed` anciens (au-delà du seuil)

---

## 4) Procédures de résolution

### 4.1 Requeue en masse via commande

**À utiliser quand :** beaucoup d'events stuck, ou après redémarrage du worker.

```bash
# Tous providers, seuil 10 min (défaut)
php artisan payments:requeue-stuck-webhooks

# Stripe uniquement, seuil 5 min
php artisan payments:requeue-stuck-webhooks --minutes=5 --provider=stripe

# Monetbil uniquement, seuil 15 min
php artisan payments:requeue-stuck-webhooks --minutes=15 --provider=monetbil
```

**Validation :**

* Vérifier que des jobs s'exécutent (ex: `php artisan queue:work --once`)
* Vérifier que la page "stuck" diminue
* Vérifier que les `dispatched_at` sont mis à jour

---

### 4.2 Requeue ciblé via UI Admin

**À utiliser quand :** incident limité à quelques events, besoin d'action auditée.

1. Ouvrir `/admin/payments/webhooks/stuck`
2. Filtrer (provider / status / minutes / dates)
3. Sélectionner des events (bulk) ou cliquer "Requeue" sur un item
4. **Raison obligatoire** (min 5 caractères)
5. Valider

**Garde-fou anti-boucle :**
- Maximum **5 requeue par heure** par event
- Si limite atteinte, le bouton "Requeue" est désactivé
- La colonne "Requeue Count" affiche le nombre de requeue effectués

**Validation :**

* Message flash (scanned / dispatched / skipped)
* Audit log visible (`action = reprocess`)
* `dispatched_at` mis à jour sur les events dispatchés
* `requeue_count` incrémenté, `last_requeue_at` mis à jour

---

### 4.3 Stripe — erreurs de signature (401)

**Symptôme :** 401, logs "Invalid signature".

**Vérifications (sans afficher le secret) :**

```bash
php artisan tinker
>>> filled(config('services.stripe.webhook_secret'))
```

* Si `false` → secret non configuré
* Si `true` → secret présent (vérifier ensuite côté Stripe Dashboard)

**Vérifier côté Stripe Dashboard :**
1. Aller sur https://dashboard.stripe.com/webhooks
2. Vérifier l'endpoint configuré : `https://<domaine>/api/webhooks/stripe`
3. Vérifier que le "Signing secret" correspond à `STRIPE_WEBHOOK_SECRET` dans `.env`
4. Vérifier le mode (Test/Live) correspond à l'environnement

**Actions :**

* Mise à jour `.env` si secret changé ou endpoint modifié
* Puis :

```bash
php artisan config:clear
php artisan cache:clear
```

**Note :** Les routes legacy `/payment/card/webhook` et `/webhooks/stripe` (routes/web.php) sont dépréciées. L'endpoint officiel Payments Hub est `/api/webhooks/stripe`.

---

### 4.4 Monetbil — erreurs HMAC (401)

**Symptôme :** 401, logs "Invalid signature".

**Vérifications (sans afficher le secret) :**

```bash
php artisan tinker
>>> filled(config('services.monetbil.service_secret'))
```

* Si `false` → secret non configuré
* Si `true` → secret présent (vérifier ensuite côté Monetbil Dashboard)

**Vérifier côté Monetbil Dashboard :**
1. Vérifier l'URL callback : `https://<domaine>/api/webhooks/monetbil`
2. Vérifier que le `service_secret` correspond à `MONETBIL_SERVICE_SECRET` dans `.env`
3. Vérifier le mode (Test/Live) correspond à l'environnement

**Actions :**

* Mettre à jour `.env` si nécessaire
* Puis `php artisan config:clear && php artisan cache:clear`

---

### 4.5 Mitigation en cas de spikes / surcharge

**Symptôme :** pic massif d'events, backlog queue, saturation CPU/RAM.

**Actions immédiates :**

1. Désactiver temporairement l'auto-requeue :

```bash
PAYMENTS_STUCK_REQUEUE_ENABLED=false
```

2. Augmenter le seuil :

```bash
PAYMENTS_STUCK_REQUEUE_MINUTES=30
```

3. Stabiliser la queue :

* augmenter le nombre de workers (scaling horizontal)
* vérifier timeouts / retries
* activer monitoring (Horizon si utilisé)

**Après stabilisation :**

* Réactiver l'auto-requeue
* Réduire progressivement le seuil (30 → 20 → 10)

---

## 5) Sécurité (obligatoire)

* **Aucun secret** ne doit apparaître dans les logs/ UI (pas de payload/headers/signatures).
* **Vérifications tinker** : utiliser `filled(config('...'))` au lieu de `config('...')` pour éviter d'afficher les secrets.
* Toute action de requeue via UI doit être :

  * **autorisée** (RBAC `payments.reprocess`)
  * **auditée** (`payment_audit_logs`)
  * avec **reason** obligatoire (min 5 caractères)
  * **limitée** : max 5 requeue/heure par event (garde-fou anti-boucle)

---

## 6) Escalade

### Niveau 1

* < 10 events stuck → UI requeue
* worker down → restart
* failed jobs isolés → retry ciblé

### Niveau 2

* > 50 events stuck → commande + investigation
* signatures invalides répétées → vérifier configuration provider
* workers instables → scaling + monitoring

### Niveau 3 (urgence)

* paiements bloqués en masse → requeue immédiat + mitigation
* fuite de secrets suspectée → rotation clés + incident sécurité

---

## 7) Ressources

* `docs/payments/ANTI_STUCK_WEBHOOKS.md`
* `docs/payments/RETENTION_POLICY.md`
* `docs/payments/LOGGING_POLICY.md`
* Dashboard Admin : `/admin/payments`
* Stuck UI : `/admin/payments/webhooks/stuck`
