# 📦 PHASE 1 : STRIPE CONNECT - Livrables Complets

**Date :** 19 décembre 2025  
**Statut :** ✅ **ARCHITECTURE COMPLÈTE - MIGRATIONS CRÉÉES**

---

## 📋 Résumé des Livrables

### 1. Architecture Complète ✅

**Fichier :** `docs/payments/STRIPE_CONNECT_PHASE_1_ARCHITECTURE.md`

**Contenu :**
- Choix Express vs Custom (justifié)
- Schéma de base de données détaillé
- Flux d'onboarding complet
- Flux de billing (abonnement)
- Flux de checkout
- Liste des webhooks requis
- Tous les cas limites identifiés
- Plan d'implémentation détaillé

### 2. Résumé Exécutif ✅

**Fichier :** `STRIPE_CONNECT_PHASE_1_RESUME.md`

**Contenu :** Version condensée de l'architecture pour vue d'ensemble rapide.

### 3. Migrations de Base de Données ✅

**Fichiers créés :**

1. **`2025_12_19_031744_create_creator_stripe_accounts_table.php`**
   - Table pour les comptes Stripe Connect
   - Colonnes : stripe_account_id, onboarding_status, charges_enabled, etc.
   - Index sur les colonnes importantes

2. **`2025_12_19_031758_create_creator_subscriptions_table.php`**
   - Table pour les abonnements mensuels
   - Colonnes : stripe_subscription_id, status, current_period_start/end, etc.
   - Index sur les colonnes importantes

3. **`2025_12_19_031805_create_creator_subscription_invoices_table.php`**
   - Table pour l'historique des factures
   - Colonnes : stripe_invoice_id, amount, status, paid_at, etc.
   - Index sur les colonnes importantes

**Statut :** ✅ Migrations créées et prêtes à être exécutées

---

## 🎯 Décisions Architecturales

### Choix : Stripe Connect Express

**Justification complète :**

1. **Simplicité :** Moins de code à développer et maintenir
2. **Conformité :** Stripe gère automatiquement KYC et réglementation
3. **Sécurité :** Pas de stockage de données sensibles sur notre serveur
4. **Rapidité :** Développement plus rapide, mise en marché plus tôt
5. **Maintenance :** Stripe met à jour automatiquement

**Alternative rejetée :** Custom (trop complexe pour nos besoins, plus de maintenance)

---

## 🗄️ Schéma de Base de Données

### Table 1 : `creator_stripe_accounts`

**Colonnes principales :**
- `creator_profile_id` (FK, unique) - Lien vers le créateur
- `stripe_account_id` (string, unique) - ID du compte Stripe Connect
- `onboarding_status` (enum) - pending, in_progress, complete, failed
- `charges_enabled` (boolean) - Le créateur peut recevoir des paiements
- `payouts_enabled` (boolean) - Le créateur peut recevoir des versements
- `requirements_currently_due` (json) - Exigences KYC en attente
- `onboarding_link_url` (string) - URL du lien d'onboarding
- `last_synced_at` (timestamp) - Dernière synchronisation avec Stripe

**Index :**
- `creator_profile_id` (unique)
- `stripe_account_id` (unique)
- `onboarding_status`
- `charges_enabled`
- `payouts_enabled`

### Table 2 : `creator_subscriptions`

**Colonnes principales :**
- `creator_profile_id` (FK, unique) - Lien vers le créateur
- `stripe_subscription_id` (string, unique) - ID de l'abonnement Stripe
- `stripe_customer_id` (string) - ID du client Stripe Billing
- `status` (enum) - incomplete, active, unpaid, canceled, etc.
- `current_period_start` (timestamp) - Début période actuelle
- `current_period_end` (timestamp) - Fin période actuelle
- `cancel_at_period_end` (boolean) - Annulation à la fin période

**Index :**
- `creator_profile_id` (unique)
- `stripe_subscription_id` (unique)
- `status`
- `current_period_end` (pour trouver les expirés)

### Table 3 : `creator_subscription_invoices`

**Colonnes principales :**
- `creator_subscription_id` (FK) - Lien vers l'abonnement
- `stripe_invoice_id` (string, unique) - ID de la facture Stripe
- `amount` (decimal) - Montant de la facture
- `status` (enum) - draft, open, paid, uncollectible, void
- `paid_at` (timestamp) - Date de paiement
- `hosted_invoice_url` (string) - URL de la facture Stripe

**Index :**
- `creator_subscription_id`
- `stripe_invoice_id` (unique)
- `status`
- `paid_at`

---

## 🔄 Flux Détaillés

### Flux 1 : Onboarding Stripe Connect

**Étapes :**

1. **Créateur clique "Connecter mon compte Stripe"**
   - Vérifier que le créateur a un CreatorProfile actif
   - Créer un compte Stripe Connect Express via API
   - Enregistrer le `stripe_account_id` dans `creator_stripe_accounts`
   - Mettre `onboarding_status` = `in_progress`

2. **Génération du lien d'onboarding**
   - Appeler Stripe API pour créer un AccountLink
   - Enregistrer l'URL et la date d'expiration
   - Rediriger le créateur vers l'URL Stripe

3. **Créateur remplit le formulaire Stripe**
   - Stripe gère tout (informations personnelles, bancaires, KYC)
   - Nous n'avons rien à faire pendant cette étape

4. **Retour depuis Stripe**
   - Stripe redirige vers notre `return_url`
   - Récupérer les informations du compte depuis Stripe
   - Mettre à jour `creator_stripe_accounts`
   - Si `charges_enabled` = true, créer l'abonnement
   - Mettre `onboarding_status` = `complete`

**Points importants :**
- Le lien d'onboarding expire après 24h (par défaut Stripe)
- Si expiration, générer un nouveau lien
- Vérifier régulièrement le statut via webhook `account.updated`

### Flux 2 : Billing (Abonnement Mensuel)

**Étapes :**

1. **Création de l'abonnement**
   - Quand : Après onboarding complété avec `charges_enabled` = true
   - Créer un client Stripe Billing pour le créateur
   - Créer un produit et prix dans Stripe (si pas déjà créé)
   - Créer l'abonnement Stripe
   - Enregistrer dans `creator_subscriptions` avec status = `incomplete`

2. **Paiement du premier abonnement**
   - Rediriger le créateur vers Stripe Checkout
   - Le créateur saisit ses informations de paiement
   - Stripe traite le paiement
   - Webhook `invoice.paid` reçu
   - Mettre status = `active`
   - Le créateur peut maintenant vendre

3. **Renouvellement mensuel**
   - Stripe facture automatiquement chaque mois
   - Si paiement réussi : webhook `invoice.paid`, abonnement reste `active`
   - Si paiement échoue : webhook `invoice.payment_failed`, abonnement passe à `past_due` puis `unpaid`

4. **Gestion des échecs**
   - Première tentative : email au créateur, status = `past_due` (période de grâce)
   - Dernière tentative : status = `unpaid`, suspendre le créateur

**Points importants :**
- L'abonnement est créé automatiquement après onboarding réussi
- Le créateur doit payer immédiatement pour activer son compte
- Si impayé, suspension automatique (voir cas limites)

### Flux 3 : Checkout Client

**Étapes :**

1. **Vérifications pré-checkout**
   - Créateur a un compte Stripe Connect actif
   - `charges_enabled` = true
   - Abonnement status = `active`
   - Créateur status = `active` et `is_active` = true
   - Si une vérification échoue, afficher erreur et empêcher checkout

2. **Création de la session de paiement**
   - Créer une session Stripe Checkout
   - **IMPORTANT :** Spécifier `stripe_account` = compte du créateur
   - Cela route le paiement vers le compte du créateur, pas la plateforme
   - Rediriger le client vers Stripe Checkout

3. **Paiement par le client**
   - Le client saisit ses informations sur Stripe
   - Stripe traite le paiement **sur le compte du créateur**
   - Le webhook `checkout.session.completed` est reçu
   - La commande est confirmée

4. **Confirmation**
   - Vérifier que la session appartient au compte créateur
   - Mettre à jour le statut de la commande
   - Envoyer les notifications

**Points importants :**
- Le paramètre `stripe_account` est CRUCIAL - sans lui, le paiement irait à la plateforme
- Toutes les vérifications doivent être faites AVANT de créer la session
- Le webhook doit vérifier que le paiement est bien sur le compte créateur

---

## 📡 Webhooks Stripe Requis

### Nouveau Contrôleur à Créer

**Fichier :** `app/Http/Controllers/Api/StripeConnectWebhookController.php`

**Important :** Ce contrôleur est SÉPARÉ de `WebhookController@stripe` existant. Nous ne modifions PAS le système webhook existant.

### Webhooks à Écouter

#### 1. `account.updated`

**Quand :** Le statut d'un compte Stripe Connect change.

**Action :**
- Mettre à jour `creator_stripe_accounts` avec les nouvelles informations
- Si `charges_enabled` passe à true et onboarding était `in_progress`, créer l'abonnement
- Si `charges_enabled` passe à false, suspendre le créateur

**Route webhook :** `POST /api/webhooks/stripe-connect`

#### 2. `checkout.session.completed` (Connect)

**Quand :** Un client termine un paiement sur le compte Stripe d'un créateur.

**Action :**
- Vérifier que la session appartient à un compte Connect
- Trouver la commande correspondante
- Mettre à jour le statut de la commande
- Envoyer les notifications

**Route webhook :** `POST /api/webhooks/stripe-connect`

#### 3. `customer.subscription.created`

**Quand :** Un nouvel abonnement est créé dans Stripe Billing.

**Action :**
- Mettre à jour `creator_subscriptions` avec les informations
- Logger l'événement

**Route webhook :** `POST /api/webhooks/stripe-connect`

#### 4. `customer.subscription.updated`

**Quand :** Un abonnement est modifié (renouvelé, annulé, etc.).

**Action :**
- Mettre à jour le statut dans `creator_subscriptions`
- Si status passe à `unpaid`, suspendre le créateur
- Si status passe à `active` après avoir été `unpaid`, réactiver le créateur

**Route webhook :** `POST /api/webhooks/stripe-connect`

#### 5. `invoice.paid`

**Quand :** Une facture d'abonnement est payée avec succès.

**Action :**
- Mettre à jour l'abonnement status = `active`
- Enregistrer la facture dans `creator_subscription_invoices`
- Réactiver le créateur s'il était suspendu

**Route webhook :** `POST /api/webhooks/stripe-connect`

#### 6. `invoice.payment_failed`

**Quand :** Le paiement d'une facture d'abonnement échoue.

**Action :**
- Mettre à jour l'abonnement status = `past_due` ou `unpaid`
- Envoyer un email au créateur
- Si dernière tentative, suspendre le créateur

**Route webhook :** `POST /api/webhooks/stripe-connect`

#### 7. `invoice.payment_action_required`

**Quand :** Une action est requise pour payer une facture (ex: 3D Secure).

**Action :**
- Envoyer un email au créateur avec le lien de paiement
- Logger l'événement

**Route webhook :** `POST /api/webhooks/stripe-connect`

---

## ⚠️ Cas Limites Identifiés

### Cas 1 : KYC Incomplet

**Détection :**
- `charges_enabled` = false
- `requirements_currently_due` contient des éléments
- `onboarding_status` = `in_progress`

**Action :**
- Empêcher le checkout
- Afficher un message au créateur avec la liste des exigences
- Proposer de générer un nouveau lien d'onboarding

### Cas 2 : Abonnement Impayé

**Détection :**
- `creator_subscriptions.status` = `unpaid` ou `past_due`
- `current_period_end` < maintenant (pour `past_due`)

**Action :**
- Suspendre automatiquement le créateur
- Mettre `creator_profiles.status` = `suspended`
- Mettre `creator_profiles.is_active` = false
- Empêcher tous les checkouts
- Envoyer un email avec lien de paiement

### Cas 3 : Compte Stripe Désactivé

**Détection :**
- Webhook `account.updated` avec `charges_enabled` = false et `details_submitted` = true
- Ou `payouts_enabled` = false alors qu'il était true avant

**Action :**
- Suspendre le créateur
- Envoyer un email : "Votre compte Stripe a été désactivé"
- Empêcher tous les checkouts
- Logger pour audit

### Cas 4 : Abonnement Annulé

**Détection :**
- `creator_subscriptions.status` = `canceled`
- `cancel_at_period_end` = true

**Action :**
- Laisser le créateur vendre jusqu'à `current_period_end`
- À la fin de la période, suspendre automatiquement
- Envoyer un email de rappel avant la fin
- Proposer de réactiver l'abonnement

### Cas 5 : Période d'Essai

**Détection :**
- `creator_subscriptions.status` = `trialing`
- `trial_end` > maintenant

**Action :**
- Laisser le créateur vendre normalement
- Afficher un message : "Période d'essai active jusqu'au [date]"
- À la fin de l'essai, facturer automatiquement
- Si paiement échoue, suspendre

### Cas 6 : Suspension Manuelle

**Détection :**
- `creator_profiles.status` = `suspended` (peu importe l'abonnement)

**Action :**
- Empêcher tous les checkouts
- Afficher : "Votre compte est suspendu. Contactez le support."
- Ne pas suspendre l'abonnement Stripe (le créateur peut toujours payer)

### Cas 7 : Multiples Échecs de Paiement

**Détection :**
- Plusieurs webhooks `invoice.payment_failed` consécutifs
- `creator_subscriptions.status` = `unpaid`

**Action :**
- Suspendre après 3 tentatives échouées
- Envoyer un email après chaque tentative
- Proposer un lien de paiement direct
- Réactiver automatiquement si paiement réussit après suspension

---

## 🔒 Sécurité et Conformité

### Séparation des Fonds

**Principe :** Les fonds des créateurs ne passent jamais par le compte de la plateforme.

**Implémentation :**
- Toujours utiliser `stripe_account` dans les opérations de paiement
- Ne jamais créer de PaymentIntent/Charge sur le compte plateforme pour les ventes créateurs
- Utiliser uniquement le compte plateforme pour les abonnements

### Vérifications de Sécurité

**Avant chaque checkout :**
1. Créateur existe et est actif
2. Compte Stripe Connect actif
3. Abonnement payé (status = active)
4. Créateur non suspendu

**Avant chaque opération sensible :**
1. Vérifier les permissions utilisateur
2. Logger toutes les opérations importantes
3. Valider les données d'entrée

### Conformité KYC

**Stripe gère automatiquement :**
- Vérification d'identité
- Vérification des documents
- Vérification bancaire
- Conformité réglementaire par pays

**Notre responsabilité :**
- Vérifier `charges_enabled` = true avant de permettre les ventes
- Ne pas permettre les ventes si KYC incomplet
- Afficher clairement les exigences au créateur

---

## 📊 Plan d'Implémentation Détaillé

### Phase 1.1 : Base de Données (2-3h)

**Tâches :**
- [x] Créer migration `create_creator_stripe_accounts_table` ✅
- [x] Créer migration `create_creator_subscriptions_table` ✅
- [x] Créer migration `create_creator_subscription_invoices_table` ✅
- [ ] Exécuter les migrations
- [ ] Créer les modèles Eloquent (CreatorStripeAccount, CreatorSubscription, CreatorSubscriptionInvoice)

### Phase 1.2 : Service Stripe Connect (4-6h)

**Tâches :**
- [ ] Créer `StripeConnectService`
- [ ] Implémenter `createAccount()`
- [ ] Implémenter `createOnboardingLink()`
- [ ] Implémenter `syncAccountStatus()`
- [ ] Implémenter `canCreatorReceivePayments()`

### Phase 1.3 : Service Billing (4-6h)

**Tâches :**
- [ ] Créer `CreatorSubscriptionService`
- [ ] Implémenter `createSubscription()`
- [ ] Implémenter `handleInvoicePaid()`
- [ ] Implémenter `handleInvoiceFailed()`
- [ ] Implémenter `suspendCreatorForUnpaidSubscription()`

### Phase 1.4 : Contrôleur Onboarding (3-4h)

**Tâches :**
- [ ] Créer `CreatorStripeConnectController`
- [ ] Implémenter `showOnboarding()`
- [ ] Implémenter `startOnboarding()`
- [ ] Implémenter `handleReturn()`
- [ ] Implémenter `refreshLink()`

### Phase 1.5 : Modification Checkout (4-6h)

**Tâches :**
- [ ] Modifier `CheckoutController` pour vérifier Stripe Connect
- [ ] Modifier `CardPaymentService` pour utiliser `stripe_account`
- [ ] Ajouter les vérifications pré-checkout
- [ ] Tester le flux complet

### Phase 1.6 : Webhooks Connect (6-8h)

**Tâches :**
- [ ] Créer `StripeConnectWebhookController`
- [ ] Implémenter tous les handlers de webhooks
- [ ] Configurer les routes webhooks
- [ ] Tester chaque webhook

### Phase 1.7 : Dashboard Créateur (4-6h)

**Tâches :**
- [ ] Afficher statut Stripe Connect
- [ ] Afficher statut abonnement
- [ ] Afficher factures
- [ ] Afficher exigences KYC
- [ ] Bouton "Connecter mon compte Stripe"

### Phase 1.8 : Tests et Validation (8-10h)

**Tâches :**
- [ ] Tests unitaires services
- [ ] Tests d'intégration flux complet
- [ ] Tests webhooks
- [ ] Tests cas limites
- [ ] Tests mode test Stripe

**Estimation totale :** 35-49 heures (1-2 semaines)

---

## ✅ Checklist de Validation Finale

Avant de considérer la Phase 1 comme terminée :

- [ ] Un créateur peut créer un compte Stripe Connect
- [ ] Un créateur peut compléter l'onboarding Stripe
- [ ] Un créateur peut payer son abonnement mensuel
- [ ] Un client peut acheter un produit et le paiement va au créateur
- [ ] Un créateur avec abonnement impayé est suspendu automatiquement
- [ ] Un créateur suspendu ne peut pas recevoir de paiements
- [ ] Les webhooks sont correctement traités
- [ ] Tous les cas limites sont gérés
- [ ] Les tests passent
- [ ] La documentation est complète

---

## 📁 Fichiers Créés

### Documentation

1. ✅ `docs/payments/STRIPE_CONNECT_PHASE_1_ARCHITECTURE.md` - Architecture complète (50+ pages)
2. ✅ `STRIPE_CONNECT_PHASE_1_RESUME.md` - Résumé exécutif
3. ✅ `STRIPE_CONNECT_PHASE_1_DELIVERABLES.md` - Ce document

### Migrations

1. ✅ `database/migrations/2025_12_19_031744_create_creator_stripe_accounts_table.php`
2. ✅ `database/migrations/2025_12_19_031758_create_creator_subscriptions_table.php`
3. ✅ `database/migrations/2025_12_19_031805_create_creator_subscription_invoices_table.php`

---

## 🚀 Prochaines Étapes

1. **Exécuter les migrations :**
   ```powershell
   php artisan migrate
   ```

2. **Créer les modèles Eloquent :**
   - `CreatorStripeAccount`
   - `CreatorSubscription`
   - `CreatorSubscriptionInvoice`

3. **Commencer la Phase 1.2 :** Créer le `StripeConnectService`

---

**Date :** 19 décembre 2025  
**Statut :** ✅ **ARCHITECTURE COMPLÈTE - MIGRATIONS PRÊTES**  
**Prochaine étape :** Exécuter les migrations et créer les modèles

