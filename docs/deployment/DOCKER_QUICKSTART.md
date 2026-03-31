# ═══════════════════════════════════════════════════════════════
# QUICK START - Production Docker
# ═══════════════════════════════════════════════════════════════

## 🚀 Déploiement en 5 Minutes

### 1. Prérequis
```bash
# Vérifier Docker
docker --version  # ≥ 24.0
docker-compose --version  # ≥ 2.20
```

### 2. Configuration
```bash
# Copier et éditer .env
cp .env.production.example .env.production
nano .env.production

# ⚠️ CHANGER OBLIGATOIREMENT:
# - APP_KEY (php artisan key:generate --show)
# - DB_PASSWORD (openssl rand -base64 32)
# - REDIS_PASSWORD (openssl rand -base64 32)
# - DB_ROOT_PASSWORD (openssl rand -base64 32)
```

### 3. SSL Certificates
```bash
# Let's Encrypt
sudo certbot certonly --standalone -d racine-by-ganda.com
sudo cp /etc/letsencrypt/live/racine-by-ganda.com/*.pem docker/nginx/ssl/
```

### 4. Volumes
```bash
# Créer et configurer permissions
sudo mkdir -p /var/lib/docker/volumes/racine_{mysql,redis,app}_data
sudo mkdir -p /var/{log,backups}/racine
sudo chown -R 999:999 /var/lib/docker/volumes/racine_mysql_data
sudo chown -R 999:999 /var/lib/docker/volumes/racine_redis_data
sudo chown -R 1000:1000 /var/lib/docker/volumes/racine_app_storage
```

### 5. Démarrage
```bash
# Build et start
docker-compose -f docker-compose.production.yml build
docker-compose -f docker-compose.production.yml up -d

# Migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# Optimisations
docker-compose -f docker-compose.production.yml exec app php artisan config:cache
docker-compose -f docker-compose.production.yml exec app php artisan route:cache
docker-compose -f docker-compose.production.yml exec app php artisan view:cache
```

### 6. Vérification
```bash
# Health checks
docker-compose -f docker-compose.production.yml ps

# Test application
curl -I https://racine-by-ganda.com/health
```

---

## 📁 Structure Fichiers

```
racine-backend/
├── .env.production              # Configuration production
├── docker-compose.production.yml # Orchestration services
├── .dockerignore                # Exclusions build
├── docker/
│   ├── app/
│   │   ├── Dockerfile           # Image Laravel
│   │   ├── php.ini              # Config PHP production
│   │   └── php-fpm-healthcheck  # Health check script
│   ├── nginx/
│   │   ├── nginx.conf           # Config principale
│   │   ├── conf.d/
│   │   │   └── default.conf     # Virtual host HTTPS
│   │   └── ssl/                 # Certificats SSL
│   ├── mysql/
│   │   └── conf.d/
│   │       └── custom.cnf       # Config MySQL
│   └── backup/
│       ├── Dockerfile           # Image backup
│       └── scripts/
│           ├── backup.sh        # Script backup
│           ├── restore.sh       # Script restore
│           └── cron.sh          # Scheduler
└── docs/
    └── DEPLOYMENT_PRODUCTION.md # Guide complet
```

---

## 🔧 Commandes Essentielles

### Gestion Services
```bash
# Démarrer
docker-compose -f docker-compose.production.yml up -d

# Arrêter
docker-compose -f docker-compose.production.yml down

# Redémarrer
docker-compose -f docker-compose.production.yml restart [service]

# Logs
docker-compose -f docker-compose.production.yml logs -f [service]

# Status
docker-compose -f docker-compose.production.yml ps
```

### Maintenance
```bash
# Mise à jour code
git pull origin main
docker-compose -f docker-compose.production.yml build app horizon
docker-compose -f docker-compose.production.yml up -d --force-recreate app horizon

# Migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate --force

# Clear cache
docker-compose -f docker-compose.production.yml exec app php artisan cache:clear
```

### Backup
```bash
# Manuel
docker-compose -f docker-compose.production.yml exec backup /scripts/backup.sh

# Restore
docker-compose -f docker-compose.production.yml exec backup /scripts/restore.sh 20260213_020000
```

### Scaling
```bash
# Augmenter instances app
docker-compose -f docker-compose.production.yml up -d --scale app=4
```

---

## 🔒 Sécurité Checklist

- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] APP_KEY unique généré
- [ ] DB_PASSWORD fort (32+ caractères)
- [ ] REDIS_PASSWORD fort (32+ caractères)
- [ ] HTTPS activé (certificats valides)
- [ ] Firewall configuré (UFW)
- [ ] User non-root tous services
- [ ] Volumes permissions correctes
- [ ] Secrets pas dans Git
- [ ] Rate limiting activé
- [ ] Monitoring configuré (Sentry)

---

## 📊 Monitoring

### Health Endpoints
```bash
# Application
curl https://racine-by-ganda.com/health

# Metrics Prometheus
curl https://racine-by-ganda.com/metrics

# Horizon
curl https://racine-by-ganda.com/horizon
```

### Logs
```bash
# Application
docker-compose -f docker-compose.production.yml logs -f app

# Nginx
docker-compose -f docker-compose.production.yml logs -f nginx

# MySQL
docker-compose -f docker-compose.production.yml logs -f mysql

# Tous
docker-compose -f docker-compose.production.yml logs -f
```

---

## 🆘 Troubleshooting

### Service ne démarre pas
```bash
# Vérifier logs
docker-compose -f docker-compose.production.yml logs [service]

# Rebuild
docker-compose -f docker-compose.production.yml build --no-cache [service]
docker-compose -f docker-compose.production.yml up -d --force-recreate [service]
```

### Problème MySQL
```bash
# Test connexion
docker-compose -f docker-compose.production.yml exec mysql mysqladmin ping

# Shell MySQL
docker-compose -f docker-compose.production.yml exec mysql mysql -u root -p
```

### Problème Redis
```bash
# Test connexion
docker-compose -f docker-compose.production.yml exec redis redis-cli -a "${REDIS_PASSWORD}" ping

# Info
docker-compose -f docker-compose.production.yml exec redis redis-cli -a "${REDIS_PASSWORD}" info
```

---

## 📞 Support

**Documentation complète:** `docs/DEPLOYMENT_PRODUCTION.md`  
**Équipe DevOps:** ops@racine-by-ganda.com  
**Slack:** #production-support

---

**Version:** 1.0  
**Dernière mise à jour:** 13 février 2026
