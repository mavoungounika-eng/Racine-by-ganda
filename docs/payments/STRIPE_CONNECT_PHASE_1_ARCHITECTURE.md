# 🏗️ PHASE 1 : STRIPE CONNECT - Architecture Complète

**Date :** 19 décembre 2025  
**Statut :** 📋 **ARCHITECTURE - PRÊT POUR IMPLÉMENTATION**  
**Type :** Marketplace Autonome (Direct Charges)

---

## 📚 Table des Matières

1. [Vue d'ensemble](#vue-densemble)
2. [Choix d'Architecture : Express vs Custom](#choix-darchitecture-express-vs-custom)
3. [Schéma de Base de Données](#schéma-de-base-de-données)
4. [Flux d'Onboarding](#flux-donboarding)
5. [Flux de Billing (Abonnement)](#flux-de-billing-abonnement)
6. [Flux de Checkout](#flux-de-checkout)
7. [Webhooks Stripe Requis](#webhooks-stripe-requis)
8. [Cas Limites et Gestion d'Erreurs](#cas-limites-et-gestion-derreurs)
9. [Sécurité et Conformité](#sécurité-et-conformité)
10. [Plan d'Implémentation](#plan-dimplémentation)

---

## 🎯 Vue d'Ensemble

### Contexte Business

**Objectif :** Permettre aux créateurs du marketplace de recevoir directement les paiements de leurs clients sur leur propre compte Stripe, sans que la plateforme ne prenne de commission.

**Modèle économique :**
- ✅ **Pas de commission par vente** - Les créateurs reçoivent 100% du montant
- ✅ **Abonnement mensuel** - Les créateurs paient un abonnement mensuel à la plateforme
- ✅ **Suspension automatique** - Si l'abonnement n'est pas payé, le compte créateur est suspendu

**Séparation légale :** La plateforme et les fonds des créateurs sont légalement séparés. Les paiements vont directement sur le compte Stripe du créateur.

### Architecture Générale

```
┌─────────────────┐
│   Client        │
│   (Acheteur)    │
└────────┬────────┘
         │
         │ Paiement
         ▼
┌─────────────────┐      ┌──────────────────┐
│   Plateforme    │──────▶│  Stripe Connect │
│   (RACINE)      │       │  (Express)      │
└────────┬────────┘       └────────┬─────────┘
         │                          │
         │ Route vers               │ Direct Charge
         │ compte créateur          │
         ▼                          ▼
┌─────────────────┐      ┌──────────────────┐
│   Créateur      │      │  Compte Stripe   │
│   (Vendeur)     │◀─────│  du Créateur     │
└─────────────────┘      └──────────────────┘
         │
         │ Abonnement mensuel
         ▼
┌─────────────────┐
│  Stripe Billing │
│  (Subscription) │
└─────────────────┘
```

**Explication simple :**
1. Un client achète un produit d'un créateur
2. Le paiement va directement sur le compte Stripe du créateur (pas sur la plateforme)
3. Le créateur paie un abonnement mensuel à la plateforme
4. Si l'abonnement n'est pas payé, le créateur ne peut plus vendre

---

## 🏛️ Choix d'Architecture : Express vs Custom

### Comparaison

| Critère | Express | Custom |
|---------|---------|--------|
| **Complexité** | ⭐ Simple | ⭐⭐⭐ Complexe |
| **Onboarding** | Stripe gère tout | Vous devez tout gérer |
| **KYC/Compliance** | Stripe gère | Vous devez gérer |
| **Maintenance** | Faible | Élevée |
| **Temps de développement** | Rapide | Long |
| **Contrôle** | Moyen | Total |
| **Coût** | Identique | Identique |

### ✅ Choix : Stripe Connect Express

**Justification :**

1. **Simplicité pour les créateurs :**
   - Onboarding en quelques clics
   - Stripe gère toute la collecte d'informations
   - Interface Stripe professionnelle et sécurisée

2. **Conformité automatique :**
   - Stripe gère KYC (Know Your Customer)
   - Stripe gère la vérification d'identité
   - Stripe gère les exigences réglementaires par pays

3. **Maintenance réduite :**
   - Moins de code à maintenir
   - Moins de bugs potentiels
   - Stripe met à jour automatiquement

4. **Sécurité :**
   - Stripe gère la sécurité des données sensibles
   - Pas de stockage de données bancaires sur notre serveur
   - Conformité PCI-DSS gérée par Stripe

5. **Rapidité de mise en marché :**
   - Développement plus rapide
   - Mise en production plus tôt
   - Moins de tests nécessaires

**Conclusion :** Pour un marketplace autonome sans commission, Express est le choix optimal. Custom serait nécessaire seulement si nous avions besoin d'un contrôle total sur l'expérience utilisateur (ce qui n'est pas le cas ici).

---

## 🗄️ Schéma de Base de Données

### Table 1 : `creator_profiles` (Existant - À Étendre)

**Fichier de migration existant :** `2024_11_24_000001_create_creator_profiles_table.php`

**Champs existants pertinents :**
- `id` - Identifiant unique
- `user_id` - Référence vers `users.id`
- `status` - Statut du profil (`pending`, `active`, `suspended`)
- `is_active` - Booléen pour activation
- `is_verified` - Booléen pour vérification

**Aucune modification nécessaire** - Cette table reste inchangée.

---

### Table 2 : `creator_stripe_accounts` (NOUVELLE)

**Fichier de migration à créer :** `2025_12_19_XXXXXX_create_creator_stripe_accounts_table.php`

**Description :** Stocke les informations de connexion Stripe Connect pour chaque créateur.

**Champs :**

| Champ | Type | Description | Contraintes |
|-------|------|-------------|-------------|
| `id` | bigint | Identifiant unique | Primary key, auto-increment |
| `creator_profile_id` | foreignId | Référence vers `creator_profiles.id` | Unique, cascade delete |
| `stripe_account_id` | string | ID du compte Stripe Connect (acct_xxx) | Unique, indexé |
| `account_type` | enum | Type de compte : `express` | Default: `express` |
| `onboarding_status` | enum | Statut onboarding : `pending`, `in_progress`, `complete`, `failed` | Default: `pending`, indexé |
| `charges_enabled` | boolean | Le créateur peut recevoir des paiements | Default: false, indexé |
| `payouts_enabled` | boolean | Le créateur peut recevoir des versements | Default: false, indexé |
| `details_submitted` | boolean | Informations KYC soumises | Default: false |
| `requirements_currently_due` | json | Liste des exigences en attente | Nullable |
| `requirements_eventually_due` | json | Liste des exigences futures | Nullable |
| `capabilities` | json | Capacités du compte (card_payments, transfers, etc.) | Nullable |
| `onboarding_link_url` | string | URL du lien d'onboarding Stripe | Nullable |
| `onboarding_link_expires_at` | timestamp | Date d'expiration du lien | Nullable |
| `last_synced_at` | timestamp | Dernière synchronisation avec Stripe | Nullable |
| `created_at` | timestamp | Date de création | |
| `updated_at` | timestamp | Date de mise à jour | |

**Index :**
- `creator_profile_id` (unique)
- `stripe_account_id` (unique)
- `onboarding_status`
- `charges_enabled`
- `payouts_enabled`

**Relations :**
- `creatorProfile()` : BelongsTo → CreatorProfile
- `subscription()` : HasOne → CreatorSubscription

**Explication des champs importants :**

- **`stripe_account_id`** : C'est l'identifiant unique du compte Stripe du créateur. Format : `acct_xxxxxxxxxxxxx`. C'est avec cet ID que nous allons router les paiements.

- **`onboarding_status`** : Suit l'état du processus d'onboarding :
  - `pending` : Le créateur n'a pas encore commencé
  - `in_progress` : Le créateur est en train de remplir le formulaire Stripe
  - `complete` : L'onboarding est terminé et le compte est actif
  - `failed` : L'onboarding a échoué (données invalides, refus, etc.)

- **`charges_enabled`** : Indique si le créateur peut recevoir des paiements. Doit être `true` pour qu'un checkout fonctionne.

- **`payouts_enabled`** : Indique si le créateur peut recevoir des versements (transfert d'argent vers son compte bancaire). Peut être `false` si KYC incomplet.

- **`requirements_currently_due`** : Liste des documents/informations que Stripe demande au créateur pour activer son compte. Exemple : `["external_account", "representative"]`

---

### Table 3 : `creator_subscriptions` (NOUVELLE)

**Fichier de migration à créer :** `2025_12_19_XXXXXX_create_creator_subscriptions_table.php`

**Description :** Gère les abonnements mensuels des créateurs à la plateforme.

**Champs :**

| Champ | Type | Description | Contraintes |
|-------|------|-------------|-------------|
| `id` | bigint | Identifiant unique | Primary key, auto-increment |
| `creator_profile_id` | foreignId | Référence vers `creator_profiles.id` | Unique, cascade delete, indexé |
| `stripe_subscription_id` | string | ID de l'abonnement Stripe (sub_xxx) | Unique, indexé |
| `stripe_customer_id` | string | ID du client Stripe (cus_xxx) | Indexé |
| `stripe_price_id` | string | ID du prix Stripe (price_xxx) | |
| `status` | enum | Statut : `incomplete`, `incomplete_expired`, `trialing`, `active`, `past_due`, `canceled`, `unpaid` | Default: `incomplete`, indexé |
| `current_period_start` | timestamp | Début de la période actuelle | |
| `current_period_end` | timestamp | Fin de la période actuelle | Indexé |
| `cancel_at_period_end` | boolean | Annulation à la fin de la période | Default: false |
| `canceled_at` | timestamp | Date d'annulation | Nullable |
| `trial_start` | timestamp | Début de la période d'essai | Nullable |
| `trial_end` | timestamp | Fin de la période d'essai | Nullable |
| `metadata` | json | Métadonnées supplémentaires | Nullable |
| `created_at` | timestamp | Date de création | |
| `updated_at` | timestamp | Date de mise à jour | |

**Index :**
- `creator_profile_id` (unique)
- `stripe_subscription_id` (unique)
- `stripe_customer_id`
- `status`
- `current_period_end` (pour trouver les abonnements expirés)

**Relations :**
- `creatorProfile()` : BelongsTo → CreatorProfile
- `invoices()` : HasMany → CreatorSubscriptionInvoice (si nécessaire)

**Explication des statuts :**

- **`incomplete`** : L'abonnement vient d'être créé mais le premier paiement n'a pas encore été effectué
- **`incomplete_expired`** : Le premier paiement a expiré (tentative échouée)
- **`trialing`** : Période d'essai active (si offerte)
- **`active`** : Abonnement actif et payé
- **`past_due`** : Paiement en retard mais toujours actif (période de grâce)
- **`canceled`** : Abonnement annulé (mais peut encore être actif jusqu'à la fin de la période)
- **`unpaid`** : Abonnement impayé (doit suspendre le créateur)

---

### Table 4 : `creator_subscription_invoices` (NOUVELLE - Optionnelle mais Recommandée)

**Fichier de migration à créer :** `2025_12_19_XXXXXX_create_creator_subscription_invoices_table.php`

**Description :** Historique des factures d'abonnement pour audit et suivi.

**Champs :**

| Champ | Type | Description | Contraintes |
|-------|------|-------------|-------------|
| `id` | bigint | Identifiant unique | Primary key |
| `creator_subscription_id` | foreignId | Référence vers `creator_subscriptions.id` | Indexé |
| `stripe_invoice_id` | string | ID de la facture Stripe (in_xxx) | Unique, indexé |
| `stripe_charge_id` | string | ID du paiement Stripe (ch_xxx) | Nullable, indexé |
| `amount` | decimal | Montant de la facture | 10,2 |
| `currency` | string | Devise (XAF, XOF, etc.) | Default: 'XAF' |
| `status` | enum | Statut : `draft`, `open`, `paid`, `uncollectible`, `void` | Default: `open`, indexé |
| `paid_at` | timestamp | Date de paiement | Nullable |
| `due_date` | timestamp | Date d'échéance | |
| `hosted_invoice_url` | string | URL de la facture Stripe | Nullable |
| `invoice_pdf` | string | URL du PDF de la facture | Nullable |
| `metadata` | json | Métadonnées | Nullable |
| `created_at` | timestamp | Date de création | |
| `updated_at` | timestamp | Date de mise à jour | |

**Index :**
- `creator_subscription_id`
- `stripe_invoice_id` (unique)
- `status`
- `paid_at`

**Relations :**
- `subscription()` : BelongsTo → CreatorSubscription

**Note :** Cette table est optionnelle mais recommandée pour :
- Historique complet des paiements
- Audit et conformité
- Support client (voir les factures)
- Rapports financiers

---

## 🔄 Flux d'Onboarding

### Vue d'Ensemble

L'onboarding permet à un créateur de connecter son compte Stripe à la plateforme pour pouvoir recevoir des paiements.

**Principe simple :** Le créateur clique sur un bouton, Stripe ouvre un formulaire sécurisé, le créateur remplit ses informations, et son compte est activé.

### Étape 1 : Création du Compte Stripe Connect

**Quand :** Le créateur clique sur "Connecter mon compte Stripe" dans son dashboard.

**Action :**
1. Vérifier que le créateur a un `CreatorProfile` actif
2. Créer un compte Stripe Connect Express via l'API Stripe
3. Enregistrer le `stripe_account_id` dans `creator_stripe_accounts`
4. Générer un lien d'onboarding Stripe
5. Rediriger le créateur vers ce lien

**Code conceptuel :**
```php
// Dans StripeConnectService
public function createAccount(CreatorProfile $creator): string
{
    // Créer le compte Stripe Connect Express
    $account = \Stripe\Account::create([
        'type' => 'express',
        'country' => 'CG', // Congo-Brazzaville
        'email' => $creator->user->email,
        'capabilities' => [
            'card_payments' => ['requested' => true],
            'transfers' => ['requested' => true],
        ],
    ]);
    
    // Enregistrer dans la base de données
    CreatorStripeAccount::create([
        'creator_profile_id' => $creator->id,
        'stripe_account_id' => $account->id,
        'onboarding_status' => 'in_progress',
        'charges_enabled' => false,
        'payouts_enabled' => false,
    ]);
    
    return $account->id;
}
```

### Étape 2 : Génération du Lien d'Onboarding

**Quand :** Immédiatement après la création du compte.

**Action :**
1. Demander à Stripe de créer un lien d'onboarding
2. Enregistrer l'URL et la date d'expiration
3. Retourner l'URL au créateur

**Code conceptuel :**
```php
public function createOnboardingLink(string $stripeAccountId): string
{
    $link = \Stripe\AccountLink::create([
        'account' => $stripeAccountId,
        'refresh_url' => route('creator.stripe.onboarding.refresh'),
        'return_url' => route('creator.stripe.onboarding.return'),
        'type' => 'account_onboarding',
    ]);
    
    // Enregistrer l'URL et l'expiration
    CreatorStripeAccount::where('stripe_account_id', $stripeAccountId)
        ->update([
            'onboarding_link_url' => $link->url,
            'onboarding_link_expires_at' => now()->addHours(24),
        ]);
    
    return $link->url;
}
```

### Étape 3 : Redirection vers Stripe

**Quand :** Le créateur clique sur le lien d'onboarding.

**Action :**
1. Rediriger le créateur vers l'URL Stripe
2. Stripe gère tout le formulaire (informations personnelles, bancaires, etc.)
3. Le créateur remplit le formulaire sur le site Stripe

**Note :** Nous n'avons rien à faire ici, Stripe gère tout.

### Étape 4 : Retour depuis Stripe

**Quand :** Le créateur termine le formulaire Stripe et clique sur "Retour à la plateforme".

**Action :**
1. Stripe redirige vers `return_url` (notre route)
2. Récupérer les informations du compte depuis Stripe
3. Mettre à jour `creator_stripe_accounts` avec les nouvelles informations
4. Vérifier si `charges_enabled` est `true`
5. Si oui, mettre `onboarding_status` à `complete`
6. Rediriger le créateur vers son dashboard avec un message de succès

**Code conceptuel :**
```php
public function handleOnboardingReturn(CreatorProfile $creator): void
{
    $stripeAccount = $creator->stripeAccount;
    
    // Récupérer les informations à jour depuis Stripe
    $account = \Stripe\Account::retrieve($stripeAccount->stripe_account_id);
    
    // Mettre à jour la base de données
    $stripeAccount->update([
        'charges_enabled' => $account->charges_enabled,
        'payouts_enabled' => $account->payouts_enabled,
        'details_submitted' => $account->details_submitted,
        'requirements_currently_due' => $account->requirements->currently_due ?? [],
        'requirements_eventually_due' => $account->requirements->eventually_due ?? [],
        'capabilities' => $account->capabilities,
        'onboarding_status' => $account->charges_enabled ? 'complete' : 'in_progress',
        'last_synced_at' => now(),
    ]);
    
    // Si le compte est activé, créer l'abonnement
    if ($account->charges_enabled) {
        $this->createSubscription($creator);
    }
}
```

### Étape 5 : Vérification du Statut

**Quand :** Le créateur consulte son dashboard.

**Action :**
1. Afficher le statut de l'onboarding
2. Si `onboarding_status` = `complete` et `charges_enabled` = `true` → Afficher "Compte activé"
3. Si `onboarding_status` = `in_progress` → Afficher "En attente de vérification"
4. Si `onboarding_status` = `failed` → Afficher "Échec, veuillez réessayer"

---

## 💳 Flux de Billing (Abonnement)

### Vue d'Ensemble

Chaque créateur doit payer un abonnement mensuel à la plateforme pour pouvoir vendre. Si l'abonnement n'est pas payé, le compte est suspendu.

**Principe simple :** Le créateur paie un abonnement mensuel (par exemple 10 000 XAF/mois). Si le paiement échoue, il ne peut plus vendre jusqu'à ce qu'il paie.

### Étape 1 : Création de l'Abonnement

**Quand :** Après que le créateur ait complété son onboarding Stripe Connect avec succès.

**Action :**
1. Créer un client Stripe Billing pour le créateur
2. Créer un produit et un prix dans Stripe (si pas déjà créé)
3. Créer l'abonnement Stripe
4. Enregistrer l'abonnement dans `creator_subscriptions`
5. Rediriger le créateur vers la page de paiement Stripe Checkout

**Code conceptuel :**
```php
public function createSubscription(CreatorProfile $creator): CreatorSubscription
{
    // 1. Créer ou récupérer le client Stripe Billing
    $customer = $this->getOrCreateBillingCustomer($creator);
    
    // 2. Créer ou récupérer le produit/prix
    $priceId = $this->getOrCreateSubscriptionPrice(); // Ex: price_xxx (10 000 XAF/mois)
    
    // 3. Créer l'abonnement
    $subscription = \Stripe\Subscription::create([
        'customer' => $customer->id,
        'items' => [['price' => $priceId]],
        'payment_behavior' => 'default_incomplete',
        'payment_settings' => [
            'save_default_payment_method' => 'on_subscription',
        ],
        'expand' => ['latest_invoice.payment_intent'],
    ]);
    
    // 4. Enregistrer dans la base de données
    $creatorSubscription = CreatorSubscription::create([
        'creator_profile_id' => $creator->id,
        'stripe_subscription_id' => $subscription->id,
        'stripe_customer_id' => $customer->id,
        'stripe_price_id' => $priceId,
        'status' => $subscription->status, // 'incomplete'
        'current_period_start' => Carbon::createFromTimestamp($subscription->current_period_start),
        'current_period_end' => Carbon::createFromTimestamp($subscription->current_period_end),
    ]);
    
    return $creatorSubscription;
}
```

### Étape 2 : Paiement de l'Abonnement

**Quand :** Le créateur est redirigé vers Stripe Checkout pour payer son premier abonnement.

**Action :**
1. Stripe Checkout s'affiche avec le montant de l'abonnement
2. Le créateur saisit ses informations de paiement
3. Stripe traite le paiement
4. Webhook `invoice.paid` est reçu (voir section Webhooks)
5. L'abonnement passe à `active`
6. Le créateur peut maintenant vendre

**Note :** Le paiement se fait via Stripe Checkout, nous n'avons pas de code spécifique à écrire ici.

### Étape 3 : Renouvellement Mensuel

**Quand :** Chaque mois, à la date d'échéance de l'abonnement.

**Action :**
1. Stripe facture automatiquement le créateur
2. Si le paiement réussit :
   - Webhook `invoice.paid` est reçu
   - L'abonnement reste `active`
   - Nouvelle période commence
3. Si le paiement échoue :
   - Webhook `invoice.payment_failed` est reçu
   - L'abonnement passe à `past_due` puis `unpaid`
   - Le créateur est suspendu (voir section Suspension)

### Étape 4 : Gestion des Échecs de Paiement

**Quand :** Un paiement d'abonnement échoue.

**Action :**
1. Webhook `invoice.payment_failed` est reçu
2. Vérifier le nombre de tentatives
3. Si c'est la première tentative :
   - Envoyer un email au créateur
   - L'abonnement passe à `past_due` (période de grâce)
4. Si c'est la dernière tentative :
   - L'abonnement passe à `unpaid`
   - Suspendre le créateur (voir section Suspension)

---

## 🛒 Flux de Checkout

### Vue d'Ensemble

Quand un client achète un produit d'un créateur, le paiement doit aller directement sur le compte Stripe du créateur (pas sur la plateforme).

**Principe simple :** Le client paie, l'argent va directement sur le compte Stripe du créateur, la plateforme ne touche rien.

### Étape 1 : Vérifications Pré-Checkout

**Quand :** Le client clique sur "Passer commande".

**Action :**
1. Vérifier que le créateur a un compte Stripe Connect actif
2. Vérifier que `charges_enabled` = `true`
3. Vérifier que l'abonnement est `active`
4. Vérifier que le créateur n'est pas suspendu
5. Si une vérification échoue, afficher une erreur

**Code conceptuel :**
```php
public function canCreatorReceivePayments(CreatorProfile $creator): bool
{
    // 1. Vérifier le compte Stripe Connect
    $stripeAccount = $creator->stripeAccount;
    if (!$stripeAccount || $stripeAccount->onboarding_status !== 'complete') {
        return false;
    }
    
    // 2. Vérifier que les charges sont activées
    if (!$stripeAccount->charges_enabled) {
        return false;
    }
    
    // 3. Vérifier l'abonnement
    $subscription = $creator->subscription;
    if (!$subscription || $subscription->status !== 'active') {
        return false;
    }
    
    // 4. Vérifier le statut du créateur
    if ($creator->status !== 'active' || !$creator->is_active) {
        return false;
    }
    
    return true;
}
```

### Étape 2 : Création de la Session de Paiement

**Quand :** Toutes les vérifications sont OK.

**Action :**
1. Créer une session Stripe Checkout
2. **Important :** Spécifier `stripe_account` pour router vers le compte du créateur
3. Rediriger le client vers Stripe Checkout

**Code conceptuel :**
```php
public function createCheckoutSession(Order $order, CreatorProfile $creator): string
{
    // Récupérer le compte Stripe du créateur
    $stripeAccountId = $creator->stripeAccount->stripe_account_id;
    
    // Créer la session de paiement sur le compte du créateur
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [
            [
                'price_data' => [
                    'currency' => 'xaf',
                    'product_data' => [
                        'name' => $order->order_number,
                    ],
                    'unit_amount' => $order->total * 100, // En centimes
                ],
                'quantity' => 1,
            ],
        ],
        'mode' => 'payment',
        'success_url' => route('checkout.success', $order),
        'cancel_url' => route('checkout.cancel', $order),
        'customer_email' => $order->user->email,
    ], [
        'stripe_account' => $stripeAccountId, // ⚠️ IMPORTANT : Route vers le compte créateur
    ]);
    
    // Enregistrer la session dans la commande
    $order->update([
        'stripe_checkout_session_id' => $session->id,
        'stripe_account_id' => $stripeAccountId,
    ]);
    
    return $session->url;
}
```

**Explication importante :**

Le paramètre `'stripe_account' => $stripeAccountId` dans les options de l'API Stripe indique à Stripe de créer la session de paiement **sur le compte du créateur**, pas sur le compte de la plateforme. C'est ainsi que le paiement va directement au créateur.

### Étape 3 : Paiement par le Client

**Quand :** Le client est redirigé vers Stripe Checkout.

**Action :**
1. Le client saisit ses informations de carte sur Stripe
2. Stripe traite le paiement **sur le compte du créateur**
3. Le webhook `checkout.session.completed` est reçu (voir section Webhooks)
4. La commande est confirmée

**Note :** Le paiement se fait sur le compte Stripe du créateur, pas sur celui de la plateforme.

### Étape 4 : Confirmation

**Quand :** Le paiement est réussi.

**Action :**
1. Webhook `checkout.session.completed` est reçu
2. Vérifier que la session appartient bien au compte du créateur
3. Mettre à jour le statut de la commande
4. Envoyer les notifications

---

## 📡 Webhooks Stripe Requis

### Vue d'Ensemble

Nous devons écouter plusieurs webhooks Stripe pour gérer les abonnements et les comptes Connect. **Important :** Ces webhooks sont différents de ceux déjà gérés par `WebhookController@stripe`. Nous devons créer un nouveau contrôleur ou étendre l'existant **sans modifier** la logique existante.

### Webhook 1 : `account.updated`

**Quand :** Le statut d'un compte Stripe Connect change (onboarding complété, KYC validé, etc.).

**Action :**
1. Récupérer le `stripe_account_id` depuis le webhook
2. Trouver le `CreatorStripeAccount` correspondant
3. Mettre à jour les informations (charges_enabled, payouts_enabled, requirements, etc.)
4. Si `charges_enabled` passe à `true` et que l'onboarding était `in_progress`, créer l'abonnement

**Code conceptuel :**
```php
public function handleAccountUpdated(array $event): void
{
    $account = $event['data']['object'];
    $stripeAccountId = $account['id'];
    
    $creatorAccount = CreatorStripeAccount::where('stripe_account_id', $stripeAccountId)->first();
    if (!$creatorAccount) {
        return; // Compte non trouvé, ignorer
    }
    
    // Mettre à jour les informations
    $creatorAccount->update([
        'charges_enabled' => $account['charges_enabled'],
        'payouts_enabled' => $account['payouts_enabled'],
        'details_submitted' => $account['details_submitted'],
        'requirements_currently_due' => $account['requirements']['currently_due'] ?? [],
        'requirements_eventually_due' => $account['requirements']['eventually_due'] ?? [],
        'capabilities' => $account['capabilities'],
        'onboarding_status' => $account['charges_enabled'] ? 'complete' : 'in_progress',
        'last_synced_at' => now(),
    ]);
    
    // Si le compte est maintenant activé et qu'il n'y a pas d'abonnement, en créer un
    if ($account['charges_enabled'] && !$creatorAccount->creatorProfile->subscription) {
        $this->createSubscription($creatorAccount->creatorProfile);
    }
}
```

### Webhook 2 : `checkout.session.completed` (Connect)

**Quand :** Un client termine un paiement sur le compte Stripe d'un créateur.

**Action :**
1. Vérifier que la session appartient à un compte Connect (via `stripe_account`)
2. Trouver la commande correspondante
3. Mettre à jour le statut de la commande
4. Envoyer les notifications

**Note :** Ce webhook est différent de celui géré actuellement car il concerne les paiements sur les comptes Connect, pas sur le compte de la plateforme.

### Webhook 3 : `customer.subscription.created`

**Quand :** Un nouvel abonnement est créé dans Stripe Billing.

**Action :**
1. Récupérer les informations de l'abonnement
2. Mettre à jour `creator_subscriptions` avec les informations
3. Logger l'événement

### Webhook 4 : `customer.subscription.updated`

**Quand :** Un abonnement est modifié (renouvelé, annulé, etc.).

**Action :**
1. Mettre à jour le statut de l'abonnement dans `creator_subscriptions`
2. Si le statut passe à `unpaid`, suspendre le créateur
3. Si le statut passe à `active` après avoir été `unpaid`, réactiver le créateur

### Webhook 5 : `invoice.paid`

**Quand :** Une facture d'abonnement est payée avec succès.

**Action :**
1. Trouver l'abonnement correspondant
2. Mettre à jour le statut à `active` si nécessaire
3. Enregistrer la facture dans `creator_subscription_invoices`
4. Réactiver le créateur s'il était suspendu

### Webhook 6 : `invoice.payment_failed`

**Quand :** Le paiement d'une facture d'abonnement échoue.

**Action :**
1. Trouver l'abonnement correspondant
2. Mettre à jour le statut à `past_due` ou `unpaid`
3. Envoyer un email au créateur
4. Si c'est la dernière tentative, suspendre le créateur

### Webhook 7 : `invoice.payment_action_required`

**Quand :** Une action est requise pour payer une facture (ex: 3D Secure).

**Action :**
1. Envoyer un email au créateur avec le lien de paiement
2. Logger l'événement

---

## ⚠️ Cas Limites et Gestion d'Erreurs

### Cas 1 : KYC Incomplet

**Situation :** Le créateur a commencé l'onboarding mais n'a pas complété toutes les informations requises par Stripe.

**Détection :**
- `charges_enabled` = `false`
- `requirements_currently_due` contient des éléments
- `onboarding_status` = `in_progress`

**Action :**
1. Afficher un message au créateur : "Votre compte nécessite des informations supplémentaires"
2. Afficher la liste des exigences (`requirements_currently_due`)
3. Proposer de générer un nouveau lien d'onboarding
4. Empêcher le checkout si `charges_enabled` = `false`

**Code conceptuel :**
```php
public function getOnboardingRequirements(CreatorProfile $creator): array
{
    $account = $creator->stripeAccount;
    if (!$account) {
        return ['error' => 'Aucun compte Stripe trouvé'];
    }
    
    return [
        'charges_enabled' => $account->charges_enabled,
        'payouts_enabled' => $account->payouts_enabled,
        'currently_due' => $account->requirements_currently_due ?? [],
        'eventually_due' => $account->requirements_eventually_due ?? [],
        'needs_onboarding' => !$account->charges_enabled,
    ];
}
```

### Cas 2 : Abonnement Impayé

**Situation :** Le créateur n'a pas payé son abonnement mensuel.

**Détection :**
- `creator_subscriptions.status` = `unpaid` ou `past_due`
- `current_period_end` < maintenant (pour `past_due`)

**Action :**
1. Suspendre automatiquement le créateur :
   - Mettre `creator_profiles.status` = `suspended`
   - Mettre `creator_profiles.is_active` = `false`
2. Empêcher tous les checkouts pour ce créateur
3. Envoyer un email au créateur avec un lien de paiement
4. Afficher un message dans le dashboard : "Votre abonnement est impayé. Veuillez régulariser pour continuer à vendre."

**Code conceptuel :**
```php
public function suspendCreatorForUnpaidSubscription(CreatorProfile $creator): void
{
    $creator->update([
        'status' => 'suspended',
        'is_active' => false,
    ]);
    
    // Envoyer un email
    Mail::to($creator->user->email)->send(new SubscriptionUnpaidMail($creator));
    
    // Logger l'événement
    Log::warning('Creator suspended for unpaid subscription', [
        'creator_id' => $creator->id,
        'subscription_id' => $creator->subscription->stripe_subscription_id,
    ]);
}
```

### Cas 3 : Compte Stripe Désactivé

**Situation :** Stripe a désactivé le compte du créateur (fraude, violation des règles, etc.).

**Détection :**
- Webhook `account.updated` avec `charges_enabled` = `false` et `details_submitted` = `true`
- Ou `payouts_enabled` = `false` alors qu'il était `true` avant

**Action :**
1. Suspendre le créateur
2. Envoyer un email au créateur : "Votre compte Stripe a été désactivé. Contactez Stripe pour plus d'informations."
3. Empêcher tous les checkouts
4. Logger l'événement pour audit

### Cas 4 : Abonnement Annulé

**Situation :** Le créateur a annulé son abonnement.

**Détection :**
- `creator_subscriptions.status` = `canceled`
- `cancel_at_period_end` = `true`

**Action :**
1. Laisser le créateur vendre jusqu'à la fin de la période (`current_period_end`)
2. À la fin de la période, suspendre automatiquement
3. Envoyer un email de rappel avant la fin
4. Proposer de réactiver l'abonnement

### Cas 5 : Période d'Essai

**Situation :** Le créateur est en période d'essai (si offerte).

**Détection :**
- `creator_subscriptions.status` = `trialing`
- `trial_end` > maintenant

**Action :**
1. Laisser le créateur vendre normalement
2. Afficher un message : "Période d'essai active jusqu'au [date]"
3. À la fin de l'essai, facturer automatiquement
4. Si le paiement échoue, suspendre

### Cas 6 : Compte Créateur Suspendu Manuellement

**Situation :** Un admin a suspendu manuellement le créateur.

**Détection :**
- `creator_profiles.status` = `suspended` (peu importe l'abonnement)

**Action :**
1. Empêcher tous les checkouts
2. Afficher un message : "Votre compte est suspendu. Contactez le support."
3. Ne pas suspendre l'abonnement Stripe (le créateur peut toujours payer)

### Cas 7 : Multiple Tentatives de Paiement Échouées

**Situation :** Plusieurs tentatives de paiement d'abonnement ont échoué.

**Détection :**
- Plusieurs webhooks `invoice.payment_failed` consécutifs
- `creator_subscriptions.status` = `unpaid`

**Action :**
1. Suspendre après 3 tentatives échouées
2. Envoyer un email après chaque tentative
3. Proposer un lien de paiement direct
4. Après suspension, réactiver automatiquement si le paiement réussit

---

## 🔒 Sécurité et Conformité

### Séparation des Fonds

**Principe :** Les fonds des créateurs ne passent jamais par le compte de la plateforme. Ils vont directement sur le compte Stripe du créateur.

**Implémentation :**
- Utiliser `stripe_account` dans toutes les opérations de paiement
- Ne jamais créer de `PaymentIntent` ou `Charge` sur le compte de la plateforme pour les ventes créateurs
- Utiliser uniquement le compte de la plateforme pour les abonnements

### Vérifications de Sécurité

**Avant chaque checkout :**
1. Vérifier que le créateur existe et est actif
2. Vérifier que le compte Stripe Connect est actif
3. Vérifier que l'abonnement est payé
4. Vérifier que le créateur n'est pas suspendu

**Avant chaque opération sensible :**
1. Vérifier les permissions de l'utilisateur
2. Logger toutes les opérations importantes
3. Valider les données d'entrée

### Conformité KYC

**Stripe gère automatiquement :**
- Vérification d'identité
- Vérification des documents
- Vérification bancaire
- Conformité réglementaire par pays

**Notre responsabilité :**
- Vérifier que `charges_enabled` = `true` avant de permettre les ventes
- Ne pas permettre les ventes si KYC incomplet
- Afficher clairement les exigences au créateur

---

## 📋 Plan d'Implémentation

### Phase 1.1 : Base de Données (Priorité : HAUTE)

**Tâches :**
1. ✅ Créer migration `create_creator_stripe_accounts_table`
2. ✅ Créer migration `create_creator_subscriptions_table`
3. ✅ Créer migration `create_creator_subscription_invoices_table` (optionnel)
4. ✅ Exécuter les migrations
5. ✅ Créer les modèles Eloquent

**Estimation :** 2-3 heures

### Phase 1.2 : Service Stripe Connect (Priorité : HAUTE)

**Tâches :**
1. ✅ Créer `StripeConnectService` pour gérer les comptes Connect
2. ✅ Implémenter `createAccount()`
3. ✅ Implémenter `createOnboardingLink()`
4. ✅ Implémenter `syncAccountStatus()`
5. ✅ Implémenter `canCreatorReceivePayments()`

**Estimation :** 4-6 heures

### Phase 1.3 : Service Billing (Priorité : HAUTE)

**Tâches :**
1. ✅ Créer `CreatorSubscriptionService` pour gérer les abonnements
2. ✅ Implémenter `createSubscription()`
3. ✅ Implémenter `handleInvoicePaid()`
4. ✅ Implémenter `handleInvoiceFailed()`
5. ✅ Implémenter `suspendCreatorForUnpaidSubscription()`

**Estimation :** 4-6 heures

### Phase 1.4 : Contrôleur Onboarding (Priorité : HAUTE)

**Tâches :**
1. ✅ Créer `CreatorStripeConnectController`
2. ✅ Implémenter `showOnboarding()` - Afficher le statut
3. ✅ Implémenter `startOnboarding()` - Créer le compte et générer le lien
4. ✅ Implémenter `handleReturn()` - Gérer le retour depuis Stripe
5. ✅ Implémenter `refreshLink()` - Régénérer le lien d'onboarding

**Estimation :** 3-4 heures

### Phase 1.5 : Modification du Checkout (Priorité : HAUTE)

**Tâches :**
1. ✅ Modifier `CheckoutController` pour vérifier Stripe Connect
2. ✅ Modifier `CardPaymentService` pour utiliser `stripe_account`
3. ✅ Ajouter les vérifications pré-checkout
4. ✅ Tester le flux complet

**Estimation :** 4-6 heures

### Phase 1.6 : Webhooks Connect (Priorité : MOYENNE)

**Tâches :**
1. ✅ Créer `StripeConnectWebhookController` (nouveau, séparé)
2. ✅ Implémenter `handleAccountUpdated()`
3. ✅ Implémenter `handleSubscriptionCreated()`
4. ✅ Implémenter `handleSubscriptionUpdated()`
5. ✅ Implémenter `handleInvoicePaid()`
6. ✅ Implémenter `handleInvoiceFailed()`
7. ✅ Configurer les routes webhooks

**Estimation :** 6-8 heures

### Phase 1.7 : Dashboard Créateur (Priorité : MOYENNE)

**Tâches :**
1. ✅ Afficher le statut Stripe Connect
2. ✅ Afficher le statut de l'abonnement
3. ✅ Afficher les factures
4. ✅ Afficher les exigences KYC si incomplètes
5. ✅ Bouton "Connecter mon compte Stripe"

**Estimation :** 4-6 heures

### Phase 1.8 : Tests et Validation (Priorité : HAUTE)

**Tâches :**
1. ✅ Tests unitaires pour les services
2. ✅ Tests d'intégration pour le flux complet
3. ✅ Tests des webhooks
4. ✅ Tests des cas limites
5. ✅ Tests en mode test Stripe

**Estimation :** 8-10 heures

---

## 📊 Estimation Totale

**Temps total estimé :** 35-49 heures (environ 1-2 semaines de développement)

**Priorités :**
- **Semaine 1 :** Phases 1.1 à 1.5 (Base de données, services, onboarding, checkout)
- **Semaine 2 :** Phases 1.6 à 1.8 (Webhooks, dashboard, tests)

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

**Date de création :** 19 décembre 2025  
**Statut :** 📋 **ARCHITECTURE COMPLÈTE - PRÊT POUR IMPLÉMENTATION**  
**Prochaine étape :** Commencer la Phase 1.1 (Base de données)

