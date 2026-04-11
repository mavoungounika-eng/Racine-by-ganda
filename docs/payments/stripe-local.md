# Stripe Webhook Local Testing Guide

Guide complet pour tester les webhooks Stripe en local avec Stripe CLI.

## Prérequis

1. **Stripe CLI installé**
   - Windows (Scoop): `scoop install stripe`
   - Windows (Chocolatey): `choco install stripe-cli`
   - Ou télécharger depuis: https://stripe.com/docs/stripe-cli

2. **Laravel serveur démarré**
   ```powershell
   php artisan serve
   ```

3. **Stripe CLI authentifié**
   ```powershell
   stripe login
   ```

## Configuration automatique

### Script PowerShell

Utilisez le script `scripts/stripe-local.ps1` pour automatiser la configuration :

```powershell
# Configuration de base
.\scripts\stripe-local.ps1

# Avec déclenchement automatique de test
.\scripts\stripe-local.ps1 -RunTrigger
```

Le script va :
1. ✅ Vérifier que Stripe CLI est installé
2. ✅ Vérifier que Laravel est accessible
3. ✅ Démarrer `stripe listen --forward-to http://127.0.0.1:8000/api/webhooks/stripe`
4. ✅ Capturer automatiquement le secret `whsec_...`
5. ✅ Mettre à jour `.env` avec `STRIPE_ENABLED=true` et `STRIPE_WEBHOOK_SECRET=whsec_...`
6. ✅ Exécuter `php artisan optimize:clear`

### Configuration manuelle

Si vous préférez configurer manuellement :

1. **Démarrer stripe listen**
   ```powershell
   stripe listen --forward-to http://127.0.0.1:8000/api/webhooks/stripe
   ```

2. **Copier le secret affiché** (commence par `whsec_...`)

3. **Mettre à jour `.env`**
   ```env
   STRIPE_ENABLED=true
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

4. **Nettoyer le cache**
   ```powershell
   php artisan optimize:clear
   ```

## Test du webhook

### Déclencher un événement de test

Dans un **nouveau terminal** (stripe listen doit rester actif) :

```powershell
# Événement payment_intent.succeeded
stripe trigger payment_intent.succeeded

# Autres événements disponibles
stripe trigger checkout.session.completed
stripe trigger payment_intent.payment_failed
```

### Vérifier la réception

#### 1. Vérification rapide (commande smoke test)

```powershell
php artisan payments:stripe-webhook-smoke
```

Affiche :
- ✅ Checklist de configuration
- ✅ Liste des 5 derniers événements reçus
- ✅ Instructions de correction si nécessaire

#### 2. Vérification avec logs

```powershell
# Afficher les 50 dernières lignes filtrées
php artisan payments:stripe-webhook-smoke --tail=50
```

#### 3. Vérification dans la base de données

```powershell
php artisan tinker
```

```php
// Dernier événement reçu
App\Models\StripeWebhookEvent::latest()->first();

// Tous les événements reçus aujourd'hui
App\Models\StripeWebhookEvent::whereDate('created_at', today())->get();

// Événements par statut
App\Models\StripeWebhookEvent::where('status', 'received')->count();
App\Models\StripeWebhookEvent::where('status', 'processed')->count();
```

#### 4. Vérification des logs Laravel

```powershell
# Windows PowerShell
Get-Content storage/logs/laravel.log -Tail 50 | Select-String "received_stripe_webhook"

# Ou avec grep (si disponible)
grep -i "received_stripe_webhook" storage/logs/laravel.log | tail -20
```

## Résultat attendu

### 1. HTTP 200 sur le webhook

Stripe CLI devrait afficher :
```
2024-01-15 10:30:45   --> payment_intent.succeeded [evt_xxx]
2024-01-15 10:30:45  <-- [200] POST http://127.0.0.1:8000/api/webhooks/stripe [evt_xxx]
```

### 2. Log dans Laravel

Le log devrait contenir :
```
[2024-01-15 10:30:45] local.INFO: received_stripe_webhook {"received_stripe_webhook":true,"event_id":"evt_xxx","event_type":"payment_intent.succeeded","signature_header_present":true,"ip":"127.0.0.1"}
```

### 3. Enregistrement en base de données

Vérifier dans `stripe_webhook_events` :
- ✅ `event_id` = `evt_xxx`
- ✅ `event_type` = `payment_intent.succeeded`
- ✅ `status` = `received` (puis `processed` après traitement du job)
- ✅ `dispatched_at` non null (job dispatché)

### 4. Traitement du job

Si un worker est actif :
```powershell
php artisan queue:work
```

Le job `ProcessStripeWebhookEventJob` devrait être exécuté et mettre à jour le statut à `processed`.

## Troubleshooting

### 401 Unauthorized - Signature invalide

**Symptôme :**
```
[401] POST http://127.0.0.1:8000/api/webhooks/stripe
```

**Causes possibles :**
1. `STRIPE_WEBHOOK_SECRET` incorrect ou manquant
2. Secret expiré (stripe listen génère un nouveau secret à chaque démarrage)
3. Cache Laravel non nettoyé

**Solution :**
```powershell
# 1. Vérifier le secret dans stripe listen
# 2. Mettre à jour .env avec le nouveau secret
# 3. Nettoyer le cache
php artisan optimize:clear
```

### 400 Bad Request - Invalid event

**Symptôme :**
```
[400] POST http://127.0.0.1:8000/api/webhooks/stripe
{"error":"Invalid event"}
```

**Causes possibles :**
1. Payload JSON invalide
2. Event ID ou type manquant

**Solution :**
- Vérifier les logs Laravel pour plus de détails
- Tester avec un autre événement : `stripe trigger checkout.session.completed`

### 404 Not Found - Route introuvable

**Symptôme :**
```
[404] POST http://127.0.0.1:8000/api/webhooks/stripe
```

**Causes possibles :**
1. Route non enregistrée
2. Serveur Laravel non démarré
3. URL incorrecte

**Solution :**
```powershell
# Vérifier que la route existe
php artisan route:list --path=webhooks/stripe

# Vérifier que le serveur est démarré
php artisan serve
```

### Aucun log "received_stripe_webhook"

**Causes possibles :**
1. Webhook non reçu (vérifier stripe listen)
2. Erreur avant le log (vérifier les logs d'erreur)
3. Channel de log incorrect

**Solution :**
```powershell
# Vérifier tous les logs récents
Get-Content storage/logs/laravel.log -Tail 100

# Vérifier la configuration des logs
php artisan config:show logging.default
```

### Événement reçu mais pas en DB

**Causes possibles :**
1. Erreur lors de la persistance
2. Migration non exécutée
3. Problème de connexion DB

**Solution :**
```powershell
# Vérifier que la table existe
php artisan payments:stripe-webhook-smoke

# Exécuter les migrations si nécessaire
php artisan migrate

# Vérifier les logs d'erreur
Get-Content storage/logs/laravel.log -Tail 50 | Select-String "Failed to persist"
```

### Job non dispatché

**Causes possibles :**
1. Queue worker non actif
2. Erreur lors du dispatch
3. Configuration queue incorrecte

**Solution :**
```powershell
# Vérifier la configuration queue
php artisan config:show queue.default

# Démarrer un worker
php artisan queue:work

# Vérifier les jobs failed
php artisan queue:failed
```

## Commandes utiles

### Smoke test complet
```powershell
php artisan payments:stripe-webhook-smoke --tail=50
```

### Vérifier la configuration
```powershell
php artisan config:show services.stripe
php artisan config:show stripe
```

### Nettoyer et redémarrer
```powershell
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
```

### Voir les événements en temps réel
```powershell
# Terminal 1: stripe listen
stripe listen --forward-to http://127.0.0.1:8000/api/webhooks/stripe

# Terminal 2: logs en temps réel
Get-Content storage/logs/laravel.log -Wait | Select-String "received_stripe_webhook"
```

## Notes importantes

1. **Secret webhook local** : Le secret généré par `stripe listen` est temporaire et change à chaque démarrage. Il ne doit **jamais** être utilisé en production.

2. **Production** : En production, utilisez le secret webhook depuis le dashboard Stripe (https://dashboard.stripe.com/webhooks).

3. **Sécurité** : Le log `received_stripe_webhook` ne contient **jamais** le payload brut ni les headers sensibles pour des raisons de sécurité.

4. **Idempotence** : Les événements sont idempotents. Si vous déclenchez le même événement plusieurs fois, il ne sera traité qu'une seule fois (basé sur `event_id`).

## Support

- Documentation Stripe CLI : https://stripe.com/docs/stripe-cli
- Dashboard Stripe : https://dashboard.stripe.com
- Logs Stripe : Dashboard → Developers → Logs

