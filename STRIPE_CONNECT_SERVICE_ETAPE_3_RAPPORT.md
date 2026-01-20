# 📋 RAPPORT ÉTAPE 3 — createOnboardingLink() IMPLÉMENTATION

**Date** : 2025-12-19  
**Service** : `StripeConnectService`  
**Méthode** : `createOnboardingLink(CreatorStripeAccount $account)`  
**Phase** : PHASE 1.1 — Implémentation Progressive

---

## ✅ ÉTAPE 3 TERMINÉE

### 🔧 Signature exacte de la méthode

```php
public function createOnboardingLink(CreatorStripeAccount $account): string
```

**Paramètres** :
- `CreatorStripeAccount $account` : Le compte Stripe Connect pour lequel créer le lien d'onboarding

**Valeur de retour** :
- `string` : L'URL du lien d'onboarding Stripe (format : `https://connect.stripe.com/setup/...`)

**Exceptions lancées** :
- `\RuntimeException` : Si le compte Stripe n'a pas d'identifiant Stripe valide (`stripe_account_id` vide)
- `ApiErrorException` : Si l'API Stripe retourne une erreur lors de la création du lien

**Rôle de la méthode** :
Créer un lien d'onboarding Stripe pour permettre au créateur de compléter son processus d'onboarding KYC via l'interface Stripe hébergée.

---

## 🔗 Création du lien Stripe

### Étape 1 : Validation préalable

Avant tout appel à l'API Stripe, la méthode effectue une validation :

```php
if (empty($account->stripe_account_id)) {
    throw new \RuntimeException(...);
}
```

**Vérification** :
- Le compte doit avoir un `stripe_account_id` valide (non vide)
- Ce champ est requis pour créer un AccountLink Stripe

**Raison** : Un compte sans identifiant Stripe ne peut pas avoir de lien d'onboarding.

### Étape 2 : Construction des URLs

La méthode construit deux URLs nécessaires pour le processus d'onboarding :

```php
$refreshUrl = url('/creator/stripe/onboarding/refresh');
$returnUrl = url('/creator/stripe/onboarding/return');
```

**URLs construites** :
- **`refresh_url`** : `/creator/stripe/onboarding/refresh`
  - Utilisée par Stripe si le lien expire ou en cas d'erreur
  - Permet de régénérer un nouveau lien d'onboarding
  - Sera implémentée dans le contrôleur d'onboarding

- **`return_url`** : `/creator/stripe/onboarding/return`
  - Utilisée par Stripe après que le créateur ait complété l'onboarding
  - Permet de traiter le retour et synchroniser le statut du compte
  - Sera implémentée dans le contrôleur d'onboarding

**Note** : Ces routes n'existent pas encore dans le projet. Elles seront créées lors de l'implémentation du contrôleur d'onboarding (Phase 1.4).

### Étape 3 : Création du AccountLink Stripe

Une fois les validations passées, la méthode appelle l'API Stripe :

```php
$accountLink = AccountLink::create([
    'account' => $account->stripe_account_id,
    'refresh_url' => $refreshUrl,
    'return_url' => $returnUrl,
    'type' => 'account_onboarding',
]);
```

**Paramètres Stripe** :
- `account` : L'identifiant du compte Stripe Connect (format `acct_xxx`)
- `refresh_url` : URL de rafraîchissement (si le lien expire)
- `return_url` : URL de retour (après complétion de l'onboarding)
- `type` : `'account_onboarding'` — Type de lien pour l'onboarding Express

**Réponse Stripe** :
L'API Stripe retourne un objet `AccountLink` contenant :
- `url` : L'URL du lien d'onboarding (format `https://connect.stripe.com/setup/...`)
- `expires_at` : Timestamp Unix de l'expiration du lien (généralement 24 heures)

### Étape 4 : Extraction de la date d'expiration

La méthode extrait la date d'expiration du lien :

```php
if (isset($accountLink->expires_at)) {
    $expiresAt = now()->setTimestamp($accountLink->expires_at);
} else {
    $expiresAt = now()->addHours(24);
}
```

**Logique** :
- Si Stripe retourne `expires_at` → Utilise cette valeur (timestamp Unix)
- Sinon → Utilise une expiration par défaut de 24 heures

**Raison** : Stripe gère l'expiration des liens, mais il est important de la persister pour vérifier si un lien est encore valide.

### Étape 5 : Persistance en base de données

Après la création réussie du lien, la méthode persiste les informations :

```php
$account->update([
    'onboarding_link_url' => $accountLink->url,
    'onboarding_link_expires_at' => $expiresAt,
]);
```

**Champs mis à jour** :
- `onboarding_link_url` : URL complète du lien d'onboarding
- `onboarding_link_expires_at` : Date et heure d'expiration du lien

**Note** : Ces champs sont mis à jour sur le compte existant, pas créés.

### Étape 6 : Logging et retour

- **Log de succès** : Enregistre un log `info` avec :
  - L'ID du compte Stripe Connect
  - L'identifiant Stripe du compte
  - L'URL du lien d'onboarding
  - La date d'expiration (format ISO8601)

- **Retour** : Retourne l'URL du lien d'onboarding (`$accountLink->url`)

### Gestion des erreurs Stripe

Si l'API Stripe retourne une erreur (`ApiErrorException`) :
- **Log d'erreur** : Enregistre un log `error` avec :
  - L'ID du compte Stripe Connect
  - L'identifiant Stripe du compte
  - Le message d'erreur Stripe
  - Le code d'erreur Stripe
- **Propagation** : Relance l'exception pour que l'appelant puisse la gérer

---

## ⏳ Gestion de l'expiration

### Expiration par Stripe

**Stripe gère l'expiration** :
- Les liens d'onboarding Stripe expirent automatiquement après une durée définie par Stripe (généralement 24 heures)
- L'expiration est gérée côté Stripe, pas côté application
- Une fois expiré, le lien ne peut plus être utilisé

**Rôle de l'application** :
- Persister la date d'expiration retournée par Stripe
- Vérifier si un lien est encore valide avant de le proposer au créateur
- Régénérer un nouveau lien si nécessaire (via `refresh_url`)

### Persistance de l'expiration

**Champ `onboarding_link_expires_at`** :
- Type : `timestamp` (nullable)
- Format : Date et heure au format datetime
- Source : `$accountLink->expires_at` (timestamp Unix) ou `now()->addHours(24)` par défaut

**Utilisation future** :
- Vérifier si un lien est encore valide : `$account->onboarding_link_expires_at > now()`
- Afficher un message d'expiration au créateur si le lien est expiré
- Régénérer automatiquement un nouveau lien si nécessaire

### Gestion des liens expirés

**Scénario** : Un créateur essaie d'utiliser un lien expiré.

**Comportement attendu** :
1. Stripe redirige vers `refresh_url` si le lien est expiré
2. Le contrôleur d'onboarding détecte l'expiration
3. Un nouveau lien est généré via `createOnboardingLink()`
4. Le créateur est redirigé vers le nouveau lien

**Note** : Cette logique sera implémentée dans le contrôleur d'onboarding (Phase 1.4).

---

## 💾 Champs persistés

### Champs mis à jour

| Champ | Valeur | Source | Description |
|-------|--------|---------|-------------|
| `onboarding_link_url` | `$accountLink->url` | API Stripe | URL complète du lien d'onboarding (format `https://connect.stripe.com/setup/...`) |
| `onboarding_link_expires_at` | `$expiresAt` | API Stripe ou défaut | Date et heure d'expiration du lien (timestamp Unix converti en datetime) |

### Champs non modifiés

Les autres champs du compte ne sont pas modifiés par cette méthode :
- `creator_profile_id` : Inchangé
- `stripe_account_id` : Inchangé
- `account_type` : Inchangé
- `onboarding_status` : Inchangé (reste `in_progress` jusqu'à complétion)
- `charges_enabled` : Inchangé
- `payouts_enabled` : Inchangé
- `details_submitted` : Inchangé
- `requirements_currently_due` : Inchangé
- `requirements_eventually_due` : Inchangé
- `capabilities` : Inchangé
- `last_synced_at` : Inchangé

**Raison** : Cette méthode ne fait que créer le lien d'onboarding. La mise à jour du statut du compte se fera via les webhooks Stripe ou via `syncAccountStatus()` après complétion de l'onboarding.

---

## 🔐 Sécurité et validations

### Validations métier

1. **Présence de l'identifiant Stripe** :
   - **Vérification** : `$account->stripe_account_id` ne doit pas être vide
   - **Raison** : Un compte sans identifiant Stripe ne peut pas avoir de lien d'onboarding
   - **Erreur** : `\RuntimeException` avec message explicite

### Sécurité des données

1. **Pas de log de données sensibles** :
   - Les logs ne contiennent que des identifiants (ID compte, ID Stripe)
   - L'URL du lien est loggée (elle est publique de toute façon)
   - Aucune donnée personnelle n'est loggée

2. **Gestion des exceptions Stripe** :
   - Les erreurs Stripe sont loggées avec le code d'erreur
   - Les exceptions sont propagées pour permettre une gestion personnalisée

3. **URLs sécurisées** :
   - Les URLs de refresh et return utilisent `url()` helper Laravel
   - Les URLs sont construites à partir de `config('app.url')`
   - Les routes seront protégées par middleware d'authentification (à implémenter)

### Conformité Stripe Connect

1. **Type de lien** : `account_onboarding` — Conforme à Stripe Connect Express
2. **URLs requises** : `refresh_url` et `return_url` — Requises par Stripe
3. **Expiration** : Gérée par Stripe, persistée par l'application

---

## ⚠️ Cas d'erreurs anticipés

### Erreurs métier (RuntimeException)

1. **Identifiant Stripe manquant** :
   - **Cause** : Le compte Stripe Connect n'a pas de `stripe_account_id` valide
   - **Message** : `"Le compte Stripe Connect {id} n'a pas d'identifiant Stripe valide. Impossible de créer un lien d'onboarding."`
   - **Gestion** : L'exception est lancée avant tout appel Stripe
   - **Solution** : Vérifier que le compte a été créé correctement via `createAccount()`

### Erreurs API Stripe (ApiErrorException)

1. **Compte Stripe introuvable** :
   - **Cause** : Le `stripe_account_id` n'existe pas dans Stripe (compte supprimé ou invalide)
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Vérifier que le compte existe dans Stripe ou recréer le compte

2. **Clé API invalide** :
   - **Cause** : La clé Stripe configurée est invalide ou expirée
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Vérifier la configuration `STRIPE_SECRET` dans `.env`

3. **URLs invalides** :
   - **Cause** : Les URLs `refresh_url` ou `return_url` ne sont pas accessibles publiquement
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Vérifier que les routes existent et sont accessibles (sera fait lors de l'implémentation du contrôleur)

4. **Limite de taux dépassée** :
   - **Cause** : Trop de requêtes à l'API Stripe en peu de temps
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Implémenter un système de retry avec backoff exponentiel

5. **Erreur de réseau** :
   - **Cause** : Problème de connexion à l'API Stripe
   - **Gestion** : Exception capturée, loggée, puis relancée
   - **Solution** : Réessayer la requête ou vérifier la connectivité

### Erreurs de base de données

1. **Compte introuvable** :
   - **Cause** : Le compte `CreatorStripeAccount` n'existe pas en base de données
   - **Gestion** : Exception Eloquent non gérée explicitement (sera propagée)
   - **Solution** : Vérifier que le compte existe avant d'appeler la méthode

2. **Erreur de mise à jour** :
   - **Cause** : Problème lors de la mise à jour du compte (contrainte, verrou, etc.)
   - **Gestion** : Exception Eloquent non gérée explicitement (sera propagée)
   - **Solution** : Vérifier les contraintes de la base de données

---

## ❌ Ce qui est volontairement exclu

### Redirection

- **Exclusion** : La méthode ne fait aucune redirection HTTP
- **Raison** : Le service est pur (sans UI), conforme aux exigences
- **Quand** : Les redirections seront gérées par le contrôleur d'onboarding

### Logique d'abonnement

- **Exclusion** : La méthode ne crée pas d'abonnement Stripe Billing
- **Raison** : Les abonnements sont gérés par `CreatorSubscriptionService`
- **Quand** : L'abonnement sera créé après l'onboarding complet (via webhook ou `syncAccountStatus()`)

### Logique KYC métier

- **Exclusion** : La méthode ne traite pas les informations KYC du créateur
- **Raison** : Stripe gère tout le processus KYC via son interface hébergée
- **Quand** : Les informations KYC sont collectées par Stripe, puis synchronisées via webhooks

### Traitement de webhook

- **Exclusion** : La méthode ne traite pas les webhooks Stripe
- **Raison** : Les webhooks sont gérés par `StripeConnectWebhookController`
- **Quand** : Les webhooks seront traités après la complétion de l'onboarding

### Logique UI

- **Exclusion** : La méthode ne génère pas d'interface utilisateur
- **Raison** : Le service est pur (sans UI), conforme aux exigences
- **Quand** : L'UI sera gérée par les vues Blade et le contrôleur d'onboarding

### Vérification d'expiration

- **Exclusion** : La méthode ne vérifie pas si un lien existant est encore valide
- **Raison** : Cette vérification sera faite dans le contrôleur avant d'appeler cette méthode
- **Quand** : La vérification sera implémentée dans le contrôleur d'onboarding

### Régénération automatique

- **Exclusion** : La méthode ne régénère pas automatiquement un lien expiré
- **Raison** : Cette logique sera gérée par le contrôleur via `refresh_url`
- **Quand** : La régénération sera implémentée dans le contrôleur d'onboarding

---

## 🧠 Justifications architecturales

### Séparation des responsabilités

1. **Service pur** :
   - Le service ne contient que la logique métier Stripe Connect
   - Aucune dépendance à l'UI, aux webhooks, ou aux redirections
   - Facilite les tests unitaires et l'intégration

2. **Méthode atomique** :
   - `createOnboardingLink()` fait une seule chose : créer un lien d'onboarding
   - Pas de side effects (pas d'abonnement, pas de redirection, pas de webhook)
   - Facilite la réutilisation et la maintenance

3. **Gestion d'erreurs explicite** :
   - Les exceptions sont claires et documentées
   - Les logs sont structurés pour faciliter le debugging
   - Les erreurs Stripe sont propagées pour permettre une gestion personnalisée

### Conformité Stripe Connect Express

1. **Type de lien** : `account_onboarding` — Conforme à Stripe Connect Express
2. **URLs requises** : `refresh_url` et `return_url` — Requises par Stripe pour gérer le flux d'onboarding
3. **Expiration** : Gérée par Stripe, persistée par l'application pour vérification

### Persistance des données

1. **Données minimales** :
   - Seules les données nécessaires sont persistées (URL et expiration)
   - Les autres champs du compte ne sont pas modifiés

2. **Format datetime** :
   - L'expiration est stockée au format datetime (pas timestamp Unix)
   - Facilite les requêtes et les comparaisons dans Laravel

3. **Mise à jour incrémentale** :
   - Utilise `update()` plutôt que `create()` pour mettre à jour le compte existant
   - Évite les conflits et les doublons

### URLs et routes

1. **URLs génériques** :
   - Les URLs utilisent des chemins génériques (`/creator/stripe/onboarding/...`)
   - Ces routes seront créées lors de l'implémentation du contrôleur d'onboarding

2. **Helper Laravel** :
   - Utilise `url()` helper Laravel pour construire les URLs
   - Respecte la configuration `APP_URL` du projet

3. **Extensibilité** :
   - Les URLs peuvent être facilement modifiées si nécessaire
   - Les routes peuvent être nommées pour une meilleure maintenabilité

---

## 📝 Notes techniques

### Utilisation de `now()` helper

La méthode utilise `now()` helper Laravel plutôt que `\Carbon\Carbon::now()` :

```php
$expiresAt = now()->setTimestamp($accountLink->expires_at);
```

**Avantages** :
- Plus concis et lisible
- Conforme aux conventions Laravel
- `now()` retourne une instance Carbon configurée avec le timezone de l'application

### Conversion de timestamp Unix

La conversion du timestamp Unix en datetime Laravel :

```php
$expiresAt = now()->setTimestamp($accountLink->expires_at);
```

**Explication** :
- `setTimestamp()` convertit un timestamp Unix en instance Carbon
- Le timezone de l'application est automatiquement appliqué
- Compatible avec les timestamps Stripe (Unix, UTC)

### Format de log ISO8601

Les dates sont loggées au format ISO8601 :

```php
'expires_at' => $expiresAt->toIso8601String(),
```

**Avantages** :
- Format standardisé et lisible
- Facilite le parsing et l'analyse des logs
- Compatible avec les outils de monitoring

---

## 🎯 Prochaines étapes

L'ÉTAPE 3 est terminée. La méthode `createOnboardingLink()` est implémentée et prête pour les tests.

**En attente de validation avant de passer à l'ÉTAPE 4 : `syncAccountStatus()`**

---

## ✅ Checklist de validation

- [x] Méthode `createOnboardingLink()` implémentée
- [x] Validation préalable (identifiant Stripe valide)
- [x] Création du AccountLink Stripe
- [x] Construction des URLs (refresh_url, return_url)
- [x] Extraction et persistance de l'expiration
- [x] Mise à jour en base de données
- [x] Gestion des erreurs Stripe
- [x] Logging structuré
- [x] Documentation PHPDoc complète
- [x] Aucune logique métier exclue (redirection, abonnement, KYC, webhook, UI)
- [x] Code conforme aux conventions Laravel 12
- [x] Aucune erreur de lint

---

**Rapport terminé. En attente de validation pour passer à l'ÉTAPE 4.**






