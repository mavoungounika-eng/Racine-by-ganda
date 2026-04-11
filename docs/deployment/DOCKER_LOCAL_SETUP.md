# 🐳 Guide Docker - Test & Déploiement Local

## ✅ Configuration Actuelle

Distributions WSL installées:
- **Ubuntu** (Running) ← Distribution principale
- **docker-desktop** (Running) ← Docker Desktop

---

## 🧪 Tests Docker

### 1. Test Connexion Internet WSL
```powershell
wsl -- ping -c 3 google.com
```

### 2. Test Docker Version
```powershell
wsl -- docker --version
# Ou depuis Windows directement
docker --version
```

### 3. Test Docker Hello World
```powershell
wsl -- docker run hello-world
# Ou depuis Windows
docker run hello-world
```

### 4. Test Docker Compose
```powershell
docker-compose --version
```

---

## 🚀 Déploiement Local (Développement)

### Option 1: Docker Desktop (Recommandé pour Windows)

```powershell
# 1. Naviguer vers le projet
cd c:\laravel_projects\racine-backend

# 2. Vérifier fichiers Docker
dir docker-compose.production.yml
dir .env.production

# 3. Créer .env.production (si pas encore fait)
copy .env.production .env.production.local
notepad .env.production.local

# 4. Générer APP_KEY
php artisan key:generate --show

# 5. Démarrer services (mode développement)
docker-compose -f docker-compose.production.yml up -d

# 6. Vérifier statut
docker-compose -f docker-compose.production.yml ps

# 7. Logs
docker-compose -f docker-compose.production.yml logs -f
```

### Option 2: WSL Ubuntu

```powershell
# Entrer dans WSL
wsl

# Dans WSL:
cd /mnt/c/laravel_projects/racine-backend
docker-compose -f docker-compose.production.yml up -d
docker-compose -f docker-compose.production.yml ps
```

---

## ⚙️ Configuration Minimale Avant Démarrage

### 1. Créer .env.production.local

```bash
# Copier template
cp .env.production .env.production.local

# Éditer (minimum requis)
nano .env.production.local
```

**Variables CRITIQUES à changer:**
```env
# Application
APP_KEY=base64:GENERER_AVEC_php_artisan_key_generate

# Database
DB_PASSWORD=VotreMotDePasseMySQL32Caracteres

# Redis
REDIS_PASSWORD=VotreMotDePasseRedis32Caracteres

# Root MySQL (pour admin)
DB_ROOT_PASSWORD=VotreMotDePasseRootMySQL32
```

**Générer mots de passe forts:**
```powershell
# APP_KEY
php artisan key:generate --show

# Mots de passe (PowerShell)
[Convert]::ToBase64String((1..32 | ForEach-Object { Get-Random -Maximum 256 }))
```

### 2. Créer Répertoires Volumes (Windows)

```powershell
# Créer répertoires pour volumes Docker
mkdir C:\docker\volumes\racine_mysql_data
mkdir C:\docker\volumes\racine_redis_data
mkdir C:\docker\volumes\racine_app_storage
mkdir C:\docker\logs\racine
mkdir C:\docker\backups\racine
```

### 3. Modifier docker-compose.production.yml (Volumes Windows)

Éditer `docker-compose.production.yml` pour utiliser chemins Windows:

```yaml
volumes:
  mysql_data:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: C:\docker\volumes\racine_mysql_data  # ← Changé
  
  redis_data:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: C:\docker\volumes\racine_redis_data  # ← Changé
```

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

# Status
docker-compose -f docker-compose.production.yml ps

# Logs
docker-compose -f docker-compose.production.yml logs -f [service]
```

### Exécuter Commandes Laravel
```powershell
# Migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate

# Cache
docker-compose -f docker-compose.production.yml exec app php artisan config:cache

# Tinker
docker-compose -f docker-compose.production.yml exec app php artisan tinker
```

### Debug
```powershell
# Entrer dans container
docker-compose -f docker-compose.production.yml exec app sh

# Vérifier MySQL
docker-compose -f docker-compose.production.yml exec mysql mysql -u root -p

# Vérifier Redis
docker-compose -f docker-compose.production.yml exec redis redis-cli -a VotreMotDePasse ping
```

---

## 🐛 Troubleshooting

### Erreur: "Port already in use"
```powershell
# Vérifier ports utilisés
netstat -ano | findstr :80
netstat -ano | findstr :443
netstat -ano | findstr :3306

# Arrêter processus (remplacer PID)
taskkill /PID 1234 /F

# Ou changer ports dans .env
NGINX_HTTP_PORT=8080
NGINX_HTTPS_PORT=8443
```

### Erreur: "Cannot connect to Docker daemon"
```powershell
# Vérifier Docker Desktop lancé
# Ou redémarrer service
wsl --shutdown
# Relancer Docker Desktop
```

### Erreur: "Permission denied" (volumes)
```powershell
# Donner permissions (PowerShell Admin)
icacls C:\docker\volumes /grant Everyone:F /T
```

---

## 📋 Checklist Démarrage Local

- [ ] Docker Desktop installé et lancé
- [ ] WSL2 configuré
- [ ] `.env.production.local` créé avec valeurs uniques
- [ ] APP_KEY généré
- [ ] Mots de passe forts (DB, Redis)
- [ ] Répertoires volumes créés
- [ ] Ports 80, 443, 3306 disponibles
- [ ] `docker-compose.production.yml` adapté pour Windows
- [ ] Test `docker run hello-world` réussi

---

## 🎯 Prochaines Étapes

1. **Tester Docker:**
   ```powershell
   docker run hello-world
   ```

2. **Configurer .env.production.local:**
   ```powershell
   copy .env.production .env.production.local
   notepad .env.production.local
   ```

3. **Créer volumes:**
   ```powershell
   mkdir C:\docker\volumes\racine_mysql_data
   mkdir C:\docker\volumes\racine_redis_data
   ```

4. **Démarrer services:**
   ```powershell
   docker-compose -f docker-compose.production.yml up -d
   ```

5. **Vérifier:**
   ```powershell
   docker-compose -f docker-compose.production.yml ps
   curl http://localhost/health
   ```

---

**Prêt pour le test !** 🚀
