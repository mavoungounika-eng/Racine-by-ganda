#!/usr/bin/env python3
import re

# Lire le fichier
with open('resources/views/admin/pos/sessions.blade.php', 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Remplacer tous les x-show par :class
# Pattern: x-show="CONDITION"
# Remplacement: :class="{ hidden: !(CONDITION) }"
content = re.sub(r'x-show="([^"]+)"', lambda m: f':class="{{ hidden: !({m.group(1)}) }}"', content)

# 2. Ajouter la classe .hidden au style si elle n'existe pas déjà
if '.hidden {' not in content:
    # Insérer la classe hidden juste avant </style>
    content = content.replace(
        '.sales-empty{text-align:center;padding:1.5rem;color:#666;font-size:.82rem}\n</style>',
        '.sales-empty{text-align:center;padding:1.5rem;color:#666;font-size:.82rem}\n.hidden{display:none!important}\n</style>'
    )

# Écrire le fichier modifié
with open('resources/views/admin/pos/sessions.blade.php', 'w', encoding='utf-8') as f:
    f.write(content)

print("✅ Fichier modifié avec succès")
