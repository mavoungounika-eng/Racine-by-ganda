# 📋 CHECKLIST DE DÉPLOIEMENT PRODUCTION
## RACINE BY GANDA

**Date de création** : 10 décembre 2025  
**Version Laravel** : 12.39.0  
**PHP** : 8.2.12

---

## 🎯 PRÉ-REQUIS

### Serveur

- [ ] PHP 8.2.12 ou supérieur
- [ ] Composer 2.x
- [ ] Node.js 18+ et npm (pour assets)
- [ ] Base de données MySQL/MariaDB 10.3+ ou PostgreSQL 13+
- [ ] Redis (recommandé pour cache et queues)
- [ ] Extension PHP : `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`

### Services externes

- [ ] Compte Stripe configuré avec clés production
- [ ] Compte MTN MoMo / Airtel Money configuré (si Mobile Money activé)
- [ ] SMTP configuré pour l'envoi d'emails
- [ ] Domaine avec certificat SSL (HTTPS obligatoire)

---

## 📝 1. CONFIGURATION ENVIRONNEMENT

### 1.1. Fichier `.env`

Créer le fichier `.env` à partir de `.env.example` et configurer :

```env
# Application
APP_NAME="RACINE BY GANDA"
APP_ENV=production
APP_KEY=base64:... # Générer avec: php artisan key:generate
APP_DEBUG=false
APP_URL=https://votre-domaine.com

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=racine_production
DB_USERNAME=votre_user
DB_PASSWORD=votre_password_secure

# Cache & Queue
CACHE_DRIVER=redis # ou 'file' si Redis non disponible
QUEUE_CONNECTION=redis # ou 'database' si Redis non disponible
SESSION_DRIVER=redis # ou 'file'

# Redis (si utilisé)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.votre-provider.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@domaine.com
MAIL_PASSWORD=votre_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="${APP_NAME}"

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Mobile Money - MTN MoMo
MTN_MOMO_ENABLED=true
MTN_MOMO_ENVIRONMENT=production
MTN_MOMO_API_KEY=votre_api_key
MTN_MOMO_API_SECRET=votre_api_secret
MTN_MOMO_SUBSCRIPTION_KEY=votre_subscription_key
MTN_MOMO_WEBHOOK_SECRET=votre_webhook_secret
MTN_MOMO_CALLBACK_URL=https://votre-domaine.com/webhooks/mobile-money/mtn_momo

# Mobile Money - Airtel Money
AIRTEL_MONEY_ENABLED=true
AIRTEL_MONEY_ENVIRONMENT=production
AIRTEL_MONEY_CLIENT_ID=votre_client_id
AIRTEL_MONEY_CLIENT_SECRET=votre_client_secret
AIRTEL_MONEY_WEBHOOK_SECRET=votre_webhook_secret
AIRTEL_MONEY_CALLBACK_URL=https://votre-domaine.com/webhooks/mobile-money/airtel_money

# Logs
LOG_CHANNEL=daily
LOG_LEVEL=info
LOG_FUNNEL_DAYS=30
```

### 1.2. Vérifications importantes

- [ ] `APP_DEBUG=false` en production
- [ ] `APP_ENV=production`
- [ ] Clés Stripe en mode **live** (pas test)
- [ ] Webhook secrets configurés et testés
- [ ] URLs de callback Mobile Money accessibles en HTTPS

---

## 🗄️ 2. BASE DE DONNÉES

### 2.1. Migrations

```bash
# Exécuter toutes les migrations
php artisan migrate --force

# Vérifier l'état des migrations
php artisan migrate:status
```

- [ ] Toutes les migrations sont exécutées
- [ ] Aucune migration en attente

### 2.2. Seeds (optionnel)

```bash
# Si nécessaire, créer les données de base
php artisan db:seed --class=AdminUserSeeder
```

- [ ] Compte admin créé et testé
- [ ] Rôles et permissions configurés

---

## 📦 3. INSTALLATION & BUILD

### 3.1. Dépendances

```bash
# Installer les dépendances PHP
composer install --no-dev --optimize-autoloader

# Installer les dépendances Node.js
npm ci

# Build des assets
npm run build
```

- [ ] Dépendances installées
- [ ] Assets compilés

### 3.2. Optimisations Laravel

```bash
# Cache de configuration
php artisan config:cache

# Cache des routes
php artisan route:cache

# Cache des vues
php artisan view:cache

# Optimiser l'autoloader
composer dump-autoload --optimize
```

- [ ] Tous les caches sont créés

### 3.3. Liens symboliques

```bash
# Lier le storage public
php artisan storage:link
```

- [ ] Le lien symbolique `public/storage` existe

---

## 🔄 4. QUEUES & SCHEDULER

### 4.1. Queue Worker

Démarrer le worker de queue (supervisor recommandé) :

```bash
# En mode développement/test
php artisan queue:work --tries=3

# En production avec supervisor (voir config ci-dessous)
```

**Configuration Supervisor** (`/etc/supervisor/conf.d/racine-queue.conf`) :

```ini
[program:racine-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/racine-backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/racine-backend/storage/logs/queue.log
stopwaitsecs=3600
```

- [ ] Queue worker démarré et fonctionnel
- [ ] Jobs traités correctement

### 4.2. Scheduler (Cron)

Ajouter dans le crontab (`crontab -e`) :

```bash
* * * * * cd /var/www/racine-backend && php artisan schedule:run >> /dev/null 2>&1
```

**Jobs planifiés** :
- Nettoyage commandes abandonnées : quotidien à 02:00
- Nettoyage paiements Mobile Money : toutes les 30 minutes
- Vérification alertes stock : quotidien à 08:00

- [ ] Cron configuré
- [ ] Scheduler fonctionne (vérifier les logs)

---

## 🔒 5. SÉCURITÉ

### 5.1. Permissions fichiers

```bash
# Permissions correctes
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

- [ ] Permissions correctes sur `storage/` et `bootstrap/cache/`

### 5.2. Sécurité webhooks

- [ ] Vérification signature Stripe activée
- [ ] Vérification signature Mobile Money activée (désactivée en `local`, activée en `production`)
- [ ] Routes webhooks exclues du CSRF (déjà configuré dans `bootstrap/app.php`)

### 5.3. HTTPS

- [ ] Certificat SSL valide
- [ ] Redirection HTTP → HTTPS configurée
- [ ] Headers de sécurité activés (middleware `SecurityHeaders`)

---

## 🧪 6. TESTS POST-DÉPLOIEMENT

### 6.1. Tests fonctionnels basiques

- [ ] **Accès site** : `https://votre-domaine.com` charge correctement
- [ ] **Boutique** : Catalogue produits accessible
- [ ] **Panier** : Ajout produit au panier fonctionne
- [ ] **Checkout** : Formulaire de commande accessible
- [ ] **Paiement Stripe** : Test avec carte de test
- [ ] **Paiement Mobile Money** : Test en mode sandbox si disponible
- [ ] **Admin** : Connexion admin fonctionne
- [ ] **Créateur** : Connexion créateur fonctionne
- [ ] **Analytics** : Dashboards admin et créateur accessibles

### 6.2. Tests techniques

```bash
# Vérifier les routes
php artisan route:list

# Vérifier les jobs
php artisan queue:work --once

# Vérifier le scheduler
php artisan schedule:list
```

- [ ] Routes accessibles
- [ ] Jobs traités
- [ ] Scheduler configuré

### 6.3. Tests de performance

- [ ] Cache fonctionne (vérifier les requêtes DB réduites)
- [ ] Analytics avec cache (deux appels rapides retournent les mêmes données)
- [ ] Queue traite les jobs rapidement

---

## 📊 7. MONITORING & LOGS

### 7.1. Logs

Vérifier les fichiers de logs :

```bash
# Logs principaux
tail -f storage/logs/laravel.log

# Logs funnel
tail -f storage/logs/funnel.log

# Logs queue
tail -f storage/logs/queue.log
```

- [ ] Logs écrits correctement
- [ ] Rotation des logs configurée (canal `daily`)

### 7.2. Monitoring recommandé

- [ ] Surveiller l'espace disque (logs, uploads)
- [ ] Surveiller la mémoire (queue worker)
- [ ] Surveiller les erreurs 500
- [ ] Surveiller les timeouts de paiement

---

## 🔄 8. MAINTENANCE POST-DÉPLOIEMENT

### 8.1. Commandes utiles

```bash
# Vider le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Vider le cache analytics (si nécessaire)
php artisan tinker
>>> app(\App\Services\AnalyticsService::class)->clearCache();

# Redémarrer les queues
php artisan queue:restart
```

### 8.2. Backups

- [ ] Backup base de données configuré (quotidien recommandé)
- [ ] Backup fichiers `storage/` configuré
- [ ] Test de restauration effectué

---

## ✅ CHECKLIST FINALE

Avant d'ouvrir au public :

- [ ] Tous les tests fonctionnels passent
- [ ] HTTPS actif et fonctionnel
- [ ] Emails envoyés correctement
- [ ] Paiements testés (Stripe + Mobile Money)
- [ ] Analytics fonctionnels
- [ ] Queue worker actif
- [ ] Scheduler configuré
- [ ] Logs surveillés
- [ ] Backups configurés
- [ ] Monitoring en place

---

## 🆘 EN CAS DE PROBLÈME

### Erreurs courantes

1. **500 Internal Server Error** :
   - Vérifier `APP_DEBUG=true` temporairement
   - Vérifier les logs `storage/logs/laravel.log`
   - Vérifier les permissions

2. **Queue ne traite pas** :
   - Vérifier que le worker est démarré
   - Vérifier `QUEUE_CONNECTION` dans `.env`
   - Vérifier les logs queue

3. **Cache ne fonctionne pas** :
   - Vérifier `CACHE_DRIVER` dans `.env`
   - Vider le cache : `php artisan cache:clear`
   - Vérifier Redis si utilisé

4. **Webhooks ne fonctionnent pas** :
   - Vérifier HTTPS
   - Vérifier les URLs de callback
   - Vérifier les secrets webhook
   - Vérifier les logs

---

**Fin de la checklist**

