# Scripts PowerShell - Docker Setup

## 📁 Scripts Disponibles

### 1. `setup-docker.ps1` (Complet avec vérifications)

**Usage:**
```powershell
.\setup-docker.ps1
```

**Options:**
```powershell
.\setup-docker.ps1 -SkipDNS      # Ignorer configuration DNS
.\setup-docker.ps1 -SkipDocker   # Ignorer test Docker
.\setup-docker.ps1 -Verbose      # Mode verbeux
```

**Fonctionnalités:**
- ✅ Vérification prérequis (WSL, Docker, projet)
- ✅ Configuration DNS WSL automatique
- ✅ Test connexion Internet
- ✅ Test Docker hello-world
- ✅ Vérification .env.production
- ✅ Démarrage services
- ✅ Gestion erreurs complète
- ✅ Messages colorés

---

### 2. `quick-start.ps1` (Simple et rapide)

**Usage:**
```powershell
.\quick-start.ps1
```

**Fonctionnalités:**
- ✅ Configuration DNS WSL
- ✅ Test Docker
- ✅ Démarrage services
- ✅ Affichage logs (optionnel)

---

## 🚀 Démarrage Rapide

### Première Fois

```powershell
# 1. Configurer .env.production
copy .env.production .env.production.local
notepad .env.production.local

# 2. Générer APP_KEY
php artisan key:generate --show

# 3. Lancer setup complet
.\setup-docker.ps1
```

### Démarrages Suivants

```powershell
# Démarrage rapide
.\quick-start.ps1

# Ou manuel
docker-compose -f docker-compose.production.yml up -d
```

---

## 🔧 Commandes Manuelles

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

### Debug

```powershell
# Entrer dans container
docker-compose -f docker-compose.production.yml exec app sh

# Migrations
docker-compose -f docker-compose.production.yml exec app php artisan migrate

# Cache
docker-compose -f docker-compose.production.yml exec app php artisan config:cache
```

---

## 🐛 Troubleshooting

### Erreur DNS

```powershell
# Reconfigurer DNS
.\setup-docker.ps1

# Ou manuellement
wsl --shutdown
wsl -- sudo bash -c "echo 'nameserver 8.8.8.8' > /etc/resolv.conf"
```

### Erreur "Port already in use"

```powershell
# Vérifier ports
netstat -ano | findstr :80
netstat -ano | findstr :443

# Arrêter services
docker-compose -f docker-compose.production.yml down
```

### Services ne démarrent pas

```powershell
# Rebuild
docker-compose -f docker-compose.production.yml build --no-cache
docker-compose -f docker-compose.production.yml up -d --force-recreate
```

---

## ✅ Checklist

- [ ] Docker Desktop installé et lancé
- [ ] WSL2 activé
- [ ] Distribution Ubuntu installée
- [ ] `.env.production` configuré
- [ ] APP_KEY généré
- [ ] Mots de passe forts (DB, Redis)
- [ ] Script exécuté avec succès
- [ ] Services "healthy"

---

**Prêt à déployer !** 🚀
