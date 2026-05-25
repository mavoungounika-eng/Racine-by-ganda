#!/bin/bash
# post-edit.sh — Validation complète après modification (PHP + Python)
# Usage : .claude/hooks/post-edit.sh <fichier>
FILE="$1"
[ -z "$FILE" ] && { echo "❌ Aucun fichier spécifié"; exit 1; }
[ ! -f "$FILE" ] && { echo "❌ Fichier introuvable : $FILE"; exit 1; }

LOG="$(dirname "$0")/../logs/edit-trail.log"
RESULT="OK"
EXT="${FILE##*.}"

echo "🔍 Validation → $FILE"

case "$EXT" in
  php)
    # 1. Syntaxe de base
    if ! php -l "$FILE" > /dev/null 2>&1; then
      echo "  ❌ php -l : erreur syntaxe"
      php -l "$FILE" 2>&1 | tail -3
      RESULT="FAIL_SYNTAX"
    else
      echo "  ✅ php -l OK"
    fi

    # 2. Inclusion (détecte méthodes tronquées)
    if [ "$RESULT" = "OK" ]; then
      ABS=$(realpath "$FILE")
      if ! php -r "error_reporting(E_ALL); include '$ABS';" > /dev/null 2>&1; then
        echo "  ❌ php include : méthode tronquée ou erreur fatale"
        RESULT="FAIL_INCLUDE"
      else
        echo "  ✅ include OK"
      fi
    fi

    # 3. Accolades équilibrées
    if [ "$RESULT" = "OK" ]; then
      OPEN=$(grep -o '{' "$FILE" | wc -l)
      CLOSE=$(grep -o '}' "$FILE" | wc -l)
      if [ "$OPEN" != "$CLOSE" ]; then
        echo "  ❌ Accolades : {=$OPEN }=$CLOSE — déséquilibre"
        RESULT="FAIL_BRACES"
      else
        echo "  ✅ Accolades OK ($OPEN/$CLOSE)"
      fi
    fi
    ;;

  py)
    if ! python3 -m py_compile "$FILE" 2>&1; then
      echo "  ❌ Python syntaxe invalide"
      RESULT="FAIL_SYNTAX"
    else
      echo "  ✅ Python syntaxe OK"
    fi
    if [ "$RESULT" = "OK" ]; then
      if ! python3 -c "import ast; ast.parse(open('$FILE').read())" > /dev/null 2>&1; then
        echo "  ❌ AST Python invalide"
        RESULT="FAIL_AST"
      else
        echo "  ✅ AST OK"
      fi
    fi
    ;;

  *)
    echo "  ⚠️  Extension .$EXT — pas de vérification automatique"
    ;;
esac

echo "$(date '+%H:%M:%S') POST_EDIT $FILE → $RESULT" >> "$LOG"

if [ "$RESULT" = "OK" ]; then
  echo ""
  echo "✅ VALIDATION OK — signaler DONE à l'orchestrateur"
  exit 0
else
  echo ""
  echo "❌ VALIDATION ÉCHOUÉE ($RESULT) — déclencher emergency-rollback.sh"
  exit 1
fi
