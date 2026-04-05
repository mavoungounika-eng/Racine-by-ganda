# ═══════════════════════════════════════════════════════════════
# GUIDE DE DÉPLOIEMENT PRODUCTION - DOCKER
# ═══════════════════════════════════════════════════════════════

## 📋 Table des Matières

1. [Prérequis](#prérequis)
2. [Configuration Initiale](#configuration-initiale)
3. [Certificats SSL](#certificats-ssl)
4. [Déploiement](#déploiement)
5. [Vérification](#vérification)
6. [Maintenance](#maintenance)
7. [Backup & Restore](#backup--restore)
8. [Monitoring](#monitoring)
9. [Troubleshooting](#troubleshooting)
10. [Sécurité](#sécurité)

---

## 1. Prérequis

### Serveur

- **OS:** Ubuntu 22.04 LTS (recommandé) ou Debian 11+
- **RAM:** Minimum 4GB (8GB recommandé)
- **CPU:** Minimum 2 cores (4 cores recommandé)
- **Stockage:** Minimum 50GB SSD
- **Docker:** Version 24.0+
- **Docker Compose:** Version 2.20+

### Installation Docker

```bash
# Installer Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Ajouter utilisateur au groupe docker
sudo usermod -aG docker $USER

# Installer Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Vérifier installations
docker --version
docker-compose --version
```

---

## 2. Configuration Initiale

### 2.1 Cloner le Projet

```bash
cd /opt
sudo git clone https://github.com/votre-org/racine-backend.git
cd racine-backend
```

### 2.2 Configurer .env.production

```bash
# Copier le template
cp .env.production.example .env.production

# Éditer avec vos valeurs
nano .env.production
```

**⚠️ CRITIQUE - Changer TOUTES ces valeurs:**

```bash
# Générer APP_KEY
php artisan key:generate --show

# Générer mots de passe forts (32+ caractères)
openssl rand -base64 32  # Pour DB_PASSWORD
openssl rand -base64 32  # Pour REDIS_PASSWORD
openssl rand -base64 32  # Pour DB_ROOT_PASSWORD
```

### 2.3 Créer Volumes Persistants

```bash
# Créer répertoires
sudo mkdir -p /var/lib/docker/volumes/racine_mysql_data
sudo mkdir -p /var/lib/docker/volumes/racine_redis_data
sudo mkdir -p /var/lib/docker/volumes/racine_app_storage
sudo mkdir -p /var/log/racine
sudo mkdir -p /var/backups/racine

# Permissions
sudo chown -R 999:999 /var/lib/docker/volumes/racine_mysql_data
sudo chown -R 999:999 /var/lib/docker/volumes/racine_redis_data
sudo chown -R 1000:1000 /var/lib/docker/volumes/racine_app_storage
sudo chown -R 1000:1000 /var/log/racine
sudo chown -R 1000:1000 /var/backups/racine
```

---

## 3. Certificats SSL

### 3.1 Avec Let's Encrypt (Recommandé)

```bash
# Installer Certbot
sudo apt update
sudo apt install certbot

# Obtenir certificat
sudo certbot certonly --standalone \
    -d racine-by-ganda.com \
    -d www.racine-by-ganda.com \
    --email ops@racine-by-ganda.com \
    --agree-tos

# Copier certificats
sudo mkdir -p docker/nginx/ssl
sudo cp /etc/letsencrypt/live/racine-by-ganda.com/fullchain.pem docker/nginx/ssl/
sudo cp /etc/letsencrypt/live/racine-by-ganda.com/privkey.pem docker/nginx/ssl/
sudo cp /etc/letsencrypt/live/racine-by-ganda.com/chain.pem docker/nginx/ssl/

# Permissions
sudo chmod 644 docker/nginx/ssl/*.pem
```

### 3.2 Renouvellement Automatique

```bash
# Créer script de renouvellement
sudo nano /etc/cron.daily/certbot-renew

#!/bin/bash
certbot renew --quiet --post-hook "docker-compose -f /opt/racine-backend/docker-compose.production.yml restart nginx"

# Rendre exécutable
sudo chmod +x /etc/cron.daily/certbot-renew
```

---

## 4. Déploiement

### 4.1 Build Images

```bash
# Build toutes les images
docker-compose -f docker-compose.production.yml build --no-cache

# Vérifier images
docker images | grep racine
```

### 4.2 Démarrage Initial

```bash
# Démarrer services
docker-compose -f docker-compose.production.yml up -d

# Vérifier statut
docker-compose -f docker-compose.production.yml ps

# Logs en temps réel
docker-compose -f docker-compose.production.yml logs -f
```

### 4.3 Initialisation Base de Données

```bash
# Attendre que MySQL soit prêt (30-60s)
docker-compose -f docker-compose.production.yml exec mysql mysqladmin ping -h localhost

# Exécuter migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# Seed données initiales (si nécessaire)
docker-compose -f docker-compose.production.yml exec app php artisan db:seed --force
```

### 4.4 Optimisations Laravel

```bash
# Cache configuration
docker-compose -f docker-compose.production.yml exec app php artisan config:cache

# Cache routes
docker-compose -f docker-compose.production.yml exec app php artisan route:cache

# Cache views
docker-compose -f docker-compose.production.yml exec app php artisan view:cache

# Cache events
docker-compose -f docker-compose.production.yml exec app php artisan event:cache

# Optimiser autoloader
docker-compose -f docker-compose.production.yml exec app composer dump-autoload --optimize
```

---

## 5. Vérification

### 5.1 Health Checks

```bash
# Vérifier tous les services
docker-compose -f docker-compose.production.yml ps

# Résultat attendu: tous "healthy"
```

### 5.2 Tests Connexion

```bash
# MySQL
docker-compose -f docker-compose.production.yml exec mysql mysql -u racine_app_user -p -e "SELECT 1;"

# Redis
docker-compose -f docker-compose.production.yml exec redis redis-cli -a "${REDIS_PASSWORD}" ping

# Application
curl -I https://racine-by-ganda.com/health

# Horizon
docker-compose -f docker-compose.production.yml exec horizon php artisan horizon:status
```

### 5.3 Logs

```bash
# Tous les services
docker-compose -f docker-compose.production.yml logs --tail=100

# Service spécifique
docker-compose -f docker-compose.production.yml logs -f app
docker-compose -f docker-compose.production.yml logs -f nginx
docker-compose -f docker-compose.production.yml logs -f mysql
```

---

## 6. Maintenance

### 6.1 Mise à Jour Application

```bash
# Pull dernières modifications
cd /opt/racine-backend
git pull origin main

# Rebuild images
docker-compose -f docker-compose.production.yml build --no-cache app horizon

# Redémarrer services
docker-compose -f docker-compose.production.yml up -d --force-recreate app horizon

# Migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# Clear caches
docker-compose -f docker-compose.production.yml exec app php artisan cache:clear
docker-compose -f docker-compose.production.yml exec app php artisan config:cache
docker-compose -f docker-compose.production.yml exec app php artisan route:cache
docker-compose -f docker-compose.production.yml exec app php artisan view:cache
```

### 6.2 Scaling

```bash
# Augmenter instances app
docker-compose -f docker-compose.production.yml up -d --scale app=4

# Vérifier
docker-compose -f docker-compose.production.yml ps app
```

### 6.3 Redémarrage Services

```bash
# Tous les services
docker-compose -f docker-compose.production.yml restart

# Service spécifique
docker-compose -f docker-compose.production.yml restart app
docker-compose -f docker-compose.production.yml restart nginx
```

---

## 7. Backup & Restore

### 7.1 Backup Manuel

```bash
# Exécuter backup
docker-compose -f docker-compose.production.yml exec backup /scripts/backup.sh

# Vérifier backup
ls -lh /var/backups/racine/
```

### 7.2 Restore

```bash
# Lister backups disponibles
ls -1 /var/backups/racine/

# Restaurer backup spécifique
docker-compose -f docker-compose.production.yml exec backup /scripts/restore.sh 20260213_020000

# Restaurer MySQL uniquement
docker-compose -f docker-compose.production.yml exec backup /scripts/restore.sh 20260213_020000 --mysql-only
```

### 7.3 Backup Automatique

Les backups sont automatiques (2h du matin par défaut).

**Modifier schedule:**

```bash
# Éditer .env.production
BACKUP_SCHEDULE="0 3 * * *"  # 3h du matin

# Redémarrer service backup
docker-compose -f docker-compose.production.yml restart backup
```

---

## 8. Monitoring

### 8.1 Ressources

```bash
# Stats temps réel
docker stats

# Utilisation disque
docker system df

# Logs système
journalctl -u docker -f
```

### 8.2 Application Metrics

```bash
# Prometheus metrics
curl https://racine-by-ganda.com/metrics

# Health endpoint
curl https://racine-by-ganda.com/health

# Horizon dashboard
# Accès: https://racine-by-ganda.com/horizon
```

### 8.3 Alertes

Configuré via Sentry (voir `.env.production`):

```env
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
```

---

## 9. Troubleshooting

### 9.1 Service ne démarre pas

```bash
# Vérifier logs
docker-compose -f docker-compose.production.yml logs [service]

# Vérifier configuration
docker-compose -f docker-compose.production.yml config

# Rebuild
docker-compose -f docker-compose.production.yml build --no-cache [service]
docker-compose -f docker-compose.production.yml up -d --force-recreate [service]
```

### 9.2 Problèmes MySQL

```bash
# Vérifier connexion
docker-compose -f docker-compose.production.yml exec mysql mysqladmin ping

# Logs MySQL
docker-compose -f docker-compose.production.yml logs mysql

# Accès MySQL shell
docker-compose -f docker-compose.production.yml exec mysql mysql -u root -p
```

### 9.3 Problèmes Redis

```bash
# Vérifier connexion
docker-compose -f docker-compose.production.yml exec redis redis-cli -a "${REDIS_PASSWORD}" ping

# Info Redis
docker-compose -f docker-compose.production.yml exec redis redis-cli -a "${REDIS_PASSWORD}" info

# Flush cache (ATTENTION!)
docker-compose -f docker-compose.production.yml exec redis redis-cli -a "${REDIS_PASSWORD}" FLUSHALL
```

### 9.4 Problèmes Nginx

```bash
# Tester configuration
docker-compose -f docker-compose.production.yml exec nginx nginx -t

# Reload configuration
docker-compose -f docker-compose.production.yml exec nginx nginx -s reload

# Vérifier certificats SSL
openssl s_client -connect racine-by-ganda.com:443 -servername racine-by-ganda.com
```

---

## 10. Sécurité

### 10.1 Firewall

```bash
# Installer UFW
sudo apt install ufw

# Règles de base
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Autoriser SSH
sudo ufw allow 22/tcp

# Autoriser HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Activer
sudo ufw enable

# Vérifier
sudo ufw status
```

### 10.2 Fail2Ban

```bash
# Installer
sudo apt install fail2ban

# Configurer pour Nginx
sudo nano /etc/fail2ban/jail.local

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
logpath = /var/log/racine/access.log
maxretry = 5
bantime = 3600

# Redémarrer
sudo systemctl restart fail2ban
```

### 10.3 Mises à Jour Sécurité

```bash
# Automatiques
sudo apt install unattended-upgrades
sudo dpkg-reconfigure --priority=low unattended-upgrades

# Manuelles
sudo apt update
sudo apt upgrade -y
```

---

## ✅ Checklist Déploiement

- [ ] Serveur configuré (Ubuntu 22.04+, 4GB+ RAM)
- [ ] Docker & Docker Compose installés
- [ ] `.env.production` configuré avec valeurs uniques
- [ ] APP_KEY généré
- [ ] Mots de passe forts (32+ caractères)
- [ ] Certificats SSL installés (Let's Encrypt)
- [ ] Volumes persistants créés
- [ ] Firewall configuré (UFW)
- [ ] Services démarrés et healthy
- [ ] Migrations exécutées
- [ ] Caches optimisés
- [ ] Backups automatiques configurés
- [ ] Monitoring activé (Sentry)
- [ ] Tests de charge effectués
- [ ] Plan de rollback défini
- [ ] Documentation équipe mise à jour

---

## 📞 Support

**Équipe DevOps:** ops@racine-by-ganda.com  
**Slack:** #production-support  
**Documentation:** https://docs.racine-by-ganda.com

---

**Version:** 1.0  
**Dernière mise à jour:** 13 février 2026
