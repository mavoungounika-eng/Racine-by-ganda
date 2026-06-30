#!/bin/bash
# post-session.sh — Bilan de session + mise à jour CLAUDE.md (fusion, jamais remplacement)
# Usage : .claude/hooks/post-session.sh
cd "$(dirname "$0")/../../" || exit 1

TIMESTAMP=$(date '+%Y-%m-%d %H:%M')
LOG=".claude/logs/edit-trail.log"
CLAUDE_MD="CLAUDE.md"

echo "📝 Clôture de session — $TIMESTAMP"
echo ""

# Stats de la session
EDITS=$(grep "PRE_EDIT" "$LOG" 2>/dev/null | grep "$(date '+%Y%m%d')" | wc -l)
FAILS=$(grep "FAIL" "$LOG" 2>/dev/null | grep "$(date '+%Y%m%d')" | wc -l)
ROLLBACKS=$(grep "ROLLBACK_OK" "$LOG" 2>/dev/null | grep "$(date '+%Y%m%d')" | wc -l)

# Tests actuels
echo "Lancement tests pour bilan..."
TEST_RESULT=$(redis-cli FLUSHDB > /dev/null 2>&1; ./vendor/bin/phpunit 2>&1 | tail -1)

# Fichiers touchés aujourd'hui
FILES_TOUCHED=$(grep "PRE_EDIT" "$LOG" 2>/dev/null | grep "$(date '+%Y%m%d')" | awk '{print $3}' | sort -u | tr '\n' ', ' | sed 's/,$//')

# Bloc à fusionner dans CLAUDE.md (RÈGLE 10 — jamais remplacer)
BILAN=$(cat << BILAN_BLOCK

---
## Bilan session — $TIMESTAMP

| Indicateur | Valeur |
|------------|--------|
| Fichiers édités | $EDITS |
| Échecs validation | $FAILS |
| Rollbacks | $ROLLBACKS |
| Fichiers touchés | ${FILES_TOUCHED:-aucun} |
| Résultat tests | $TEST_RESULT |

BILAN_BLOCK
)

echo "$BILAN" >> "$CLAUDE_MD"
echo "✅ Bilan fusionné dans CLAUDE.md (RÈGLE 10 respectée)"

# Archiver le log du jour
ARCHIVE=".claude/logs/archives/edit-trail_$(date +%Y%m%d).log"
cp "$LOG" "$ARCHIVE" 2>/dev/null && echo "✅ Log archivé → $ARCHIVE"

echo ""
echo "🏁 Session clôturée. Tests : $TEST_RESULT"
