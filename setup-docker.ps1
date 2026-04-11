# ═══════════════════════════════════════════════════════════════
# Script Automatisé - Docker + WSL + Racine by Ganda
# ═══════════════════════════════════════════════════════════════
# 
# Usage: .\setup-docker.ps1
# Prérequis: Docker Desktop installé, WSL2 activé
#
# ═══════════════════════════════════════════════════════════════

param(
    [switch]$SkipDNS,
    [switch]$SkipDocker,
    [switch]$Verbose
)

$ErrorActionPreference = "Stop"
$wslDistro = "Ubuntu"
$projectPath = "C:\laravel_projects\racine-backend"

# ───────────────────────────────────────────────────────────────
# Fonctions Utilitaires
# ───────────────────────────────────────────────────────────────

function Write-Step {
    param([string]$Message)
    Write-Host "`n🔹 $Message" -ForegroundColor Cyan
}

function Write-Success {
    param([string]$Message)
    Write-Host "✅ $Message" -ForegroundColor Green
}

function Write-Error-Custom {
    param([string]$Message)
    Write-Host "❌ $Message" -ForegroundColor Red
}

function Write-Warning-Custom {
    param([string]$Message)
    Write-Host "⚠️  $Message" -ForegroundColor Yellow
}

function Test-Command {
    param([string]$Command)
    try {
        Get-Command $Command -ErrorAction Stop | Out-Null
        return $true
    } catch {
        return $false
    }
}

# ───────────────────────────────────────────────────────────────
# Vérifications Prérequis
# ───────────────────────────────────────────────────────────────

Write-Host "`n╔═══════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║     SETUP DOCKER - RACINE BY GANDA                            ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════════════╝`n" -ForegroundColor Cyan

Write-Step "Vérification des prérequis..."

# Vérifier WSL
if (-not (Test-Command "wsl")) {
    Write-Error-Custom "WSL n'est pas installé. Installez WSL2 d'abord."
    exit 1
}
Write-Success "WSL installé"

# Vérifier Docker
if (-not (Test-Command "docker")) {
    Write-Error-Custom "Docker n'est pas installé. Installez Docker Desktop d'abord."
    exit 1
}
Write-Success "Docker installé"

# Vérifier distribution WSL
$wslList = wsl --list --quiet
if ($wslList -notcontains $wslDistro) {
    Write-Error-Custom "Distribution WSL '$wslDistro' non trouvée."
    Write-Host "Distributions disponibles:"
    wsl --list
    exit 1
}
Write-Success "Distribution WSL '$wslDistro' trouvée"

# Vérifier projet existe
if (-not (Test-Path $projectPath)) {
    Write-Error-Custom "Projet non trouvé: $projectPath"
    exit 1
}
Write-Success "Projet trouvé: $projectPath"

# ───────────────────────────────────────────────────────────────
# Configuration DNS WSL
# ───────────────────────────────────────────────────────────────

if (-not $SkipDNS) {
    Write-Step "Arrêt de WSL..."
    wsl --shutdown
    Start-Sleep -Seconds 2
    Write-Success "WSL arrêté"

    Write-Step "Configuration DNS WSL (mot de passe sudo requis)..."
    Write-Warning-Custom "Vous devrez entrer votre mot de passe sudo Ubuntu"
    
    try {
        wsl -d $wslDistro -- bash -c @"
sudo bash -c '
echo ""[network]"" > /etc/wsl.conf
echo ""generateResolvConf = false"" >> /etc/wsl.conf
rm -f /etc/resolv.conf
echo ""nameserver 8.8.8.8"" > /etc/resolv.conf
echo ""nameserver 8.8.4.4"" >> /etc/resolv.conf
'
"@
        Write-Success "DNS configuré (8.8.8.8, 8.8.4.4)"
    } catch {
        Write-Error-Custom "Échec configuration DNS: $_"
        exit 1
    }

    Write-Step "Redémarrage WSL..."
    wsl --shutdown
    Start-Sleep -Seconds 2
    wsl -d $wslDistro -- echo "WSL redémarré" | Out-Null
    Write-Success "WSL redémarré"

    Write-Step "Test connexion Internet WSL..."
    try {
        $pingResult = wsl -d $wslDistro -- ping -c 3 google.com 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Success "Connexion Internet OK"
        } else {
            Write-Error-Custom "Pas de connexion Internet dans WSL"
            Write-Host $pingResult
            exit 1
        }
    } catch {
        Write-Error-Custom "Test ping échoué: $_"
        exit 1
    }
} else {
    Write-Warning-Custom "Configuration DNS ignorée (--SkipDNS)"
}

# ───────────────────────────────────────────────────────────────
# Test Docker
# ───────────────────────────────────────────────────────────────

if (-not $SkipDocker) {
    Write-Step "Test Docker hello-world..."
    try {
        $dockerTest = docker run --rm hello-world 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Success "Docker fonctionne correctement"
        } else {
            Write-Error-Custom "Docker test échoué"
            Write-Host $dockerTest
            
            Write-Warning-Custom "Essayez de configurer DNS dans Docker Desktop:"
            Write-Host "  1. Ouvrir Docker Desktop"
            Write-Host "  2. Settings → Resources → Network"
            Write-Host "  3. DNS Server: 8.8.8.8"
            Write-Host "  4. Apply & Restart"
            
            $continue = Read-Host "`nVoulez-vous continuer quand même? (o/N)"
            if ($continue -ne "o") {
                exit 1
            }
        }
    } catch {
        Write-Error-Custom "Erreur Docker: $_"
        exit 1
    }
} else {
    Write-Warning-Custom "Test Docker ignoré (--SkipDocker)"
}

# ───────────────────────────────────────────────────────────────
# Vérification Configuration
# ───────────────────────────────────────────────────────────────

Write-Step "Vérification configuration projet..."

Set-Location $projectPath

# Vérifier .env.production existe
if (-not (Test-Path ".env.production")) {
    Write-Error-Custom "Fichier .env.production manquant"
    Write-Host "Créez-le avec: copy .env.example .env.production"
    exit 1
}
Write-Success ".env.production trouvé"

# Vérifier docker-compose.production.yml
if (-not (Test-Path "docker-compose.production.yml")) {
    Write-Error-Custom "Fichier docker-compose.production.yml manquant"
    exit 1
}
Write-Success "docker-compose.production.yml trouvé"

# Vérifier APP_KEY configuré
$envContent = Get-Content ".env.production" -Raw
if ($envContent -notmatch "APP_KEY=base64:[A-Za-z0-9+/=]{40,}") {
    Write-Warning-Custom "APP_KEY non configuré ou invalide"
    Write-Host "`nGénérez-le avec: php artisan key:generate --show"
    Write-Host "Puis ajoutez-le dans .env.production"
    
    $continue = Read-Host "`nVoulez-vous continuer quand même? (o/N)"
    if ($continue -ne "o") {
        exit 1
    }
}

# ───────────────────────────────────────────────────────────────
# Démarrage Services
# ───────────────────────────────────────────────────────────────

Write-Step "Démarrage des services Docker..."

try {
    docker-compose -f docker-compose.production.yml up -d
    
    if ($LASTEXITCODE -eq 0) {
        Write-Success "Services démarrés"
    } else {
        Write-Error-Custom "Échec démarrage services"
        exit 1
    }
} catch {
    Write-Error-Custom "Erreur démarrage: $_"
    exit 1
}

# ───────────────────────────────────────────────────────────────
# Vérification Statut
# ───────────────────────────────────────────────────────────────

Write-Step "Vérification statut des services..."
Start-Sleep -Seconds 5

docker-compose -f docker-compose.production.yml ps

# ───────────────────────────────────────────────────────────────
# Résumé
# ───────────────────────────────────────────────────────────────

Write-Host "`n╔═══════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║                    SETUP TERMINÉ                              ║" -ForegroundColor Green
Write-Host "╚═══════════════════════════════════════════════════════════════╝`n" -ForegroundColor Green

Write-Host "✅ WSL configuré avec DNS 8.8.8.8"
Write-Host "✅ Docker fonctionnel"
Write-Host "✅ Services démarrés"

Write-Host "`n📝 Commandes utiles:"
Write-Host "  • Logs:     docker-compose -f docker-compose.production.yml logs -f"
Write-Host "  • Status:   docker-compose -f docker-compose.production.yml ps"
Write-Host "  • Arrêter:  docker-compose -f docker-compose.production.yml down"
Write-Host "  • Restart:  docker-compose -f docker-compose.production.yml restart"

Write-Host "`n🌐 Accès application:"
Write-Host "  • HTTP:     http://localhost"
Write-Host "  • HTTPS:    https://localhost"
Write-Host "  • Health:   http://localhost/health"

$showLogs = Read-Host "`nAfficher les logs en temps réel? (o/N)"
if ($showLogs -eq "o") {
    Write-Host "`n📋 Logs (Ctrl+C pour arrêter)...`n" -ForegroundColor Cyan
    docker-compose -f docker-compose.production.yml logs -f
}
