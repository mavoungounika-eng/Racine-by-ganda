# Stripe Local Webhook Setup Script
# Automatise la configuration Stripe CLI pour les tests locaux

param(
    [switch]$RunTrigger = $false
)

$ErrorActionPreference = "Stop"

Write-Host "=== Stripe Local Webhook Setup ===" -ForegroundColor Cyan
Write-Host ""

# 1. Vérifier que Stripe CLI est installé
Write-Host "[1/5] Vérification de Stripe CLI..." -ForegroundColor Yellow
try {
    $stripeVersion = stripe --version 2>&1
    if ($LASTEXITCODE -ne 0) {
        throw "Stripe CLI non trouvé"
    }
    Write-Host "✓ Stripe CLI installé: $stripeVersion" -ForegroundColor Green
} catch {
    Write-Host "✗ Stripe CLI n'est pas installé" -ForegroundColor Red
    Write-Host ""
    Write-Host "Pour installer Stripe CLI:" -ForegroundColor Yellow
    Write-Host "  Windows (Scoop): scoop install stripe" -ForegroundColor White
    Write-Host "  Windows (Chocolatey): choco install stripe-cli" -ForegroundColor White
    Write-Host "  Ou télécharger depuis: https://stripe.com/docs/stripe-cli" -ForegroundColor White
    Write-Host ""
    exit 1
}

# 2. Vérifier que Laravel est démarré
Write-Host ""
Write-Host "[2/5] Vérification du serveur Laravel..." -ForegroundColor Yellow
try {
    $response = Invoke-WebRequest -Uri "http://127.0.0.1:8000" -Method GET -TimeoutSec 2 -UseBasicParsing -ErrorAction SilentlyContinue
    Write-Host "✓ Serveur Laravel accessible" -ForegroundColor Green
} catch {
    Write-Host "✗ Serveur Laravel non accessible sur http://127.0.0.1:8000" -ForegroundColor Red
    Write-Host ""
    Write-Host "Démarrez le serveur avec:" -ForegroundColor Yellow
    Write-Host "  php artisan serve" -ForegroundColor White
    Write-Host ""
    exit 1
}

# 3. Démarrer stripe listen et capturer le secret
Write-Host ""
Write-Host "[3/5] Démarrage de Stripe CLI listen..." -ForegroundColor Yellow
Write-Host "  URL: http://127.0.0.1:8000/api/webhooks/stripe" -ForegroundColor Gray
Write-Host ""
Write-Host "⚠️  IMPORTANT: Le processus stripe listen va démarrer en arrière-plan." -ForegroundColor Yellow
Write-Host "   Vous verrez le secret whsec_... dans la sortie." -ForegroundColor Yellow
Write-Host "   Appuyez sur Ctrl+C pour arrêter stripe listen quand vous avez terminé." -ForegroundColor Yellow
Write-Host ""

# Lancer stripe listen en arrière-plan et capturer la sortie
$stripeProcess = Start-Process -FilePath "stripe" -ArgumentList "listen", "--forward-to", "http://127.0.0.1:8000/api/webhooks/stripe" -NoNewWindow -PassThru -RedirectStandardOutput "stripe-output.txt" -RedirectStandardError "stripe-error.txt"

# Attendre un peu pour que stripe démarre et affiche le secret
Start-Sleep -Seconds 3

# Lire la sortie pour extraire le secret
$webhookSecret = $null
$maxAttempts = 10
$attempt = 0

while ($attempt -lt $maxAttempts -and $null -eq $webhookSecret) {
    Start-Sleep -Seconds 1
    $attempt++
    
    if (Test-Path "stripe-output.txt") {
        $output = Get-Content "stripe-output.txt" -Raw -ErrorAction SilentlyContinue
        if ($output -match "whsec_[A-Za-z0-9]+") {
            $webhookSecret = $matches[0]
        }
    }
    
    if (Test-Path "stripe-error.txt") {
        $error = Get-Content "stripe-error.txt" -Raw -ErrorAction SilentlyContinue
        if ($error -match "whsec_[A-Za-z0-9]+") {
            $webhookSecret = $matches[0]
        }
    }
}

if ($null -eq $webhookSecret) {
    Write-Host "⚠️  Impossible de capturer automatiquement le secret." -ForegroundColor Yellow
    Write-Host "   Regardez la sortie de stripe listen ci-dessus et copiez le secret whsec_..." -ForegroundColor Yellow
    Write-Host ""
    $webhookSecret = Read-Host "Entrez le secret webhook (whsec_...)"
    
    if ([string]::IsNullOrWhiteSpace($webhookSecret) -or -not $webhookSecret.StartsWith("whsec_")) {
        Write-Host "✗ Secret invalide" -ForegroundColor Red
        Stop-Process -Id $stripeProcess.Id -Force -ErrorAction SilentlyContinue
        exit 1
    }
} else {
    Write-Host "✓ Secret capturé: $webhookSecret" -ForegroundColor Green
}

# 4. Mettre à jour .env
Write-Host ""
Write-Host "[4/5] Mise à jour du fichier .env..." -ForegroundColor Yellow

if (-not (Test-Path ".env")) {
    Write-Host "✗ Fichier .env non trouvé" -ForegroundColor Red
    Stop-Process -Id $stripeProcess.Id -Force -ErrorAction SilentlyContinue
    exit 1
}

# Lire le contenu actuel
$envContent = Get-Content ".env" -Raw

# Mettre à jour ou ajouter STRIPE_ENABLED
if ($envContent -match "(?m)^STRIPE_ENABLED=.*$") {
    $envContent = $envContent -replace "(?m)^STRIPE_ENABLED=.*$", "STRIPE_ENABLED=true"
} else {
    $envContent += "`nSTRIPE_ENABLED=true"
}

# Mettre à jour ou ajouter STRIPE_WEBHOOK_SECRET
if ($envContent -match "(?m)^STRIPE_WEBHOOK_SECRET=.*$") {
    $envContent = $envContent -replace "(?m)^STRIPE_WEBHOOK_SECRET=.*$", "STRIPE_WEBHOOK_SECRET=$webhookSecret"
} else {
    $envContent += "`nSTRIPE_WEBHOOK_SECRET=$webhookSecret"
}

# Écrire le fichier
Set-Content -Path ".env" -Value $envContent -NoNewline
Write-Host "✓ .env mis à jour" -ForegroundColor Green

# 5. Nettoyer le cache Laravel
Write-Host ""
Write-Host "[5/5] Nettoyage du cache Laravel..." -ForegroundColor Yellow
php artisan optimize:clear
if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Cache nettoyé" -ForegroundColor Green
} else {
    Write-Host "⚠️  Erreur lors du nettoyage du cache" -ForegroundColor Yellow
}

# Résumé
Write-Host ""
Write-Host "=== Configuration terminée ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "✓ Stripe CLI listen est en cours d'exécution (PID: $($stripeProcess.Id))" -ForegroundColor Green
Write-Host "✓ .env mis à jour avec STRIPE_WEBHOOK_SECRET=$webhookSecret" -ForegroundColor Green
Write-Host ""
Write-Host "=== Prochaines étapes ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Dans un NOUVEAU terminal, exécutez:" -ForegroundColor Yellow
Write-Host ""
Write-Host "  # Déclencher un événement de test" -ForegroundColor White
Write-Host "  stripe trigger payment_intent.succeeded" -ForegroundColor Cyan
Write-Host ""
Write-Host "  # Vérifier les logs" -ForegroundColor White
Write-Host "  php artisan payments:stripe-webhook-smoke --tail=50" -ForegroundColor Cyan
Write-Host ""
Write-Host "  # Ou vérifier directement dans la DB" -ForegroundColor White
Write-Host "  php artisan tinker" -ForegroundColor Cyan
Write-Host "  >>> App\Models\StripeWebhookEvent::latest()->first()" -ForegroundColor Gray
Write-Host ""

# Option bonus: lancer automatiquement le trigger
if ($RunTrigger) {
    Write-Host "=== Déclenchement automatique ===" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Déclenchement de l'événement de test..." -ForegroundColor Yellow
    Start-Sleep -Seconds 2
    
    try {
        $triggerOutput = stripe trigger payment_intent.succeeded 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ Événement déclenché" -ForegroundColor Green
            Write-Host ""
            Write-Host "Vérifiez les logs avec:" -ForegroundColor Yellow
            Write-Host "  php artisan payments:stripe-webhook-smoke --tail=50" -ForegroundColor Cyan
        } else {
            Write-Host "⚠️  Erreur lors du déclenchement: $triggerOutput" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "⚠️  Impossible de déclencher automatiquement l'événement" -ForegroundColor Yellow
        Write-Host "   Exécutez manuellement: stripe trigger payment_intent.succeeded" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "Pour arrêter stripe listen, appuyez sur Ctrl+C ou fermez ce terminal." -ForegroundColor Gray
Write-Host ""

