#!/usr/bin/env bash
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

CYAN='\033[0;36m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; RESET='\033[0m'

echo ""
echo -e "${CYAN}🖥️  RACINE POS — Démarrage environnement dev${RESET}"
echo "============================================================"

[ ! -d "node_modules" ] && echo -e "${YELLOW}  ⚠  npm install en cours...${RESET}" && npm install
echo -e "${GREEN}  ✓ node_modules OK${RESET}"

if [ ! -f ".env" ]; then
  echo "VITE_API_URL=http://127.0.0.1:8000" > .env
  echo -e "${GREEN}  ✓ .env créé (VITE_API_URL=http://127.0.0.1:8000)${RESET}"
else
  echo -e "${GREEN}  ✓ .env OK${RESET}"
fi

! node -e "require('electron')" 2>/dev/null && npm install

API_URL=$(grep 'VITE_API_URL' .env 2>/dev/null | cut -d= -f2 | tr -d ' \r' || echo "http://127.0.0.1:8000")
echo ""
echo "============================================================"
echo -e "  Backend Laravel  → ${CYAN}${API_URL}${RESET}  (lancer séparément avec ./start.sh)"
echo -e "  Vite renderer    → ${CYAN}http://localhost:8080${RESET}"
echo -e "  Electron         → charge localhost:8080"
echo "============================================================"
echo -e "  ${YELLOW}Ctrl+C pour tout stopper${RESET}"
echo ""

npx concurrently --names "Vite,Electron" --prefix-colors "blue,magenta" "vite" "electron ."
