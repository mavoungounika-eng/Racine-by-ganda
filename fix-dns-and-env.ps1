# Script Fix DNS Docker + Preparation .env
# Encodage: UTF-8 sans BOM

$ErrorActionPreference = "Stop"

Write-Host "`n================================================================" -ForegroundColor Cyan
Write-Host "     FIX DNS DOCKER + PREPARATION ENV" -ForegroundColor Cyan
Write-Host "================================================================`n" -ForegroundColor Cyan

# PARTIE 1: FIX DNS DOCKER

Write-Host "PARTIE 1: Configuration DNS Docker`n" -ForegroundColor Yellow

$daemonJsonPath = "$env:USERPROFILE\.docker\daemon.json"
$daemonJsonDir = Split-Path $daemonJsonPath -Parent

Write-Host "Creation repertoire .docker..." -ForegroundColor Cyan
if (-not (Test-Path $daemonJsonDir)) {
    New-Item -ItemType Directory -Path $daemonJsonDir -Force | Out-Null
    Write-Host "OK Repertoire cree: $daemonJsonDir" -ForegroundColor Green
}
else {
    Write-Host "OK Repertoire existe deja" -ForegroundColor Green
}

Write-Host "`nCreation daemon.json..." -ForegroundColor Cyan

$daemonConfig = @{
    "dns"        = @("8.8.8.8", "8.8.4.4")
    "log-driver" = "json-file"
    "log-opts"   = @{
        "max-size" = "10m"
        "max-file" = "3"
    }
} | ConvertTo-Json -Depth 10

$daemonConfig | Out-File -FilePath $daemonJsonPath -Encoding UTF8 -Force

Write-Host "OK daemon.json cree: $daemonJsonPath" -ForegroundColor Green
Write-Host "`nContenu:" -ForegroundColor Gray
Write-Host $daemonConfig -ForegroundColor Gray

Write-Host "`nIMPORTANT: Redemarrez Docker Desktop maintenant!" -ForegroundColor Yellow
Write-Host "   1. Clic droit sur icone Docker Desktop (barre des taches)" -ForegroundColor White
Write-Host "   2. Selectionner Restart" -ForegroundColor White
Write-Host "   3. Attendre 30 a 60 secondes" -ForegroundColor White

$dockerRestarted = Read-Host "`nDocker Desktop redemarre? (o/N)"

if ($dockerRestarted -eq "o") {
    Write-Host "`nTest Docker..." -ForegroundColor Cyan
    
    try {
        $testResult = docker run --rm hello-world 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "OK Docker fonctionne correctement!" -ForegroundColor Green
        }
        else {
            Write-Host "ERREUR Docker test echoue" -ForegroundColor Red
            Write-Host $testResult
            Write-Host "`nVerifiez que Docker Desktop est bien redemarre" -ForegroundColor Yellow
        }
    }
    catch {
        Write-Host "ERREUR: $_" -ForegroundColor Red
    }
}
else {
    Write-Host "Redemarrez Docker Desktop avant de continuer" -ForegroundColor Yellow
}

# PARTIE 2: PREPARATION .env.production

Write-Host "`n`nPARTIE 2: Preparation .env.production`n" -ForegroundColor Yellow

$projectPath = "C:\laravel_projects\racine-backend"
Set-Location $projectPath

if (-not (Test-Path ".env.production")) {
    Write-Host "ERREUR Fichier .env.production non trouve" -ForegroundColor Red
    exit 1
}

Write-Host "Generation APP_KEY..." -ForegroundColor Cyan
try {
    $appKey = php artisan key:generate --show
    Write-Host "OK APP_KEY genere: $appKey" -ForegroundColor Green
}
catch {
    Write-Host "ERREUR generation APP_KEY: $_" -ForegroundColor Red
    $appKey = "base64:P5J2rsB67L85CcIy8wcEKcp7rgvkzmApjNMp33clQzY="
    Write-Host "Utilisation APP_KEY par defaut" -ForegroundColor Yellow
}

Write-Host "`nGeneration mots de passe forts..." -ForegroundColor Cyan

function Generate-Password {
    param([int]$Length = 32)
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
    -join ((1..$Length) | ForEach-Object { $chars[(Get-Random -Maximum $chars.Length)] })
}

$dbPassword = Generate-Password
$redisPassword = Generate-Password
$dbRootPassword = Generate-Password

Write-Host "OK Mots de passe generes" -ForegroundColor Green

Write-Host "`nCreation .env.production.local..." -ForegroundColor Cyan

$envContent = Get-Content ".env.production" -Raw

$envContent = $envContent -replace 'APP_KEY=.*', "APP_KEY=$appKey"
$envContent = $envContent -replace 'DB_PASSWORD=.*', "DB_PASSWORD=$dbPassword"
$envContent = $envContent -replace 'REDIS_PASSWORD=.*', "REDIS_PASSWORD=$redisPassword"
$envContent = $envContent -replace 'DB_ROOT_PASSWORD=.*', "DB_ROOT_PASSWORD=$dbRootPassword"

$envContent | Out-File -FilePath ".env.production.local" -Encoding UTF8 -Force

Write-Host "OK .env.production.local cree" -ForegroundColor Green

# RESUME

Write-Host "`n================================================================" -ForegroundColor Green
Write-Host "                    CONFIGURATION TERMINEE" -ForegroundColor Green
Write-Host "================================================================`n" -ForegroundColor Green

Write-Host "OK DNS Docker configure (8.8.8.8, 8.8.4.4)" -ForegroundColor Green
Write-Host "OK .env.production.local cree avec:" -ForegroundColor Green
Write-Host "   APP_KEY: $appKey" -ForegroundColor Gray
Write-Host "   DB_PASSWORD: $($dbPassword.Substring(0,8))..." -ForegroundColor Gray
Write-Host "   REDIS_PASSWORD: $($redisPassword.Substring(0,8))..." -ForegroundColor Gray
Write-Host "   DB_ROOT_PASSWORD: $($dbRootPassword.Substring(0,8))..." -ForegroundColor Gray

Write-Host "`nFichier sauvegarde: .env.production.local" -ForegroundColor Cyan

Write-Host "`nProchaines etapes:" -ForegroundColor Yellow
Write-Host "   1. Verifier .env.production.local (optionnel)" -ForegroundColor White
Write-Host "   2. Lancer: .\setup-docker.ps1" -ForegroundColor White
Write-Host "   3. Ou: .\quick-start.ps1" -ForegroundColor White

$launchSetup = Read-Host "`nLancer setup-docker.ps1 maintenant? (o/N)"

if ($launchSetup -eq "o") {
    Write-Host "`nLancement setup-docker.ps1...`n" -ForegroundColor Cyan
    & ".\setup-docker.ps1"
}
else {
    Write-Host "`nOK Configuration terminee. Lancez manuellement:" -ForegroundColor Green
    Write-Host "   .\setup-docker.ps1" -ForegroundColor Cyan
}
