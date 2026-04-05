# CHARTE DE GOUVERNANCE DES DONNÉES
## RACINE BY GANDA - Document Officiel

> **Statut :** OFFICIEL - CONTRACTUEL
> **Date :** 2026-01-04
> **Révision :** Annuelle
> **Approbation :** Direction Générale

---

# PRÉAMBULE

L'ERP RACINE BY GANDA est la **colonne vertébrale de l'entreprise**.

La qualité des données = La qualité des décisions.
La gouvernance des données = La survie de l'entreprise.

Cette charte définit les **responsabilités, interdictions et sanctions** pour garantir l'intégrité du système.

---

# ARTICLE 1 : PROPRIÉTÉ DES DONNÉES

## 1.1 Source Unique de Vérité

L'ERP est la **SEULE** source de vérité pour :
- Stock matières premières et produits finis
- Coûts de production
- Historique fabrication
- Traçabilité matière

**Interdiction :**
❌ Tenir fichier Excel parallèle pour stock/coûts
❌ Utiliser données hors ERP pour décisions

## 1.2 Propriété Collective

Les données appartiennent à **l'entreprise**, pas aux individus.

**Conséquence :**
- Accès révocable à tout moment
- Audit possible sans préavis
- Export interdit sans autorisation

---

# ARTICLE 2 : RESPONSABILITÉS PAR RÔLE

## 2.1 Opérateur Atelier

**Responsable de :** Exactitude saisie matière et temps

**Obligations :**
- Scanner matière (pas "à peu près")
- Logger temps honnêtement (pas de gonflage/minoration)
- Signaler erreurs immédiatement

**Interdictions :**
- ❌ Saisir sans scanner (sauf exception validée)
- ❌ Modifier logs après validation
- ❌ Partager identifiants/badge

**Sanction si manquement :**
- 1ère fois : Avertissement + Formation
- 2ème fois : Blâme écrit
- 3ème fois : Sanction disciplinaire

---

## 2.2 Chef d'Atelier

**Responsable de :** Cohérence production et validations

**Obligations :**
- Vérifier physiquement avant validation clôture
- Analyser alertes (R12, retards) et agir
- Former opérateurs (éviter récidives erreurs)

**Interdictions :**
- ❌ Valider clôture sans vérification physique
- ❌ Forcer clôture si règle bloquante
- ❌ Modifier OF completed

**Sanction si manquement :**
- 1ère fois : Avertissement direction
- 2ème fois : Suspension temporaire accès
- 3ème fois : Révocation fonction

---

## 2.3 Contrôle Qualité

**Responsable de :** Qualité produit et traçabilité défauts

**Obligations :**
- Inspecter selon standards (pas de compromis)
- Tracer tous défauts (avec photos)
- Analyser causes récurrentes

**Interdictions :**
- ❌ Approuver sans inspection
- ❌ Modifier inspection validée
- ❌ Accepter pression pour baisser standards

**Sanction si manquement :**
- 1ère fois : Avertissement + Audit qualité
- 2ème fois : Suspension temporaire
- 3ème fois : Révocation fonction

---

## 2.4 Admin ERP

**Responsable de :** Cohérence globale système

**Obligations :**
- Valider ou rejeter (jamais corriger calculs)
- Justifier tous ajustements stock
- Audit mensuel (stock, coûts, qualité)

**Interdictions :**
- ❌ Modifier coûts calculés
- ❌ Corriger stock sans mouvement tracé
- ❌ Modifier OF completed
- ❌ Supprimer logs historiques

**Sanction si manquement :**
- 1ère fois : Avertissement direction + Audit complet
- 2ème fois : Révocation accès admin
- 3ème fois : Licenciement (faute grave)

---

# ARTICLE 3 : INTERDICTIONS ABSOLUES

## 3.1 Interdictions Techniques

❌ **Modifier un OF completed** (R6)
- Raison : Intégrité comptable
- Sanction : Révocation accès

❌ **Saisir manuellement un coût**
- Raison : Coûts = Calculés automatiquement
- Sanction : Avertissement + Formation

❌ **Corriger stock sans mouvement**
- Raison : Traçabilité cassée
- Sanction : Blâme + Audit

❌ **Consommer sans stock disponible** (R12)
- Raison : Vérité physique
- Sanction : Système bloque (pas de sanction humaine)

❌ **Agréger outputs** (sans détail variante)
- Raison : Perte information critique
- Sanction : Rejet saisie + Formation

---

## 3.2 Interdictions Organisationnelles

❌ **Partager identifiants/badges**
- Raison : Traçabilité WHO
- Sanction : Suspension immédiate

❌ **Exporter données sans autorisation**
- Raison : Confidentialité
- Sanction : Avertissement + Enquête

❌ **Utiliser données ERP hors cadre professionnel**
- Raison : Propriété entreprise
- Sanction : Licenciement (faute grave)

---

# ARTICLE 4 : PROCÉDURES EXCEPTIONNELLES

## 4.1 Correction Erreur Saisie

**Procédure OBLIGATOIRE :**
1. ❌ NE PAS modifier log existant
2. ✅ Créer mouvement ADJUSTMENT inverse
3. ✅ Justifier dans notes (obligatoire)
4. ✅ Informer chef d'atelier/admin

**Non-respect :** Sanction selon gravité

---

## 4.2 OF Clôturé par Erreur

**Procédure OBLIGATOIRE :**
1. ❌ IMPOSSIBLE de rouvrir (R6)
2. ✅ Créer NOUVEL OF pour quantité restante
3. ✅ Lier dans notes
4. ✅ Analyser cause (formation ? process ?)

**Non-respect :** Impossible (système bloque)

---

## 4.3 Inventaire Physique (Écart Stock)

**Procédure OBLIGATOIRE :**
1. Compter stock physique (2 personnes minimum)
2. Calculer écart (physique - ERP)
3. Créer mouvement ADJUSTMENT
4. Justifier : "Inventaire physique [date]"
5. Analyser cause écart (vol ? perte ? erreur ?)

**Si écart > 5% :** Enquête approfondie obligatoire

---

# ARTICLE 5 : AUDIT & CONTRÔLE

## 5.1 Audit Mensuel (Obligatoire)

**Responsable :** Admin ERP

**Vérifications :**
- Stock physique vs ERP (écart < 5%)
- Variance coûts (produits > 10%)
- Taux non-qualité (global < 5%)
- Stock mort (> 90 jours sans mouvement)

**Rapport :** Transmission direction avant 5 du mois

---

## 5.2 Audit Trimestriel (Obligatoire)

**Responsable :** Direction + Admin ERP

**Vérifications :**
- Cohérence globale système
- Efficacité règles bloquantes
- Adoption terrain (utilisation réelle)
- Pertinence KPIs

**Rapport :** Transmission direction générale

---

## 5.3 Audit Surprise (Possible)

**Déclencheurs :**
- Écarts stock récurrents
- Suspicion fraude
- Variance anormale
- Plainte interne

**Procédure :**
- Sans préavis
- Inventaire complet
- Analyse logs système
- Entretiens individuels

---

# ARTICLE 6 : SANCTIONS

## 6.1 Échelle Sanctions

### Niveau 1 : Avertissement
**Cas :** Erreur de bonne foi, première fois
**Conséquence :** Formation + Suivi 1 mois

### Niveau 2 : Blâme Écrit
**Cas :** Récidive ou négligence
**Conséquence :** Dossier personnel + Suivi 3 mois

### Niveau 3 : Suspension Temporaire
**Cas :** Manquement grave ou 3ème récidive
**Conséquence :** Suspension accès 1 semaine + Audit

### Niveau 4 : Révocation Accès/Fonction
**Cas :** Manquement très grave
**Conséquence :** Retrait fonction + Mutation

### Niveau 5 : Licenciement
**Cas :** Faute grave (fraude, vol données, sabotage)
**Conséquence :** Licenciement pour faute grave

---

## 6.2 Exemples Sanctions

| Manquement | Sanction |
|:-----------|:---------|
| Oubli scanner matière (1ère fois) | Niveau 1 |
| Oubli scanner matière (3ème fois) | Niveau 2 |
| Validation clôture sans vérification physique | Niveau 3 |
| Modification OF completed (tentative) | Niveau 4 |
| Export données non autorisé | Niveau 4 |
| Fraude (falsification données) | Niveau 5 |
| Partage identifiants | Niveau 3 |
| Vol données | Niveau 5 |

---

# ARTICLE 7 : FORMATION & SENSIBILISATION

## 7.1 Formation Initiale (Obligatoire)

**Avant accès système :**
- Principes ERP (Bible ERP)
- Guide utilisateur rôle
- Charte gouvernance (signature)
- Pratique supervisée

**Durée :** Selon rôle (2h à 2 jours)

---

## 7.2 Formation Continue

**Fréquence :**
- Opérateur : Annuelle
- Chef atelier : Semestrielle
- Contrôle qualité : Trimestrielle
- Admin : Annuelle

**Contenu :**
- Rappel principes
- Nouveautés système
- Retour expérience (erreurs fréquentes)

---

## 7.3 Sensibilisation

**Moyens :**
- Affichage atelier (règles clés)
- Newsletter mensuelle (KPIs, bonnes pratiques)
- Réunion trimestrielle (bilan gouvernance)

---

# ARTICLE 8 : ÉVOLUTION CHARTE

## 8.1 Révision

**Fréquence :** Annuelle
**Approbation :** Direction générale
**Communication :** Tous collaborateurs

## 8.2 Modification

**Procédure :**
1. Proposition motivée (direction/admin)
2. Consultation parties prenantes
3. Validation direction générale
4. Communication + Formation
5. Signature nouvelle version

---

# ARTICLE 9 : ACCEPTATION

## 9.1 Signature Obligatoire

**Tous les utilisateurs ERP doivent :**
- Lire cette charte
- Comprendre responsabilités et interdictions
- Signer acceptation

**Sans signature :** Pas d'accès ERP

---

## 9.2 Formulaire Acceptation

```
Je soussigné(e) [Nom Prénom],
Fonction : [Rôle],
Déclare avoir lu et compris la Charte de Gouvernance des Données.
Je m'engage à respecter les principes, responsabilités et interdictions.
Je comprends les sanctions en cas de manquement.

Date : ___________
Signature :
```

---

# ARTICLE 10 : CONTACTS

**Questions Charte :** Admin ERP
**Signalement Manquement :** Direction
**Formation :** Responsable RH

---

**FIN DE CHARTE**

*Cette charte est contractuelle et opposable.*
*Tout manquement peut entraîner sanctions disciplinaires.*
