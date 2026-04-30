# 📦 GUIDE COMPLET - EXPORTATION DU PROJET RACINE BY GANDA

**Date :** {{ date('Y-m-d') }}  
**Projet :** RACINE BY GANDA - Backend Laravel

---

## 📋 TABLE DES MATIÈRES

1. [Méthode 1 : Export complet (Recommandé)](#méthode-1--export-complet-recommandé)
2. [Méthode 2 : Export pour déploiement](#méthode-2--export-pour-déploiement)
3. [Méthode 3 : Export minimal (Code uniquement)](#méthode-3--export-minimal-code-uniquement)
4. [Export de la base de données](#export-de-la-base-de-données)
5. [Vérification post-export](#vérification-post-export)

---

## 🎯 MÉTHODE 1 : EXPORT COMPLET (Recommandé)

### Étape 1 : Préparer l'environnement

```bash
# 1. Nettoyer le cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 2. Optimiser l'application
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Étape 2 : Créer le package d'export

```bash
# Créer un dossier d'export
mkdir -p ../racine-backend-export
cd ../racine-backend-export

# Copier le projet (sans node_modules et vendor)
rsync -av --exclude='node_modules' \
         --exclude='vendor' \
         --exclude='.git' \
         --exclude='storage/logs/*' \
         --exclude='storage/framework/cache/*' \
         --exclude='storage/framework/sessions/*' \
         --exclude='storage/framework/views/*' \
         --exclude='.env' \
         ../racine-backend/ .
```

### Étape 3 : Créer l'archive

```bash
# Depuis le dossier parent
cd ..
tar -czf racine-backend-export-$(date +%Y%m%d).tar.gz racine-backend-export/
# OU pour Windows (PowerShell)
Compress-Archive -Path racine-backend-export -DestinationPath racine-backend-export-$(Get-Date -Format "yyyyMMdd").zip
```

---

## 🚀 MÉTHODE 2 : EXPORT POUR DÉPLOIEMENT

### Étape 1 : Script d'export automatisé

Créez un fichier `export-project.sh` (Linux/Mac) ou `export-project.ps1` (Windows) :

**export-project.sh** (Linux/Mac) :
```bash
#!/bin/bash

# Configuration
PROJECT_NAME="racine-backend"
EXPORT_DIR="../${PROJECT_NAME}-export-$(date +%Y%m%d-%H%M%S)"
CURRENT_DIR=$(pwd)

echo "🚀 Début de l'exportation du projet ${PROJECT_NAME}..."

# Créer le dossier d'export
mkdir -p "${EXPORT_DIR}"
cd "${EXPORT_DIR}"

# Copier les fichiers essentiels
echo "📁 Copie des fichiers..."
rsync -av \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='.env' \
    --exclude='.env.backup' \
    --exclude='*.log' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    "${CURRENT_DIR}/" .

# Créer le fichier .env.example si nécessaire
if [ ! -f .env.example ]; then
    echo "📝 Création de .env.example..."
    cp .env .env.example 2>/dev/null || echo "⚠️  .env non trouvé"
    # Masquer les valeurs sensibles
    sed -i.bak 's/=.*/=/' .env.example 2>/dev/null || true
fi

# Créer le fichier README d'export
cat > README-EXPORT.md << EOF
# 📦 PROJET RACINE BY GANDA - EXPORT

**Date d'export :** $(date)
**Version :** $(git describe --tags 2>/dev/null || echo "Non versionné")

## 📋 INSTRUCTIONS D'INSTALLATION

### 1. Installer les dépendances PHP
\`\`\`bash
composer install --no-dev --optimize-autoloader
\`\`\`

### 2. Installer les dépendances Node.js
\`\`\`bash
npm install
\`\`\`

### 3. Configurer l'environnement
\`\`\`bash
cp .env.example .env
php artisan key:generate
\`\`\`

### 4. Configurer la base de données
Éditer le fichier \`.env\` et configurer :
- DB_CONNECTION
- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

### 5. Importer la base de données
\`\`\`bash
php artisan migrate --force
# OU importer le dump SQL si disponible
mysql -u user -p database_name < database/dumps/racine-backend.sql
\`\`\`

### 6. Créer les liens symboliques
\`\`\`bash
php artisan storage:link
\`\`\`

### 7. Compiler les assets
\`\`\`bash
npm run build
# OU pour le développement
npm run dev
\`\`\`

### 8. Optimiser l'application
\`\`\`bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
\`\`\`

## 📝 NOTES

- Le fichier \`.env\` n'est pas inclus pour des raisons de sécurité
- Les dossiers \`node_modules\` et \`vendor\` doivent être régénérés
- Les logs et caches ont été exclus de l'export
EOF

echo "✅ Export terminé dans : ${EXPORT_DIR}"
echo "📦 Taille du dossier : $(du -sh . | cut -f1)"
```

**export-project.ps1** (Windows PowerShell) :
```powershell
# Configuration
$ProjectName = "racine-backend"
$ExportDir = "..\$ProjectName-export-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
$CurrentDir = Get-Location

Write-Host "🚀 Début de l'exportation du projet $ProjectName..." -ForegroundColor Green

# Créer le dossier d'export
New-Item -ItemType Directory -Path $ExportDir -Force | Out-Null
Set-Location $ExportDir

# Copier les fichiers (exclure node_modules, vendor, .git)
Write-Host "📁 Copie des fichiers..." -ForegroundColor Yellow
Get-ChildItem -Path $CurrentDir -Recurse | Where-Object {
    $_.FullName -notmatch 'node_modules|vendor|\.git|storage\\logs|storage\\framework\\cache|storage\\framework\\sessions|storage\\framework\\views|\.env$|\.env\.backup|\.log$|\.DS_Store|Thumbs\.db'
} | Copy-Item -Destination {
    $_.FullName.Replace($CurrentDir, $ExportDir)
} -Recurse -Force

# Créer .env.example
if (Test-Path "$CurrentDir\.env") {
    Write-Host "📝 Création de .env.example..." -ForegroundColor Yellow
    Copy-Item "$CurrentDir\.env" "$ExportDir\.env.example"
    # Masquer les valeurs sensibles
    (Get-Content "$ExportDir\.env.example") | ForEach-Object {
        if ($_ -match '^([^=]+)=(.+)$') {
            "$($matches[1])="
        } else {
            $_
        }
    } | Set-Content "$ExportDir\.env.example"
}

Write-Host "✅ Export terminé dans : $ExportDir" -ForegroundColor Green
```

### Étape 2 : Exécuter le script

**Linux/Mac :**
```bash
chmod +x export-project.sh
./export-project.sh
```

**Windows :**
```powershell
.\export-project.ps1
```

---

## 📦 MÉTHODE 3 : EXPORT MINIMAL (Code uniquement)

### Pour partager uniquement le code source

```bash
# Créer une archive avec uniquement le code source
tar -czf racine-backend-code-only.tar.gz \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    --exclude='storage' \
    --exclude='bootstrap/cache' \
    --exclude='.env' \
    --exclude='*.log' \
    app/ \
    config/ \
    database/ \
    public/ \
    resources/ \
    routes/ \
    modules/ \
    composer.json \
    composer.lock \
    package.json \
    package-lock.json \
    artisan \
    phpunit.xml \
    vite.config.js
```

---

## 🗄️ EXPORT DE LA BASE DE DONNÉES

### Méthode 1 : Export SQL avec mysqldump

```bash
# Export complet
mysqldump -u root -p racine_backend > database/dumps/racine-backend-$(date +%Y%m%d).sql

# Export avec structure et données
mysqldump -u root -p --single-transaction --routines --triggers racine_backend > database/dumps/racine-backend-full.sql

# Export uniquement la structure
mysqldump -u root -p --no-data racine_backend > database/dumps/racine-backend-structure.sql

# Export uniquement les données
mysqldump -u root -p --no-create-info racine_backend > database/dumps/racine-backend-data.sql
```

### Méthode 2 : Export via Laravel Artisan

```bash
# Créer un dump avec les migrations
php artisan migrate:dump > database/dumps/migrations-$(date +%Y%m%d).sql

# Exporter les données spécifiques
php artisan db:export --table=users,products,orders > database/dumps/data-export.sql
```

### Méthode 3 : Export via phpMyAdmin

1. Ouvrir phpMyAdmin
2. Sélectionner la base de données `racine_backend`
3. Cliquer sur "Exporter"
4. Choisir "Méthode d'exportation" : SQL
5. Cliquer sur "Exécuter"

---

## 📋 CHECKLIST D'EXPORT

### Fichiers à inclure ✅

- [x] `app/` - Code applicatif
- [x] `config/` - Configuration
- [x] `database/` - Migrations, seeders, factories
- [x] `public/` - Assets publics
- [x] `resources/` - Vues, assets sources
- [x] `routes/` - Routes
- [x] `modules/` - Modules personnalisés
- [x] `composer.json` et `composer.lock`
- [x] `package.json` et `package-lock.json`
- [x] `artisan` - CLI Laravel
- [x] `phpunit.xml` - Configuration tests
- [x] `vite.config.js` - Configuration Vite
- [x] `.env.example` - Exemple de configuration
- [x] `README.md` - Documentation

### Fichiers à exclure ❌

- [ ] `vendor/` - À régénérer avec `composer install`
- [ ] `node_modules/` - À régénérer avec `npm install`
- [ ] `.git/` - Historique Git (optionnel)
- [ ] `.env` - Configuration sensible
- [ ] `storage/logs/*` - Logs
- [ ] `storage/framework/cache/*` - Cache
- [ ] `storage/framework/sessions/*` - Sessions
- [ ] `storage/framework/views/*` - Vues compilées
- [ ] `bootstrap/cache/*` - Cache bootstrap
- [ ] `*.log` - Fichiers de log
- [ ] `.DS_Store` - Fichiers système Mac
- [ ] `Thumbs.db` - Fichiers système Windows

---

## 🔍 VÉRIFICATION POST-EXPORT

### 1. Vérifier la structure

```bash
cd racine-backend-export
ls -la

# Vérifier les dossiers essentiels
test -d app && echo "✅ app/" || echo "❌ app/ manquant"
test -d config && echo "✅ config/" || echo "❌ config/ manquant"
test -d database && echo "✅ database/" || echo "❌ database/ manquant"
test -d resources && echo "✅ resources/" || echo "❌ resources/ manquant"
test -d routes && echo "✅ routes/" || echo "❌ routes/ manquant"
test -f composer.json && echo "✅ composer.json" || echo "❌ composer.json manquant"
test -f artisan && echo "✅ artisan" || echo "❌ artisan manquant"
```

### 2. Vérifier la taille

```bash
# Taille totale
du -sh racine-backend-export

# Taille par dossier
du -sh racine-backend-export/*/
```

### 3. Créer l'archive finale

```bash
# Archive tar.gz (Linux/Mac)
tar -czf racine-backend-export-$(date +%Y%m%d).tar.gz racine-backend-export/

# Archive zip (Windows/Universal)
zip -r racine-backend-export-$(date +%Y%m%d).zip racine-backend-export/
```

---

## 📤 OPTIONS D'ENVOI

### 1. Partage local
- Copier l'archive sur clé USB
- Copier sur disque externe
- Partager via réseau local

### 2. Partage cloud
- **Google Drive** : Upload de l'archive
- **Dropbox** : Upload de l'archive
- **OneDrive** : Upload de l'archive
- **WeTransfer** : Envoi temporaire (7 jours)

### 3. Partage Git (Recommandé pour le code)

```bash
# Initialiser un nouveau repo (sans historique)
cd racine-backend-export
git init
git add .
git commit -m "Export initial du projet RACINE BY GANDA"
git remote add origin https://github.com/votre-username/racine-backend.git
git push -u origin main
```

### 4. Serveur FTP/SFTP

```bash
# Upload via SFTP
sftp user@server.com
put racine-backend-export-*.tar.gz /path/to/destination/

# Upload via SCP
scp racine-backend-export-*.tar.gz user@server.com:/path/to/destination/
```

---

## 🔐 SÉCURITÉ

### ⚠️ IMPORTANT : Ne jamais inclure

- ❌ Fichier `.env` avec les vraies valeurs
- ❌ Clés API réelles
- ❌ Mots de passe
- ❌ Certificats SSL privés
- ❌ Tokens d'accès

### ✅ Toujours inclure

- ✅ `.env.example` avec des valeurs d'exemple
- ✅ Documentation de configuration
- ✅ Instructions d'installation

---

## 📝 FICHIER .env.example RECOMMANDÉ

Créez un fichier `.env.example` avec cette structure :

```env
APP_NAME="RACINE BY GANDA"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=racine_backend
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@racine.cm"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## ✅ RÉSUMÉ RAPIDE

### Export rapide (1 commande)

```bash
# Linux/Mac
tar -czf ../racine-export.tar.gz --exclude='node_modules' --exclude='vendor' --exclude='.git' --exclude='storage/logs' --exclude='.env' .

# Windows (PowerShell)
Compress-Archive -Path * -DestinationPath ../racine-export.zip -Exclude node_modules,vendor,.git,storage\logs,.env
```

### Export base de données

```bash
mysqldump -u root -p racine_backend > database-export.sql
```

---

**Fin du guide**


