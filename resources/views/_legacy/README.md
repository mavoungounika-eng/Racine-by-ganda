# Vues Legacy / Archivées

Ce dossier contient les vues et layouts obsolètes qui ne sont plus utilisées dans le flux principal de l'application.

**⚠️ IMPORTANT : Ne pas modifier ces fichiers directement.**  
Si des éléments sont nécessaires, les copier dans les vues officielles.

---

## Structure

### `/checkout/`
Vues checkout obsolètes (Phase 1 - Unification checkout)

### `/admin/`
Vues admin obsolètes (Phase 3 - Nettoyage legacy)

#### Vues `-old.blade.php`
Anciennes versions des vues admin, remplacées par les versions actuelles.

#### Vues `-improved.blade.php`
Versions améliorées/testées qui n'ont finalement pas été adoptées, ou versions de travail intermédiaires.

**Exemples :**
- `admin/products/index-old.blade.php` → Ancienne version de la liste produits
- `admin/products/index-improved.blade.php` → Version améliorée non adoptée
- `admin/categories/create-old.blade.php` → Ancienne version du formulaire création catégorie

---

## Raison de l'archivage

Ces vues sont conservées à titre de référence historique ou pour une éventuelle réutilisation partielle, mais ne sont **plus utilisées dans les routes actives**.

**Date d'archivage** : Décembre 2025 (Phase 3)

---

## Vues officielles actuelles

- **Checkout** : `resources/views/checkout/index.blade.php`
- **Admin** : `resources/views/admin/*/index.blade.php` (sans suffixe)

---

## Layouts legacy

### `layouts/master.blade.php`
Layout Tailwind/Vite non utilisé.  
**Statut** : À archiver si confirmé non utilisé (Phase 3)

---

**Note** : Ces fichiers peuvent être supprimés définitivement après une période de rétention si nécessaire, mais sont conservés pour l'instant pour référence.

