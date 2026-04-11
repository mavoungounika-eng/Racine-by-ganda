# PILOTE TERRAIN POS — JOURNAL D'EXÉCUTION

**Date:** 2026-01-06 23:20 UTC  
**Machine ID:** POS-PILOT-001  
**Auditeur:** Cabinet Senior

---

## PHASE 1: OUVERTURE SESSION (T+00:00)

**Acteur:** Alice (Caissier, User ID: 1)  
**Action:** Ouverture session avec 50,000 XAF

**Commande simulée:**
```php
$session = app(\App\Services\Pos\PosSessionService::class)->openSession(
    'POS-PILOT-001',
    1, // Alice
    50000.00
);
```

**Vérifications attendues:**
- [ ] Session créée avec status='open'
- [ ] opened_by = 1 (Alice)
- [ ] opening_cash = 50,000 XAF
- [ ] PosCashMovement créé (type=opening, direction=in)

---

## PHASE 2: VENTES NORMALES (T+00:05 - T+02:00)

### Vente #1 (T+00:10) - CASH
- Montant: 5,000 XAF
- Méthode: cash
- Statut attendu: pending

### Vente #2 (T+00:25) - CARTE
- Montant: 15,000 XAF
- Méthode: card
- Statut attendu: confirmed (après TPE)
- Intent attendu: pos_card_payment

### Vente #3 (T+01:15) - MOBILE
- Montant: 8,000 XAF
- Méthode: mobile_money
- Statut attendu: confirmed (après webhook)
- Intent attendu: pos_mobile_payment

### Ventes #4-7 (T+01:45)
- 3x cash: 12,000 XAF total
- 1x carte: 20,000 XAF

**Total attendu:**
- Cash: 17,000 XAF (pending)
- Carte: 35,000 XAF (confirmed)
- Mobile: 8,000 XAF (confirmed)
- **TOTAL: 60,000 XAF**

---

## PHASE 3: INCIDENT SIMULÉ (T+02:00 - T+02:15)

**T+02:00** - Charlie arrête Redis
**T+02:02** - Alice tente clôture → ÉCHEC
**T+02:05** - Bob (supervisor) consulte INCIDENT_POS.md
**T+02:10** - Charlie redémarre Redis
**T+02:12** - Vérification queue opérationnelle

**Critères de succès:**
- [ ] Session reste status='open'
- [ ] Aucune perte de données
- [ ] Procédure incident suivie

---

## PHASE 4: CLÔTURE DIFFÉRÉE (T+02:15 - T+02:30)

**Acteur:** Bob (Supervisor, User ID: 5)

**Cash compté:** 67,000 XAF  
**Expected cash:** 50,000 + 17,000 = 67,000 XAF  
**Difference:** 0 XAF

**Note obligatoire:**
```
[INCIDENT] Redis down 2026-01-06 23:20 — clôture différée validée par supervisor #5
```

**Vérifications SQL critiques:**

```sql
-- 1. Session fermée
SELECT status, closed_by, notes 
FROM pos_sessions 
WHERE machine_id = 'POS-PILOT-001';
-- Attendu: status='closed', closed_by=5, notes contient '[INCIDENT]'

-- 2. Intent créé
SELECT id, intent_type, status, amount
FROM financial_intents
WHERE reference_type = 'pos_session'
AND reference_id = (SELECT id FROM pos_sessions WHERE machine_id = 'POS-PILOT-001');
-- Attendu: 1 ligne, type='pos_cash_settlement', status='committed', amount=17000

-- 3. Écriture comptable unique
SELECT COUNT(*) as count
FROM accounting_entries ae
JOIN financial_intents fi ON ae.intent_id = fi.id
WHERE fi.reference_type = 'pos_session';
-- Attendu: count = 1

-- 4. Cohérence temporelle
SELECT 
    ps.closed_at,
    ae.created_at as accounting_created_at,
    CASE WHEN ae.created_at > ps.closed_at THEN 'OK' ELSE 'VIOLATION' END as temporal_check
FROM pos_sessions ps
JOIN financial_intents fi ON fi.reference_id = ps.id
JOIN accounting_entries ae ON ae.intent_id = fi.id
WHERE ps.machine_id = 'POS-PILOT-001';
-- Attendu: temporal_check = 'OK'

-- 5. Tous paiements cash confirmés
SELECT COUNT(*) as pending_cash
FROM pos_payments pp
JOIN pos_sales ps ON pp.pos_sale_id = ps.id
WHERE ps.session_id = (SELECT id FROM pos_sessions WHERE machine_id = 'POS-PILOT-001')
AND pp.method = 'cash'
AND pp.status = 'pending';
-- Attendu: pending_cash = 0
```

---

## PHASE 5: Z-REPORT & VALIDATION

**Génération Z-Report**
**Signatures requises:**
- [ ] Responsable magasin (Bob)
- [ ] Équipe technique (Charlie)
- [ ] Finance

---

## INVARIANTS À VÉRIFIER

1. [ ] Aucune vente sans session ouverte
2. [ ] Aucun cash confirmé avant clôture
3. [ ] POS ≠ autorité comptable
4. [ ] Session a un responsable
5. [ ] Anomalie traçable
6. [ ] Offline-safe (idempotence)
7. [ ] Fait terrain ≠ écriture comptable

---

## VERDICT

**Statut:** EN COURS  
**Échecs détectés:** 0  
**Violations:** 0

**Verdict final:** [À COMPLÉTER]
