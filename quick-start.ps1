# ═══════════════════════════════════════════════════════════════
# Script Simple - Démarrage Rapide Docker
# ═══════════════════════════════════════════════════════════════

$wslDistro = "Ubuntu"

Write-Host "`n🔹 Arrêt de WSL..." -ForegroundColor Cyan
wsl --shutdown

Write-Host "`n🔹 Configuration DNS WSL (mot de passe sudo requis)..." -ForegroundColor Cyan
wsl -d $wslDistro -- bash -c "
sudo bash -c '
echo ""[network]"" > /etc/wsl.conf
echo ""generateResolvConf = false"" >> /etc/wsl.conf
rm -f /etc/resolv.conf
echo ""nameserver 8.8.8.8"" > /etc/resolv.conf
echo ""nameserver 8.8.4.4"" >> /etc/resolv.conf
echo ""DNS configuré avec succès !""
'
"

Write-Host "`n🔹 Redémarrage WSL..." -ForegroundColor Cyan
wsl --shutdown
Start-Sleep -Seconds 2

Write-Host "`n🔹 Test de connexion Internet dans WSL..." -ForegroundColor Cyan
wsl -d $wslDistro -- ping -c 3 google.com

Write-Host "`n🔹 Test Docker hello-world..." -ForegroundColor Cyan
docker run --rm hello-world

Write-Host "`n🔹 Démarrage des services Racine by Ganda..." -ForegroundColor Cyan
cd C:\laravel_projects\racine-backend
docker-compose -f docker-compose.production.yml up -d

Write-Host "`n🔹 Vérification du statut des services..." -ForegroundColor Cyan
docker-compose -f docker-compose.production.yml ps

Write-Host "`n✅ Services démarrés !`n" -ForegroundColor Green
Write-Host "📋 Commandes utiles:"
Write-Host "  • Logs:    docker-compose -f docker-compose.production.yml logs -f"
Write-Host "  • Arrêter: docker-compose -f docker-compose.production.yml down"

$showLogs = Read-Host "`nAfficher les logs? (o/N)"
if ($showLogs -eq "o") {
    docker-compose -f docker-compose.production.yml logs -f
}
