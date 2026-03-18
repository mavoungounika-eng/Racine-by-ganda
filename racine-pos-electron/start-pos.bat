@echo off
setlocal
cd /d "%~dp0"

echo [RACINE POS] Starting Vite + Electron...
call npm run electron:dev

if errorlevel 1 (
  echo.
  echo [RACINE POS] Launch failed.
  echo - Verify Node.js/npm are installed
  echo - Run npm install in this folder
  echo - Ensure backend is running on http://127.0.0.1:8000
  pause
)
