#!/bin/bash
# ============================================================
# RACINE BY GANDA — Script de démarrage dev
# Usage : ./start.sh
# ============================================================

set -e
cd "$(dirname "$0")"

echo ""
echo "🌿 RACINE BY GANDA — Démarrage environnement dev"
echo "=================================================="

# 1. Nettoyage fichiers stales
echo "► Nettoyage du cache..."
rm -f public/hot
php artisan config:clear --quiet
php artisan view:clear --quiet
php artisan cache:clear --quiet
echo "  ✓ Cache nettoyé"

# 2. Vérification .env
if [ ! -f .env ]; then
    echo "  ✗ Fichier .env manquant ! Copie .env.example..."
    cp .env.example .env
    php artisan key:generate
fi
echo "  ✓ .env OK"

# 3. Vérification composer
if [ ! -d vendor ]; then
    echo "► Installation des dépendances PHP..."
    composer install --no-interaction --quiet
fi
echo "  ✓ Vendor OK"

# 4. Vérification node_modules
if [ ! -d node_modules ]; then
    echo "► Installation des dépendances Node..."
    npm install --silent
fi
echo "  ✓ Node modules OK"

# 5. Vérification build Vite (si pas de dev server, au moins le build doit exister)
if [ ! -f public/build/manifest.json ]; then
    echo "► Aucun build Vite trouvé — build en cours..."
    npm run build
    echo "  ✓ Build Vite créé"
fi

# 5b. Vérification migrations en attente (warning uniquement)
PENDING=$(php artisan migrate:status --no-ansi 2>/dev/null | grep -c "Pending" || true)
if [ "${PENDING:-0}" -gt 0 ]; then
    echo "  ⚠ $PENDING migration(s) en attente ! Lancer: php artisan migrate"
fi

echo ""
echo "=================================================="
echo "  Laravel  → http://127.0.0.1:8000"
echo "  Vite     → build --watch (assets servis par Laravel)"
echo "  CSS      → public/build/assets/app-*.css"
echo "=================================================="
echo ""

# 6. Lancement des serveurs (Ctrl+C pour tout arrêter)
npm run start
