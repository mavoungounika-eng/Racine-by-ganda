# 📋 RAPPORT ÉTAPE 2 — createAccount() IMPLÉMENTATION

**Date** : 2025-12-19  
**Service** : `StripeConnectService`  
**Méthode** : `createAccount(CreatorProfile $creator)`  
**Phase** : PHASE 1.1 — Implémentation Progressive

---

## ✅ ÉTAPE 2 TERMINÉE

### 🔧 Signature exacte de la méthode

```php
public function createAccount(CreatorProfile $creator): CreatorStripeAccount
```

**Paramètres** :
- `CreatorProfile $creator` : Le profil du créateur pour lequel créer le compte Stripe Connect Express

**Valeur de retour** :
- `CreatorStripeAccount` : Le modèle Eloquent du compte Stripe Connect créé et persisté en base de données

**Exceptions lancées** :
- `\RuntimeException` : Si le créateur a déjà un compte Stripe Connect
- `\RuntimeException` : Si le créateur n'a pas d'utilisateur associé
- `\RuntimeException` : Si l'utilisateur n'a pas d'adresse email
- `ApiErrorException` : Si l'API Stripe retourne une erreur lors de la création du compte

**Rôle de la méthode** :
Créer un compte Stripe Connect Express pour un créateur, avec les capacités `card_payments` et `transfers`, et persister les informations initiales dans la base de données.

---

## 🔄 Flux Stripe → Base de données

### Étape 1 : Validations préalables

Avant tout appel à l'API Stripe, la méthode effectue trois validations :

1. **Vérification de l'unicité du compte** :
   ```php
   $existingAccount = CreatorStripeAccount::where('creator_profile_id', $creator->id)->first();
   ```
   - Recherche un compte Stripe existant pour ce créateur
   - Si trouvé → Lance une `\RuntimeException` avec un message explicite

2. **Vérification de l'utilisateur associé** :
   ```php
   if (!$creator->user) { ... }
   ```
   - Vérifie que le créateur a une relation `user()` valide
   - Si absent → Lance une `\RuntimeException`

3. **Vérification de l'email** :
   ```php
   $userEmail = $creator->user->email;
   if (empty($userEmail)) { ... }
   ```
   - Récupère l'email de l'utilisateur
   - Si vide → Lance une `\RuntimeException`

### Étape 2 : Création du compte Stripe Connect Express

Une fois les validations passées, la méthode appelle l'API Stripe :

```php
$stripeAccount = Account::create([
    'type' => 'express',
    'country' => 'CG', // République du Congo
    'email' => $userEmail,
    'capabilities' => [
        'card_payments' => ['requested' => true],
        'transfers' => ['requested' => true],
    ],
]);
```

**Paramètres Stripe** :
- `type` : `'express'` — Type de compte Stripe Connect Express (géré par Stripe)
- `country` : `'CG'` — Code ISO du pays (République du Congo)
- `email` : Email de l'utilisateur du créateur
- `capabilities` : Capacités demandées pour le compte
  - `card_payments` : Permet au créateur de recevoir des paiements par carte
  - `transfers` : Permet au créateur de recevoir des transferts directs

**Réponse Stripe** :
L'API Stripe retourne un objet `Account` contenant :
- `id` : Identifiant unique du compte (format `acct_xxx`)
- `requirements` : Exigences KYC (currently_due, eventually_due)
- `capabilities` : Statut des capacités demandées

### Étape 3 : Persistance en base de données

Après la création réussie du compte Stripe, la méthode persiste les informations dans la table `creator_stripe_accounts` :

```php
$creatorStripeAccount = CreatorStripeAccount::create([
    'creator_profile_id' => $creator->id,
    'stripe_account_id' => $stripeAccount->id,
    'account_type' => 'express',
    'onboarding_status' => 'in_progress',
    'charges_enabled' => false,
    'payouts_enabled' => false,
    'details_submitted' => false,
    'requirements_currently_due' => $stripeAccount->requirements->currently_due ?? null,
    'requirements_eventually_due' => $stripeAccount->requirements->eventually_due ?? null,
    'capabilities' => $this->extractCapabilities($stripeAccount),
]);
```

**Méthode helper** : `extractCapabilities()`
- Extrait les capacités du compte Stripe au format array
- Structure : `['card_payments' => ['status' => '...', 'requested' => true], ...]`
- Retourne `null` si les capacités ne sont pas disponibles

### Étape 4 : Logging et retour

- **Log de succès** : Enregistre un log `info` avec les identifiants du créateur et du compte Stripe
- **Retour** : Retourne l'instance `CreatorStripeAccount` créée

### Gestion des erreurs Stripe

Si l'API Stripe retourne une erreur (`ApiErrorException`) :
- **Log d'erreur** : Enregistre un log `error` avec :
  - L'ID du créateur
  - Le message d'erreur Stripe
  - Le code d'erreur Stripe
- **Propagation** : Relance l'exception pour que l'appelant puisse la gérer

---

## 📊 Champs persistés

### Champs obligatoires (toujours présents)

| Champ | Valeur | Source | Description |
|-------|--------|---------|-------------|
| `creator_profile_id` | `$creator->id` | Paramètre | ID du créateur (clé étrangère) |
| `stripe_account_id` | `$stripeAccount->id` | API Stripe | Identifiant unique du compte Stripe (format `acct_xxx`) |
| `account_type` | `'express'` | Constante | Type de compte Stripe Connect |
| `onboarding_status` | `'in_progress'` | Constante | Statut initial de l'onboarding |
| `charges_enabled` | `false` | Constante | Le créateur ne peut pas encore recevoir de paiements |
| `payouts_enabled` | `false` | Constante | Le créateur ne peut pas encore recevoir de versements |
| `details_submitted` | `false` | Constante | Les informations KYC ne sont pas encore soumises |

### Champs conditionnels (peuvent être null)

| Champ | Valeur | Source | Description |
|-------|--------|---------|-------------|
| `requirements_currently_due` | `$stripeAccount->requirements->currently_due ?? null` | API Stripe | Exigences KYC en attente (array JSON) |
| `requirements_eventually_due` | `$stripeAccount->requirements->eventually_due ?? null` | API Stripe | Exigences KYC futures (array JSON) |
| `capabilities` | `extractCapabilities($stripeAccount)` | API Stripe | Statut des capacités demandées (array JSON) |

### Champs non initialisés (null par défaut)

Ces champs ne sont pas remplis lors de la création du compte (seront remplis plus tard) :
- `onboarding_link_url` : URL du lien d'onboarding (rempli par `createOnboardingLink()`)
- `onboarding_link_expires_at` : Date d'expiration du lien (rempli par `createOnboardingLink()`)
- `last_synced_at` : Date de dernière synchronisation (rempli par `syncAccountStatus()`)

### Champs automatiques

- `id` : Généré automatiquement par la base de données
- `created_at` : Timestamp de création (géré par Eloquent)
- `updated_at` : Timestamp de mise à jour (géré par Eloquent)

---

## 🔐 Sécurité et validations

### Validations métier

1. **Unicité du compte Stripe par créateur** :
   - **Vérification** : Recherche d'un compte existant avant création
   - **Raison** : Un créateur ne peut avoir qu'un seul compte Stripe Connect
   - **Erreur** : `\RuntimeException` avec message explicite

2. **Présence de l'utilisateur** :
   - **Vérification** : `$creator->user` doit exister
   - **Raison** : L'email est requis pour créer un compte Stripe
   - **Erreur** : `\RuntimeException` si l'utilisateur est absent

3. **Présence de l'email** :
   - **Vérification** : `$creator->user->email` ne doit pas être vide
   - **Raison** : Stripe exige un email pour créer un compte Connect
   - **Erreur** : `\RuntimeException` si l'email est vide

### Sécurité des données

1. **Pas de log de données sensibles** :
   - Les logs ne contiennent que des identifiants (ID créateur, ID compte Stripe)
   - Aucun email, aucune donnée personnelle n'est loggée

2. **Gestion des exceptions Stripe** :
   - Les erreurs Stripe sont loggées avec le code d'erreur
   - Les exceptions sont propagées pour permettre une gestion personnalisée

3. **Validation des données Stripe** :
   - Utilisation de l'opérateur `??` pour gérer les valeurs null
   - Extraction sécurisée des capacités via une méthode dédiée

### Conformité Stripe Connect

1. **Type de compte** : Express (conforme à l'architecture Phase 1)
2. **Pays** : CG (République du Congo) — conforme aux exigences du projet
3. **Capacités** : `card_payments` et `transfers` — nécessaires pour le marketplace

---

## ⚠️ Cas d'erreurs anticipés

### Erreurs métier (RuntimeException)

1. **Compte Stripe déjà existant** :
   - **Cause** : Tentative de créer un compte pour un créateur qui en a déjà un
   - **Message** : `"Le créateur {id} possède déjà un compte Stripe Connect (ID: {stripe_account_id})."`
   - **Gestion** : L'exception est lancée avant tout appel Stripe
   - **Solution** : Utiliser `syncAccountStatus()` ou récupérer le compte existant

2. **Utilisateur absent** :
   - **Cause** : Le créateur n'a pas de relation `user()` valide
   - **Message** : `"Le créateur {id} n'a pas d'utilisateur associé. Impossible de créer un compte Stripe Connect."`
   - **Gestion** : L'exception est lancée avant tout appel Stripe
   - **Solution** : Créer ou associer un utilisateur au créateur

3. **Email manquant** :
   - **Cause** : L'utilisateur du créateur n'a pas d'adresse email
   - **Message** : `"L'utilisateur du créateur {id} n'a pas d'adresse email. Impossible de créer un compte Stripe Connect."`
   - **Gestion** : L'exception est lancée avant tout appel Stripe
   - **Solution** : Ajouter une adresse email à l'utilisateur

### Erreurs API Stripe (ApiErrorException)

1. **Erreur de réseau** :
   - **Cause** : Problème de connexion à l'API Stripe
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Réessayer la requête ou vérifier la connectivité

2. **Clé API invalide** :
   - **Cause** : La clé Stripe configurée est invalide ou expirée
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Vérifier la configuration `STRIPE_SECRET` dans `.env`

3. **Email déjà utilisé** :
   - **Cause** : L'email est déjà associé à un autre compte Stripe
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Utiliser un email différent ou récupérer le compte existant

4. **Pays non supporté** :
   - **Cause** : Le pays 'CG' n'est pas supporté par Stripe Connect (peu probable)
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Vérifier la liste des pays supportés par Stripe

5. **Limite de taux dépassée** :
   - **Cause** : Trop de requêtes à l'API Stripe en peu de temps
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Implémenter un système de retry avec backoff exponentiel

### Erreurs de base de données

1. **Contrainte d'unicité violée** :
   - **Cause** : Race condition (deux requêtes simultanées créent un compte pour le même créateur)
   - **Gestion** : Exception Eloquent non gérée explicitement (sera propagée)
   - **Solution** : Utiliser une transaction ou un verrou (lock) sur la création

2. **Clé étrangère invalide** :
   - **Cause** : Le `creator_profile_id` n'existe pas dans la table `creator_profiles`
   - **Gestion** : Exception Eloquent non gérée explicitement (sera propagée)
   - **Solution** : Vérifier que le créateur existe avant d'appeler la méthode

---

## ❌ Ce qui est volontairement exclu

### Création d'abonnement

- **Exclusion** : La méthode ne crée pas d'abonnement Stripe Billing
- **Raison** : Les abonnements sont gérés par `CreatorSubscriptionService`
- **Quand** : L'abonnement sera créé après l'onboarding complet du compte Stripe

### Génération de lien d'onboarding

- **Exclusion** : La méthode ne génère pas de lien d'onboarding Stripe
- **Raison** : Le lien d'onboarding est généré par `createOnboardingLink()` (ÉTAPE 3)
- **Quand** : Le lien sera généré après la création du compte, via un appel séparé

### Redirection ou UI

- **Exclusion** : La méthode ne fait aucune redirection ni génération d'UI
- **Raison** : Le service est pur (sans UI), conforme aux exigences
- **Quand** : Les redirections seront gérées par les contrôleurs

### Traitement de webhook

- **Exclusion** : La méthode ne traite pas les webhooks Stripe
- **Raison** : Les webhooks sont gérés par `StripeConnectWebhookController`
- **Quand** : Les webhooks seront traités après la création du compte

### Envoi de notifications

- **Exclusion** : La méthode n'envoie pas d'email ou de notification
- **Raison** : Les notifications sont gérées par `NotificationService`
- **Quand** : Les notifications seront envoyées par les contrôleurs ou les jobs

### Suspension de créateur

- **Exclusion** : La méthode ne suspend pas le créateur
- **Raison** : La suspension est gérée par `CreatorSuspensionService`
- **Quand** : La suspension sera gérée après vérification de l'abonnement

### Vérification d'éligibilité

- **Exclusion** : La méthode ne vérifie pas si le créateur peut recevoir des paiements
- **Raison** : La vérification est gérée par `canCreatorReceivePayments()` (ÉTAPE 5)
- **Quand** : La vérification sera faite avant chaque checkout

---

## 🧠 Justifications architecturales

### Séparation des responsabilités

1. **Service pur** :
   - Le service ne contient que la logique métier Stripe Connect
   - Aucune dépendance à l'UI, aux webhooks, ou aux notifications
   - Facilite les tests unitaires et l'intégration

2. **Méthode atomique** :
   - `createAccount()` fait une seule chose : créer un compte Stripe
   - Pas de side effects (pas d'abonnement, pas de lien, pas de notification)
   - Facilite la réutilisation et la maintenance

3. **Gestion d'erreurs explicite** :
   - Les exceptions sont claires et documentées
   - Les logs sont structurés pour faciliter le debugging
   - Les erreurs Stripe sont propagées pour permettre une gestion personnalisée

### Conformité Stripe Connect Express

1. **Type Express** :
   - Choix conforme à l'architecture Phase 1
   - Stripe gère l'onboarding et la KYC
   - Le créateur complète ses informations via le lien d'onboarding

2. **Capacités demandées** :
   - `card_payments` : Nécessaire pour recevoir des paiements par carte
   - `transfers` : Nécessaire pour recevoir des transferts directs (sans commission plateforme)
   - Les capacités seront activées après l'onboarding complet

3. **Statut initial** :
   - `onboarding_status = 'in_progress'` : Le compte est créé mais l'onboarding n'est pas terminé
   - `charges_enabled = false` : Le créateur ne peut pas encore recevoir de paiements
   - `payouts_enabled = false` : Le créateur ne peut pas encore recevoir de versements

### Persistance des données

1. **Données complètes** :
   - Toutes les informations nécessaires sont persistées dès la création
   - Les exigences KYC et les capacités sont stockées pour référence future

2. **Format JSON** :
   - Les données complexes (requirements, capabilities) sont stockées en JSON
   - Facilite l'extraction et la mise à jour ultérieure

3. **Timestamps automatiques** :
   - `created_at` et `updated_at` sont gérés par Eloquent
   - Permet de tracer l'historique des comptes

---

## 📝 Notes techniques

### Méthode helper : `extractCapabilities()`

Cette méthode privée extrait les capacités du compte Stripe au format attendu par la base de données :

```php
private function extractCapabilities(Account $stripeAccount): ?array
{
    if (!isset($stripeAccount->capabilities)) {
        return null;
    }

    $capabilities = [];
    foreach ($stripeAccount->capabilities as $capability => $status) {
        $capabilities[$capability] = [
            'status' => $status->status ?? null,
            'requested' => $status->requested ?? false,
        ];
    }

    return $capabilities;
}
```

**Raison d'être** :
- Les capacités Stripe sont des objets complexes
- La base de données attend un array JSON
- Cette méthode normalise le format pour la persistance

### Logging structuré

Les logs sont structurés avec des contextes clairs :

**Succès** :
```php
Log::info('Compte Stripe Connect créé avec succès', [
    'creator_profile_id' => $creator->id,
    'stripe_account_id' => $stripeAccount->id,
]);
```

**Erreur** :
```php
Log::error('Erreur Stripe lors de la création du compte Connect', [
    'creator_profile_id' => $creator->id,
    'stripe_error' => $e->getMessage(),
    'stripe_error_code' => $e->getStripeCode(),
]);
```

**Avantages** :
- Facilite le debugging
- Permet la recherche dans les logs
- Conforme aux bonnes pratiques Laravel

---

## 🎯 Prochaines étapes

L'ÉTAPE 2 est terminée. La méthode `createAccount()` est implémentée et prête pour les tests.

**En attente de validation avant de passer à l'ÉTAPE 3 : `createOnboardingLink()`**

---

## ✅ Checklist de validation

- [x] Méthode `createAccount()` implémentée
- [x] Validations préalables (compte existant, utilisateur, email)
- [x] Création du compte Stripe Connect Express
- [x] Persistance en base de données
- [x] Gestion des erreurs Stripe
- [x] Logging structuré
- [x] Documentation PHPDoc complète
- [x] Aucune logique métier exclue (abonnement, lien, etc.)
- [x] Code conforme aux conventions Laravel 12
- [x] Aucune erreur de lint

---

**Rapport terminé. En attente de validation pour passer à l'ÉTAPE 3.**






