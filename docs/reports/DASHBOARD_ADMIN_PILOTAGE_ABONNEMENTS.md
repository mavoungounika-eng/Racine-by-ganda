# 📊 DASHBOARD ADMIN — PILOTAGE ABONNEMENTS CRÉATEURS

**Date :** 19 décembre 2025  
**Projet :** RACINE BY GANDA  
**Version :** 1.0  
**Type :** Pilotage Business & Décisions

---

## 🎯 OBJECTIF

Dashboard admin simple, lisible et décisionnel pour piloter :
- Les revenus
- La croissance
- La rétention
- Les risques

---

## 1️⃣ KPIs PRINCIPAUX

### Revenus

| KPI | Description | Formule | Objectif |
|-----|------------|---------|----------|
| **MRR** | Monthly Recurring Revenue | Somme des prix des abonnements actifs | Croissance mensuelle |
| **ARR** | Annual Recurring Revenue | MRR × 12 | Projection annuelle |
| **Revenu Net** | Revenu après frais | MRR - Frais Stripe (futur) | Maximiser |
| **ARPU** | Average Revenue Per User | MRR / Nombre créateurs payants | Augmenter via upgrades |

### Croissance

| KPI | Description | Formule | Objectif |
|-----|------------|---------|----------|
| **% Créateurs Payants** | Taux de conversion | (Créateurs payants / Total créateurs) × 100 | > 30% |
| **Conversion FREE → OFFICIEL** | Taux de montée en gamme | (Upgrades FREE→OFFICIEL / Créateurs FREE) × 100 | > 10% |
| **Conversion OFFICIEL → PREMIUM** | Taux d'upgrade premium | (Upgrades OFFICIEL→PREMIUM / Créateurs OFFICIEL) × 100 | > 5% |
| **Nouveaux Abonnements** | Abonnements créés ce mois | COUNT(abonnements créés ce mois) | Croissance constante |

### Rétention

| KPI | Description | Formule | Objectif |
|-----|------------|---------|----------|
| **Churn Mensuel** | Taux d'attrition | (Abonnements annulés / Abonnements actifs début mois) × 100 | < 5% |
| **LTV** | Lifetime Value | ARPU × Durée moyenne abonnement | Maximiser |
| **Durée Moyenne Abonnement** | Durée moyenne en mois | AVG(durée abonnements annulés) | > 6 mois |
| **Taux de Renouvellement** | % abonnements renouvelés | (Renouvellements / Abonnements arrivant à échéance) × 100 | > 80% |

### Risques

| KPI | Description | Formule | Objectif |
|-----|------------|---------|----------|
| **Créateurs à Risque** | Abonnements past_due/unpaid | COUNT(abonnements past_due/unpaid) | Minimiser |
| **Taux d'Échec Paiement** | % paiements échoués | (Paiements échoués / Total paiements) × 100 | < 5% |
| **Stripe Health Score** | Score santé comptes Stripe | Composite (charges_enabled, payouts_enabled, onboarding) | > 90% |
| **Onboarding Incomplet** | Créateurs onboarding > 7 jours | COUNT(onboarding in_progress > 7j) | Minimiser |

---

## 2️⃣ VUES ESSENTIELLES

### Vue Globale (Dashboard Principal)

**Section 1 : Revenus**
- MRR (grand chiffre, évolution %)
- ARR (projection)
- ARPU (revenu moyen)
- Graphique MRR (30/60/90 jours)

**Section 2 : Abonnements**
- Total actifs (grand chiffre)
- Nouveaux ce mois
- Annulés ce mois
- Churn rate (avec indicateur couleur)

**Section 3 : Créateurs**
- Total créateurs
- Créateurs payants (%)
- Créateurs FREE / OFFICIEL / PREMIUM (répartition)
- Graphique répartition par plan

**Section 4 : Conversions**
- Conversion FREE → OFFICIEL (%)
- Conversion OFFICIEL → PREMIUM (%)
- Graphique funnel de conversion

**Section 5 : Risques**
- Créateurs à risque (badge ⚠️)
- Taux d'échec paiement
- Stripe Health Score (barre de progression)

**Section 6 : Alertes**
- Liste des alertes actives
- Actions suggérées

---

### Vue par Plan

**Tableau comparatif :**

| Plan | Abonnés | MRR | ARPU | Churn | Conversion |
|------|---------|-----|------|-------|------------|
| FREE | X | 0 | 0 | - | - |
| OFFICIEL | X | X XAF | X XAF | X% | X% |
| PREMIUM | X | X XAF | X XAF | X% | X% |

**Graphiques :**
- Répartition par plan (camembert)
- Évolution par plan (ligne temporelle)
- Conversion entre plans (sankey)

---

### Vue Temporelle (30 / 60 / 90 jours)

**Sélecteur de période :** 30 jours | 60 jours | 90 jours | 12 mois

**Graphiques :**
- MRR (ligne, évolution)
- Nouveaux abonnements (barres)
- Annulations (barres rouges)
- Churn rate (ligne, objectif < 5%)
- Conversion FREE → OFFICIEL (ligne)

**Tableaux :**
- Top créateurs (par revenu)
- Créateurs à risque (liste)
- Événements récents (timeline)

---

### Alertes (Churn Élevé, Anomalies)

**Types d'alertes :**

1. **Churn Élevé** ⚠️
   - Condition : Churn > 10%
   - Action : Analyser les raisons d'annulation
   - Priorité : Haute

2. **Paiements Échoués** ⚠️
   - Condition : Taux d'échec > 10%
   - Action : Vérifier les problèmes Stripe
   - Priorité : Haute

3. **Stripe Health Score Faible** ⚠️
   - Condition : Score < 70%
   - Action : Relancer l'onboarding
   - Priorité : Moyenne

4. **Conversion Faible** ⚠️
   - Condition : Conversion FREE → OFFICIEL < 5%
   - Action : Améliorer l'offre FREE
   - Priorité : Moyenne

5. **Anomalie MRR** ⚠️
   - Condition : Baisse MRR > 20% vs mois précédent
   - Action : Analyser les causes
   - Priorité : Critique

---

## 3️⃣ RÈGLES D'ALERTE

### Trop de Downgrades

**Déclencheur :**
- Downgrades (OFFICIEL → FREE ou PREMIUM → OFFICIEL) > 10% des abonnements actifs

**Action :**
- Analyser les raisons (prix, features, support)
- Améliorer la rétention
- Relancer les créateurs downgradés

**Dashboard :**
- Badge ⚠️ "Downgrades élevés"
- Liste des créateurs downgradés
- Graphique évolution downgrades

---

### Abonnements Actifs sans Paiement

**Déclencheur :**
- Abonnements avec statut `active` mais dernier paiement > 30 jours

**Action :**
- Vérifier la cohérence Stripe ↔ DB
- Synchroniser manuellement si nécessaire
- Mettre à jour les statuts

**Dashboard :**
- Badge ⚠️ "Incohérences détectées"
- Liste des abonnements suspects
- Bouton "Synchroniser avec Stripe"

---

### Pics Anormaux d'Upgrade

**Déclencheur :**
- Upgrades > 50% de la moyenne mensuelle

**Action :**
- Analyser la cause (promotion, événement, etc.)
- Capitaliser sur le succès
- Répliquer si possible

**Dashboard :**
- Badge ✅ "Pic d'upgrades"
- Graphique évolution upgrades
- Analyse des causes

---

## 4️⃣ RECOMMANDATIONS PRODUIT

### Quand Augmenter les Prix

**Indicateurs :**
- Churn < 3% (très faible)
- Conversion FREE → OFFICIEL > 15% (forte demande)
- ARPU stable depuis 6+ mois
- Satisfaction créateurs élevée

**Recommandation :**
- Augmenter les prix de 10-15%
- Communiquer 30 jours à l'avance
- Offrir un prix de fidélité aux abonnés actuels

**Dashboard :**
- Badge 💡 "Opportunité d'augmentation de prix"
- Analyse de marché
- Simulation impact revenus

---

### Quand Ajouter des Add-ons

**Indicateurs :**
- Demande récurrente de features spécifiques
- Créateurs PREMIUM demandent plus
- ARPU stable, besoin de croissance
- Concurrence ajoute des features

**Recommandation :**
- Créer des add-ons (ex: API, Analytics avancés, Support prioritaire)
- Prix : 20-30% du plan de base
- Test avec créateurs PREMIUM d'abord

**Dashboard :**
- Badge 💡 "Opportunité add-ons"
- Analyse des demandes
- Simulation revenus add-ons

---

### Quand Pousser OFFICIEL → PREMIUM

**Indicateurs :**
- Créateurs OFFICIEL actifs depuis 6+ mois
- Utilisation intensive des features
- Revenus créateur en croissance
- Demande de features premium

**Recommandation :**
- Campagne ciblée créateurs OFFICIEL matures
- Offre promotionnelle (ex: -20% 3 premiers mois)
- Mise en avant des features premium

**Dashboard :**
- Badge 💡 "Créateurs OFFICIEL prêts pour PREMIUM"
- Liste des créateurs cibles
- Simulation conversion

---

## 📊 STRUCTURE DASHBOARD

### Layout Recommandé

```
┌─────────────────────────────────────────────────┐
│  DASHBOARD PILOTAGE ABONNEMENTS                 │
│  [Sélecteur période: 30j | 60j | 90j | 12m]    │
├─────────────────────────────────────────────────┤
│  KPIs PRINCIPAUX (4 cartes)                     │
│  [MRR] [ARR] [Churn] [% Payants]                │
├─────────────────────────────────────────────────┤
│  REVENUS                                        │
│  [Graphique MRR 30j] [Graphique ARPU]           │
├─────────────────────────────────────────────────┤
│  ABONNEMENTS                                    │
│  [Total Actifs] [Nouveaux] [Annulés] [Churn]   │
├─────────────────────────────────────────────────┤
│  CRÉATEURS                                      │
│  [Répartition par plan] [Conversion funnel]     │
├─────────────────────────────────────────────────┤
│  RISQUES & ALERTES                              │
│  [Créateurs à risque] [Stripe Health] [Alertes]│
├─────────────────────────────────────────────────┤
│  RECOMMANDATIONS PRODUIT                        │
│  [Augmenter prix?] [Add-ons?] [Push PREMIUM?]  │
└─────────────────────────────────────────────────┘
```

---

## 🎯 DÉCISIONS BUSINESS

### Décision 1 : Augmenter les Prix

**Quand :**
- Churn < 3%
- Conversion élevée
- ARPU stable

**Action :**
- Augmenter de 10-15%
- Communiquer 30j avant
- Offre fidélité

---

### Décision 2 : Ajouter des Add-ons

**Quand :**
- Demande récurrente
- ARPU stable
- Besoin croissance

**Action :**
- Créer add-ons
- Tester avec PREMIUM
- Prix 20-30% plan base

---

### Décision 3 : Pousser OFFICIEL → PREMIUM

**Quand :**
- Créateurs OFFICIEL matures (6+ mois)
- Utilisation intensive
- Revenus en croissance

**Action :**
- Campagne ciblée
- Offre promotionnelle
- Mise en avant features

---

## 📈 MÉTRIQUES DE SUCCÈS

### Objectifs Mensuels

| Métrique | Objectif | Seuil Critique |
|----------|----------|----------------|
| MRR | +10% | -5% |
| Churn | < 5% | > 10% |
| Conversion FREE → OFFICIEL | > 10% | < 5% |
| Taux d'échec paiement | < 5% | > 10% |
| Stripe Health Score | > 90% | < 70% |

---

## 🔔 ALERTES AUTOMATIQUES

### Niveaux d'Alerte

1. **Critique** 🔴
   - Churn > 15%
   - MRR en baisse > 20%
   - Stripe Health Score < 50%

2. **Élevé** 🟠
   - Churn > 10%
   - Taux d'échec paiement > 10%
   - Conversion < 5%

3. **Moyen** 🟡
   - Churn > 5%
   - Stripe Health Score < 70%
   - Onboarding incomplet > 20%

---

## 📝 NOTES IMPORTANTES

### Performance

- Dashboard doit charger en < 2 secondes
- Requêtes optimisées avec index
- Cache des KPI (15 minutes)

### Données

- Vérification Stripe vs DB mensuelle
- Export comptable disponible
- Audit mensuel recommandé

---

**Dernière mise à jour :** 19 décembre 2025  
**Auteur :** CTO / Head of Data & BI  
**Version :** 1.0

