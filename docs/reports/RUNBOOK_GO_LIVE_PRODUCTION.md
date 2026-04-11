# 🚀 RUNBOOK GO-LIVE PRODUCTION — RACINE BY GANDA

**Date :** 19 décembre 2025  
**Projet :** RACINE BY GANDA  
**Version :** 1.0  
**Type :** Exécution réelle, zéro improvisation

---

## 🎯 OBJECTIF

Runbook de lancement production utilisable par :
- CTO / Ops Lead
- Équipe technique
- Partenaire tech
- Futur CTO

**Zéro ambiguïté. Instructions claires. Checklists complètes.**

---

## 1️⃣ CHECKLIST TECHNIQUE AVANT GO-LIVE

### Base de données

- [ ] **Migrations exécutées**
  ```bash
  php artisan migrate --force
  ```
  - Vérifier : `creator_subscriptions`, `creator_stripe_accounts`, `creator_subscription_events`

- [ ] **Seeders exécutés**
  ```bash
  php artisan db:seed --class=CreatorPlanSeeder
  php artisan db:seed --class=PlanCapabilitySeeder
  ```
  - Vérifier : Plans FREE, OFFICIEL, PREMIUM créés
  - Vérifier : Capabilities associées

- [ ] **Index créés**
  - `creator_subscriptions.stripe_subscription_id` (unique)
  - `creator_subscriptions.stripe_customer_id` (index)
  - `creator_subscriptions.status` (index)
  - `creator_subscriptions.current_period_end` (index)
  - `creator_stripe_accounts.stripe_account_id` (unique)

- [ ] **Contraintes vérifiées**
  - Pas de doublons `stripe_subscription_id`
  - Pas de doublons `stripe_customer_id`
  - Relations foreign keys valides

- [ ] **Backup configuré**
  - Backup quotidien automatique
  - Backup avant migration
  - Test de restauration effectué

---

### Cache

- [ ] **Cache configuré**
  - Driver : Redis (production) ou File (dev)
  - TTL : 60 minutes pour capabilities
  - TTL : 24 heures pour plans

- [ ] **Cache vidé avant go-live**
  ```bash
  php artisan cache:clear
  php artisan config:clear
  php artisan route:clear
  php artisan view:clear
  ```

- [ ] **Cache warm-up (optionnel)**
  ```bash
  php artisan tinker
  >>> app(\App\Services\CreatorCapabilityService::class)->getFreePlan();
  ```

---

### Jobs / Queues

- [ ] **Queue configurée**
  - Driver : Redis (production) ou Database (dev)
  - Workers démarrés
  - Supervisor configuré (production)

- [ ] **Jobs critiques vérifiés**
  - `ProcessStripeWebhookEventJob` — Traitement webhooks
  - `DowngradeExpiredSubscriptions` — Vérification abonnements expirés

- [ ] **Commandes cron configurées**
  ```bash
  # Vérification abonnements expirés (quotidien 3h)
  0 3 * * * php /path/to/artisan creator:check-expired-subscriptions

  # Détection risques (quotidien 8h)
  0 8 * * * php /path/to/artisan financial:detect-risks

  # Optimisations (quotidien 3h)
  0 3 * * * php /path/to/artisan financial:optimize
  ```

---

### Webhooks

- [ ] **Webhooks Stripe configurés**

  **Dashboard Stripe :** https://dashboard.stripe.com/webhooks

  **Endpoint 1 : Connect**
  - URL : `https://votre-domaine.com/api/webhooks/stripe/connect`
  - Événements : `account.updated`, `capability.updated`, `account.application.deauthorized`
  - Secret : Copié dans `.env` → `STRIPE_WEBHOOK_SECRET`

  **Endpoint 2 : Billing**
  - URL : `https://votre-domaine.com/api/webhooks/stripe/billing`
  - Événements : `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.paid`, `invoice.payment_failed`
  - Secret : Même secret ou différent (selon config Stripe)

- [ ] **Signature vérifiée**
  - Test avec Stripe CLI : `stripe listen --forward-to localhost/api/webhooks/stripe/billing`
  - Vérifier logs : Signature validée

- [ ] **Webhooks testés**
  - Envoyer événement test depuis Stripe Dashboard
  - Vérifier traitement dans logs
  - Vérifier création/mise à jour en DB

---

### Paiements

- [ ] **Stripe configuré**
  - Clé secrète : `STRIPE_SECRET=sk_live_...` (production)
  - Clé publique : `STRIPE_KEY=pk_live_...` (frontend)
  - Webhook secret : `STRIPE_WEBHOOK_SECRET=whsec_...`
  - Devise : `STRIPE_CURRENCY=XAF`

- [ ] **Mobile Money configuré (si applicable)**
  - Monetbil configuré
  - Callbacks testés
  - Secret vérifié

- [ ] **Test paiement effectué**
  - Créer session checkout test
  - Payer avec carte test Stripe
  - Vérifier webhook reçu
  - Vérifier abonnement créé

---

### Sécurité

- [ ] **Variables d'environnement sécurisées**
  - `.env` non commité
  - Secrets en variables d'environnement serveur
  - Pas de secrets dans le code

- [ ] **HTTPS activé**
  - Certificat SSL valide
  - Redirection HTTP → HTTPS
  - HSTS activé (optionnel)

- [ ] **Rate limiting configuré**
  - Webhooks : 60 req/min
  - API : Selon besoins
  - Protection DDoS (Cloudflare, etc.)

- [ ] **CORS configuré**
  - Origines autorisées définies
  - Pas de `*` en production

- [ ] **Logs sécurisés**
  - Pas de données sensibles dans logs
  - Rotation des logs configurée
  - Accès logs restreint

---

## 2️⃣ CHECKLIST MÉTIER

### Plans actifs

- [ ] **Plans créés et actifs**
  - Plan FREE : `code='free'`, `price=0`, `is_active=true`
  - Plan OFFICIEL : `code='official'`, `price=5000`, `is_active=true`
  - Plan PREMIUM : `code='premium'`, `price=15000`, `is_active=true`

- [ ] **Capabilities associées**
  - Vérifier chaque plan a ses capabilities
  - Vérifier valeurs correctes (bool, int, string)

- [ ] **Prix validés**
  - Prix en XAF
  - Prix cohérents avec stratégie
  - Pas de prix négatifs

---

### CGU créateurs

- [ ] **CGU rédigées**
  - Conditions d'abonnement
  - Politique de remboursement
  - Politique d'annulation
  - Responsabilités créateur

- [ ] **CGU accessibles**
  - Lien dans footer
  - Lien lors de l'inscription
  - Lien lors de l'abonnement

---

### Support prêt

- [ ] **Canaux support configurés**
  - Email : support@racinebyganda.com
  - WhatsApp : +242 XXX XXX XXX
  - Chat (si applicable)

- [ ] **Documentation support**
  - FAQ créateurs
  - Guide onboarding
  - Guide paiement
  - Guide abonnement

- [ ] **Équipe support formée**
  - Connaissance des plans
  - Connaissance des problèmes courants
  - Processus d'escalade

---

### Messages UX validés

- [ ] **Messages de succès**
  - Abonnement activé
  - Paiement réussi
  - Upgrade réussi

- [ ] **Messages d'erreur**
  - Paiement échoué
  - Abonnement expiré
  - Erreur technique

- [ ] **Messages onboarding**
  - Bienvenue créateur
  - Guide premier pas
  - Rappel onboarding Stripe

---

## 3️⃣ PLAN DE SURVEILLANCE J+1 / J+7 / J+30

### J+1 (Premier jour)

**KPI à surveiller :**

| KPI | Seuil | Action si dépassé |
|-----|-------|-------------------|
| Erreurs webhooks | > 5 | Vérifier logs, contacter Stripe |
| Paiements échoués | > 20% | Vérifier configuration Stripe |
| Créateurs bloqués | > 10% | Vérifier onboarding Stripe |
| Temps réponse API | > 2s | Vérifier performance serveur |

**Alertes critiques :**
- Webhook non reçu depuis 1h → Vérifier endpoint
- Paiement bloqué → Vérifier Stripe
- Erreur 500 → Vérifier logs, rollback si nécessaire

**Actions rapides :**
- Vérifier logs toutes les heures
- Surveiller dashboard Stripe
- Répondre aux tickets support dans l'heure

---

### J+7 (Première semaine)

**KPI à surveiller :**

| KPI | Objectif | Action si non atteint |
|-----|----------|----------------------|
| Créateurs inscrits | > 10 | Analyser acquisition |
| Créateurs payants | > 3 | Analyser conversion |
| MRR | > 15 000 XAF | Analyser pricing |
| Churn | < 10% | Analyser rétention |

**Alertes :**
- Churn > 15% → Analyser raisons
- Conversion < 5% → Améliorer onboarding
- Support > 10 tickets/jour → Améliorer UX

**Actions :**
- Revue hebdomadaire des métriques
- Ajustements UX si nécessaire
- Communication créateurs

---

### J+30 (Premier mois)

**KPI à surveiller :**

| KPI | Objectif | Action si non atteint |
|-----|----------|----------------------|
| Créateurs inscrits | > 50 | Stratégie acquisition |
| Créateurs payants | > 15 | Stratégie conversion |
| MRR | > 150 000 XAF | Stratégie pricing |
| Churn | < 10% | Stratégie rétention |
| Stripe Health Score | > 80% | Améliorer onboarding |

**Revue mensuelle :**
- Analyse complète des métriques
- Ajustements stratégiques
- Planification mois suivant

---

## 4️⃣ PLAN DE GESTION INCIDENT

### Paiement bloqué

**Symptômes :**
- Créateur ne peut pas payer
- Erreur Stripe lors du checkout
- Webhook non reçu

**Actions immédiates :**
1. Vérifier statut Stripe : https://status.stripe.com
2. Vérifier logs erreurs
3. Vérifier configuration Stripe (clés, webhooks)
4. Tester avec carte test

**Escalade :**
- Si problème Stripe → Contacter support Stripe
- Si problème code → Rollback si nécessaire
- Communication créateur : "Paiement temporairement indisponible, réessayez dans 1h"

**Documentation :**
- Logger l'incident
- Documenter la solution
- Mettre à jour le runbook

---

### Créateur mécontent

**Symptômes :**
- Ticket support critique
- Réclamation paiement
- Demande remboursement

**Actions immédiates :**
1. Répondre dans l'heure
2. Écouter le problème
3. Vérifier les données (abonnement, paiement)
4. Proposer solution (remboursement si justifié)

**Escalade :**
- Si remboursement > 50 000 XAF → Validation manager
- Si problème technique → Escalade tech
- Si problème récurrent → Analyse root cause

**Documentation :**
- Logger la réclamation
- Documenter la solution
- Améliorer le processus si nécessaire

---

### Bug critique

**Symptômes :**
- Erreur 500 sur route critique
- Données corrompues
- Doublons créés

**Actions immédiates :**
1. **Isoler le problème**
   - Désactiver la feature si possible
   - Rollback si nécessaire
   - Communiquer aux utilisateurs

2. **Diagnostiquer**
   - Analyser logs
   - Reproduire le bug
   - Identifier la cause

3. **Corriger**
   - Fix en urgence
   - Test en staging
   - Déploiement en production

**Escalade :**
- Si impact > 10 créateurs → Alerte équipe
- Si perte de données → Priorité absolue
- Si sécurité → Alerte sécurité

**Documentation :**
- Post-mortem
- Correctif appliqué
- Prévention future

---

### Abus détecté

**Symptômes :**
- Créateur avec plusieurs comptes
- Tentative de contournement paiement
- Utilisation frauduleuse

**Actions immédiates :**
1. **Suspendre le compte**
   - Bloquer l'accès
   - Marquer comme suspect
   - Logger l'abus

2. **Analyser**
   - Vérifier les données
   - Identifier le pattern
   - Documenter l'abus

3. **Action**
   - Suspension définitive si confirmé
   - Communication créateur
   - Améliorer la détection

**Escalade :**
- Si fraude financière → Alerte légale
- Si pattern récurrent → Améliorer sécurité
- Si impact autres créateurs → Communication

---

## 5️⃣ PLAN DE COMMUNICATION INTERNE

### Qui décide ?

**Décisions techniques :**
- **CTO / Tech Lead** — Architecture, sécurité, performance
- **DevOps** — Infrastructure, déploiement
- **Backend Lead** — Logique métier, APIs

**Décisions métier :**
- **CEO / Founder** — Stratégie, pricing, partenariats
- **Product Manager** — Features, UX
- **Support Lead** — Support créateurs

**Décisions financières :**
- **CEO / Founder** — Remboursements > 50k XAF
- **Finance** — Facturation, comptabilité

---

### Qui corrige ?

**Bugs techniques :**
- **Backend Dev** — Bugs backend, APIs
- **Frontend Dev** — Bugs UI/UX
- **DevOps** — Bugs infrastructure

**Problèmes métier :**
- **Support** — Problèmes créateurs (première ligne)
- **Product Manager** — Problèmes UX
- **CEO** — Problèmes stratégiques

**Incidents critiques :**
- **Équipe tech complète** — Mobilisation immédiate
- **CTO** — Coordination
- **CEO** — Communication externe

---

### Qui communique ?

**Communication créateurs :**
- **Support** — Tickets, emails
- **Product Manager** — Annonces features
- **CEO** — Annonces importantes

**Communication interne :**
- **CTO** — Incidents techniques
- **Product Manager** — Évolutions produit
- **CEO** — Stratégie, décisions

**Communication externe :**
- **CEO** — Partenaires, investisseurs
- **Marketing** — Presse, réseaux sociaux
- **Support** — Clients (si autorisé)

---

## 📋 CHECKLIST FINALE GO-LIVE

### Avant le lancement

- [ ] Toutes les migrations exécutées
- [ ] Tous les seeders exécutés
- [ ] Cache vidé
- [ ] Webhooks Stripe configurés et testés
- [ ] Paiements testés (carte test)
- [ ] Plans actifs et validés
- [ ] CGU accessibles
- [ ] Support prêt
- [ ] Messages UX validés
- [ ] Backup configuré
- [ ] Monitoring configuré
- [ ] Alertes configurées

### Au moment du lancement

- [ ] Communication créateurs (email, réseaux sociaux)
- [ ] Surveillance active (logs, dashboard)
- [ ] Équipe support disponible
- [ ] Équipe tech disponible (standby)

### Après le lancement (J+1)

- [ ] Revue des métriques J+1
- [ ] Analyse des incidents
- [ ] Ajustements si nécessaire
- [ ] Communication équipe

---

## 🚨 CONTACTS D'URGENCE

### Équipe technique

- **CTO :** [nom] — [email] — [téléphone]
- **Backend Lead :** [nom] — [email] — [téléphone]
- **DevOps :** [nom] — [email] — [téléphone]

### Support

- **Support Lead :** [nom] — [email] — [téléphone]
- **Support 24/7 :** [email] — [téléphone]

### Partenaires

- **Stripe Support :** https://support.stripe.com
- **Hébergeur :** [contact]

---

## 📝 TEMPLATES DE COMMUNICATION

### Email créateur — Lancement

**Objet :** RACINE BY GANDA est en ligne — Rejoignez l'écosystème premium

**Message :**
[Utiliser le message officiel de lancement créateurs]

---

### Email équipe — Incident critique

**Objet :** [URGENT] Incident production — [Description]

**Message :**
Bonjour équipe,

Un incident critique a été détecté :
- **Type :** [Bug / Paiement / Sécurité]
- **Impact :** [Nombre créateurs affectés]
- **Actions :** [Actions en cours]
- **Status :** [En cours / Résolu]

Suivi : [Lien dashboard / Logs]

---

## ✅ VALIDATION FINALE

**Avant de lancer en production, vérifier :**

- [ ] Tous les tests passent
- [ ] Aucune erreur dans les logs
- [ ] Webhooks fonctionnent
- [ ] Paiements fonctionnent
- [ ] Dashboard admin accessible
- [ ] Support prêt
- [ ] Équipe disponible

**Si toutes les cases sont cochées → GO-LIVE autorisé ✅**

---

**Dernière mise à jour :** 19 décembre 2025  
**Auteur :** CTO / Ops Lead  
**Version :** 1.0

