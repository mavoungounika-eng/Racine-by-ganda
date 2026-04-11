# Script d'export du projet RACINE BY GANDA (Windows PowerShell)
# Usage: .\export-project.ps1

$ProjectName = "racine-backend"
$ExportDir = "..\$ProjectName-export-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
$CurrentDir = Get-Location

Write-Host "🚀 Début de l'exportation du projet $ProjectName..." -ForegroundColor Green
Write-Host "📁 Dossier d'export : $ExportDir" -ForegroundColor Cyan

# Créer le dossier d'export
New-Item -ItemType Directory -Path $ExportDir -Force | Out-Null

# Copier les fichiers (exclure node_modules, vendor, .git, etc.)
Write-Host "📁 Copie des fichiers..." -ForegroundColor Yellow

Get-ChildItem -Path $CurrentDir -Recurse -File | Where-Object {
    $exclude = @('node_modules', 'vendor', '.git', 'storage\logs', 'storage\framework\cache', 
                 'storage\framework\sessions', 'storage\framework\views', 'bootstrap\cache',
                 '.env$', '.env.backup', '\.log$', '\.DS_Store', 'Thumbs\.db', 
                 '\.phpunit\.result\.cache', '\.phpunit\.cache')
    
    $shouldExclude = $false
    foreach ($pattern in $exclude) {
        if ($_.FullName -match $pattern) {
            $shouldExclude = $true
            break
        }
    }
    return -not $shouldExclude
} | ForEach-Object {
    $relativePath = $_.FullName.Substring($CurrentDir.Path.Length + 1)
    $destPath = Join-Path $ExportDir $relativePath
    $destDir = Split-Path $destPath -Parent
    
    if (-not (Test-Path $destDir)) {
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    }
    
    Copy-Item $_.FullName -Destination $destPath -Force
}

# Créer .env.example
if (Test-Path "$CurrentDir\.env") {
    Write-Host "📝 Création de .env.example..." -ForegroundColor Yellow
    $envContent = Get-Content "$CurrentDir\.env"
    $envExample = $envContent | ForEach-Object {
        if ($_ -match '^([^=]+)=(.+)$') {
            "$($matches[1])="
        } else {
            $_
        }
    }
    $envExample | Set-Content "$ExportDir\.env.example"
}

# Créer README-EXPORT.md
$readme = @"
# 📦 PROJET RACINE BY GANDA - EXPORT

## 📋 INSTRUCTIONS D'INSTALLATION

### 1. Installer les dépendances PHP
\`\`\`bash
composer install --no-dev --optimize-autoloader
\`\`\`

### 2. Installer les dépendances Node.js
\`\`\`bash
npm install
\`\`\`

### 3. Configurer l'environnement
\`\`\`bash
cp .env.example .env
php artisan key:generate
\`\`\`

### 4. Configurer la base de données
Éditer le fichier \`.env\` et configurer :
- DB_CONNECTION
- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

### 5. Importer la base de données
\`\`\`bash
php artisan migrate --force
# OU importer le dump SQL si disponible
mysql -u user -p database_name < database/dumps/racine-backend.sql
\`\`\`

### 6. Créer les liens symboliques
\`\`\`bash
php artisan storage:link
\`\`\`

### 7. Compiler les assets
\`\`\`bash
npm run build
# OU pour le développement
npm run dev
\`\`\`

### 8. Optimiser l'application
\`\`\`bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
\`\`\`
"@

$readme | Out-File -FilePath "$ExportDir\README-EXPORT.md" -Encoding UTF8

# Créer l'archive
Write-Host "📦 Création de l'archive..." -ForegroundColor Yellow
$ArchiveName = "$ProjectName-export-$(Get-Date -Format 'yyyyMMdd-HHmmss').zip"
$ArchivePath = Join-Path (Split-Path $ExportDir -Parent) $ArchiveName
Compress-Archive -Path $ExportDir -DestinationPath $ArchivePath -Force

$size = (Get-ChildItem $ExportDir -Recurse | Measure-Object -Property Length -Sum).Sum / 1MB

Write-Host ""
Write-Host "✅ Export terminé !" -ForegroundColor Green
Write-Host "📁 Dossier : $ExportDir" -ForegroundColor Cyan
Write-Host "📦 Archive : $ArchivePath" -ForegroundColor Cyan
Write-Host "📊 Taille : $([math]::Round($size, 2)) MB" -ForegroundColor Cyan
Write-Host ""
Write-Host "💡 Pour exporter la base de données :" -ForegroundColor Yellow
Write-Host '   mysqldump -u root -p racine_backend > database-export.sql' -ForegroundColor Gray
Write-Host ""

