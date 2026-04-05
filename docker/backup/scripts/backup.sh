#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# Backup Script - MySQL + Redis + Storage
# ═══════════════════════════════════════════════════════════════

set -e

# Configuration
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backups/${TIMESTAMP}"
RETENTION_DAYS=${BACKUP_RETENTION_DAYS:-30}

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" >&2
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1"
}

# Create backup directory
mkdir -p "${BACKUP_DIR}"

# ───────────────────────────────────────────────────────────────
# 1. MYSQL BACKUP
# ───────────────────────────────────────────────────────────────
log "Starting MySQL backup..."

mysqldump \
    -h "${MYSQL_HOST}" \
    -P "${MYSQL_PORT}" \
    -u "${MYSQL_USER}" \
    -p"${MYSQL_PASSWORD}" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --databases "${MYSQL_DATABASE}" \
    | gzip > "${BACKUP_DIR}/mysql_${TIMESTAMP}.sql.gz"

if [ $? -eq 0 ]; then
    log "MySQL backup completed: mysql_${TIMESTAMP}.sql.gz"
else
    error "MySQL backup failed"
    exit 1
fi

# ───────────────────────────────────────────────────────────────
# 2. REDIS BACKUP
# ───────────────────────────────────────────────────────────────
log "Starting Redis backup..."

redis-cli \
    -h "${REDIS_HOST}" \
    -p "${REDIS_PORT}" \
    -a "${REDIS_PASSWORD}" \
    --rdb "${BACKUP_DIR}/redis_${TIMESTAMP}.rdb"

if [ $? -eq 0 ]; then
    gzip "${BACKUP_DIR}/redis_${TIMESTAMP}.rdb"
    log "Redis backup completed: redis_${TIMESTAMP}.rdb.gz"
else
    warn "Redis backup failed (non-critical)"
fi

# ───────────────────────────────────────────────────────────────
# 3. CREATE MANIFEST
# ───────────────────────────────────────────────────────────────
cat > "${BACKUP_DIR}/manifest.json" <<EOF
{
    "timestamp": "${TIMESTAMP}",
    "date": "$(date -Iseconds)",
    "mysql": {
        "host": "${MYSQL_HOST}",
        "database": "${MYSQL_DATABASE}",
        "file": "mysql_${TIMESTAMP}.sql.gz",
        "size": "$(stat -f%z "${BACKUP_DIR}/mysql_${TIMESTAMP}.sql.gz" 2>/dev/null || stat -c%s "${BACKUP_DIR}/mysql_${TIMESTAMP}.sql.gz")"
    },
    "redis": {
        "host": "${REDIS_HOST}",
        "file": "redis_${TIMESTAMP}.rdb.gz",
        "size": "$(stat -f%z "${BACKUP_DIR}/redis_${TIMESTAMP}.rdb.gz" 2>/dev/null || stat -c%s "${BACKUP_DIR}/redis_${TIMESTAMP}.rdb.gz" || echo 0)"
    }
}
EOF

log "Manifest created"

# ───────────────────────────────────────────────────────────────
# 4. UPLOAD TO S3 (if configured)
# ───────────────────────────────────────────────────────────────
if [ -n "${S3_BUCKET}" ]; then
    log "Uploading to S3: ${S3_BUCKET}..."
    
    aws s3 sync "${BACKUP_DIR}" "s3://${S3_BUCKET}/backups/${TIMESTAMP}/" \
        --storage-class STANDARD_IA \
        --only-show-errors
    
    if [ $? -eq 0 ]; then
        log "S3 upload completed"
    else
        error "S3 upload failed"
    fi
fi

# ───────────────────────────────────────────────────────────────
# 5. CLEANUP OLD BACKUPS
# ───────────────────────────────────────────────────────────────
log "Cleaning up old backups (retention: ${RETENTION_DAYS} days)..."

find /backups -type d -mtime +${RETENTION_DAYS} -exec rm -rf {} + 2>/dev/null || true

if [ -n "${S3_BUCKET}" ]; then
    aws s3 ls "s3://${S3_BUCKET}/backups/" | \
        awk '{print $2}' | \
        while read -r backup_date; do
            if [ $(date -d "${backup_date}" +%s 2>/dev/null || date -j -f "%Y-%m-%d" "${backup_date}" +%s 2>/dev/null || echo 0) -lt $(date -d "${RETENTION_DAYS} days ago" +%s 2>/dev/null || date -v-${RETENTION_DAYS}d +%s) ]; then
                aws s3 rm "s3://${S3_BUCKET}/backups/${backup_date}" --recursive
            fi
        done
fi

log "Cleanup completed"

# ───────────────────────────────────────────────────────────────
# 6. SUMMARY
# ───────────────────────────────────────────────────────────────
BACKUP_SIZE=$(du -sh "${BACKUP_DIR}" | cut -f1)

log "═══════════════════════════════════════════════════════════"
log "Backup completed successfully!"
log "Timestamp: ${TIMESTAMP}"
log "Location: ${BACKUP_DIR}"
log "Size: ${BACKUP_SIZE}"
log "═══════════════════════════════════════════════════════════"

exit 0
