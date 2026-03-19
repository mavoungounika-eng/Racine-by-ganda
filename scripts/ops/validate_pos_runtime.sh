#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="${1:-$(pwd)}"
PHP_BIN="${PHP_BIN:-php}"

cd "$PROJECT_DIR"

echo "== POS Runtime Validation =="
echo "Project: $PROJECT_DIR"
echo

echo "[1/7] Scheduler list"
"$PHP_BIN" artisan schedule:list
echo

echo "[2/7] Queue restart signal"
"$PHP_BIN" artisan queue:restart
echo

echo "[3/7] Manual scheduler run"
"$PHP_BIN" artisan schedule:run
echo

echo "[4/7] POS cleanup test"
"$PHP_BIN" artisan test tests/Feature/Pos/PosCleanupPendingPaymentsTest.php --stop-on-failure
echo

echo "[5/7] POS suite smoke"
"$PHP_BIN" artisan test tests/Feature/Pos --stop-on-failure
echo

echo "[6/7] Cron entry check (if available)"
if command -v crontab >/dev/null 2>&1; then
  crontab -l 2>/dev/null | grep artisan || echo "No artisan cron entry found for current user."
else
  echo "crontab command not found."
fi
echo

echo "[7/7] Log paths"
echo "Laravel log: $PROJECT_DIR/storage/logs/laravel.log"
echo "Worker log:  $PROJECT_DIR/storage/logs/worker.log"
echo
echo "Validation complete."

