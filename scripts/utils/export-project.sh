#!/bin/bash

# Script d'export du projet RACINE BY GANDA
# Usage: ./export-project.sh

PROJECT_NAME="racine-backend"
EXPORT_DIR="../${PROJECT_NAME}-export-$(date +%Y%m%d-%H%M%S)"
CURRENT_DIR=$(pwd)

echo "🚀 Début de l'exportation du projet ${PROJECT_NAME}..."
echo "📁 Dossier d'export : ${EXPORT_DIR}"

# Créer le dossier d'export
mkdir -p "${EXPORT_DIR}"
cd "${EXPORT_DIR}"

# Copier les fichiers essentiels
echo "📁 Copie des fichiers..."
rsync -av \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='bootstrap/cache/*' \
    --exclude='.env' \
    --exclude='.env.backup' \
    --exclude='*.log' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    --exclude='.phpunit.result.cache' \
    --exclude='.phpunit.cache' \
    "${CURRENT_DIR}/" .

# Créer le fichier .env.example si nécessaire
if [ -f "${CURRENT_DIR}/.env" ]; then
    echo "📝 Création de .env.example..."
    cp "${CURRENT_DIR}/.env" .env.example
    # Masquer les valeurs sensibles
    sed -i.bak 's/=.*/=/' .env.example 2>/dev/null || true
    rm -f .env.example.bak
fi

# Créer le fichier README d'export
cat > README-EXPORT.md << 'EOF'
# 📦 PROJET RACINE BY GANDA - EXPORT

## 📋 INSTRUCTIONS D'INSTALLATION

### 1. Installer les dépendances PHP
```bash
composer install --no-dev --optimize-autoloader
```

### 2. Installer les dépendances Node.js
```bash
npm install
```

### 3. Configurer l'environnement
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurer la base de données
Éditer le fichier `.env` et configurer :
- DB_CONNECTION
- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

### 5. Importer la base de données
```bash
php artisan migrate --force
# OU importer le dump SQL si disponible
mysql -u user -p database_name < database/dumps/racine-backend.sql
```

### 6. Créer les liens symboliques
```bash
php artisan storage:link
```

### 7. Compiler les assets
```bash
npm run build
# OU pour le développement
npm run dev
```

### 8. Optimiser l'application
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
EOF

# Retourner au dossier d'origine
cd "${CURRENT_DIR}"

# Créer l'archive
echo "📦 Création de l'archive..."
ARCHIVE_NAME="${PROJECT_NAME}-export-$(date +%Y%m%d-%H%M%S).tar.gz"
cd ..
tar -czf "${ARCHIVE_NAME}" "$(basename ${EXPORT_DIR})"

echo ""
echo "✅ Export terminé !"
echo "📁 Dossier : ${EXPORT_DIR}"
echo "📦 Archive : ${ARCHIVE_NAME}"
echo "📊 Taille : $(du -sh ${EXPORT_DIR} | cut -f1)"
echo ""
echo "💡 Pour exporter la base de données :"
echo "   mysqldump -u root -p racine_backend > database-export.sql"
echo ""


