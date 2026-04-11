#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# Cron Scheduler for Automated Backups
# ═══════════════════════════════════════════════════════════════

set -e

log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "Starting backup cron scheduler..."
log "Schedule: ${BACKUP_SCHEDULE:-0 2 * * *}"

# Create crontab
echo "${BACKUP_SCHEDULE:-0 2 * * *} /scripts/backup.sh >> /var/log/backup/cron.log 2>&1" > /tmp/crontab

# Install crontab
crontab /tmp/crontab

log "Crontab installed"
crontab -l

# Run cron in foreground
exec crond -f -l 2
