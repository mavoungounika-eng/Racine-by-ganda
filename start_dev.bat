@echo off
echo ==========================================
echo   Lancement de Racine By Ganda (WSL)
echo ==========================================
echo.
echo URL: http://127.0.0.1:8000
echo.
echo Appuyez sur Ctrl+C pour arreter le serveur.
echo.
wsl -d Ubuntu -- bash -c "cd /mnt/c/laravel_projects/racine-backend && php artisan serve"
pause
