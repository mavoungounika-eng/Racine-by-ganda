# ═══════════════════════════════════════════════════════════════
# Guide de Test - Déploiement Docker Local
# ═══════════════════════════════════════════════════════════════

## 🎯 Objectif
Tester le déploiement Docker en local avant production.

---

## ⚡ Démarrage Rapide (3 étapes)

### Étape 1: Préparer .env.production

```powershell
# Copier le fichier
copy .env.production .env.production.local

# Éditer (minimum requis)
notepad .env.production.local
```

**Variables CRITIQUES à modifier:**
```env
# Générer APP_KEY
APP_KEY=base64:VOTRE_CLE_GENEREE_ICI

# Mots de passe (32+ caractères)
DB_PASSWORD=ChangezMoiAvecMotDePasseFort32Caracteres
REDIS_PASSWORD=ChangezMoiAvecMotDePasseFort32Caracteres
DB_ROOT_PASSWORD=ChangezMoiAvecMotDePasseFort32Caracteres
```

**Générer APP_KEY:**
```powershell
php artisan key:generate --show
```

**Générer mots de passe forts:**
```powershell
# PowerShell
-join ((48..57) + (65..90) + (97..122) | Get-Random -Count 32 | ForEach-Object {[char]$_})
```

### Étape 2: Lancer le script

```powershell
# Option 1: Setup complet (recommandé première fois)
.\setup-docker.ps1

# Option 2: Démarrage rapide
.\quick-start.ps1
```

### Étape 3: Vérifier

```powershell
# Status services
docker-compose -f docker-compose.production.yml ps

# Tous doivent être "Up" et "healthy"
```

---

## 📋 Checklist Avant Démarrage

- [ ] Docker Desktop installé et **lancé**
- [ ] WSL2 activé
- [ ] `.env.production.local` créé
- [ ] `APP_KEY` généré et ajouté
- [ ] Mots de passe forts configurés
- [ ] Ports 80, 443, 3306 disponibles

---

## 🧪 Tests Post-Déploiement

### 1. Vérifier Services

```powershell
# Status
docker-compose -f docker-compose.production.yml ps

# Résultat attendu:
# NAME                STATE    HEALTH
# mysql               Up       healthy
# redis               Up       healthy
# app                 Up       healthy
# horizon             Up       healthy
# nginx               Up       healthy
# backup              Up       healthy
```

### 2. Tester Endpoints

```powershell
# Health check
curl http://localhost/health

# Page d'accueil
curl http://localhost

# Ou ouvrir dans navigateur
start http://localhost
```

### 3. Vérifier Logs

```powershell
# Tous les services
docker-compose -f docker-compose.production.yml logs --tail=50

# Service spécifique
docker-compose -f docker-compose.production.yml logs -f app
docker-compose -f docker-compose.production.yml logs -f nginx
```

### 4. Tester MySQL

```powershell
# Connexion MySQL
docker-compose -f docker-compose.production.yml exec mysql mysql -u racine_app_user -p

# Mot de passe: celui configuré dans DB_PASSWORD
```

### 5. Tester Redis

```powershell
# Ping Redis
docker-compose -f docker-compose.production.yml exec redis redis-cli -a VOTRE_REDIS_PASSWORD ping

# Résultat attendu: PONG
```

### 6. Exécuter Migrations

```powershell
# Migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate

# Seed (optionnel)
docker-compose -f docker-compose.production.yml exec app php artisan db:seed
```

### 7. Optimiser Laravel

```powershell
# Cache configuration
docker-compose -f docker-compose.production.yml exec app php artisan config:cache

# Cache routes
docker-compose -f docker-compose.production.yml exec app php artisan route:cache

# Cache views
docker-compose -f docker-compose.production.yml exec app php artisan view:cache
```

---

## 🐛 Troubleshooting

### Problème 1: "Port 80 already in use"

```powershell
# Vérifier processus
netstat -ano | findstr :80

# Arrêter IIS (si installé)
iisreset /stop

# Ou changer port dans docker-compose.production.yml
ports:
  - "8080:80"  # HTTP sur port 8080
```

### Problème 2: "Cannot connect to Docker daemon"

```powershell
# Vérifier Docker Desktop lancé
# Ou redémarrer
wsl --shutdown
# Relancer Docker Desktop
```

### Problème 3: Services "unhealthy"

```powershell
# Vérifier logs
docker-compose -f docker-compose.production.yml logs [service]

# Rebuild
docker-compose -f docker-compose.production.yml build --no-cache [service]
docker-compose -f docker-compose.production.yml up -d --force-recreate [service]
```

### Problème 4: "DNS resolution failed"

```powershell
# Reconfigurer DNS
.\setup-docker.ps1

# Ou manuellement dans Docker Desktop:
# Settings → Resources → Network → DNS: 8.8.8.8
```

### Problème 5: Erreur MySQL "Access denied"

```powershell
# Vérifier mot de passe dans .env.production.local
# Recréer container MySQL
docker-compose -f docker-compose.production.yml down -v
docker-compose -f docker-compose.production.yml up -d mysql
```

---

## 📊 Métriques de Succès

### ✅ Déploiement Réussi Si:

- [ ] Tous services "Up" et "healthy"
- [ ] `curl http://localhost/health` retourne 200
- [ ] MySQL accessible
- [ ] Redis répond PONG
- [ ] Migrations exécutées sans erreur
- [ ] Logs sans erreurs critiques
- [ ] Application accessible dans navigateur

---

## 🔧 Commandes Utiles

### Gestion Services

```powershell
# Démarrer
docker-compose -f docker-compose.production.yml up -d

# Arrêter
docker-compose -f docker-compose.production.yml down

# Redémarrer
docker-compose -f docker-compose.production.yml restart

# Rebuild
docker-compose -f docker-compose.production.yml build --no-cache
docker-compose -f docker-compose.production.yml up -d --force-recreate
```

### Debug

```powershell
# Entrer dans container app
docker-compose -f docker-compose.production.yml exec app sh

# Voir processus
docker-compose -f docker-compose.production.yml top

# Stats ressources
docker stats

# Nettoyer
docker system prune -a
```

### Laravel

```powershell
# Artisan
docker-compose -f docker-compose.production.yml exec app php artisan [command]

# Tinker
docker-compose -f docker-compose.production.yml exec app php artisan tinker

# Queue work (manuel)
docker-compose -f docker-compose.production.yml exec app php artisan queue:work

# Horizon status
docker-compose -f docker-compose.production.yml exec horizon php artisan horizon:status
```

---

## 🎯 Prochaines Étapes

### Après Test Local Réussi:

1. **Documenter problèmes rencontrés**
2. **Ajuster configuration si nécessaire**
3. **Tester backup/restore**
4. **Préparer déploiement production**
5. **Compléter TODOs Phase 1** (CAPTCHA, KPIs)

---

## 📞 Support

**Problème persistant?**
- Vérifier logs: `docker-compose -f docker-compose.production.yml logs`
- Consulter: `DOCKER_DNS_FIX.md`
- Consulter: `DOCKER_LOCAL_SETUP.md`

---

**Prêt pour le test !** 🚀

**Commande pour démarrer:**
```powershell
.\setup-docker.ps1
```
