# GUIDE OPÉRATEUR ATELIER
## ERP RACINE BY GANDA - Manuel Utilisateur

> **Rôle :** Opérateur Atelier
> **Version :** 1.0
> **Date :** 2026-01-04

---

# 🎯 VOTRE RÔLE

Vous êtes responsable de **l'exactitude des données de production**.

**Vos actions :**
- Scanner la matière utilisée
- Logger votre temps de travail
- Signaler les problèmes

**Votre impact :**
- Vos données → Calcul du coût réel
- Vos données → Stock exact
- Vos données → Décisions de l'entreprise

**Règle d'or :** Scanner > Saisir. Toujours privilégier le scan.

---

# 📱 ACCÈS TABLETTE ATELIER

## Connexion

1. Allumer la tablette
2. Ouvrir l'application "ERP RACINE"
3. Scanner votre badge opérateur
4. Vous êtes connecté ✅

**Si problème :** Contacter chef d'atelier

---

# 🧵 TÂCHE 1 : SCANNER MATIÈRE

## Quand ?
Dès que vous commencez à utiliser un nouveau rouleau de tissu, fil, bouton, etc.

## Comment ?

### Étape 1 : Sélectionner l'OF
```
Écran d'accueil → "Mes OFs" → Sélectionner OF actif
```

### Étape 2 : Scanner le rouleau
```
Bouton "Scanner Matière" → Caméra s'ouvre
```

**📷 PHOTO : Positionnez le QR code du rouleau devant la caméra**

![Exemple scan QR code](scan_qr_example.jpg)

### Étape 3 : Vérifier les informations
```
✅ Référence : LIN-BLEU-R042
✅ Stock disponible : 125.5 m
```

**⚠️ Si stock affiché = 0 ou très bas :**
→ STOP ! Alerter chef d'atelier (Règle R12)

### Étape 4 : Saisir quantité utilisée
```
Quantité utilisée : [____] m
```

**💡 Conseils :**
- Mesurez précisément (pas "à peu près")
- Utilisez le mètre ruban fourni
- Arrondissez au 0.1m (ex: 15.3m)

### Étape 5 : Valider
```
Bouton [VALIDER]
```

**✅ Confirmation :** "Matière enregistrée"

---

## ❌ ERREURS FRÉQUENTES

### Erreur 1 : "Stock insuffisant"
**Message :** "Demandé 150m, Disponible 75m"

**Que faire :**
1. Vérifier que vous avez saisi la bonne quantité
2. Si oui → Alerter chef d'atelier
3. NE PAS forcer la saisie

**Pourquoi :** Règle R12 - On ne peut pas utiliser ce qu'on n'a pas

---

### Erreur 2 : "QR code illisible"
**Que faire :**
1. Nettoyer l'étiquette
2. Améliorer l'éclairage
3. Si toujours illisible → Saisie manuelle (bouton "Saisie manuelle")
4. Informer chef d'atelier (étiquette à remplacer)

---

### Erreur 3 : "J'ai fait une erreur de quantité"
**Que faire :**
1. ❌ NE PAS modifier le log existant
2. ✅ Informer chef d'atelier immédiatement
3. Chef créera une correction tracée

**Pourquoi :** Traçabilité - Chaque action doit être visible

---

# ⏱️ TÂCHE 2 : LOGGER TEMPS

## Quand ?
À la fin de chaque opération (coupe, couture, finition, etc.)

## Comment ?

### Étape 1 : Sélectionner l'opération
```
OF actif → "Logger Temps" → Sélectionner opération
Exemple : "COUPE"
```

### Étape 2 : Scanner votre badge
```
"Scanner Badge Opérateur" → Caméra s'ouvre
```

**📷 PHOTO : Positionnez votre badge devant la caméra**

### Étape 3 : Saisir durée
```
Durée : [____] minutes
```

**💡 Conseils :**
- Soyez honnête (pas de gonflage/minoration)
- Incluez les pauses courtes (< 5 min)
- Excluez les pauses longues (repas, etc.)

**📊 Info affichée :**
```
Temps standard : 120 min
Votre temps : 130 min (+8%)
```

**⚠️ Si variance > 20% :**
→ Système vous demandera la raison (liste déroulante)

### Étape 4 : Valider
```
Bouton [VALIDER]
```

**✅ Confirmation :** "Temps enregistré"

---

## 💡 RAISONS VARIANCE TEMPS

Si votre temps > temps standard de plus de 20% :

**Raisons acceptables :**
- Problème machine
- Matière difficile
- Première fois sur ce modèle
- Interruption (panne, urgence)

**Raisons NON acceptables :**
- "J'ai pris mon temps"
- "J'ai discuté"

**Conséquence :** Formation si variance fréquente

---

# 📦 TÂCHE 3 : DÉCLARER OUTPUTS (Fin de Production)

## Quand ?
Uniquement à la fin de la production, avec le chef d'atelier

## Comment ?

### Étape 1 : Compter physiquement
```
Taille S :
- 1er choix (parfait) : _____ pcs
- 2nd choix (petit défaut) : _____ pcs
- Rebut (non vendable) : _____ pcs

Taille M :
- 1er choix : _____ pcs
- 2nd choix : _____ pcs
- Rebut : _____ pcs
```

**💡 Conseils :**
- Comptez 2 fois pour vérifier
- Séparez physiquement les 3 catégories
- Chef d'atelier doit vérifier

### Étape 2 : Saisir dans tablette
```
OF actif → "Clôturer Production" → Saisir quantités
```

### Étape 3 : Validation chef
```
Chef d'atelier scanne son badge → Valide
```

**✅ Confirmation :** "OF clôturé"

---

## ❌ ERREURS FRÉQUENTES

### Erreur 1 : "Total = 0"
**Message :** "Output avec quantité totale = 0 interdit"

**Que faire :**
- Vérifier que vous avez saisi au moins 1 pièce
- Si vraiment 0 → Contacter chef (problème production)

**Pourquoi :** Règle R4 - Un output vide n'a pas de sens

---

### Erreur 2 : "Données manquantes"
**Message :** "Impossible de clôturer : Pas de matière loggée"

**Que faire :**
1. Vérifier que vous avez scanné toute la matière
2. Vérifier que vous avez loggé tous les temps
3. Si oui → Contacter chef d'atelier

**Pourquoi :** Règles R1, R2, R3 - Données complètes requises

---

# 🚨 SITUATIONS D'URGENCE

## Problème 1 : Tablette cassée/perdue
**Que faire :**
1. Informer chef d'atelier immédiatement
2. Noter sur papier (temporaire) :
   - Matière utilisée
   - Temps passé
3. Chef saisira dans système dès que possible

---

## Problème 2 : Panne électrique
**Que faire :**
1. Continuer production si possible
2. Noter sur papier
3. Saisir dans système dès retour électricité

---

## Problème 3 : Erreur déjà validée
**Que faire :**
1. ❌ NE PAS essayer de corriger vous-même
2. ✅ Informer chef d'atelier immédiatement
3. Chef créera correction tracée

**Pourquoi :** Traçabilité - Pas de modification silencieuse

---

# ✅ CHECKLIST QUOTIDIENNE

**Début de journée :**
- [ ] Tablette chargée
- [ ] Badge opérateur fonctionnel
- [ ] Connexion ERP OK

**Pendant production :**
- [ ] Scanner chaque nouveau rouleau
- [ ] Logger temps après chaque opération
- [ ] Signaler problèmes immédiatement

**Fin de journée :**
- [ ] Tous les temps loggés
- [ ] Toute la matière scannée
- [ ] Tablette rechargée

---

# 🎓 FORMATION

## Durée
- Formation initiale : 2 heures
- Pratique supervisée : 1 semaine
- Recyclage : Annuel

## Contenu
- Scan matière (pratique)
- Log temps (pratique)
- Déclaration outputs (pratique)
- Gestion erreurs

---

# 📞 CONTACTS

**Chef d'Atelier :** [Nom] - [Téléphone]
**Support Technique :** [Nom] - [Téléphone]
**Urgence :** [Numéro]

---

# ❓ FAQ

**Q : Puis-je saisir "à peu près" la quantité de tissu ?**
R : ❌ NON. Mesurez précisément. Vos données → Coût réel.

**Q : Que faire si j'oublie de scanner un rouleau ?**
R : Informer chef d'atelier immédiatement. Il créera le log.

**Q : Puis-je modifier un temps déjà validé ?**
R : ❌ NON. Informer chef d'atelier pour correction tracée.

**Q : Pourquoi le système bloque parfois ?**
R : Protection. Exemple : Stock insuffisant (R12). Alerter chef.

**Q : Que faire si QR code illisible ?**
R : Saisie manuelle (bouton prévu) + Informer chef (étiquette à remplacer).

---

**FIN DU GUIDE**

*Gardez ce guide à portée de main sur votre poste de travail.*
*En cas de doute, demandez au chef d'atelier.*
