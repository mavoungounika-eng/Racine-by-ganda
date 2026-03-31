# RACINE BY GANDA - Architecture Décisionnelle

## Version: 2.0 | Date: 2026-01-05

---

## 🏛️ CONTRATS ARCHITECTURAUX CRITIQUES

### 📜 CONTRAT #1: Intégrité Financière (OBLIGATOIRE)

> [!CAUTION]
> **Aucune écriture comptable sans référence métier unique et verrouillée.**

#### Règles impératives:

1. **Unicité absolue**: Chaque `AccountingEntry` doit avoir un couple `(reference_type, reference_id)` unique
   - Garanti par contrainte DB: `UNIQUE(reference_type, reference_id)`
   - Vérification applicative AVANT insertion

2. **Idempotence obligatoire**: Tout listener créant une écriture comptable DOIT:
   - Vérifier existence via `EXISTS` query
   - Retourner silencieusement si déjà existant
   - Logger collision via `AccountingIdempotenceService`

3. **Immutabilité postée**: Une écriture `is_posted = true` ne peut JAMAIS être modifiée
   - Exception: soft-delete avec contre-passation

4. **Traçabilité**: Chaque collision doit être loggée avec:
   - `reference_type`, `reference_id`
   - Listener source
   - ID écriture existante

#### Listeners concernés:
- `PaymentRecordedListener` ✅
- `CreatorPayoutListener` ✅
- [Tout futur listener finance]

#### Évolution planifiée:
- Migration vers **Intent-Based Architecture** (Sprint +2)
- Tout nouveau flux finance passera par `financial_intents`

---

### 📜 CONTRAT #2: Isolation des Modules

| Module | Peut écrire dans | Ne peut pas écrire dans |
|--------|------------------|------------------------|
| Accounting | `accounting_*` | `orders`, `payments` |
| ERP | `erp_*` | `accounting_*` |
| Payments | `payments`, `orders` | `accounting_*` |

#### Communication inter-modules:
- Via **Events uniquement**
- Jamais d'appel direct de Service à Service entre modules

---

### 📜 CONTRAT #3: Queue Retry Safety

Tout job `ShouldQueue` touchant aux finances DOIT:
1. Implémenter `ShouldBeUnique`
2. Utiliser `lockForUpdate()` sur les entités
3. Vérifier état avant action

---

## 🔒 FILETS DE SÉCURITÉ ACTIFS

| Protection | Niveau | Mécanisme |
|------------|--------|-----------|
| Double écriture | 🔴 DB | UNIQUE constraint |
| Double écriture | 🟡 App | EXISTS check |
| Modification posted | 🔴 Model | `booted()` guard |
| Équilibre D/C | 🔴 DB | CHECK constraint |
| Retry infini | 🟡 App | `WebhookRequeueGuard` |

---

## 📂 STRUCTURE CRITIQUE

```
modules/
└── Accounting/
    ├── Events/           # Événements métier
    ├── Listeners/        # Consommateurs idempotents
    ├── Models/           # Entités avec guards
    └── Services/
        └── LedgerService.php   # Point unique création écritures

app/
└── Services/
    └── Financial/
        └── AccountingIdempotenceService.php  # Observabilité
```

---

## ⚠️ INTERDICTIONS

❌ Créer une `AccountingEntry` sans passer par `LedgerService`  
❌ Ajouter un listener finance sans guard idempotence  
❌ Modifier une écriture postée  
❌ Supprimer physiquement une écriture  

---

## 📋 RÉVISION

Ce document doit être revu à chaque:
- Ajout de module finance
- Modification du flux comptable
- Incident production finance

**Dernière revue**: 2026-01-05  
**Prochaine revue obligatoire**: 2026-02-05
