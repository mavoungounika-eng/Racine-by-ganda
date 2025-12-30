# 🗑️ SQL - NETTOYAGE DES RÔLES DOUBLONS

**Date :** 28 novembre 2025  
**Objectif :** Supprimer les rôles créés par erreur lors de l'inscription

---

## ⚠️ PROBLÈME

Lors de l'inscription, si le rôle n'était pas trouvé, un nouveau rôle était créé avec :
- `name` en minuscules (`'client'` ou `'creator'`)
- `slug` = `NULL` ou manquant
- Créant des doublons avec les rôles corrects (`'Client'` / `'Créateur'`)

---

## 🔍 VÉRIFICATION AVANT NETTOYAGE

### 1. Lister tous les rôles
```sql
SELECT id, name, slug, description, is_active, created_at 
FROM roles 
ORDER BY name, slug;
```

### 2. Identifier les doublons
```sql
-- Rôles avec name en minuscules (probablement créés par erreur)
SELECT * FROM roles 
WHERE name IN ('client', 'creator') 
   OR (name = 'Client' AND slug IS NULL)
   OR (name = 'Créateur' AND slug IS NULL);
```

### 3. Vérifier les utilisateurs affectés
```sql
-- Utilisateurs avec des rôles problématiques
SELECT u.id, u.name, u.email, u.role_id, r.name as role_name, r.slug as role_slug
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
WHERE r.name IN ('client', 'creator')
   OR r.slug IS NULL
   OR (r.name = 'Client' AND r.slug IS NULL)
   OR (r.name = 'Créateur' AND r.slug IS NULL);
```

---

## 🧹 NETTOYAGE

### Étape 1 : Identifier les IDs des rôles à supprimer

**Rôles corrects (à GARDER) :**
```sql
SELECT id, name, slug FROM roles 
WHERE (name = 'Client' AND slug = 'client')
   OR (name = 'Créateur' AND slug = 'createur')
   OR slug IN ('super_admin', 'admin', 'staff');
```

**Rôles à SUPPRIMER (doublons) :**
```sql
SELECT id, name, slug FROM roles 
WHERE name IN ('client', 'creator')  -- Minuscules
   OR slug IS NULL                    -- Sans slug
   OR (name = 'Client' AND slug IS NULL)
   OR (name = 'Créateur' AND slug IS NULL);
```

### Étape 2 : Migrer les utilisateurs vers les bons rôles

**Avant de supprimer, migrer les utilisateurs :**

```sql
-- Migrer les utilisateurs avec role name='client' vers role slug='client'
UPDATE users u
INNER JOIN roles r_old ON u.role_id = r_old.id
INNER JOIN roles r_new ON r_new.slug = 'client'
SET u.role_id = r_new.id
WHERE r_old.name = 'client' AND r_new.slug = 'client';

-- Migrer les utilisateurs avec role name='creator' vers role slug='createur'
UPDATE users u
INNER JOIN roles r_old ON u.role_id = r_old.id
INNER JOIN roles r_new ON r_new.slug = 'createur'
SET u.role_id = r_new.id
WHERE r_old.name = 'creator' AND r_new.slug = 'createur';

-- Migrer les utilisateurs avec role sans slug vers le bon rôle selon le name
UPDATE users u
INNER JOIN roles r_old ON u.role_id = r_old.id
INNER JOIN roles r_new ON (
    (r_old.name = 'Client' AND r_new.slug = 'client')
    OR (r_old.name = 'Créateur' AND r_new.slug = 'createur')
)
SET u.role_id = r_new.id
WHERE r_old.slug IS NULL;
```

### Étape 3 : Supprimer les rôles doublons

**⚠️ ATTENTION : Assurez-vous que tous les utilisateurs ont été migrés avant !**

```sql
-- Supprimer les rôles avec name en minuscules
DELETE FROM roles 
WHERE name IN ('client', 'creator');

-- Supprimer les rôles sans slug (sauf ceux qui doivent en avoir)
DELETE FROM roles 
WHERE slug IS NULL 
  AND name NOT IN ('Super Administrateur', 'Administrateur', 'Staff', 'Créateur', 'Client');
```

### Étape 4 : Vérification finale

```sql
-- Vérifier qu'il n'y a plus de doublons
SELECT name, COUNT(*) as count 
FROM roles 
GROUP BY name 
HAVING count > 1;

-- Vérifier que tous les rôles ont un slug
SELECT * FROM roles WHERE slug IS NULL;

-- Vérifier que tous les utilisateurs ont un role_id valide
SELECT u.id, u.name, u.email, u.role_id, r.name as role_name, r.slug as role_slug
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
WHERE r.id IS NULL;
```

---

## 📋 SCRIPT SQL COMPLET (À EXÉCUTER DANS L'ORDRE)

```sql
-- ============================================
-- 1. VÉRIFICATION
-- ============================================
SELECT 'Rôles actuels:' as info;
SELECT id, name, slug, description FROM roles ORDER BY name;

SELECT 'Utilisateurs avec rôles problématiques:' as info;
SELECT u.id, u.name, u.email, u.role_id, r.name as role_name, r.slug as role_slug
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
WHERE r.name IN ('client', 'creator') OR r.slug IS NULL;

-- ============================================
-- 2. MIGRATION DES UTILISATEURS
-- ============================================
-- Migrer 'client' vers 'client' (slug)
UPDATE users u
INNER JOIN roles r_old ON u.role_id = r_old.id
INNER JOIN roles r_new ON r_new.slug = 'client'
SET u.role_id = r_new.id
WHERE r_old.name = 'client' AND r_new.slug = 'client';

-- Migrer 'creator' vers 'createur' (slug)
UPDATE users u
INNER JOIN roles r_old ON u.role_id = r_old.id
INNER JOIN roles r_new ON r_new.slug = 'createur'
SET u.role_id = r_new.id
WHERE r_old.name = 'creator' AND r_new.slug = 'createur';

-- Migrer les rôles sans slug
UPDATE users u
INNER JOIN roles r_old ON u.role_id = r_old.id
INNER JOIN roles r_new ON (
    (r_old.name = 'Client' AND r_new.slug = 'client')
    OR (r_old.name = 'Créateur' AND r_new.slug = 'createur')
)
SET u.role_id = r_new.id
WHERE r_old.slug IS NULL;

-- ============================================
-- 3. SUPPRESSION DES RÔLES DOUBLONS
-- ============================================
-- Supprimer les rôles avec name en minuscules
DELETE FROM roles WHERE name IN ('client', 'creator');

-- Supprimer les rôles sans slug (sauf les essentiels)
DELETE FROM roles 
WHERE slug IS NULL 
  AND name NOT IN ('Super Administrateur', 'Administrateur', 'Staff', 'Créateur', 'Client');

-- ============================================
-- 4. VÉRIFICATION FINALE
-- ============================================
SELECT 'Rôles après nettoyage:' as info;
SELECT id, name, slug, description FROM roles ORDER BY name;

SELECT 'Vérification doublons:' as info;
SELECT name, COUNT(*) as count 
FROM roles 
GROUP BY name 
HAVING count > 1;

SELECT 'Vérification slugs manquants:' as info;
SELECT * FROM roles WHERE slug IS NULL;
```

---

## ⚠️ PRÉCAUTIONS

1. **Faire une sauvegarde** avant d'exécuter les suppressions
2. **Vérifier** que tous les utilisateurs ont été migrés
3. **Tester** sur un environnement de développement d'abord
4. **Vérifier** qu'il n'y a plus de doublons après nettoyage

---

## ✅ RÉSULTAT ATTENDU

Après nettoyage, la table `roles` doit contenir uniquement :
- `id=1`, `name='Super Administrateur'`, `slug='super_admin'`
- `id=2`, `name='Administrateur'`, `slug='admin'`
- `id=3`, `name='Staff'`, `slug='staff'`
- `id=4`, `name='Créateur'`, `slug='createur'`
- `id=5`, `name='Client'`, `slug='client'`

**Tous les rôles doivent avoir un `slug` non NULL.**

---

**Document créé le :** 28 novembre 2025

