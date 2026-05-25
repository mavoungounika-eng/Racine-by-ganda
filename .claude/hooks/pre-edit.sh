#!/bin/bash
# pre-edit.sh — Backup obligatoire avant toute modification
# Usage : .claude/hooks/pre-edit.sh <fichier>
FILE="$1"
[ -z "$FILE" ] && { echo "❌ Aucun fichier spécifié"; exit 1; }
[ ! -f "$FILE" ] && { echo "❌ Fichier introuvable : $FILE"; exit 1; }

BACKUPS_DIR="$(dirname "$0")/../backups"
mkdir -p "$BACKUPS_DIR"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BASENAME=$(basename "$FILE" | tr '/' '_')
BACKUP="$BACKUPS_DIR/${BASENAME}_${TIMESTAMP}"

cp "$FILE" "$BACKUP"
[ -f "$BACKUP" ] && echo "✅ BACKUP → $BACKUP" || { echo "❌ Backup échoué"; exit 1; }

# Trail log
echo "$(date '+%H:%M:%S') PRE_EDIT $FILE → $BACKUP" >> "$(dirname "$0")/../logs/edit-trail.log"
echo "$BACKUP"
