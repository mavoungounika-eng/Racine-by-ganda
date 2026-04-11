# RACINE BY GANDA - Test Runner Script (Windows PowerShell)
# =============================================================================
# Cross-platform test execution script for local and CI environments
#
# Usage:
#   .\scripts\run-tests.ps1 [options]
#
# Options:
#   -Parallel          Run tests in parallel
#   -StopOnFailure     Stop on first test failure
#   -Profile           Show test execution timing
#   -Coverage          Generate code coverage report
#   -Filter <pattern>  Run only tests matching pattern
#   -Help              Show this help message
# =============================================================================

param(
    [switch]$Parallel,
    [switch]$StopOnFailure,
    [switch]$Profile,
    [switch]$Coverage,
    [string]$Filter = "",
    [switch]$Help
)

# Show help
if ($Help) {
    Get-Content $PSCommandPath | Select-String "^#" | Where-Object { $_ -notmatch "^#!/" } | ForEach-Object { $_ -replace "^# ", "" }
    exit 0
}

# Colors
function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

Write-ColorOutput Blue "=== RACINE Test Runner ==="
Write-Output ""

# Environment Validation
Write-ColorOutput Blue "=== Environment Validation ==="

# Check PHP
try {
    $phpVersion = php -r "echo PHP_VERSION;"
    Write-ColorOutput Green "✓ PHP $phpVersion"
} catch {
    Write-ColorOutput Red "✗ PHP not found"
    Write-Output "Please install PHP 8.1 or higher"
    exit 1
}

# Check Composer
try {
    composer --version | Out-Null
    Write-ColorOutput Green "✓ Composer installed"
} catch {
    Write-ColorOutput Red "✗ Composer not found"
    exit 1
}

# Check SQLite
$sqliteCheck = php -m | Select-String "sqlite3"
if (-not $sqliteCheck) {
    Write-ColorOutput Yellow "⚠ SQLite extension not found"
    Write-Output "Some tests may fail. Install php-sqlite3 for full compatibility."
}

# Database Setup
Write-Output ""
Write-ColorOutput Blue "=== Database Setup ==="

if (-not (Test-Path ".env.testing")) {
    Write-ColorOutput Yellow "⚠ .env.testing not found, creating from .env.example"
    Copy-Item ".env.example" ".env.testing"
    (Get-Content ".env.testing") -replace "DB_CONNECTION=mysql", "DB_CONNECTION=sqlite" | Set-Content ".env.testing"
}

Write-ColorOutput Green "✓ Test environment configured"

# Build Test Command
$testCmd = "php artisan test"

if ($Parallel) {
    $testCmd += " --parallel"
    Write-ColorOutput Blue "→ Parallel execution enabled"
}

if ($StopOnFailure) {
    $testCmd += " --stop-on-failure"
    Write-ColorOutput Blue "→ Stop-on-failure enabled"
}

if ($Profile) {
    $testCmd += " --profile"
    Write-ColorOutput Blue "→ Profiling enabled"
}

if ($Coverage) {
    $xdebugCheck = php -m | Select-String "xdebug"
    if (-not $xdebugCheck) {
        Write-ColorOutput Red "✗ Xdebug not found"
        Write-Output "Code coverage requires Xdebug extension"
        exit 1
    }
    $testCmd += " --coverage"
    Write-ColorOutput Blue "→ Coverage enabled"
}

if ($Filter) {
    $testCmd += " --filter=$Filter"
    Write-ColorOutput Blue "→ Filter: $Filter"
}

# Run Tests
Write-Output ""
Write-ColorOutput Blue "=== Running Tests ==="
Write-Output ""

$startTime = Get-Date

# Execute tests
Invoke-Expression $testCmd
$exitCode = $LASTEXITCODE

$endTime = Get-Date
$duration = ($endTime - $startTime).TotalSeconds

Write-Output ""
Write-ColorOutput Blue "=== Summary ==="
Write-Output "Duration: $([math]::Round($duration, 2))s"
Write-Output "Exit code: $exitCode"

if ($exitCode -eq 0) {
    Write-ColorOutput Green "✓ All tests passed"
} else {
    Write-ColorOutput Red "✗ Tests failed"
}

exit $exitCode
