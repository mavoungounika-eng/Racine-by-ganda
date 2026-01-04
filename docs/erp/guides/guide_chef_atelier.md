# GUIDE CHEF D'ATELIER
## ERP RACINE BY GANDA - Manuel Utilisateur

> **Rôle :** Chef d'Atelier
> **Version :** 1.0
> **Date :** 2026-01-04

---

# 🎯 VOTRE RÔLE

Vous êtes le **garant de la cohérence production**.

**Vos responsabilités :**
- Créer et planifier les OFs
- Superviser la production
- Valider les outputs (vérification physique)
- Interpréter et résoudre les alertes
- Gérer les exceptions

**Votre impact :**
- Vos décisions → Flux production
- Vos validations → Vérité stock & coûts
- Votre réactivité → Respect délais

---

# 💻 ACCÈS SYSTÈME

## Connexion Desktop

1. Ouvrir navigateur
2. URL : `https://erp.racinebyganda.com/admin`
3. Login : [Votre identifiant]
4. Password : [Votre mot de passe]
5. Section : "Production"

---

# 📋 TÂCHE 1 : CRÉER UN OF

## Quand ?
Dès réception d'une commande ou planification production

## Comment ?

### Étape 1 : Accéder à la création
```
Menu Production → "Créer OF"
```

### Étape 2 : Sélectionner produit
```
Rechercher produit : [Chemise Bleu]
Sélectionner variante : [Toutes tailles]
```

**💡 Info affichée :**
- BOM (nomenclature) actuelle
- Stock MP disponible
- Temps standard par opération

### Étape 3 : Définir quantité cible
```
Quantité à produire : [____] pcs
```

**⚠️ Vérification automatique :**
```
✅ Stock tissu suffisant : 125m disponible (besoin 75m)
❌ Stock boutons insuffisant : 200 pcs disponibles (besoin 250 pcs)
```

**Si stock insuffisant :**
1. Commander matière manquante
2. OU réduire quantité OF
3. OU planifier OF plus tard

### Étape 4 : Définir dates
```
Date début planifiée : [____]
Date limite (deadline) : [____]
```

**💡 Système calcule :**
- Charge atelier (heures nécessaires)
- Taux occupation (% capacité)

**⚠️ Si taux > 100% :**
→ Alerte "Capacité dépassée" → Replanifier

### Étape 5 : Définir opérations (gamme)
```
Opération 1 : COUPE (120 min standard)
Opération 2 : COUTURE (180 min standard)
Opération 3 : FINITION (60 min standard)
```

**💡 Conseil :** Utiliser modèles pré-définis par produit

### Étape 6 : Valider création
```
Bouton [CRÉER OF]
```

**✅ Confirmation :**
```
OF-26-001 créé
Status : DRAFT
BOM snapshot : Figé ✅
```

**📌 IMPORTANT :** BOM snapshot = Immuable (Règle R8)

---

## 🔄 CYCLE DE VIE OF

```
DRAFT → PLANNED → RELEASED → IN_PROGRESS → COMPLETED
```

**Actions possibles par status :**

| Status | Actions |
|:-------|:--------|
| DRAFT | Modifier, Supprimer |
| PLANNED | Démarrer, Modifier, Supprimer |
| RELEASED | Démarrer |
| IN_PROGRESS | Logger matière/temps, Clôturer |
| COMPLETED | ❌ Aucune (Immuable - R6, R7) |

---

# ▶️ TÂCHE 2 : DÉMARRER PRODUCTION

## Quand ?
Quand atelier prêt à commencer

## Comment ?

### Étape 1 : Sélectionner OF
```
Liste OFs → Filtrer "PLANNED" → Sélectionner OF
```

### Étape 2 : Vérifier prérequis
```
✅ Matière disponible
✅ Opérateurs disponibles
✅ Machines fonctionnelles
```

### Étape 3 : Démarrer
```
Bouton [DÉMARRER PRODUCTION]
```

**✅ Confirmation :**
```
OF-26-001
Status : IN_PROGRESS
Démarré le : 2026-01-04 08:30
```

**📌 À partir de maintenant :**
- Opérateurs peuvent logger matière/temps
- OF apparaît sur tablettes atelier

---

# 📊 TÂCHE 3 : SUPERVISER PRODUCTION

## Dashboard Temps Réel

### Vue d'ensemble
```
┌─────────────────────────────────────────────┐
│ OFs EN COURS (3)                            │
├─────────────────────────────────────────────┤
│ OF-26-001 | Chemise Bleu | 80% | ⚠️ Retard │
│ OF-26-002 | Pantalon Noir | 45% | ✅ OK    │
│ OF-26-003 | Robe Rouge | 10% | ✅ OK       │
└─────────────────────────────────────────────┘
```

### Détail OF
```
OF-26-001 : Chemise Bleu (50 pcs)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Progression : ████████████░░░░░ 80%

Matière consommée :
✅ Tissu : 72m / 75m (96%)
✅ Fil : 15 bobines / 15 (100%)
⚠️ Boutons : 180 / 200 (90%)

Temps passé :
✅ COUPE : 125 min / 120 min (+4%)
⚠️ COUTURE : 210 min / 180 min (+17%)
⏳ FINITION : En attente

Deadline : 2026-01-06 (dans 2 jours) ⚠️
```

---

## 🚨 ALERTES À SURVEILLER

### Alerte 1 : Stock Insuffisant (R12)
```
❌ OF-26-004 : Stock tissu insuffisant
Demandé : 150m | Disponible : 75m
```

**Actions :**
1. Vérifier stock physique (inventaire rapide)
2. Si confirmé → Commander matière
3. OU réduire quantité OF
4. OU annuler OF

**⚠️ Production BLOQUÉE tant que stock insuffisant**

---

### Alerte 2 : Retard Deadline
```
⚠️ OF-26-001 : Deadline dans 2 jours
Progression : 80% (risque retard)
```

**Actions :**
1. Analyser cause retard (temps opération > standard ?)
2. Affecter plus d'opérateurs
3. Prioriser cet OF
4. OU négocier nouveau délai avec client

---

### Alerte 3 : Variance Temps Excessive
```
⚠️ OF-26-001 - COUTURE : +17% vs standard
Opérateur : Jean Dupont
```

**Actions :**
1. Discuter avec opérateur (problème ?)
2. Vérifier machine (panne ?)
3. Vérifier matière (difficile ?)
4. Formation si variance récurrente

---

# ✅ TÂCHE 4 : VALIDER CLÔTURE OF

## Quand ?
Quand opérateurs ont terminé production

## Comment ?

### Étape 1 : Vérification physique (OBLIGATOIRE)
```
Compter physiquement :
Taille S : 1er choix [__] | 2nd choix [__] | Rebut [__]
Taille M : 1er choix [__] | 2nd choix [__] | Rebut [__]
Taille L : 1er choix [__] | 2nd choix [__] | Rebut [__]
```

**💡 Conseils :**
- Séparer physiquement les 3 catégories
- Compter 2 fois
- Vérifier cohérence vs matière consommée

**Exemple cohérence :**
```
Tissu consommé : 75m
Outputs : 48 pcs
Yield : 0.64 pcs/m ✅ (normal pour chemise)

Si yield anormal (ex: 0.3 pcs/m) → Enquêter
```

### Étape 2 : Accéder à la clôture
```
OF actif → Bouton [CLÔTURER]
```

### Étape 3 : Saisir outputs
```
Taille S :
- 1er choix : 15 pcs
- 2nd choix : 1 pcs
- Rebut : 0 pcs

Taille M :
- 1er choix : 20 pcs
- 2nd choix : 0 pcs
- Rebut : 1 pcs

Taille L :
- 1er choix : 13 pcs
- 2nd choix : 0 pcs
- Rebut : 0 pcs
```

### Étape 4 : Vérifications automatiques

**Système vérifie (Règles R1-R5) :**
```
✅ R1 : Matière loggée
✅ R2 : Temps loggés
✅ R3 : Outputs saisis
✅ R4 : Qty totale > 0
✅ R5 : Status = IN_PROGRESS
```

**Si une règle échoue :**
```
❌ Impossible de clôturer : Pas de temps loggés
→ Demander aux opérateurs de logger temps
```

### Étape 5 : Validation finale
```
Bouton [VALIDER CLÔTURE]
```

**✅ Confirmation :**
```
OF-26-001 CLÔTURÉ
Status : COMPLETED
Coût réel : 125,000 XAF
Stock PF mis à jour : +48 pcs
```

**📌 IMPORTANT :** OF completed = IMMUABLE (R6, R7)

---

## ❌ ERREURS FRÉQUENTES

### Erreur 1 : "Données manquantes"
**Message :** "Impossible de clôturer : Pas de matière loggée"

**Cause :** Opérateurs ont oublié de scanner matière

**Solution :**
1. Vérifier avec opérateurs
2. Scanner matière maintenant (si possible)
3. OU créer log manuellement (justification requise)

---

### Erreur 2 : "Output qty = 0"
**Message :** "Output avec quantité totale = 0 interdit"

**Cause :** Erreur saisie ou vraiment 0 pièces

**Solution :**
1. Vérifier comptage physique
2. Si vraiment 0 → Analyser cause (problème grave)
3. Créer rapport incident

---

### Erreur 3 : "OF déjà clôturé"
**Message :** "Cannot modify completed order"

**Cause :** Tentative modification OF completed (R6)

**Solution :**
❌ IMPOSSIBLE de modifier
✅ Si erreur → Créer NOUVEL OF pour correction
✅ Documenter dans notes

---

# 🔧 TÂCHE 5 : GÉRER EXCEPTIONS

## Exception 1 : Erreur Saisie Matière

**Situation :** Opérateur a saisi 150m au lieu de 15m

**Procédure :**
1. ❌ NE PAS modifier le log
2. ✅ Créer mouvement ADJUSTMENT (-135m)
3. ✅ Justifier : "Correction erreur saisie OF-26-001"
4. ✅ Former opérateur (éviter récidive)

**Menu :** Stock → Ajustements → Créer

---

## Exception 2 : OF Clôturé par Erreur

**Situation :** OF clôturé alors que production pas terminée

**Procédure :**
1. ❌ IMPOSSIBLE de rouvrir (R6)
2. ✅ Créer NOUVEL OF pour quantité restante
3. ✅ Lier dans notes : "Suite OF-26-001 (clôturé par erreur)"
4. ✅ Analyser cause (formation ? process ?)

---

## Exception 3 : Matière Défectueuse

**Situation :** Rouleau tissu défectueux (taches, déchirures)

**Procédure :**
1. Arrêter utilisation
2. Créer mouvement WASTE (rebut)
3. Justifier : "Tissu défectueux - Rouleau R042"
4. Contacter fournisseur (retour/remboursement)
5. Utiliser nouveau rouleau

---

# 📈 TÂCHE 6 : ANALYSER PERFORMANCE

## KPIs à Surveiller (Hebdomadaire)

### 1. Taux de Respect Délais
```
OFs livrés à temps : 18 / 20 = 90% ✅
```

**Si < 85% :** Analyser causes retards

### 2. Taux de Non-Qualité
```
Rebut : 5 pcs / 200 pcs = 2.5% ✅
```

**Si > 5% :** Formation opérateurs / Révision process

### 3. Variance Temps
```
Temps réel : 450 min
Temps standard : 420 min
Variance : +7% ✅
```

**Si > 15% :** Mettre à jour temps standard OU formation

### 4. Yield Matière
```
Tissu consommé : 75m
Pièces produites : 48
Yield : 0.64 pcs/m ✅
```

**Si yield anormal :** Optimiser patron coupe

---

# ✅ CHECKLIST QUOTIDIENNE

**Matin (8h) :**
- [ ] Consulter dashboard OFs en cours
- [ ] Vérifier alertes (R12, retards)
- [ ] Planifier journée (priorités)
- [ ] Brief équipe

**Midi (12h) :**
- [ ] Vérifier progression OFs
- [ ] Résoudre blocages
- [ ] Ajuster planning si nécessaire

**Soir (17h) :**
- [ ] Valider clôtures OF (si terminés)
- [ ] Préparer planning lendemain
- [ ] Rapport direction (si demandé)

---

# 🎓 FORMATION

## Durée
- Formation initiale : 1 journée
- Pratique supervisée : 2 semaines
- Recyclage : Semestriel

## Contenu
- Créer OF (pratique)
- Superviser production (dashboard)
- Valider clôture (vérification physique)
- Gérer exceptions (cas réels)
- Analyser KPIs

---

# 📞 CONTACTS

**Admin ERP :** [Nom] - [Téléphone]
**Direction Production :** [Nom] - [Téléphone]
**Support Technique :** [Nom] - [Téléphone]

---

# ❓ FAQ

**Q : Puis-je modifier un OF completed ?**
R : ❌ NON (R6). Créer nouvel OF si nécessaire.

**Q : Que faire si stock insuffisant (R12) ?**
R : Commander matière OU réduire quantité OF OU annuler.

**Q : Puis-je forcer une clôture sans temps loggés ?**
R : ❌ NON (R2). Système bloque. Logger temps d'abord.

**Q : Comment corriger une erreur de saisie ?**
R : Créer mouvement ADJUSTMENT (tracé). Jamais modifier directement.

**Q : Que faire si variance temps > 20% ?**
R : Analyser cause (machine ? opérateur ? matière ?). Former si récurrent.

---

**FIN DU GUIDE**

*Vous êtes le garant de la cohérence production.*
*En cas de doute, consulter la Bible ERP ou contacter Admin.*
