#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# Restore Script - MySQL + Redis
# ═══════════════════════════════════════════════════════════════

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR:${NC} $1" >&2
}

warn() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING:${NC} $1"
}

# Check arguments
if [ $# -lt 1 ]; then
    error "Usage: $0 <backup_timestamp> [--mysql-only|--redis-only]"
    error "Example: $0 20260213_020000"
    error ""
    error "Available backups:"
    ls -1 /backups | grep -E '^[0-9]{8}_[0-9]{6}$' || echo "  No backups found"
    exit 1
fi

TIMESTAMP=$1
BACKUP_DIR="/backups/${TIMESTAMP}"
MYSQL_ONLY=false
REDIS_ONLY=false

# Parse options
if [ "$2" = "--mysql-only" ]; then
    MYSQL_ONLY=true
elif [ "$2" = "--redis-only" ]; then
    REDIS_ONLY=true
fi

# Verify backup exists
if [ ! -d "${BACKUP_DIR}" ]; then
    error "Backup not found: ${BACKUP_DIR}"
    exit 1
fi

log "═══════════════════════════════════════════════════════════"
log "Starting restore from backup: ${TIMESTAMP}"
log "═══════════════════════════════════════════════════════════"

# ───────────────────────────────────────────────────────────────
# 1. MYSQL RESTORE
# ───────────────────────────────────────────────────────────────
if [ "$REDIS_ONLY" = false ]; then
    MYSQL_BACKUP="${BACKUP_DIR}/mysql_${TIMESTAMP}.sql.gz"
    
    if [ -f "${MYSQL_BACKUP}" ]; then
        log "Restoring MySQL from: ${MYSQL_BACKUP}"
        
        # Confirmation prompt
        warn "⚠️  This will OVERWRITE the current database!"
        read -p "Continue? (yes/no): " confirm
        
        if [ "$confirm" != "yes" ]; then
            error "Restore cancelled by user"
            exit 1
        fi
        
        # Restore
        gunzip -c "${MYSQL_BACKUP}" | mysql \
            -h "${MYSQL_HOST}" \
            -P "${MYSQL_PORT}" \
            -u "${MYSQL_USER}" \
            -p"${MYSQL_PASSWORD}"
        
        if [ $? -eq 0 ]; then
            log "✅ MySQL restore completed"
        else
            error "MySQL restore failed"
            exit 1
        fi
    else
        error "MySQL backup file not found: ${MYSQL_BACKUP}"
        exit 1
    fi
fi

# ───────────────────────────────────────────────────────────────
# 2. REDIS RESTORE
# ───────────────────────────────────────────────────────────────
if [ "$MYSQL_ONLY" = false ]; then
    REDIS_BACKUP="${BACKUP_DIR}/redis_${TIMESTAMP}.rdb.gz"
    
    if [ -f "${REDIS_BACKUP}" ]; then
        log "Restoring Redis from: ${REDIS_BACKUP}"
        
        # Flush Redis
        warn "⚠️  This will FLUSH all Redis data!"
        read -p "Continue? (yes/no): " confirm
        
        if [ "$confirm" != "yes" ]; then
            error "Restore cancelled by user"
            exit 1
        fi
        
        redis-cli \
            -h "${REDIS_HOST}" \
            -p "${REDIS_PORT}" \
            -a "${REDIS_PASSWORD}" \
            FLUSHALL
        
        # Restore RDB
        gunzip -c "${REDIS_BACKUP}" > /tmp/dump.rdb
        
        redis-cli \
            -h "${REDIS_HOST}" \
            -p "${REDIS_PORT}" \
            -a "${REDIS_PASSWORD}" \
            --rdb /tmp/dump.rdb
        
        rm -f /tmp/dump.rdb
        
        if [ $? -eq 0 ]; then
            log "✅ Redis restore completed"
        else
            warn "Redis restore failed (non-critical)"
        fi
    else
        warn "Redis backup file not found: ${REDIS_BACKUP}"
    fi
fi

log "═══════════════════════════════════════════════════════════"
log "Restore completed successfully!"
log "═══════════════════════════════════════════════════════════"

exit 0
