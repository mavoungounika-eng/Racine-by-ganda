-- ==============================================================================
-- SCRIPT SQL: DÉTECTION DES DOUBLONS COMPTABLES
-- Objectif: Identifier les écritures comptables dupliquées avant migration UNIQUE
-- Date: 2026-01-05
-- ==============================================================================

-- 1. DÉTECTER LES DOUBLONS (reference_type, reference_id)
SELECT 
    reference_type,
    reference_id,
    COUNT(*) as duplicate_count,
    GROUP_CONCAT(id ORDER BY id) as entry_ids,
    GROUP_CONCAT(entry_number ORDER BY id) as entry_numbers,
    GROUP_CONCAT(is_posted ORDER BY id) as posted_statuses,
    MIN(created_at) as first_created,
    MAX(created_at) as last_created
FROM accounting_entries
WHERE reference_type IS NOT NULL 
  AND reference_id IS NOT NULL
GROUP BY reference_type, reference_id
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC, reference_type, reference_id;

-- 2. RAPPORT RÉSUMÉ DES DOUBLONS
SELECT 
    reference_type,
    COUNT(*) as total_duplicated_pairs,
    SUM(cnt - 1) as extra_entries_to_remove
FROM (
    SELECT 
        reference_type,
        reference_id,
        COUNT(*) as cnt
    FROM accounting_entries
    WHERE reference_type IS NOT NULL 
      AND reference_id IS NOT NULL
    GROUP BY reference_type, reference_id
    HAVING COUNT(*) > 1
) duplicates
GROUP BY reference_type;

-- 3. TOTAL GLOBAL
SELECT 
    (SELECT COUNT(*) FROM accounting_entries WHERE reference_type IS NOT NULL AND reference_id IS NOT NULL) as total_referenced_entries,
    (
        SELECT SUM(cnt - 1)
        FROM (
            SELECT COUNT(*) as cnt
            FROM accounting_entries
            WHERE reference_type IS NOT NULL 
              AND reference_id IS NOT NULL
            GROUP BY reference_type, reference_id
            HAVING COUNT(*) > 1
        ) d
    ) as total_duplicates_to_remove;

-- ==============================================================================
-- STRATÉGIE DE NETTOYAGE SÉCURISÉE
-- ==============================================================================

-- RÈGLE 1: Conserver l'écriture POSTÉE la plus ancienne
-- RÈGLE 2: Si aucune postée, conserver la plus ancienne (brouillon)
-- RÈGLE 3: Marquer les autres pour soft-delete (pas de suppression physique)

-- 4. IDENTIFIER LES ÉCRITURES À MARQUER COMME SUPPRIMÉES
-- (Cette requête retourne les IDs à supprimer, pas à conserver)
WITH ranked_entries AS (
    SELECT 
        id,
        reference_type,
        reference_id,
        is_posted,
        created_at,
        ROW_NUMBER() OVER (
            PARTITION BY reference_type, reference_id 
            ORDER BY is_posted DESC, created_at ASC, id ASC
        ) as rn
    FROM accounting_entries
    WHERE reference_type IS NOT NULL 
      AND reference_id IS NOT NULL
      AND deleted_at IS NULL
)
SELECT 
    id,
    reference_type,
    reference_id,
    is_posted,
    created_at
FROM ranked_entries
WHERE rn > 1;

-- 5. SOFT-DELETE DES DOUBLONS (EXÉCUTER AVEC PRÉCAUTION)
-- ⚠️ DÉSACTIVÉ PAR DÉFAUT - DÉCOMMENTER UNIQUEMENT APRÈS VALIDATION MANUELLE
/*
UPDATE accounting_entries
SET deleted_at = NOW()
WHERE id IN (
    SELECT id FROM (
        WITH ranked_entries AS (
            SELECT 
                id,
                reference_type,
                reference_id,
                ROW_NUMBER() OVER (
                    PARTITION BY reference_type, reference_id 
                    ORDER BY is_posted DESC, created_at ASC, id ASC
                ) as rn
            FROM accounting_entries
            WHERE reference_type IS NOT NULL 
              AND reference_id IS NOT NULL
              AND deleted_at IS NULL
        )
        SELECT id FROM ranked_entries WHERE rn > 1
    ) to_delete
);
*/

-- 6. VÉRIFICATION POST-NETTOYAGE
SELECT 
    reference_type,
    reference_id,
    COUNT(*) as count
FROM accounting_entries
WHERE reference_type IS NOT NULL 
  AND reference_id IS NOT NULL
  AND deleted_at IS NULL
GROUP BY reference_type, reference_id
HAVING COUNT(*) > 1;
-- Résultat attendu après nettoyage: 0 lignes
