# GUIDE CONTRÔLE QUALITÉ
## ERP RACINE BY GANDA - Manuel Utilisateur

> **Rôle :** Contrôleur Qualité
> **Version :** 1.0
> **Date :** 2026-01-04

---

# 🎯 VOTRE RÔLE

Vous êtes le **garant de la qualité produit**.

**Vos responsabilités :**
- Inspecter la production
- Décider : Approve / Rework / Reject
- Tracer les défauts
- Analyser causes récurrentes

**Votre impact :**
- Vos décisions → Qualité stock PF
- Vos traces → Amélioration continue
- Votre rigueur → Réputation marque

**Principe :** **Zéro compromis sur la qualité.**

---

# 📱 ACCÈS SYSTÈME

## Connexion Tablette/Desktop
```
Application : ERP RACINE
Login : [Votre identifiant]
Section : "Contrôle Qualité"
```

---

# 🔍 TÂCHE 1 : INSPECTER PRODUCTION

## Quand ?
- En cours de production (contrôle process)
- Fin de production (contrôle final)

## Comment ?

### Étape 1 : Sélectionner OF à inspecter
```
Liste OFs → Filtrer "IN_PROGRESS" ou "À Inspecter"
Sélectionner : OF-26-001
```

### Étape 2 : Prélever échantillon
```
Méthode : Échantillonnage aléatoire
Taille échantillon :
- Production < 50 pcs : 10% minimum
- Production 50-200 pcs : 20 pcs
- Production > 200 pcs : 10%
```

**💡 Exemple :**
```
OF-26-001 : 50 pcs produites
Échantillon : 10 pcs (20%)
```

### Étape 3 : Inspecter chaque pièce

**Points de contrôle (Textile) :**
```
✅ Couture : Rectiligne, solide, sans fil apparent
✅ Finition : Propre, sans bavures
✅ Dimensions : Conforme patron (tolérance ±2cm)
✅ Couleur : Uniforme, sans taches
✅ Tissu : Sans défaut (trous, déchirures)
```

### Étape 4 : Classifier chaque pièce

**Catégories :**
- **1er Choix (GOOD)** : Parfait, vendable prix plein
- **2nd Choix (SECOND)** : Petit défaut, vendable prix réduit
- **Rebut (REJECT)** : Défaut majeur, non vendable

**Exemples :**

| Défaut | Classification |
|:-------|:---------------|
| Couture légèrement décalée (< 5mm) | 2nd choix |
| Couture décalée (> 5mm) | Rebut |
| Petite tache (< 1cm) | 2nd choix |
| Grande tache (> 1cm) | Rebut |
| Fil apparent (facile à couper) | 2nd choix |
| Trou tissu | Rebut |

---

# 📝 TÂCHE 2 : ENREGISTRER INSPECTION

## Comment ?

### Étape 1 : Accéder à l'enregistrement
```
OF sélectionné → "Enregistrer Inspection"
```

### Étape 2 : Saisir résultats
```
Quantité inspectée : 10 pcs
Quantité conforme : 8 pcs
Quantité non-conforme : 2 pcs

Détail non-conformes :
- Défaut 1 : Couture décalée (1 pcs) → Rebut
- Défaut 2 : Petite tache (1 pcs) → 2nd choix
```

### Étape 3 : Tracer défauts
```
Pour chaque défaut :

Type : COUTURE
Localisation : Manche gauche
Gravité : MAJEUR
Cause probable : Opérateur inexpérimenté
Photo : [Prendre photo défaut]
```

**💡 Types défauts courants :**
- COUTURE (décalage, fil apparent)
- TISSU (tache, trou, déchirure)
- DIMENSION (trop grand/petit)
- COULEUR (non uniforme, délavé)
- FINITION (bavures, bouton mal fixé)

### Étape 4 : Décision

**3 Options :**

**Option 1 : APPROVE (Approuver)**
```
Taux conformité : 80% (8/10) ✅
Défauts mineurs acceptables
Décision : APPROVE
```

**Option 2 : REWORK (Retouche)**
```
Taux conformité : 60% (6/10) ⚠️
Défauts corrigibles (ex: recoudre)
Décision : REWORK
Instructions : "Recoudre manches pièces #3, #5"
```

**Option 3 : REJECT (Rejeter)**
```
Taux conformité : 40% (4/10) ❌
Défauts majeurs non corrigibles
Décision : REJECT
Raison : "Tissu défectueux - Taches multiples"
```

### Étape 5 : Valider
```
Bouton [ENREGISTRER INSPECTION]
```

**✅ Confirmation :**
```
Inspection enregistrée
OF-26-001 : APPROVED
Taux conformité : 80%
```

---

# 🔄 TÂCHE 3 : GÉRER REWORK

## Quand ?
Décision = REWORK (retouche requise)

## Comment ?

### Étape 1 : Créer fiche rework
```
OF : OF-26-001
Pièces concernées : #3, #5, #7
Défaut : Couture décalée
Action requise : Recoudre manche gauche
Délai : 2 heures
```

### Étape 2 : Transmettre à atelier
```
Notification automatique → Chef d'atelier
Chef assigne opérateur pour retouche
```

### Étape 3 : Ré-inspecter après rework
```
Vérifier pièces retouchées
Si OK → APPROVE
Si toujours défaut → REJECT
```

---

# 📊 TÂCHE 4 : ANALYSER DÉFAUTS

## Rapport Hebdomadaire

### Taux de Non-Qualité Global
```
Semaine 1 - Janvier 2026
Pièces inspectées : 500
Pièces rejetées : 15
Taux rebut : 3% ✅
```

**Seuil acceptable :** < 5%
**Si > 5% :** Alerte direction

### Défauts par Type
```
COUTURE : 8 pièces (53%)
TISSU : 4 pièces (27%)
DIMENSION : 2 pièces (13%)
FINITION : 1 pièce (7%)
```

**Action :** Formation opérateurs couture

### Défauts par Opérateur
```
Jean Dupont : 6 défauts (40%)
Marie Martin : 2 défauts (13%)
Paul Durand : 1 défaut (7%)
```

**Action :** Formation ciblée Jean Dupont

### Défauts par Produit
```
Chemise Bleu : 10 défauts
Pantalon Noir : 3 défauts
Robe Rouge : 2 défauts
```

**Action :** Réviser process Chemise Bleu

---

# 🚨 SITUATIONS CRITIQUES

## Situation 1 : Taux Rebut > 10%

**Procédure :**
1. STOP production immédiatement
2. Alerter chef d'atelier + direction
3. Analyser cause (machine ? matière ? opérateur ?)
4. Corriger avant reprise

---

## Situation 2 : Défaut Récurrent

**Exemple :** Couture décalée sur 5 OFs consécutifs

**Procédure :**
1. Identifier cause racine (machine ? formation ?)
2. Proposer action corrective
3. Vérifier efficacité action (suivi 2 semaines)

---

## Situation 3 : Matière Défectueuse

**Exemple :** Tissu avec taches multiples

**Procédure :**
1. STOP utilisation rouleau
2. Créer rapport défaut fournisseur
3. Contacter fournisseur (retour/remboursement)
4. Utiliser rouleau de remplacement

---

# ✅ CHECKLIST QUOTIDIENNE

**Matin :**
- [ ] Consulter OFs à inspecter
- [ ] Préparer matériel inspection (mètre, loupe, etc.)
- [ ] Vérifier éclairage zone inspection

**Pendant journée :**
- [ ] Inspecter selon planning
- [ ] Enregistrer résultats immédiatement
- [ ] Tracer défauts avec photos
- [ ] Gérer reworks

**Soir :**
- [ ] Finaliser inspections en cours
- [ ] Transmettre rapports chef d'atelier
- [ ] Préparer planning lendemain

---

# 📋 CHECKLIST HEBDOMADAIRE

**Lundi :**
- [ ] Analyser taux rebut semaine précédente
- [ ] Identifier défauts récurrents
- [ ] Proposer actions correctives

**Mercredi :**
- [ ] Vérifier efficacité actions correctives
- [ ] Rapport mi-semaine

**Vendredi :**
- [ ] Rapport hebdomadaire complet
- [ ] Transmission direction
- [ ] Planification semaine suivante

---

# 🎓 FORMATION

## Durée
- Formation initiale : 3 jours
- Pratique supervisée : 2 semaines
- Recyclage : Trimestriel

## Contenu
- Standards qualité RACINE BY GANDA
- Techniques inspection textile
- Classification défauts
- Utilisation système ERP
- Analyse statistique défauts

---

# 📞 CONTACTS

**Chef d'Atelier :** [Nom] - [Téléphone]
**Direction Qualité :** [Nom] - [Téléphone]
**Fournisseurs :** [Liste contacts]

---

# ❓ FAQ

**Q : Quel taux de rebut acceptable ?**
R : < 5% acceptable. 5-10% alerte. > 10% critique (STOP production).

**Q : Puis-je approuver avec défauts mineurs ?**
R : Oui, si défauts non visibles client et < 20% échantillon.

**Q : Que faire si désaccord avec chef d'atelier ?**
R : Escalader à direction. Qualité = Non négociable.

**Q : Combien de pièces inspecter ?**
R : 10% minimum. Plus si doute ou nouveau produit.

**Q : Puis-je modifier une inspection validée ?**
R : ❌ NON. Créer nouvelle inspection si nécessaire.

---

**FIN DU GUIDE**

*Vous êtes le dernier rempart qualité.*
*Zéro compromis. La réputation de la marque dépend de vous.*
