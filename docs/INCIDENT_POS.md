# INCIDENT POS — PROCÉDURE CAISSE BLOQUÉE

## 🚨 Scénario
- Session POS ouverte
- Ventes effectuées
- Queue Redis indisponible ou bloquée
- Impossible de clôturer immédiatement

---

## ⚠️ Règle d'or

❌ **NE JAMAIS** forcer une écriture comptable manuelle  
❌ **NE JAMAIS** modifier la base directement  
✅ **TOUJOURS** suivre cette procédure

---

## 📋 Procédure Opérateur (Terrain)

### 1. Stopper les ventes
- ✅ Désactiver l'interface POS
- ✅ Informer le responsable magasin
- ✅ Afficher message "Caisse temporairement fermée"

### 2. Vérifier l'état système
```bash
# Vérifier queue POS
php artisan queue:monitor pos

# Lister jobs échoués
php artisan queue:failed

# Vérifier Redis
redis-cli ping
```

### 3. Si la queue est indisponible

**Actions immédiates:**
- ✅ Laisser la session en statut `open`
- ✅ Noter sur papier:
  - Heure de l'incident
  - Cash physique en caisse
  - Nom du responsable
  - Dernière vente enregistrée

**Ne PAS:**
- ❌ Forcer la clôture
- ❌ Modifier manuellement la DB
- ❌ Créer une nouvelle session

### 4. Rétablissement système

```bash
# Redémarrer les workers queue
php artisan queue:restart

# Relancer les jobs échoués
php artisan queue:retry all

# Vérifier que les workers tournent
ps aux | grep queue:work
```

### 5. Clôture différée

#### 🔐 Autorité de clôture après incident

**RÈGLE CRITIQUE:**
Après un incident technique:
- ❌ Le caissier initial **NE PEUT PAS** clôturer seul
- ✅ La clôture **DOIT** être effectuée par:
  - Le responsable magasin OU
  - Un utilisateur avec rôle `pos_supervisor` OU
  - Un membre de l'équipe technique

**Traçabilité obligatoire:**
Toute clôture post-incident DOIT inclure dans `notes`:
```
[INCIDENT] <cause technique> <date heure> — clôture différée validée par <rôle> #<user_id>
```

**Exemple:**
```
[INCIDENT] Redis down 2026-01-06 14:32 — clôture différée validée par supervisor #12
```

#### Procédure de clôture

Une fois le système rétabli ET l'autorisation obtenue:

1. ✅ Reprendre la clôture via l'interface POS normale
2. ✅ **Ajouter note d'incident** (format ci-dessus)
3. ✅ Vérifier génération du Z-report
4. ✅ Vérifier création du `FinancialIntent`
5. ✅ Comparer cash physique vs expected_cash

---

## 🔍 Vérifications post-incident

### Vérifier intent créé
```sql
SELECT * FROM financial_intents
WHERE reference_type = 'pos_session'
AND reference_id = :session_id;
```
**Résultat attendu:** 1 ligne avec `status = 'committed'`

### Vérifier écriture comptable unique
```sql
SELECT intent_id, COUNT(*) as count
FROM accounting_entries
WHERE intent_id IS NOT NULL
GROUP BY intent_id
HAVING count > 1;
```
**Résultat attendu:** 0 ligne (aucun doublon)

### Vérifier cohérence temporelle (CRITIQUE)
```sql
-- Vérifier que l'écriture comptable est APRÈS la clôture
SELECT 
    ps.id AS session_id,
    ps.closed_at,
    ae.created_at AS accounting_created_at,
    TIMESTAMPDIFF(SECOND, ps.closed_at, ae.created_at) AS delay_seconds
FROM pos_sessions ps
JOIN financial_intents fi 
  ON fi.reference_type = 'pos_session'
 AND fi.reference_id = ps.id
JOIN accounting_entries ae
  ON ae.intent_id = fi.id
WHERE ae.created_at < ps.closed_at;
```
**Résultat attendu:** 0 ligne

⚠️ **Si cette requête retourne des lignes:** FRAUDE ou BUG CRITIQUE
- Suspendre immédiatement toutes les caisses
- Escalade niveau direction
- Audit forensique obligatoire

### Vérifier cash movements
```sql
SELECT type, direction, amount, reason
FROM pos_cash_movements
WHERE session_id = :session_id
ORDER BY created_at;
```
**Résultat attendu:** Tous les mouvements présents (opening, sales, closing)

### Vérifier trace incident en base
```sql
-- Vérifier que les sessions post-incident sont marquées
SELECT id, closed_at, notes
FROM pos_sessions
WHERE status = 'closed'
AND closed_at > :incident_start_time
AND (notes IS NULL OR notes NOT LIKE '%[INCIDENT]%');
```
**Résultat attendu:** 0 ligne (toutes les sessions post-incident doivent avoir `[INCIDENT]` dans notes)

---

## 📐 Règle d'audit incident

### Convention structurelle OBLIGATOIRE

Toute session clôturée après incident **DOIT** contenir dans `pos_sessions.notes`:

1. ✅ Le mot-clé `[INCIDENT]` (en majuscules)
2. ✅ La cause technique précise
3. ✅ La date et heure de l'incident
4. ✅ Le rôle du validateur
5. ✅ L'ID du validateur

**Format strict:**
```
[INCIDENT] <cause> <YYYY-MM-DD HH:MM> — clôture différée validée par <role> #<user_id>
```

**Exemples valides:**
```
[INCIDENT] Redis down 2026-01-06 14:32 — clôture différée validée par supervisor #12
[INCIDENT] Queue worker crash 2026-01-06 09:15 — clôture différée validée par tech_lead #5
[INCIDENT] DB lock timeout 2026-01-06 16:45 — clôture différée validée par manager #8
```

**Exemple invalide (REJETÉ en audit):**
```
Problème technique résolu
```

### Vérification automatique

Cette convention permet:
- ✅ Audit automatisé via SQL
- ✅ Traçabilité légale
- ✅ Responsabilité claire
- ✅ Timeline reconstructible

---

## 📞 Escalade

### Si anomalie détectée après rétablissement:

1. ✅ **Suspendre immédiatement la caisse**
2. ✅ **Contacter support technique**
3. ✅ **Joindre les logs:**
   - `storage/logs/pos.log`
   - `storage/logs/accounting.log`
   - `storage/logs/laravel.log`
4. ✅ **Fournir:**
   - Session ID
   - Machine ID
   - Heure de l'incident
   - Cash physique compté

### Contacts support
- **Technique:** [email/phone]
- **Finance:** [email/phone]
- **Urgence:** [phone]

---

## 🎯 Effet garanti

- ✅ Le terrain **ne panique pas**
- ✅ La finance **reste vraie**
- ✅ L'audit **passe**
- ✅ Aucune perte de données

---

## 📚 Scénarios courants

### Scénario A: Redis down
**Symptôme:** Queue ne traite plus les jobs  
**Action:** Redémarrer Redis + queue:restart  
**Durée:** 2-5 minutes

### Scénario B: Worker crash
**Symptôme:** Jobs en "processing" mais rien ne bouge  
**Action:** queue:restart + retry failed jobs  
**Durée:** 1-2 minutes

### Scénario C: DB lock timeout
**Symptôme:** "Lock wait timeout exceeded"  
**Action:** Attendre 30s + retry  
**Durée:** < 1 minute

---

## ✅ Checklist post-résolution

### Technique
- [ ] Queue fonctionne (`queue:monitor`)
- [ ] Aucun job failed (`queue:failed`)
- [ ] Intent créé et committed
- [ ] Écriture comptable unique
- [ ] **Cohérence temporelle vérifiée** (accounting APRÈS closure)
- [ ] Z-report généré
- [ ] Cash difference expliqué
- [ ] Logs archivés

### Gouvernance
- [ ] **Autorisation supervisor obtenue**
- [ ] **Note `[INCIDENT]` ajoutée** (format strict)
- [ ] Incident documenté dans registre magasin
- [ ] Responsable informé
- [ ] Rapport incident rédigé (si > 30min)

---

## 📞 CONTACTS ESCALADE — PRODUCTION

| Rôle | Nom | Email | Téléphone | Disponibilité |
|------|-----|-------|-----------|--------------| | **Responsable Magasin** | RACINE BY GANDA | contact@racinebyganda.com | +242 06 XXX XX XX | Heures ouverture |
| **Lead Dev** | NIKA DIGITAL HUB | nikadigitalhub1@gmail.com | +242 06 832 52 86 | 24/7 |
| **DBA** | NIKA DIGITAL HUB | nikadigitalhub1@gmail.com | +242 06 832 52 86 | 24/7 si critique |
| **Escalade** | NIKA DIGITAL HUB (CTO) | nikadigitalhub1@gmail.com | +242 06 832 52 86 | 24/7 escalade |

**✅ CONTACTS VALIDÉS POUR PRODUCTION**

---

**Version:** 1.0.0  
**Dernière mise à jour:** 2026-01-06  
**Propriétaire:** Équipe Technique RACINE BY GANDA
