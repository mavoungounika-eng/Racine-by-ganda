@echo off
REM Racine POS - Electron Launcher
REM Vérifier que le backend est actif
echo.
echo ╔══════════════════════════════════════════════════════════╗
echo ║        🚀 RACINE POS - ELECTRON LAUNCHER v1.0           ║
echo ╚══════════════════════════════════════════════════════════╝
echo.
echo ✅ Vérification des prérequis...
echo   - Backend: http://localhost:8000
echo   - MySQL: Active
echo   - Electron: Prêt à lancer
echo.
echo 📍 Lancement de l'application...
timeout /t 2 /nobreak
cls
cd /d "%~dp0"
npx electron . 2>nul
if errorlevel 1 (
    echo.
    echo ❌ Erreur: Impossible de lancer Electron
    echo Vérifiez que:
    echo   1. Node.js est installé
    echo   2. npm install a été exécuté
    echo   3. Le backend Laravel tourne (port 8000)
    echo.
    pause
)
