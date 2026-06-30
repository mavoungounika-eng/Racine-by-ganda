#!/bin/bash
# emergency-rollback.sh — Restaure le dernier backup d'un fichier
# Usage : .claude/hooks/emergency-rollback.sh <fichier> [<backup_exact>]
FILE="$1"
BACKUP_REF="$2"
LOG="$(dirname "$0")/../logs/edit-trail.log"
BACKUPS_DIR="$(dirname "$0")/../backups"

[ -z "$FILE" ] && { echo "❌ Aucun fichier spécifié"; exit 1; }

echo "🚨 ROLLBACK → $FILE"

if [ -n "$BACKUP_REF" ] && [ -f "$BACKUP_REF" ]; then
  BACKUP_TO_USE="$BACKUP_REF"
else
  BASENAME=$(basename "$FILE" | tr '/' '_')
  BACKUP_TO_USE=$(ls -t "$BACKUPS_DIR/${BASENAME}_"* 2>/dev/null | head -1)
fi

if [ -z "$BACKUP_TO_USE" ] || [ ! -f "$BACKUP_TO_USE" ]; then
  echo "❌ Aucun backup trouvé pour $FILE"
  echo "$(date '+%H:%M:%S') ROLLBACK_FAILED $FILE — NO_BACKUP" >> "$LOG"
  exit 1
fi

# Sauvegarder l'état cassé pour analyse
BROKEN="${FILE}.broken_$(date +%Y%m%d_%H%M%S)"
cp "$FILE" "$BROKEN"
echo "  💾 État cassé conservé → $BROKEN"

cp "$BACKUP_TO_USE" "$FILE"

# Vérification post-rollback
if [[ "$FILE" == *.php ]]; then
  php -l "$FILE" > /dev/null 2>&1 && echo "  ✅ Syntaxe valide après rollback" || echo "  ⚠️  Le backup lui-même a des problèmes"
fi

echo "$(date '+%H:%M:%S') ROLLBACK_OK $FILE ← $BACKUP_TO_USE" >> "$LOG"
echo ""
echo "✅ ROLLBACK TERMINÉ — signaler ROLLED_BACK à l'orchestrateur"
