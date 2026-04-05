# Déploiement Production — RACINE BY GANDA

> **Guide de déploiement production**  
> **Version** : 1.0

---

## Pré-requis Serveur

### Stack Minimum

- **PHP** : 8.2+
- **MySQL** : 8.0+
- **Redis** : 6.0+ (cache & queues)
- **Composer** : 2.x
- **Node.js** : 18+ (build assets)
- **Supervisor** : Pour queue workers

### Extensions PHP Requises

```
php-mysql
php-redis
php-mbstring
php-xml
php-curl
php-zip
php-gd
php-bcmath
```

---

## Variables `.env` Critiques

### Application

```env
APP_NAME="RACINE BY GANDA"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:... # généré via php artisan key:generate
```

### Base de Données

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=racine_production
DB_USERNAME=racine_user
DB_PASSWORD=your_secure_password
```

### Cache & Queues

```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Paiements Stripe

```env
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_CURRENCY=XAF
```

### Mobile Money (Monetbil)

```env
MONETBIL_SERVICE_KEY=your_service_key
MONETBIL_SECRET=your_secret
```

### OAuth (Google)

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://your-domain.com/auth/google/callback
```

### Email (Production)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your_email@domain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@racinebyganda.com
MAIL_FROM_NAME="RACINE BY GANDA"
```

---

## Commandes de Déploiement

### 1. Installation

```bash
# Cloner le projet
git clone https://github.com/your-org/racine-backend.git
cd racine-backend

# Installer dépendances (production)
composer install --optimize-autoloader --no-dev

# Build assets
npm install
npm run build

# Configuration
cp .env.example .env
# Éditer .env avec valeurs production

# Générer clé application
php artisan key:generate
```

### 2. Base de Données

```bash
# Exécuter migrations
php artisan migrate --force

# Seeders (plans créateurs)
php artisan db:seed --class=CreatorPlanSeeder
php artisan db:seed --class=PlanCapabilitySeeder

# Vérifier migrations
php artisan migrate:status
```

### 3. Cache & Optimisations

```bash
# Vider caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Construire caches production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Permissions

```bash
# Permissions storage & cache
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 5. Queue Workers (Supervisor)

**Fichier** : `/etc/supervisor/conf.d/racine-worker.conf`

```ini
[program:racine-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/racine-backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/racine-backend/storage/logs/worker.log
stopwaitsecs=3600
```

**Commandes** :

```bash
# Démarrer supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start racine-worker:*

# Vérifier statut
sudo supervisorctl status racine-worker:*
```

---

## Webhooks

### Stripe Webhooks

**Dashboard Stripe** : https://dashboard.stripe.com/webhooks

**Endpoint Connect** :
- URL : `https://your-domain.com/api/webhooks/stripe/connect`
- Événements : `account.updated`, `capability.updated`, `account.application.deauthorized`

**Endpoint Billing** :
- URL : `https://your-domain.com/api/webhooks/stripe/billing`
- Événements : `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.paid`, `invoice.payment_failed`

**Secret** : Copier dans `.env` → `STRIPE_WEBHOOK_SECRET=whsec_...`

**Test** :
```bash
stripe listen --forward-to https://your-domain.com/api/webhooks/stripe/billing
stripe trigger customer.subscription.created
```

### Monetbil Callback

**URL** : `https://your-domain.com/payment/monetbil/notify`

Configurer dans dashboard Monetbil.

---

## Cron Tasks

**Fichier** : `/etc/cron.d/racine`

```bash
# Vérification abonnements expirés (quotidien 3h)
0 3 * * * www-data php /path/to/artisan creator:check-expired-subscriptions >> /dev/null 2>&1

# Détection risques financiers (quotidien 8h)
0 8 * * * www-data php /path/to/artisan financial:detect-risks >> /dev/null 2>&1

# Optimisations financières (quotidien 3h)
0 3 * * * www-data php /path/to/artisan financial:optimize >> /dev/null 2>&1

# Laravel scheduled tasks (toutes les minutes)
* * * * * www-data php /path/to/artisan schedule:run >> /dev/null 2>&1
```

---

## SSL / HTTPS

### Certbot (Let's Encrypt)

```bash
# Installer certbot
sudo apt install certbot python3-certbot-nginx

# Générer certificat
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# Renouvellement automatique (déjà configuré par certbot)
sudo certbot renew --dry-run
```

**Nginx** : Redirection HTTP → HTTPS automatique via certbot.

---

## Rollback

### Rollback Complet (DB + Code)

```bash
# 1. Restaurer backup DB
mysql -u user -p racine_production < backup_YYYY-MM-DD.sql

# 2. Revenir à version précédente
git checkout <previous-commit>

# 3. Installer dépendances
composer install --optimize-autoloader --no-dev

# 4. Vider caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 5. Reconstruire caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Redémarrer workers
php artisan queue:restart
```

### Rollback Code Uniquement

```bash
git checkout <previous-commit>
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

---

## Monitoring & Logs

### Logs Laravel

```bash
# Logs principaux
tail -f storage/logs/laravel.log

# Logs erreurs
tail -f storage/logs/errors.log

# Logs webhooks
tail -f storage/logs/webhooks.log

# Logs paiements
tail -f storage/logs/payments.log
```

### Rotation Logs

**Fichier** : `/etc/logrotate.d/racine`

```
/path/to/racine-backend/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    sharedscripts
}
```

### Monitoring Recommandé

- **Application** : Laravel Telescope (dev), Sentry (prod)
- **Serveur** : New Relic, Datadog
- **Uptime** : UptimeRobot, Pingdom
- **Stripe** : Dashboard Stripe (webhooks, paiements)

---

## Diagnostic Rapide

### Erreurs 5xx

```bash
# Vérifier logs
tail -100 storage/logs/laravel.log | grep ERROR

# Vérifier permissions
ls -la storage/
ls -la bootstrap/cache/

# Vérifier config
php artisan config:show
```

### Queue Bloquée

```bash
# Vérifier workers
sudo supervisorctl status racine-worker:*

# Redémarrer workers
sudo supervisorctl restart racine-worker:*

# Jobs échoués
php artisan queue:failed

# Retry all
php artisan queue:retry all
```

### Webhooks Non Traités

```bash
# Stripe events pending
php artisan tinker
>>> \App\Models\StripeWebhookEvent::where('status', 'pending')->count()

# Monetbil events pending
>>> \App\Models\MonetbilCallbackEvent::where('status', 'pending')->count()
```

### Paiements Bloqués

```bash
# Paiements pending dernières 24h
php artisan tinker
>>> \App\Models\Payment::where('status', 'pending')->where('created_at', '>', now()->subHours(24))->count()

# Vérifier Stripe status
# https://status.stripe.com
```

---

## Sécurité

### Checklist Sécurité

- ✅ **HTTPS activé** (certificat Let's Encrypt)
- ✅ **APP_DEBUG=false** en production
- ✅ **Secrets jamais committés** (utiliser variables d'environnement serveur)
- ✅ **Rate limiting** activé (webhooks: 60 req/min)
- ✅ **CORS configuré** (pas de `*` en production)
- ✅ **2FA activé** pour admins
- ✅ **Logs sécurisés** (pas de données sensibles loggées)

### Backup

**Automatique quotidien** :

```bash
#!/bin/bash
# /path/to/scripts/backup.sh

DATE=$(date +%Y-%m-%d)
DB_NAME="racine_production"
BACKUP_DIR="/backups/racine"

# Backup DB
mysqldump -u user -p$MYSQL_PASSWORD $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup uploads
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz storage/app/public

# Garder 30 jours
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete
```

**Cron** :
```bash
0 2 * * * /path/to/scripts/backup.sh >> /var/log/racine_backup.log 2>&1
```

---

## Support

### Logs Critiques

```bash
# Toutes erreurs dernières 24h
grep -i "error" storage/logs/laravel-*.log | tail -100

# Webhooks échoués
grep -i "failed" storage/logs/webhooks.log | tail -50
```

### Commandes Rapides

```bash
# Vider tous caches
php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear

# Redémarrer tout
sudo supervisorctl restart racine-worker:*
php artisan queue:restart

# Vérifier santé application
php artisan tinker
>>> DB::connection()->getPdo() // Test DB
>>> Cache::get('test') // Test cache
```

---

## Contacts

- **Dashboard Stripe** : https://dashboard.stripe.com
- **Dashboard Monetbil** : https://dashboard.monetbil.com
- **Support Stripe** : https://support.stripe.com

---

**Dernière mise à jour** : 2026-01-20
