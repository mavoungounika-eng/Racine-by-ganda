# Invariants Financiers

## RACINE BY GANDA - Module Finance
**Version**: 1.0  
**Date**: 2026-01-06  

---

## Définition

Un **invariant financier** est une propriété du système qui doit TOUJOURS être vraie, quelles que soient les conditions (concurrence, retry, crash, etc.).

---

## Liste des Invariants

### INV-1: Unicité des Écritures par Référence

> **Une référence métier (order, payout) ne peut avoir qu'UNE SEULE écriture comptable.**

| Mécanisme | Niveau | Fichier |
|-----------|--------|---------|
| `UNIQUE(reference_type, reference_id)` | DB | Migration |
| EXISTS check avant création | App | Listeners |
| Intent idempotency_key | App | FinancialIntentService |

**Preuve technique**:
```sql
-- Contrainte DB inviolable
ALTER TABLE accounting_entries 
ADD CONSTRAINT uq_accounting_entries_reference 
UNIQUE (reference_type, reference_id);
```

---

### INV-2: Équilibre Débit = Crédit

> **Toute écriture postée doit avoir total_debit = total_credit.**

| Mécanisme | Niveau | Fichier |
|-----------|--------|---------|
| `CHECK (is_posted = 0 OR total_debit = total_credit)` | DB | Migration |
| `validateBalance()` | App | LedgerService |

---

### INV-3: Immutabilité des Écritures Postées

> **Une écriture postée (is_posted = true) ne peut JAMAIS être modifiée.**

| Mécanisme | Niveau | Fichier |
|-----------|--------|---------|
| `booted()` updating guard | Model | AccountingEntry.php |
| `booted()` deleting guard | Model | AccountingEntry.php |

**Exception**: Soft-delete avec contre-passation via `reverseEntry()`.

---

### INV-4: Création Exclusive via LedgerService

> **Aucune création d'AccountingEntry hors LedgerService.**

| Mécanisme | Niveau | Fichier |
|-----------|--------|---------|
| `booted()` creating guard | Model | AccountingEntry.php |
| Container flag `ledger.creating.allowed` | App | LedgerService |
| `final class` | Architecture | LedgerService |

---

### INV-5: Exercice Ouvert Requis

> **Impossible de créer une écriture dans un exercice clôturé.**

| Mécanisme | Niveau | Fichier |
|-----------|--------|---------|
| `is_closed` check | App | LedgerService::createEntry() |

---

### INV-6: Traçabilité Complète

> **Toute écriture doit avoir: created_by, entry_date, reference (optionnel), entry_number unique.**

| Champ | Obligatoire | Source |
|-------|-------------|--------|
| `created_by` | ✅ | Auth::id() |
| `entry_date` | ✅ | Paramètre |
| `entry_number` | ✅ | Auto-généré |
| `posted_by` | Si posté | Auth::id() |
| `posted_at` | Si posté | now() |

---

### INV-7: Point d'Irréversibilité Clair

> **Le passage d'un intent à 'committed' est le point après lequel aucun retour n'est possible.**

| Status | Réversible | Action possible |
|--------|------------|-----------------|
| pending | ✅ | Annuler, modifier |
| processing | ⚠️ | Re-essayer |
| committed | ❌ | Contre-passation uniquement |
| reversed | ❌ | Aucune |
| failed | ✅ | Retry manuel |

---

## Validations par Test

| Invariant | Test PHPUnit |
|-----------|--------------|
| INV-1 | `PaymentAccountingIdempotenceTest` |
| INV-2 | `PaymentAccountingIntegrationTest::it_creates_balanced_entry` |
| INV-3 | `AccountingEntryTest::it_blocks_update_on_posted_entry` |
| INV-4 | `LedgerServiceArchitectureTest` |
| INV-5 | `LedgerServiceTest::it_rejects_closed_fiscal_year` |
| INV-6 | Implicite (fillable validation) |
| INV-7 | `IntentBasedAccountingTest` |

---

## Violations et Alertes

Toute violation suspectée déclenche:

1. **Log critique**: `ACCOUNTING_INVARIANT_VIOLATION`
2. **Alerte**: Email/Slack immédiat
3. **Action**: STOP queue workers si doublon
