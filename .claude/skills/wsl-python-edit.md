---
description: Editer des fichiers PHP/MD en WSL sans corruption UTF-8
triggers:
  - modifier un fichier
  - éditer
  - remplacer du contenu
---
# Skill — Edition fichiers WSL-safe

## Pourquoi Python et pas sed/echo
- sed échoue silencieusement sur UTF-8 (WSL)
- Les heredocs bash sont instables dans WSL
- Python gère UTF-8 nativement

## Pattern lecture avant modification
```bash
wc -l FICHIER
head -30 FICHIER
grep -n "function\|class\|TODO" FICHIER
```

## Pattern remplacement ciblé
```bash
python3 -c "
lines = open('FICHIER.php').readlines()
for i, l in enumerate(lines[N-3:N+3], N-2):
    print(i, repr(l))
"
python3 -c "
lines = open('FICHIER.php').readlines()
lines[INDEX] = '    nouveau contenu\n'
open('FICHIER.php', 'w').writelines(lines)
print('OK')
"
```

## Pattern écriture fichier complet
```python
from pathlib import Path
Path('FICHIER.php').write_text("""<?php
// contenu ici""", encoding='utf-8')
print('OK')
```

## Vérification obligatoire après écriture PHP
```bash
php -l FICHIER.php
```
