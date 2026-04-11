# ⚙️ CONFIGURATION QUEUE — PAYMENTS HUB

**Date :** 2025-12-14  
**Version :** 1.0  
**Statut :** ✅ **ACTIF**

---

## 🎯 OBJECTIF

Documenter la configuration des queues pour le traitement asynchrone des webhooks/callbacks, avec retry, backoff, timeout et supervision.

---

## 📋 CONFIGURATION

### Variables d'environnement

```env
# Connection queue (sync, database, redis, sqs)
QUEUE_CONNECTION=database

# Pour Redis (si utilisé)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Configuration des jobs

Les jobs Payments Hub sont configurés avec :

- **Tries** : 3 tentatives
- **Timeout** : 60 secondes
- **Backoff** : [10, 30, 60] secondes (délai entre tentatives)

```php
// ProcessStripeWebhookEventJob
public $tries = 3;
public $timeout = 60;
public $backoff = [10, 30, 60];

// ProcessMonetbilCallbackEventJob
public $tries = 3;
public $timeout = 60;
public $backoff = [10, 30, 60];
```

---

## 🔄 STRATÉGIE DE RETRY

### Tentatives

1. **Tentative 1** : Immédiate
2. **Tentative 2** : Après 10 secondes
3. **Tentative 3** : Après 30 secondes supplémentaires (40s total depuis tentative 1)

### Limites

- Maximum 3 tentatives par job
- Après 3 échecs, le job est marqué comme `failed` et stocké dans `failed_jobs`
- Pas de retry automatique infini (évite boucles)

---

## 🚀 SUPERVISION

### Laravel Queue Worker

**Commande de base :**
```bash
php artisan queue:work --queue=default --tries=3 --timeout=60
```

**Avec Supervisor (recommandé production) :**

Créer `/etc/supervisor/conf.d/laravel-worker.conf` :

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work database --sleep=3 --tries=3 --timeout=60 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

**Commandes Supervisor :**
```bash
# Recharger la config
sudo supervisorctl reread
sudo supervisorctl update

# Démarrer/arrêter
sudo supervisorctl start laravel-worker:*
sudo supervisorctl stop laravel-worker:*

# Statut
sudo supervisorctl status
```

### Laravel Horizon (si installé)

Si Laravel Horizon est installé, utiliser Horizon pour la supervision :

```bash
php artisan horizon
```

Horizon gère automatiquement :
- Scaling des workers
- Monitoring en temps réel
- Métriques et alertes

---

## 📊 MONITORING

### Vérifier les jobs en attente

```bash
# Nombre de jobs en attente
php artisan queue:work --once

# Lister les jobs failed
php artisan queue:failed
```

### Vérifier les jobs failed

```bash
# Lister
php artisan queue:failed

# Retry un job spécifique
php artisan queue:retry {job-id}

# Retry tous les jobs failed
php artisan queue:retry all

# Supprimer un job failed
php artisan queue:forget {job-id}

# Vider tous les jobs failed
php artisan queue:flush
```

---

## 🔍 DÉBOGAGE

### Logs

Les jobs loggent leurs actions :

- **Succès** : `Log::info()` avec event_id/event_key, transaction_id, status
- **Échec** : `Log::error()` avec error, exception_class
- **Idempotence** : `Log::info()` avec "already processed"

### Vérifier les logs

```bash
tail -f storage/logs/laravel.log | grep "ProcessStripeWebhookEventJob\|ProcessMonetbilCallbackEventJob"
```

---

## ✅ CHECKLIST PRODUCTION

- ✅ Queue connection configurée (`QUEUE_CONNECTION=database` ou `redis`)
- ✅ Tables `jobs` et `failed_jobs` migrées
- ✅ Supervisor configuré (ou Horizon)
- ✅ Workers démarrés et surveillés
- ✅ Monitoring des jobs failed en place
- ✅ Procédure de retry documentée (voir `FAILED_JOBS_RUNBOOK.md`)

---

## 📝 NOTES

### Pourquoi database queue ?

- Simple à configurer (pas de Redis/SQS requis)
- Idempotence garantie par contraintes DB
- Parfait pour début de projet

### Migration vers Redis (optionnel)

Pour de meilleures performances avec beaucoup de jobs :

1. Installer Redis
2. Configurer `QUEUE_CONNECTION=redis`
3. Adapter la config si nécessaire

---

**Configuration en vigueur depuis le Sprint 4 (2025-12-14)**









