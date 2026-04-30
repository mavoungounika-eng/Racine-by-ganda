#!/usr/bin/env python3
"""
Migration Bootstrap 4 -> Bootstrap 5 pour tous les fichiers Blade.

Remplace :
  - badge-danger/warning/success/info/primary/secondary/dark/light
    -> bg-danger/warning/... (la classe "badge" base reste)
  - mr-N / ml-N  -> me-N / ms-N
  - pl-N / pr-N  -> ps-N / pe-N
  - float-left/right -> float-start/end
  - text-left/right -> text-start/end
  - border-left/right -> border-start/end

Affiche un compte des remplacements par fichier.
"""
import os
import re
import sys
from pathlib import Path

ROOT = Path('/home/nika/projects/racine-backend/resources/views')

# Chaque règle est (regex, remplacement, libellé). On met \b en frontière
# de mot pour ne pas toucher les noms de classes plus longues qui contiennent
# la sous-chaîne (ex: my-badge-danger-custom).
RULES = [
    # Badges (la "base" badge reste, seul le suffixe couleur change en bg-*)
    (re.compile(r'\bbadge-danger\b'),    'bg-danger',    'badge-danger'),
    (re.compile(r'\bbadge-warning\b'),   'bg-warning',   'badge-warning'),
    (re.compile(r'\bbadge-success\b'),   'bg-success',   'badge-success'),
    (re.compile(r'\bbadge-info\b'),      'bg-info',      'badge-info'),
    (re.compile(r'\bbadge-primary\b'),   'bg-primary',   'badge-primary'),
    (re.compile(r'\bbadge-secondary\b'), 'bg-secondary', 'badge-secondary'),
    (re.compile(r'\bbadge-dark\b'),      'bg-dark',      'badge-dark'),
    (re.compile(r'\bbadge-light\b'),     'bg-light text-dark', 'badge-light'),

    # Spacing : mr-*, ml-*, pr-*, pl-* (y compris -auto, -0..5, -n1..5)
    (re.compile(r'\bmr-(0|1|2|3|4|5|auto|n1|n2|n3|n4|n5)\b'), r'me-\1', 'mr-*'),
    (re.compile(r'\bml-(0|1|2|3|4|5|auto|n1|n2|n3|n4|n5)\b'), r'ms-\1', 'ml-*'),
    (re.compile(r'\bpr-(0|1|2|3|4|5)\b'), r'pe-\1', 'pr-*'),
    (re.compile(r'\bpl-(0|1|2|3|4|5)\b'), r'ps-\1', 'pl-*'),

    # Responsive variantes : mr-sm-2, ml-md-auto, etc.
    (re.compile(r'\bmr-(sm|md|lg|xl|xxl)-(0|1|2|3|4|5|auto|n1|n2|n3|n4|n5)\b'),
     r'me-\1-\2', 'mr-{bp}-*'),
    (re.compile(r'\bml-(sm|md|lg|xl|xxl)-(0|1|2|3|4|5|auto|n1|n2|n3|n4|n5)\b'),
     r'ms-\1-\2', 'ml-{bp}-*'),
    (re.compile(r'\bpr-(sm|md|lg|xl|xxl)-(0|1|2|3|4|5)\b'), r'pe-\1-\2', 'pr-{bp}-*'),
    (re.compile(r'\bpl-(sm|md|lg|xl|xxl)-(0|1|2|3|4|5)\b'), r'ps-\1-\2', 'pl-{bp}-*'),

    # Alignements
    (re.compile(r'\bfloat-left\b'),  'float-start', 'float-left'),
    (re.compile(r'\bfloat-right\b'), 'float-end',   'float-right'),
    (re.compile(r'\btext-left\b'),   'text-start',  'text-left'),
    (re.compile(r'\btext-right\b'),  'text-end',    'text-right'),
    (re.compile(r'\bborder-left\b'), 'border-start','border-left'),
    (re.compile(r'\bborder-right\b'),'border-end',  'border-right'),
]


def process_file(path: Path) -> dict:
    """Retourne un dict {libelle: nb_remplacements} pour ce fichier."""
    try:
        content = path.read_text(encoding='utf-8')
    except Exception as e:
        print(f"  ⚠️  {path}: {e}")
        return {}

    original = content
    counts = {}
    for regex, replacement, label in RULES:
        new_content, n = regex.subn(replacement, content)
        if n > 0:
            counts[label] = counts.get(label, 0) + n
            content = new_content

    if content != original:
        path.write_text(content, encoding='utf-8')

    return counts


def main():
    if not ROOT.exists():
        print(f"❌ Dossier introuvable : {ROOT}")
        sys.exit(1)

    total_files = 0
    modified_files = 0
    total_replacements = {}

    for blade_file in ROOT.rglob('*.blade.php'):
        total_files += 1
        counts = process_file(blade_file)
        if counts:
            modified_files += 1
            rel = blade_file.relative_to(ROOT)
            summary = ', '.join(f"{k}:{v}" for k, v in counts.items())
            print(f"  ✓ {rel}  →  {summary}")
            for k, v in counts.items():
                total_replacements[k] = total_replacements.get(k, 0) + v

    print()
    print("=" * 60)
    print(f"Fichiers Blade scannés   : {total_files}")
    print(f"Fichiers modifiés        : {modified_files}")
    print(f"Total remplacements      : {sum(total_replacements.values())}")
    print()
    print("Détail par type :")
    for k, v in sorted(total_replacements.items(), key=lambda x: -x[1]):
        print(f"  {k:25s} : {v}")


if __name__ == '__main__':
    main()
