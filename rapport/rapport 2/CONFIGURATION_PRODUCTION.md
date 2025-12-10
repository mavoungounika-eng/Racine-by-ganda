# 🚀 CONFIGURATION PRODUCTION — RACINE BY GANDA

**Date :** 2025  
**Projet :** RACINE-BACKEND  
**Framework :** Laravel 12

---

## 📋 TABLE DES MATIÈRES

1. [Variables d'environnement](#1-variables-denvironnement)
2. [Configuration HTTPS](#2-configuration-https)
3. [Configuration Emails transactionnels](#3-configuration-emails-transactionnels)
4. [Tests des flux utilisateurs](#4-tests-des-flux-utilisateurs)
5. [Configuration des backups](#5-configuration-des-backups)
6. [Configuration du monitoring](#6-configuration-du-monitoring)

---

## 1. VARIABLES D'ENVIRONNEMENT

### Fichier `.env` pour Production

Créez un fichier `.env` à partir de `.env.example` et configurez les variables suivantes :

```env
# ============================================
# APPLICATION
# ============================================
APP_NAME="RACINE BY GANDA"
APP_ENV=production
APP_KEY=base64:VOTRE_CLE_APPLICATION_GENEREE
APP_DEBUG=false
APP_URL=https://votre-domaine.com

# ============================================
# BASE DE DONNÉES
# ============================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=racine_production
DB_USERNAME=votre_utilisateur_db
DB_PASSWORD=votre_mot_de_passe_db

# ============================================
# MAIL (Configuration complète ci-dessous)
# ============================================
MAIL_MAILER=smtp
MAIL_HOST=smtp.votre-domaine.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@votre-domaine.com
MAIL_PASSWORD=votre_mot_de_passe_email
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@votre-domaine.com"
MAIL_FROM_NAME="${APP_NAME}"

# ============================================
# STRIPE (Paiements)
# ============================================
STRIPE_KEY=pk_live_VOTRE_CLE_PUBLIQUE_STRIPE
STRIPE_SECRET=sk_live_VOTRE_CLE_SECRETE_STRIPE
STRIPE_WEBHOOK_SECRET=whsec_VOTRE_SECRET_WEBHOOK_STRIPE

# ============================================
# MOBILE MONEY (Optionnel)
# ============================================
MTN_MOMO_API_KEY=votre_cle_api_mtn
MTN_MOMO_API_SECRET=votre_secret_mtn
MTN_MOMO_ENVIRONMENT=production
MTN_MOMO_SUBSCRIPTION_KEY=votre_subscription_key

AIRTEL_MONEY_CLIENT_ID=votre_client_id
AIRTEL_MONEY_CLIENT_SECRET=votre_client_secret
AIRTEL_MONEY_ENVIRONMENT=production

# ============================================
# OAUTH GOOGLE
# ============================================
GOOGLE_CLIENT_ID=votre_google_client_id
GOOGLE_CLIENT_SECRET=votre_google_client_secret
GOOGLE_REDIRECT_URI=https://votre-domaine.com/auth/google/callback

# ============================================
# SESSION & CACHE
# ============================================
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_DRIVER=file
QUEUE_CONNECTION=database

# ============================================
# LOGGING
# ============================================
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# ============================================
# SÉCURITÉ
# ============================================
# Ne JAMAIS exposer ces clés publiquement
# Utiliser des valeurs aléatoires fortes
```

### Génération de la clé d'application

```bash
php artisan key:generate
```

### Vérification de la configuration

```bash
# Vérifier la configuration
php artisan config:clear
php artisan config:cache

# Vérifier les variables
php artisan tinker
>>> config('app.env')
>>> config('app.debug')
```

---

## 2. CONFIGURATION HTTPS

### Option 1 : Let's Encrypt (Gratuit - Recommandé)

#### Installation Certbot

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install certbot python3-certbot-nginx

# CentOS/RHEL
sudo yum install certbot python3-certbot-nginx
```

#### Obtenir le certificat

```bash
# Avec Nginx
sudo certbot --nginx -d votre-domaine.com -d www.votre-domaine.com

# Avec Apache
sudo certbot --apache -d votre-domaine.com -d www.votre-domaine.com
```

#### Renouvellement automatique

Le Certbot configure automatiquement le renouvellement. Vérifier :

```bash
sudo certbot renew --dry-run
```

### Option 2 : Configuration Nginx avec SSL

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name votre-domaine.com www.votre-domaine.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name votre-domaine.com www.votre-domaine.com;

    ssl_certificate /etc/letsencrypt/live/votre-domaine.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/votre-domaine.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    root /var/www/racine-backend/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Option 3 : Configuration Apache avec SSL

```apache
<VirtualHost *:80>
    ServerName votre-domaine.com
    Redirect permanent / https://votre-domaine.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName votre-domaine.com
    DocumentRoot /var/www/racine-backend/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/votre-domaine.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/votre-domaine.com/privkey.pem

    <Directory /var/www/racine-backend/public>
        AllowOverride All
        Require all granted
    </Directory>

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/var/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

### Forcer HTTPS dans Laravel

Dans `app/Providers/AppServiceProvider.php` :

```php
use Illuminate\Support\Facades\URL;

public function boot(): void
{
    if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
}
```

Ou dans `bootstrap/app.php` :

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\Illuminate\Http\Middleware\TrustProxies::class);
})
```

Et dans `app/Http/Middleware/TrustProxies.php` :

```php
protected $proxies = '*'; // Ou spécifier les IPs de votre proxy
protected $headers = Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO;
```

---

## 3. CONFIGURATION EMAILS TRANSACTIONNELS

### Option 1 : SMTP (Recommandé)

#### Configuration dans `.env`

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.votre-domaine.com
MAIL_PORT=587
MAIL_USERNAME=noreply@votre-domaine.com
MAIL_PASSWORD=votre_mot_de_passe
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@votre-domaine.com"
MAIL_FROM_NAME="RACINE BY GANDA"
```

#### Services SMTP recommandés

**SendGrid :**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=votre_api_key_sendgrid
```

**Mailgun :**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=votre_mailgun_username
MAIL_PASSWORD=votre_mailgun_password
```

**Postmark :**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.postmarkapp.com
MAIL_PORT=587
MAIL_USERNAME=votre_postmark_token
MAIL_PASSWORD=votre_postmark_token
```

### Option 2 : AWS SES

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=votre_access_key
AWS_SECRET_ACCESS_KEY=votre_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_SES_REGION=us-east-1
```

Installer le package :

```bash
composer require aws/aws-sdk-php
```

### Option 3 : Mailtrap (Développement)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username_mailtrap
MAIL_PASSWORD=votre_password_mailtrap
MAIL_ENCRYPTION=tls
```

### Tester l'envoi d'emails

```bash
php artisan tinker

Mail::raw('Test email', function ($message) {
    $message->to('votre-email@test.com')
            ->subject('Test Email RACINE');
});
```

### Templates d'emails disponibles

- `OrderConfirmationMail` — Confirmation de commande
- `OrderStatusUpdateMail` — Mise à jour de statut
- `WelcomeMail` — Email de bienvenue
- `SecurityAlertMail` — Alertes de sécurité

---

## 4. TESTS DES FLUX UTILISATEURS

### Checklist complète

#### 🛒 Flux E-commerce

- [ ] **Catalogue produits**
  - [ ] Affichage des produits
  - [ ] Filtres par catégorie
  - [ ] Recherche produits
  - [ ] Pagination

- [ ] **Fiche produit**
  - [ ] Affichage détails
  - [ ] Images galerie
  - [ ] Ajout au panier
  - [ ] Gestion quantité

- [ ] **Panier**
  - [ ] Ajout produit
  - [ ] Modification quantité
  - [ ] Suppression produit
  - [ ] Calcul total
  - [ ] Persistance session

- [ ] **Checkout**
  - [ ] Formulaire commande
  - [ ] Validation adresse
  - [ ] Sélection mode de paiement
  - [ ] Récapitulatif

- [ ] **Paiement**
  - [ ] Paiement Stripe (carte)
  - [ ] Paiement Mobile Money (simulation)
  - [ ] Confirmation paiement
  - [ ] Email confirmation

#### 👤 Flux Client

- [ ] **Authentification**
  - [ ] Inscription
  - [ ] Connexion
  - [ ] Déconnexion
  - [ ] Récupération mot de passe
  - [ ] OAuth Google

- [ ] **Profil**
  - [ ] Vue dashboard
  - [ ] Modification profil
  - [ ] Changement mot de passe
  - [ ] Gestion adresses
  - [ ] Liste commandes
  - [ ] Détail commande
  - [ ] Favoris/Wishlist

#### 🎨 Flux Créateur

- [ ] **Authentification**
  - [ ] Inscription créateur
  - [ ] Connexion créateur
  - [ ] Statut pending/active/suspended

- [ ] **Dashboard**
  - [ ] Vue statistiques
  - [ ] Accès rapides

- [ ] **Produits**
  - [ ] Liste produits
  - [ ] Création produit
  - [ ] Édition produit
  - [ ] Publication produit
  - [ ] Suppression produit

- [ ] **Commandes**
  - [ ] Liste commandes
  - [ ] Détail commande
  - [ ] Mise à jour statut

- [ ] **Finances**
  - [ ] Vue finances
  - [ ] Calcul commissions
  - [ ] Historique

- [ ] **Statistiques**
  - [ ] Graphiques
  - [ ] Filtres période
  - [ ] Export

- [ ] **Notifications**
  - [ ] Badge notifications
  - [ ] Liste notifications
  - [ ] Marquer comme lu

#### 👨‍💼 Flux Admin

- [ ] **Authentification**
  - [ ] Connexion admin
  - [ ] 2FA (si activé)

- [ ] **Dashboard**
  - [ ] Statistiques globales
  - [ ] Graphiques

- [ ] **Gestion**
  - [ ] Utilisateurs (CRUD)
  - [ ] Produits (CRUD)
  - [ ] Commandes (liste, détails, statuts)
  - [ ] Catégories (CRUD)
  - [ ] Scanner QR Code

- [ ] **CMS**
  - [ ] Création pages
  - [ ] Édition contenu
  - [ ] Gestion sections

### Script de test automatisé

Créer `tests/Feature/UserFlowsTest.php` :

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_complete_purchase()
    {
        // Test complet du flux d'achat
    }

    public function test_creator_can_manage_products()
    {
        // Test gestion produits créateur
    }

    // ... autres tests
}
```

---

## 5. CONFIGURATION DES BACKUPS

### Option 1 : Backup manuel

#### Script de backup

Créer `scripts/backup.sh` :

```bash
#!/bin/bash

BACKUP_DIR="/var/backups/racine"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="racine_production"
DB_USER="votre_user"
DB_PASS="votre_pass"

# Créer le dossier de backup
mkdir -p $BACKUP_DIR

# Backup base de données
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_$DATE.sql

# Backup fichiers (storage, uploads)
tar -czf $BACKUP_DIR/files_$DATE.tar.gz /var/www/racine-backend/storage

# Garder uniquement les 30 derniers backups
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup terminé : $DATE"
```

Rendre exécutable :

```bash
chmod +x scripts/backup.sh
```

#### Cron job

```bash
# Éditer le crontab
crontab -e

# Backup quotidien à 2h du matin
0 2 * * * /var/www/racine-backend/scripts/backup.sh >> /var/log/racine-backup.log 2>&1
```

### Option 2 : Laravel Backup (Package)

#### Installation

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
php artisan migrate
```

#### Configuration

Dans `config/backup.php` :

```php
'backup' => [
    'destination' => [
        'disks' => [
            'local',
            's3', // Si vous utilisez S3
        ],
    ],
],
```

#### Commandes

```bash
# Backup complet
php artisan backup:run

# Backup base de données uniquement
php artisan backup:run --only-db

# Backup fichiers uniquement
php artisan backup:run --only-files

# Nettoyer les anciens backups
php artisan backup:clean
```

#### Cron job

```bash
# Dans app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:clean')->daily()->at('01:00');
    $schedule->command('backup:run')->daily()->at('02:00');
}
```

### Option 3 : AWS S3

```bash
composer require league/flysystem-aws-s3-v3
```

Configuration `.env` :

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=votre_key
AWS_SECRET_ACCESS_KEY=votre_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=racine-backups
AWS_USE_PATH_STYLE_ENDPOINT=false
```

---

## 6. CONFIGURATION DU MONITORING

### Option 1 : Laravel Telescope (Développement/Debug)

#### Installation

```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

#### Configuration

Dans `.env` :

```env
TELESCOPE_ENABLED=true
```

**⚠️ Important :** Désactiver en production ou limiter l'accès.

### Option 2 : Laravel Horizon (Queues)

#### Installation

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan migrate
```

#### Configuration

Dans `.env` :

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### Dashboard

Accès : `https://votre-domaine.com/horizon`

Protéger par authentification dans `app/Providers/HorizonServiceProvider.php`.

### Option 3 : Monitoring externe

#### Sentry (Gestion d'erreurs)

```bash
composer require sentry/sentry-laravel
```

Configuration `.env` :

```env
SENTRY_LARAVEL_DSN=https://votre-dsn@sentry.io/project-id
SENTRY_TRACES_SAMPLE_RATE=0.2
```

#### New Relic

Installer l'agent :

```bash
# Ubuntu/Debian
curl -s https://download.newrelic.com/install/newrelic-cli/scripts/install.sh | bash
```

Configuration dans `config/newrelic.php`.

#### Logflare (Logs)

```env
LOG_CHANNEL=stack
LOG_STACK=single,logflare
LOGFLARE_API_KEY=votre_key
LOGFLARE_SOURCE_ID=votre_source_id
```

### Option 4 : Health Checks

#### Route de santé

Créer `routes/health.php` :

```php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
    ]);
});
```

#### Monitoring Uptime

Services recommandés :
- **UptimeRobot** (gratuit)
- **Pingdom**
- **StatusCake**

### Scripts de monitoring

#### Vérification disque

```bash
#!/bin/bash
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "Alerte : Espace disque à ${DISK_USAGE}%"
    # Envoyer notification
fi
```

#### Vérification base de données

```bash
#!/bin/bash
mysql -u user -ppass -e "SELECT 1" racine_production
if [ $? -ne 0 ]; then
    echo "Alerte : Base de données inaccessible"
    # Envoyer notification
fi
```

---

## ✅ CHECKLIST FINALE PRODUCTION

### Avant le déploiement

- [ ] Variables d'environnement configurées
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Clé d'application générée
- [ ] HTTPS configuré et testé
- [ ] Emails transactionnels configurés et testés
- [ ] Base de données migrée
- [ ] Cache optimisé
- [ ] Permissions fichiers correctes
- [ ] Backup configuré
- [ ] Monitoring configuré

### Commandes post-déploiement

```bash
# Optimiser Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Vérifier les logs
tail -f storage/logs/laravel.log
```

---

**Dernière mise à jour :** 2025


