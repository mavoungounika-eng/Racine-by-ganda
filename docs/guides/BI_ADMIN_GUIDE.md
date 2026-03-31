# 📊 GUIDE ADMIN — BUSINESS INTELLIGENCE & FINANCIER

**Date :** 19 décembre 2025  
**Projet :** RACINE BY GANDA  
**Version :** 1.0

---

## 🎯 OBJECTIF

Ce guide explique comment utiliser le dashboard financier et interpréter les métriques BI pour piloter la plateforme RACINE BY GANDA.

---

## 📊 DASHBOARD FINANCIER

### Accès

**URL :** `/admin/financial/dashboard`  
**Rôle requis :** Admin

### Sections principales

#### 1. Revenus

**MRR (Monthly Recurring Revenue)**
- Revenu récurrent mensuel
- Calcul : Somme des prix de tous les abonnements actifs
- Objectif : Suivre la croissance mensuelle

**ARR (Annual Recurring Revenue)**
- Revenu récurrent annuel
- Calcul : MRR × 12
- Objectif : Projection annuelle

**Revenu net plateforme**
- Revenu net après déduction des frais
- Actuellement = MRR (frais Stripe non déduits)

#### 2. Abonnements

**Total abonnements actifs**
- Nombre d'abonnements avec statut `active`
- Exclut les abonnements expirés

**Total abonnements annulés (ce mois)**
- Nombre d'abonnements annulés dans le mois sélectionné
- Indicateur de churn

#### 3. Créateurs

**Créateurs actifs**
- Créateurs avec abonnement actif et compte Stripe activé

**Créateurs bloqués**
- Par Stripe : Compte non activé
- Par abonnement : Statut unpaid/past_due/canceled

**Créateurs en onboarding**
- Onboarding Stripe en cours (> 7 jours = risque)

**Créateurs en risque**
- Abonnements `past_due`
- Nécessitent une attention

#### 4. Paiements

**Paiements réussis / échoués**
- Statistiques du mois sélectionné
- Taux d'échec : % de paiements échoués

#### 5. Stripe

**Derniers webhooks reçus**
- 10 derniers webhooks Billing
- Permet de vérifier la synchronisation

**Derniers incidents Stripe**
- Créateurs avec problèmes Stripe
- Charges/payouts désactivés, onboarding échoué

---

## 📈 MÉTRIQUES STRATÉGIQUES (BI)

### Churn Rate

**Définition :** Taux d'attrition mensuel

**Calcul :** (Abonnements annulés / Abonnements actifs au début du mois) × 100

**Interprétation :**
- < 5% : Excellent
- 5-10% : Acceptable
- > 10% : Préoccupant → Action requise

**Action si élevé :**
- Analyser les raisons d'annulation
- Améliorer la rétention
- Relancer les créateurs à risque

### ARPU (Average Revenue Per User)

**Définition :** Revenu moyen par créateur payant

**Calcul :** MRR / Nombre de créateurs payants

**Interprétation :**
- Indicateur de valeur par créateur
- Objectif : Augmenter l'ARPU (upgrades)

**Action :**
- Encourager les upgrades vers Premium
- Améliorer les features premium

### LTV (Lifetime Value)

**Définition :** Valeur totale d'un créateur sur sa durée de vie

**Calcul :** ARPU × Durée moyenne d'abonnement (en mois)

**Interprétation :**
- Valeur totale d'un créateur
- Objectif : Maximiser le LTV

**Action :**
- Améliorer la rétention
- Prolonger la durée d'abonnement

### Taux d'activation créateur

**Définition :** % de créateurs avec onboarding complet

**Calcul :** (Créateurs complete / Créateurs inscrits) × 100

**Interprétation :**
- Indicateur de qualité de l'onboarding
- Objectif : > 80%

**Action si faible :**
- Simplifier l'onboarding
- Relancer les créateurs en attente

### Stripe Health Score

**Définition :** Score de santé global des comptes Stripe

**Composants :**
- % comptes avec `charges_enabled`
- % comptes avec `payouts_enabled`
- % onboarding complet

**Interprétation :**
- Score composite (moyenne des 3 composants)
- Objectif : > 90%

**Action si faible :**
- Analyser les comptes bloqués
- Relancer l'onboarding
- Résoudre les problèmes Stripe

---

## ⚠️ DÉTECTION DES RISQUES

### Créateurs à risque

**Niveaux de risque :**

1. **Critique** (rouge)
   - Abonnement `unpaid`
   - Action : Suspension automatique + Downgrade FREE

2. **Élevé** (orange)
   - Abonnement `past_due`
   - Action : Relance email + Surveillance

3. **Moyen** (jaune)
   - Onboarding incomplet > 7 jours
   - Action : Relance email + Rappel onboarding

### Alertes automatiques

**Déclenchement :**
- Via commande `php artisan financial:detect-risks`
- Recommandé : Cron quotidien

**Actions :**
- Email admin (niveau critique)
- Flag `risk_level` dans dashboard
- Badge ⚠️ dans l'interface

---

## 🔄 OPTIMISATION AUTOMATIQUE

### Actions automatiques

1. **Suspension unpaid**
   - Créateurs avec abonnement `unpaid`
   - Période de grâce configurable
   - Action : Downgrade vers FREE

2. **Downgrade expirés**
   - Abonnements expirés (`ends_at` passé)
   - Action : Downgrade vers FREE

3. **Réactivation après paiement**
   - Géré automatiquement par webhook `invoice.paid`
   - Action : Réactivation immédiate

### Commande

```bash
php artisan financial:optimize
```

**Recommandé :** Cron quotidien à 3h du matin

---

## 📝 EXPORT COMPTABLE

### Vérification Stripe vs DB

**Objectif :** S'assurer que les données sont cohérentes

**Méthode :**
1. Exporter les abonnements depuis Stripe Dashboard
2. Comparer avec `creator_subscriptions` en DB
3. Vérifier les incohérences

**Commandes utiles :**
```bash
# Compter les abonnements actifs
php artisan tinker
>>> App\Models\CreatorSubscription::where('status', 'active')->count();

# Vérifier les incohérences
>>> App\Models\CreatorSubscription::whereNull('stripe_subscription_id')->count();
```

### Audit mensuel

**Checklist :**
- [ ] MRR cohérent avec Stripe
- [ ] Nombre d'abonnements actifs = Stripe
- [ ] Aucun doublon dans `creator_subscriptions`
- [ ] Tous les webhooks traités
- [ ] Aucun créateur bloqué sans raison

---

## 🚨 RUNBOOK FINANCIER

### Scénario 1 : Revenu en baisse

**Symptômes :**
- MRR diminue
- Churn rate élevé

**Actions :**
1. Analyser les abonnements annulés
2. Identifier les raisons (prix, features, support)
3. Relancer les créateurs à risque
4. Améliorer la rétention

### Scénario 2 : Churn élevé

**Symptômes :**
- Churn rate > 10%
- Nombre d'annulations en hausse

**Actions :**
1. Analyser les créateurs qui partent
2. Identifier les patterns (plan, durée, etc.)
3. Améliorer l'offre
4. Relancer les créateurs avant annulation

### Scénario 3 : Stripe incident majeur

**Symptômes :**
- Webhooks non reçus
- Paiements bloqués
- Stripe Health Score en baisse

**Actions :**
1. Vérifier le statut Stripe (status.stripe.com)
2. Vérifier les logs webhooks
3. Synchroniser manuellement si nécessaire
4. Contacter le support Stripe si besoin

---

## 📊 INTERPRÉTATION DES DONNÉES

### Dashboard stable avec 10k créateurs

**Performance attendue :**
- Chargement dashboard < 2 secondes
- Requêtes optimisées avec index
- Cache des métriques (15 minutes)

**Optimisations :**
- Index sur `creator_subscriptions.status`
- Index sur `creator_subscriptions.stripe_subscription_id`
- Cache des KPI calculés

---

## 🔗 LIENS UTILES

- **Stripe Dashboard :** https://dashboard.stripe.com
- **Stripe Status :** https://status.stripe.com
- **Documentation Stripe :** https://stripe.com/docs

---

**Dernière mise à jour :** 19 décembre 2025

