# 📋 RAPPORT ÉTAPE 4 — syncAccountStatus() IMPLÉMENTATION

**Date** : 2025-12-19  
**Service** : `StripeConnectService`  
**Méthode** : `syncAccountStatus(string $stripeAccountId)`  
**Phase** : PHASE 1.1 — Implémentation Progressive

---

## ✅ ÉTAPE 4 TERMINÉE

### 🔧 Signature exacte de la méthode

```php
public function syncAccountStatus(string $stripeAccountId): void
```

**Paramètres** :
- `string $stripeAccountId` : L'identifiant du compte Stripe Connect (format `acct_xxx`)

**Valeur de retour** :
- `void` : La méthode ne retourne rien, elle met à jour directement la base de données

**Exceptions lancées** :
- `\RuntimeException` : Si le compte Stripe n'existe pas en base de données
- `ApiErrorException` : Si l'API Stripe retourne une erreur lors de la récupération du compte

**Rôle de la méthode** :
Synchroniser le statut d'un compte Stripe Connect Express avec la base de données en récupérant les informations les plus récentes depuis l'API Stripe et en mettant à jour les champs de statut correspondants.

---

## 🔄 Flux Stripe → Base de données

### Étape 1 : Chargement du compte depuis la base de données

Avant tout appel à l'API Stripe, la méthode charge le compte depuis la base de données :

```php
$creatorAccount = CreatorStripeAccount::where('stripe_account_id', $stripeAccountId)->first();
```

**Vérification** :
- Le compte doit exister en base de données avec le `stripe_account_id` fourni
- Si le compte n'existe pas → Lance une `\RuntimeException` avec un message explicite

**Raison** : La synchronisation nécessite un compte existant en base de données pour mettre à jour ses informations.

### Étape 2 : Récupération du compte Stripe via l'API

Une fois le compte chargé, la méthode appelle l'API Stripe pour récupérer les informations les plus récentes :

```php
$stripeAccount = Account::retrieve($stripeAccountId);
```

**Méthode Stripe** : `Account::retrieve()` — Récupère un compte Stripe Connect existant

**Réponse Stripe** :
L'API Stripe retourne un objet `Account` contenant :
- `charges_enabled` : Boolean indiquant si le créateur peut recevoir des paiements
- `payouts_enabled` : Boolean indiquant si le créateur peut recevoir des versements
- `details_submitted` : Boolean indiquant si les informations KYC sont soumises
- `requirements` : Objet contenant les exigences KYC
  - `currently_due` : Array des exigences en attente
  - `eventually_due` : Array des exigences futures
- `capabilities` : Objet contenant les capacités du compte (card_payments, transfers, etc.)

### Étape 3 : Extraction et normalisation des données

La méthode extrait et normalise les données du compte Stripe :

```php
$chargesEnabled = (bool) ($stripeAccount->charges_enabled ?? false);
$payoutsEnabled = (bool) ($stripeAccount->payouts_enabled ?? false);
$detailsSubmitted = (bool) ($stripeAccount->details_submitted ?? false);

$requirementsCurrentlyDue = $stripeAccount->requirements->currently_due ?? null;
$requirementsEventuallyDue = $stripeAccount->requirements->eventually_due ?? null;
```

**Normalisation** :
- Conversion explicite en boolean avec `(bool)` pour garantir le type
- Utilisation de l'opérateur `??` pour gérer les valeurs null
- Conversion des requirements en array si nécessaire (peuvent être des objets Stripe)

**Conversion des requirements** :
```php
$requirementsCurrentlyDueArray = is_array($requirementsCurrentlyDue) 
    ? $requirementsCurrentlyDue 
    : (is_object($requirementsCurrentlyDue) ? (array) $requirementsCurrentlyDue : null);
```

**Raison** : Les requirements peuvent être retournés comme des objets Stripe ou des arrays, et la base de données attend un array JSON.

### Étape 4 : Détermination du statut d'onboarding

La méthode détermine le statut d'onboarding en fonction des indicateurs Stripe :

```php
$onboardingStatus = $this->determineOnboardingStatus(
    $chargesEnabled,
    $payoutsEnabled,
    $detailsSubmitted,
    $requirementsCurrentlyDueArray
);
```

**Méthode helper** : `determineOnboardingStatus()` — Logique de mapping des statuts (voir section suivante)

### Étape 5 : Mise à jour en base de données

Après extraction et détermination du statut, la méthode met à jour le compte :

```php
$creatorAccount->update([
    'charges_enabled' => $chargesEnabled,
    'payouts_enabled' => $payoutsEnabled,
    'details_submitted' => $detailsSubmitted,
    'requirements_currently_due' => $requirementsCurrentlyDueArray,
    'requirements_eventually_due' => $requirementsEventuallyDueArray,
    'capabilities' => $this->extractCapabilities($stripeAccount),
    'onboarding_status' => $onboardingStatus,
    'last_synced_at' => now(),
]);
```

**Champs mis à jour** :
- Tous les champs de statut sont mis à jour avec les valeurs les plus récentes de Stripe
- `last_synced_at` est mis à jour avec la date/heure actuelle

### Étape 6 : Logging et fin

- **Log de succès** : Enregistre un log `info` avec :
  - L'ID du compte Stripe Connect
  - L'identifiant Stripe du compte
  - Les valeurs des indicateurs (charges_enabled, payouts_enabled, details_submitted)
  - Le statut d'onboarding déterminé

- **Fin** : La méthode se termine sans retourner de valeur

### Gestion des erreurs Stripe

Si l'API Stripe retourne une erreur (`ApiErrorException`) :
- **Log d'erreur** : Enregistre un log `error` avec :
  - L'ID du compte Stripe Connect
  - L'identifiant Stripe du compte
  - Le message d'erreur Stripe
  - Le code d'erreur Stripe
- **Propagation** : Relance l'exception pour que l'appelant puisse la gérer

---

## 📌 Mapping précis des statuts

### Méthode `determineOnboardingStatus()`

Cette méthode privée détermine le statut d'onboarding en fonction des indicateurs Stripe.

**Signature** :
```php
private function determineOnboardingStatus(
    bool $chargesEnabled,
    bool $payoutsEnabled,
    bool $detailsSubmitted,
    ?array $requirementsCurrentlyDue
): string
```

### Règles de mapping

#### 1. Statut `complete` (Terminé et actif)

**Condition** :
```php
if ($chargesEnabled && $payoutsEnabled) {
    return 'complete';
}
```

**Signification** :
- Le créateur peut recevoir des paiements (`charges_enabled === true`)
- Le créateur peut recevoir des versements (`payouts_enabled === true`)
- Le compte est complètement activé et fonctionnel

**Cas d'usage** :
- Onboarding complété avec succès
- KYC validé par Stripe
- Compte prêt pour recevoir des paiements

**Exemple** :
```
charges_enabled = true
payouts_enabled = true
details_submitted = true
requirements_currently_due = []
→ onboarding_status = 'complete'
```

#### 2. Statut `in_progress` (En cours de remplissage)

**Conditions multiples** (évaluées dans l'ordre) :

**Condition 2.1 : Détails soumis mais pas encore activé**
```php
if ($detailsSubmitted) {
    return 'in_progress';
}
```

**Signification** :
- Les informations KYC sont soumises (`details_submitted === true`)
- Mais le compte n'est pas encore complètement activé (charges ou payouts désactivés)
- En attente de validation par Stripe

**Cas d'usage** :
- Le créateur a complété le formulaire d'onboarding
- Stripe est en train de vérifier les informations
- Le compte sera activé une fois la vérification terminée

**Exemple** :
```
charges_enabled = false
payouts_enabled = false
details_submitted = true
requirements_currently_due = []
→ onboarding_status = 'in_progress'
```

**Condition 2.2 : Exigences en attente**
```php
if (!empty($requirementsCurrentlyDue) && is_array($requirementsCurrentlyDue)) {
    return 'in_progress';
}
```

**Signification** :
- Il y a des exigences KYC en attente (`requirements_currently_due` non vide)
- Le créateur doit fournir des informations supplémentaires
- L'onboarding n'est pas terminé

**Cas d'usage** :
- Le créateur a commencé l'onboarding mais n'a pas fourni toutes les informations
- Stripe demande des documents supplémentaires (pièce d'identité, compte bancaire, etc.)
- Le créateur doit compléter les informations manquantes

**Exemple** :
```
charges_enabled = false
payouts_enabled = false
details_submitted = false
requirements_currently_due = ['external_account', 'representative']
→ onboarding_status = 'in_progress'
```

**Condition 2.3 : Par défaut (onboarding non terminé)**
```php
return 'in_progress';
```

**Signification** :
- Aucune des conditions précédentes n'est remplie
- Le compte n'est pas complètement activé
- L'onboarding est en cours ou n'a pas encore commencé

**Cas d'usage** :
- Compte créé mais onboarding non commencé
- Compte en attente d'activation
- État transitoire avant activation complète

**Exemple** :
```
charges_enabled = false
payouts_enabled = false
details_submitted = false
requirements_currently_due = null
→ onboarding_status = 'in_progress'
```

#### 3. Statut `failed` (Échec)

**État actuel** : Non implémenté dans cette version

**Raison** :
- La détection d'échec nécessite une analyse plus approfondie des erreurs Stripe
- Les comptes en échec sont généralement gérés via les webhooks Stripe
- Pour l'instant, les comptes en échec restent en `in_progress`

**Implémentation future** :
- Analyser les erreurs Stripe (`requirements.errors`)
- Détecter les comptes restreints (`restrictions`)
- Mettre à jour le statut à `failed` si nécessaire

#### 4. Statut `pending` (Pas encore commencé)

**État actuel** : Non utilisé après la création du compte

**Raison** :
- Le statut `pending` est utilisé uniquement lors de la création initiale du compte
- Une fois le compte créé, le statut passe à `in_progress`
- Après synchronisation, le statut sera toujours `in_progress` ou `complete`

**Utilisation** :
- Initialisation lors de `createAccount()` : `onboarding_status = 'pending'`
- Après génération du lien d'onboarding : `onboarding_status = 'in_progress'`
- Après synchronisation : `onboarding_status = 'in_progress'` ou `'complete'`

### Tableau récapitulatif du mapping

| charges_enabled | payouts_enabled | details_submitted | requirements_currently_due | onboarding_status |
|-----------------|-----------------|-------------------|--------------------------|-------------------|
| `true` | `true` | `true` | `[]` | `complete` |
| `true` | `true` | `true` | `['...']` | `complete` |
| `false` | `false` | `true` | `[]` | `in_progress` |
| `false` | `false` | `true` | `['...']` | `in_progress` |
| `false` | `false` | `false` | `['...']` | `in_progress` |
| `false` | `false` | `false` | `[]` | `in_progress` |
| `true` | `false` | `true` | `[]` | `in_progress` |
| `false` | `true` | `true` | `[]` | `in_progress` |

**Règle principale** : Le statut est `complete` **uniquement** si `charges_enabled === true` **ET** `payouts_enabled === true`. Sinon, le statut est `in_progress`.

### Influence des indicateurs sur le statut

#### `charges_enabled`

**Rôle** : Indique si le créateur peut recevoir des paiements

**Influence sur `onboarding_status`** :
- Si `charges_enabled === true` **ET** `payouts_enabled === true` → `complete`
- Sinon → `in_progress`

**Explication** : Un compte ne peut être considéré comme complètement activé que si les deux capacités (charges et payouts) sont activées.

#### `payouts_enabled`

**Rôle** : Indique si le créateur peut recevoir des versements

**Influence sur `onboarding_status`** :
- Si `charges_enabled === true` **ET** `payouts_enabled === true` → `complete`
- Sinon → `in_progress`

**Explication** : Même logique que `charges_enabled`. Les deux doivent être activés pour que le compte soit `complete`.

#### `details_submitted`

**Rôle** : Indique si les informations KYC sont soumises

**Influence sur `onboarding_status`** :
- Si `details_submitted === true` → `in_progress` (sauf si charges ET payouts sont activés)
- Si `details_submitted === false` → `in_progress` (onboarding non terminé)

**Explication** : Si les détails sont soumis, le compte est au moins en cours d'onboarding. Si les détails ne sont pas soumis, l'onboarding n'a pas encore commencé ou est incomplet.

#### `requirements_currently_due`

**Rôle** : Liste des exigences KYC en attente

**Influence sur `onboarding_status`** :
- Si `requirements_currently_due` n'est pas vide → `in_progress`
- Si `requirements_currently_due` est vide → Dépend des autres indicateurs

**Explication** : Si des exigences sont en attente, l'onboarding n'est pas terminé. Le créateur doit fournir les informations manquantes.

---

## 💾 Champs mis à jour

### Champs obligatoires (toujours mis à jour)

| Champ | Source | Description |
|-------|--------|-------------|
| `charges_enabled` | `$stripeAccount->charges_enabled` | Le créateur peut recevoir des paiements (boolean) |
| `payouts_enabled` | `$stripeAccount->payouts_enabled` | Le créateur peut recevoir des versements (boolean) |
| `details_submitted` | `$stripeAccount->details_submitted` | Les informations KYC sont soumises (boolean) |
| `requirements_currently_due` | `$stripeAccount->requirements->currently_due` | Exigences KYC en attente (array JSON) |
| `requirements_eventually_due` | `$stripeAccount->requirements->eventually_due` | Exigences KYC futures (array JSON) |
| `capabilities` | `extractCapabilities($stripeAccount)` | Statut des capacités (array JSON) |
| `onboarding_status` | `determineOnboardingStatus(...)` | Statut d'onboarding calculé (enum) |
| `last_synced_at` | `now()` | Date/heure de dernière synchronisation (datetime) |

### Champs non modifiés

Les autres champs du compte ne sont pas modifiés par cette méthode :
- `creator_profile_id` : Inchangé
- `stripe_account_id` : Inchangé
- `account_type` : Inchangé
- `onboarding_link_url` : Inchangé (géré par `createOnboardingLink()`)
- `onboarding_link_expires_at` : Inchangé (géré par `createOnboardingLink()`)

**Raison** : Cette méthode ne synchronise que les statuts du compte, pas les autres informations.

### Normalisation des données

**Requirements** :
- Conversion en array si nécessaire (peuvent être des objets Stripe)
- Gestion des valeurs null
- Format JSON pour la persistance

**Capabilities** :
- Extraction via `extractCapabilities()` (méthode existante)
- Format array avec structure `['capability' => ['status' => '...', 'requested' => true]]`

---

## ⚠️ Cas d'erreurs anticipés

### Erreurs métier (RuntimeException)

1. **Compte introuvable en base de données** :
   - **Cause** : Le `stripe_account_id` fourni n'existe pas dans la table `creator_stripe_accounts`
   - **Message** : `"Aucun compte Stripe Connect trouvé avec l'identifiant Stripe : {stripe_account_id}."`
   - **Gestion** : L'exception est lancée avant tout appel Stripe
   - **Solution** : Vérifier que le compte existe en base de données ou créer le compte via `createAccount()`

### Erreurs API Stripe (ApiErrorException)

1. **Compte Stripe introuvable** :
   - **Cause** : Le `stripe_account_id` n'existe pas dans Stripe (compte supprimé ou invalide)
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Vérifier que le compte existe dans Stripe ou recréer le compte

2. **Clé API invalide** :
   - **Cause** : La clé Stripe configurée est invalide ou expirée
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Vérifier la configuration `STRIPE_SECRET` dans `.env`

3. **Limite de taux dépassée** :
   - **Cause** : Trop de requêtes à l'API Stripe en peu de temps
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Implémenter un système de retry avec backoff exponentiel

4. **Erreur de réseau** :
   - **Cause** : Problème de connexion à l'API Stripe
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Réessayer la requête ou vérifier la connectivité

### Erreurs de base de données

1. **Erreur de mise à jour** :
   - **Cause** : Problème lors de la mise à jour du compte (contrainte, verrou, etc.)
   - **Gestion** : Exception Eloquent non gérée explicitement (sera propagée)
   - **Solution** : Vérifier les contraintes de la base de données

2. **Compte supprimé entre-temps** :
   - **Cause** : Le compte a été supprimé entre le chargement et la mise à jour
   - **Gestion** : Exception Eloquent non gérée explicitement (sera propagée)
   - **Solution** : Vérifier que le compte existe avant la mise à jour

---

## ❌ Ce qui est volontairement exclu

### Création d'abonnement

- **Exclusion** : La méthode ne crée pas d'abonnement Stripe Billing
- **Raison** : Les abonnements sont gérés par `CreatorSubscriptionService`
- **Quand** : L'abonnement sera créé après l'onboarding complet (via webhook ou appel explicite)

### Suspension de créateur

- **Exclusion** : La méthode ne suspend pas le créateur
- **Raison** : La suspension est gérée par `CreatorSuspensionService`
- **Quand** : La suspension sera gérée après vérification de l'abonnement

### Notification

- **Exclusion** : La méthode n'envoie pas d'email ou de notification
- **Raison** : Les notifications sont gérées par `NotificationService`
- **Quand** : Les notifications seront envoyées par les contrôleurs ou les jobs

### Redirection

- **Exclusion** : La méthode ne fait aucune redirection HTTP
- **Raison** : Le service est pur (sans UI), conforme aux exigences
- **Quand** : Les redirections seront gérées par le contrôleur

### Traitement de webhook

- **Exclusion** : La méthode ne traite pas les webhooks Stripe
- **Raison** : Les webhooks sont gérés par `StripeConnectWebhookController`
- **Quand** : Les webhooks appelleront cette méthode pour synchroniser le statut

### Logique UI

- **Exclusion** : La méthode ne génère pas d'interface utilisateur
- **Raison** : Le service est pur (sans UI), conforme aux exigences
- **Quand** : L'UI sera gérée par les vues Blade et le contrôleur

### Détection d'échec avancée

- **Exclusion** : La méthode ne détecte pas les comptes en échec (`failed`)
- **Raison** : La détection d'échec nécessite une analyse plus approfondie des erreurs Stripe
- **Quand** : La détection d'échec sera implémentée dans une version future ou via les webhooks

---

## 🧠 Justifications architecturales

### Séparation des responsabilités

1. **Service pur** :
   - Le service ne contient que la logique métier Stripe Connect
   - Aucune dépendance à l'UI, aux webhooks, ou aux notifications
   - Facilite les tests unitaires et l'intégration

2. **Méthode atomique** :
   - `syncAccountStatus()` fait une seule chose : synchroniser le statut
   - Pas de side effects (pas d'abonnement, pas de suspension, pas de notification)
   - Facilite la réutilisation et la maintenance

3. **Gestion d'erreurs explicite** :
   - Les exceptions sont claires et documentées
   - Les logs sont structurés pour faciliter le debugging
   - Les erreurs Stripe sont propagées pour permettre une gestion personnalisée

### Logique de mapping des statuts

1. **Règles simples et claires** :
   - Le mapping est basé sur des règles booléennes simples
   - Facile à comprendre et à maintenir
   - Conforme aux indicateurs Stripe standards

2. **Gestion des états partiels** :
   - La méthode gère correctement les états partiels (onboarding incomplet)
   - Le statut `in_progress` couvre tous les cas non terminés
   - Permet de suivre l'évolution de l'onboarding

3. **Extensibilité** :
   - La méthode `determineOnboardingStatus()` peut être étendue pour gérer d'autres statuts
   - La détection d'échec peut être ajoutée facilement
   - Les règles de mapping peuvent être affinées si nécessaire

### Normalisation des données

1. **Conversion des types** :
   - Conversion explicite en boolean pour garantir le type
   - Conversion des requirements en array pour la persistance JSON
   - Gestion des valeurs null avec l'opérateur `??`

2. **Format JSON** :
   - Les données complexes (requirements, capabilities) sont stockées en JSON
   - Facilite l'extraction et la mise à jour ultérieure
   - Compatible avec les structures Stripe

### Synchronisation incrémentale

1. **Mise à jour sélective** :
   - Seuls les champs de statut sont mis à jour
   - Les autres champs (onboarding_link_url, etc.) ne sont pas modifiés
   - Évite les conflits et les pertes de données

2. **Timestamp de synchronisation** :
   - `last_synced_at` est mis à jour à chaque synchronisation
   - Permet de suivre la fraîcheur des données
   - Facilite le debugging et l'audit

---

## 📝 Notes techniques

### Utilisation de `Account::retrieve()`

La méthode utilise `Account::retrieve()` pour récupérer un compte Stripe Connect :

```php
$stripeAccount = Account::retrieve($stripeAccountId);
```

**Avantages** :
- Récupère les informations les plus récentes depuis Stripe
- Inclut tous les champs nécessaires (charges_enabled, payouts_enabled, requirements, etc.)
- Méthode standard Stripe pour récupérer un compte Connect

### Conversion des requirements

Les requirements peuvent être retournés comme des objets Stripe ou des arrays :

```php
$requirementsCurrentlyDueArray = is_array($requirementsCurrentlyDue) 
    ? $requirementsCurrentlyDue 
    : (is_object($requirementsCurrentlyDue) ? (array) $requirementsCurrentlyDue : null);
```

**Raison** : Stripe peut retourner les requirements sous différents formats selon le contexte. La conversion garantit un format array pour la persistance JSON.

### Méthode helper `determineOnboardingStatus()`

Cette méthode privée encapsule la logique de mapping des statuts :

**Avantages** :
- Logique isolée et testable
- Facile à modifier ou étendre
- Documentation claire des règles de mapping

**Extensibilité** :
- Peut être étendue pour gérer le statut `failed`
- Peut être affinée pour gérer des cas particuliers
- Peut être déplacée dans une classe dédiée si nécessaire

---

## 🎯 Prochaines étapes

L'ÉTAPE 4 est terminée. La méthode `syncAccountStatus()` est implémentée et prête pour les tests.

**En attente de validation avant de passer à l'ÉTAPE 5 : `canCreatorReceivePayments()`**

---

## ✅ Checklist de validation

- [x] Méthode `syncAccountStatus()` implémentée
- [x] Chargement du compte depuis la base de données
- [x] Récupération du compte Stripe via l'API
- [x] Extraction et normalisation des données
- [x] Détermination du statut d'onboarding (mapping précis)
- [x] Mise à jour en base de données
- [x] Gestion des erreurs Stripe
- [x] Logging structuré
- [x] Documentation PHPDoc complète
- [x] Méthode helper `determineOnboardingStatus()` avec règles de mapping claires
- [x] Aucune logique métier exclue (abonnement, suspension, notification, redirection, webhook, UI)
- [x] Code conforme aux conventions Laravel 12
- [x] Aucune erreur de lint

---

**Rapport terminé. En attente de validation pour passer à l'ÉTAPE 5.**






