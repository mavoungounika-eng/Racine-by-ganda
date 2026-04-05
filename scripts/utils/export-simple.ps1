# Script d'export du projet RACINE BY GANDA (Windows PowerShell)
# Usage: .\export-simple.ps1

$ProjectName = "racine-backend"
$ExportDir = "..\$ProjectName-export-$(Get-Date -Format 'yyyyMMdd-HHmmss')"
$CurrentDir = Get-Location

Write-Host "Debut de l'exportation du projet $ProjectName..." -ForegroundColor Green
Write-Host "Dossier d'export : $ExportDir" -ForegroundColor Cyan

# Creer le dossier d'export
New-Item -ItemType Directory -Path $ExportDir -Force | Out-Null

# Copier les fichiers
Write-Host "Copie des fichiers..." -ForegroundColor Yellow

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

# Creer .env.example
if (Test-Path "$CurrentDir\.env") {
    Write-Host "Creation de .env.example..." -ForegroundColor Yellow
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

# Creer l'archive
Write-Host "Creation de l'archive..." -ForegroundColor Yellow
$ArchiveName = "$ProjectName-export-$(Get-Date -Format 'yyyyMMdd-HHmmss').zip"
$ArchivePath = Join-Path (Split-Path $ExportDir -Parent) $ArchiveName
Compress-Archive -Path $ExportDir -DestinationPath $ArchivePath -Force

$size = (Get-ChildItem $ExportDir -Recurse | Measure-Object -Property Length -Sum).Sum / 1MB

Write-Host ""
Write-Host "Export termine !" -ForegroundColor Green
Write-Host "Dossier : $ExportDir" -ForegroundColor Cyan
Write-Host "Archive : $ArchivePath" -ForegroundColor Cyan
Write-Host "Taille : $([math]::Round($size, 2)) MB" -ForegroundColor Cyan
Write-Host ""


