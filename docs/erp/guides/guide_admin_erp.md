# GUIDE ADMIN ERP
## ERP RACINE BY GANDA - Manuel Utilisateur

> **Rôle :** Administrateur ERP
> **Version :** 1.0
> **Date :** 2026-01-04

---

# 🎯 VOTRE RÔLE

Vous êtes le **garant de la cohérence globale** du système.

**Vos responsabilités :**
- Initialiser le stock MP (première fois)
- Consulter données (LECTURE SEULE)
- Valider clôtures OF (vérification finale)
- Exporter données comptables
- Gérer utilisateurs et permissions

**Votre principe :** **JAMAIS CORRIGER, TOUJOURS VALIDER**

**⚠️ RÈGLE ABSOLUE :**
Vous ne modifiez JAMAIS les données calculées (coûts, stock, totaux).
Vous validez ou rejetez, mais ne corrigez pas.

---

# 💻 ACCÈS SYSTÈME

## Connexion
```
URL : https://erp.racinebyganda.com/admin
Login : [Admin]
Section : "Administration ERP"
```

---

# 📦 TÂCHE 1 : INITIALISER STOCK MP (Première Fois)

## Quand ?
- Au démarrage du système
- Après inventaire physique complet

## Comment ?

### Étape 1 : Préparer inventaire physique
```
Compter TOUT le stock MP :
- Rouleaux tissu (mètre par mètre)
- Bobines fil (unités)
- Boutons (unités)
- Accessoires (unités)
```

**💡 Conseil :** Impliquer chef d'atelier + 2 opérateurs

### Étape 2 : Accéder à l'initialisation
```
Menu Stock → "Initialisation Stock"
```

### Étape 3 : Créer mouvements INITIAL
```
Pour chaque matière :

Type : fabric
Référence : LIN-BLEU-R042
Quantité : 125.5 m
Coût unitaire : 3,500 XAF/m
Direction : IN
Source : INITIAL
Notes : "Inventaire initial 2026-01-04"
```

**⚠️ IMPORTANT :**
- Coût unitaire = Prix d'achat réel (facture fournisseur)
- Notes = Toujours mentionner "Inventaire initial + date"

### Étape 4 : Vérifier totaux
```
Stock Total Valorisé : 2,450,000 XAF
Détail :
- Tissus : 1,800,000 XAF
- Fils : 350,000 XAF
- Boutons : 200,000 XAF
- Accessoires : 100,000 XAF
```

---

# 📊 TÂCHE 2 : CONSULTER DONNÉES (Lecture Seule)

## Dashboard Global

### Vue Production
```
OFs Actifs : 12
OFs Complétés (mois) : 45
Taux respect délais : 88% ✅
Taux non-qualité : 3.2% ✅
```

### Vue Stock
```
Stock MP : 2,450,000 XAF
Stock PF : 1,200,000 XAF
Stock Mort (>90j) : 150,000 XAF ⚠️
```

### Vue Coûts
```
Coût moyen production : 12,500 XAF/pièce
Variance vs standard : +5.2%
Marge moyenne : 35%
```

---

## Rapports Disponibles

### Rapport 1 : Production Mensuelle
```
Période : Janvier 2026
OFs complétés : 45
Pièces produites : 2,340
Coût total : 29,250,000 XAF
```

### Rapport 2 : Variance Coûts
```
Produits avec variance > 10% :
- Chemise Bleu : +12% (analyser)
- Pantalon Noir : +8% (acceptable)
```

### Rapport 3 : Stock Mort
```
Matières sans mouvement > 90 jours :
- Tissu Rouge Bordeaux : 45m (liquidation)
- Boutons Dorés : 500 pcs (liquidation)
```

---

# ✅ TÂCHE 3 : VALIDER CLÔTURE OF

## Quand ?
Quand chef d'atelier demande validation finale

## Comment ?

### Étape 1 : Accéder à la validation
```
Menu Production → "OFs à Valider"
```

### Étape 2 : Vérifier cohérence
```
OF-26-001 : Chemise Bleu (50 pcs cible)

Matière consommée :
✅ Tissu : 75.5m (cohérent)
✅ Fil : 15 bobines (cohérent)
✅ Boutons : 200 pcs (cohérent)

Temps total :
✅ 450 min (vs 420 min standard = +7%) ✅

Outputs :
✅ 1er choix : 48 pcs (96% rendement) ✅
✅ 2nd choix : 1 pcs
✅ Rebut : 1 pcs

Coût réel : 125,000 XAF
Coût standard : 120,000 XAF
Variance : +4.2% ✅
```

### Étape 3 : Décision

**Si tout cohérent :**
```
Bouton [VALIDER CLÔTURE]
```

**Si incohérence détectée :**
```
Exemples incohérences :
- Yield anormal (0.3 pcs/m au lieu de 0.6)
- Variance > 20%
- Outputs > cible (suspect)

Action :
Bouton [REJETER] + Commentaire explicatif
→ Chef d'atelier doit vérifier et corriger
```

**⚠️ IMPORTANT :**
Vous ne corrigez PAS les données.
Vous validez ou rejetez.

---

# 📤 TÂCHE 4 : EXPORTER DONNÉES COMPTABLES

## Quand ?
- Fin de mois (clôture comptable)
- Sur demande expert-comptable

## Comment ?

### Étape 1 : Accéder aux exports
```
Menu Comptabilité → "Exports"
```

### Étape 2 : Sélectionner période
```
Période : Janvier 2026
Type : Écritures Production
Format : CSV
```

### Étape 3 : Générer export
```
Bouton [GÉNÉRER EXPORT]
```

**📄 Fichier généré :**
```csv
Date,Compte Débit,Compte Crédit,Montant,Libellé,Pièce
2026-01-04,601,31,75500,"Consommation tissu",OF-26-001
2026-01-04,33,601,75500,"Transfert MP → WIP",OF-26-001
2026-01-04,33,641,49500,"Main d'œuvre",OF-26-001
2026-01-04,35,33,125000,"Transfert WIP → PF",OF-26-001
```

### Étape 4 : Transmettre à comptabilité
```
Email → Expert-comptable
Objet : "Export ERP Production - Janvier 2026"
Pièce jointe : export_production_2026_01.csv
```

---

# 👥 TÂCHE 5 : GÉRER UTILISATEURS

## Créer Utilisateur

### Étape 1 : Accéder à la gestion
```
Menu Admin → "Utilisateurs"
```

### Étape 2 : Créer compte
```
Nom : Jean Dupont
Email : jean.dupont@racinebyganda.com
Rôle : Opérateur Atelier
Badge : [Scan badge physique]
```

### Étape 3 : Définir permissions
```
Rôle : Opérateur Atelier
Permissions :
✅ Scanner matière
✅ Logger temps
❌ Créer OF
❌ Valider clôture
❌ Modifier coûts
```

---

## Rôles & Permissions

| Rôle | Permissions |
|:-----|:------------|
| **Opérateur** | Scanner matière, Logger temps |
| **Chef Atelier** | Créer OF, Superviser, Valider outputs |
| **Contrôle Qualité** | Inspecter, Décider (approve/reject) |
| **Admin ERP** | Tout (lecture seule sur calculs) |

---

# 🔧 TÂCHE 6 : GÉRER AJUSTEMENTS STOCK

## Quand ?
- Après inventaire physique (écart détecté)
- Matière défectueuse (rebut)
- Correction erreur saisie

## Comment ?

### Étape 1 : Accéder aux ajustements
```
Menu Stock → "Ajustements"
```

### Étape 2 : Créer ajustement
```
Type : ADJUSTMENT
Matière : LIN-BLEU-R042
Quantité : -10 m (si diminution)
Direction : OUT
Justification : "Inventaire physique - Écart détecté"
```

**⚠️ RÈGLE :**
Toujours justifier (notes obligatoires).
Ajustement sans justification = Refusé.

---

# 📋 TÂCHE 7 : AUDIT MENSUEL

## Checklist Audit

### 1. Cohérence Stock Physique vs ERP
```
Inventaire physique :
Tissu LIN-BLEU : 125m physique vs 125.5m ERP
Écart : -0.5m (-0.4%) ✅ Acceptable

Si écart > 5% → Enquête approfondie
```

### 2. Variance Coûts
```
Produits avec variance > 10% :
- Chemise Bleu : +12%
  Cause : Temps couture excessif
  Action : Formation opérateur

- Pantalon Noir : +15%
  Cause : Tissu plus cher (fournisseur changé)
  Action : Mettre à jour BOM standard
```

### 3. Stock Mort
```
Matières > 90 jours sans mouvement :
- Tissu Rouge : 45m
  Action : Liquidation -30%
```

### 4. Taux Non-Qualité
```
Rebut global : 3.2% ✅
Par opération :
- COUPE : 1.5% ✅
- COUTURE : 4.8% ⚠️ (formation requise)
```

---

# ⚠️ SITUATIONS EXCEPTIONNELLES

## Situation 1 : Perte Données (Panne)

**Procédure :**
1. Vérifier backup automatique (quotidien)
2. Restaurer depuis dernier backup
3. Saisir manuellement données perdues (si < 1 jour)
4. Documenter incident

---

## Situation 2 : Suspicion Fraude

**Indicateurs :**
- Écarts stock récurrents (même matière)
- Variance temps anormale (même opérateur)
- Outputs incohérents vs matière

**Procédure :**
1. Documenter observations
2. Alerter direction
3. Audit approfondi (inventaire surprise)
4. Mesures correctives

---

## Situation 3 : Migration Données

**Cas :** Import données ancien système

**Procédure :**
1. Exporter données ancien système (CSV)
2. Valider format (colonnes requises)
3. Import via interface admin
4. Vérifier cohérence (totaux)
5. Valider avec direction

---

# ✅ CHECKLIST MENSUELLE

**Semaine 1 :**
- [ ] Audit stock physique (échantillon 20%)
- [ ] Vérifier écarts stock vs ERP
- [ ] Créer ajustements si nécessaire

**Semaine 2 :**
- [ ] Analyser variance coûts
- [ ] Identifier produits > 10% variance
- [ ] Proposer actions correctives

**Semaine 3 :**
- [ ] Identifier stock mort (> 90j)
- [ ] Proposer liquidations
- [ ] Analyser taux non-qualité

**Semaine 4 :**
- [ ] Générer exports comptables
- [ ] Transmettre à expert-comptable
- [ ] Rapport mensuel direction

---

# 🎓 FORMATION

## Durée
- Formation initiale : 2 jours
- Pratique supervisée : 1 mois
- Recyclage : Annuel

## Contenu
- Initialisation stock
- Lecture dashboards
- Validation clôtures
- Exports comptables
- Gestion utilisateurs
- Audit mensuel

---

# 📞 CONTACTS

**Direction :** [Nom] - [Téléphone]
**Expert-Comptable :** [Nom] - [Téléphone]
**Support Technique :** [Nom] - [Téléphone]

---

# ❓ FAQ

**Q : Puis-je modifier un coût calculé ?**
R : ❌ NON. Coûts = Calculés automatiquement. Jamais saisis.

**Q : Puis-je corriger le stock directement ?**
R : ❌ NON. Créer ajustement ADJUSTMENT (tracé).

**Q : Puis-je modifier un OF completed ?**
R : ❌ NON (R6). Validation ou rejet uniquement.

**Q : Que faire si écart stock > 5% ?**
R : Enquête approfondie. Inventaire complet. Analyser cause.

**Q : Puis-je supprimer un utilisateur ?**
R : Désactivation uniquement (traçabilité). Jamais suppression.

---

**FIN DU GUIDE**

*Vous êtes le garant de la cohérence globale.*
*Lecture seule sur calculs. Validation/Rejet uniquement.*
