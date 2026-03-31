# PROCÉDURE D'INCIDENT FINANCE
## RACINE BY GANDA - Finance Module

---

## 🚨 SYMPTÔMES D'ALERTE

| Symptôme | Sévérité | Action immédiate |
|----------|----------|------------------|
| Doublon `AccountingEntry` détecté | 🔴 CRITIQUE | STOP queue workers |
| Collision count > 10/heure | 🟠 HAUTE | Investigation |
| Écriture non équilibrée | 🔴 CRITIQUE | Bloquer ventes |
| Log `ACCOUNTING_IDEMPOTENCE_COLLISION` récurrent | 🟡 MOYENNE | Analyser |

---

## 🔧 PROCÉDURE DE RÉSOLUTION

### Étape 1: Isolation (max 5 min)
```bash
# Stopper les workers queue
php artisan queue:restart

# Si critique: mode maintenance
php artisan down --secret=racine-emergency-2026
```

### Étape 2: Diagnostic (max 10 min)
```sql
-- Détecter doublons
SELECT reference_type, reference_id, COUNT(*) 
FROM accounting_entries 
WHERE deleted_at IS NULL 
GROUP BY reference_type, reference_id 
HAVING COUNT(*) > 1;
```

### Étape 3: Correction
```sql
-- Identifier entrées à soft-delete (garder oldest posted)
-- Utiliser scripts/detect_accounting_duplicates.sql
```

### Étape 4: Validation
```bash
php artisan test --filter=PaymentAccountingIdempotenceTest
```

### Étape 5: Reprise
```bash
php artisan up
php artisan queue:work --queue=high,default
```

---

## 📞 CONTACTS ESCALADE — PRODUCTION

| Rôle | Nom | Email | Téléphone | Disponibilité |
|------|-----|-------|-----------|--------------| | **Lead Dev** | NIKA DIGITAL HUB | nikadigitalhub1@gmail.com | +242 06 832 52 86 | 24/7 si critique |
| **DBA** | NIKA DIGITAL HUB | nikadigitalhub1@gmail.com | +242 06 832 52 86 | 24/7 si critique |
| **Product Manager** | NIKA DIGITAL HUB | nikadigitalhub1@gmail.com | +242 06 832 52 86 | Heures bureau |
| **Escalade** | NIKA DIGITAL HUB (CTO) | nikadigitalhub1@gmail.com | +242 06 832 52 86 | 24/7 escalade |

**✅ CONTACTS VALIDÉS POUR PRODUCTION**

---

## 🔙 ROLLBACK MIGRATION

```bash
# Si la migration UNIQUE cause problème
php artisan migrate:rollback --path=database/migrations/2026_01_05_224500_add_unique_constraint_accounting_entries_reference.php
```

---

**Document créé**: 2026-01-05  
**Validé par**: [À compléter]
