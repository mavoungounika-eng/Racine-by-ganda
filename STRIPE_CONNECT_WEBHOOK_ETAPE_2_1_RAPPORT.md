# 📋 RAPPORT ÉTAPE 2.1 — STRIPE CONNECT WEBHOOK CONTROLLER

**Date** : 2025-12-19  
**Contrôleur** : `StripeConnectWebhookController`  
**Phase** : PHASE 2 — Stripe Connect Webhooks

---

## ✅ ÉTAPE 2.1 TERMINÉE

### 📁 Fichier créé

**Chemin exact** : `app/Http/Controllers/Webhooks/StripeConnectWebhookController.php`

Le fichier a été créé dans le répertoire `app/Http/Controllers/Webhooks/` pour respecter la structure organisée du projet.

---

## 1. Signature du contrôleur

### Namespace

```php
namespace App\Http\Controllers\Webhooks;
```

Le namespace respecte la convention Laravel et correspond à l'emplacement physique du fichier.

### Classe

```php
class StripeConnectWebhookController extends Controller
```

Le contrôleur étend `Controller` (classe de base Laravel) pour bénéficier des fonctionnalités communes.

### Méthode principale

```php
public function __invoke(Request $request): JsonResponse
```

**Type de méthode** : `__invoke()` — Permet d'utiliser le contrôleur comme un callable (route simple)

**Paramètres** :
- `Request $request` : La requête HTTP contenant le webhook Stripe

**Valeur de retour** :
- `JsonResponse` : Réponse JSON avec statut `200 OK` ou erreur `400 Bad Request`

**Avantages de `__invoke()`** :
- Route simple : `Route::post('/webhooks/stripe-connect', StripeConnectWebhookController::class)`
- Pas besoin de spécifier une méthode dans la route
- Code plus propre et concis

---

## 2. Vérification de signature Stripe

### Méthode utilisée

**Méthode Stripe officielle** :
```php
$event = Webhook::constructEvent($payload, $signature, $webhookSecret);
```

**Paramètres** :
- `$payload` : Contenu brut de la requête (`$request->getContent()`)
- `$signature` : Header `Stripe-Signature` de la requête
- `$webhookSecret` : Secret configuré dans `config('services.stripe.webhook_secret')`

**Fonctionnalités de `Webhook::constructEvent()`** :
- Vérifie la signature HMAC SHA256
- Vérifie le timestamp (évite les replay attacks)
- Parse le payload JSON
- Retourne un objet `Event` Stripe

### Gestion des erreurs

#### Erreur 1 : Signature manquante en production

```php
if ($isProduction && empty($signature)) {
    Log::error('Stripe Connect webhook: Missing signature in production', [
        'ip' => $request->ip(),
    ]);
    return response()->json(['error' => 'Missing signature'], 400);
}
```

**Comportement** :
- En production : Rejette la requête avec `400 Bad Request`
- Log l'erreur avec l'IP de l'expéditeur
- Protège contre les requêtes non signées

#### Erreur 2 : Secret non configuré

```php
if ($isProduction && empty($webhookSecret)) {
    Log::error('Stripe Connect webhook: Webhook secret not configured', [
        'ip' => $request->ip(),
    ]);
    return response()->json(['error' => 'Configuration error'], 500);
}
```

**Comportement** :
- En production : Rejette la requête avec `500 Internal Server Error`
- Log l'erreur de configuration
- Indique un problème de configuration système

#### Erreur 3 : Signature invalide

```php
catch (SignatureVerificationException $e) {
    if (!$isProduction) {
        // En dev, parser quand même
        $event = json_decode($payload, true);
    } else {
        Log::error('Stripe Connect webhook: Invalid signature', [
            'ip' => $request->ip(),
            'error' => $e->getMessage(),
        ]);
        return response()->json(['error' => 'Invalid signature'], 400);
    }
}
```

**Comportement** :
- En production : Rejette la requête avec `400 Bad Request`
- Log l'erreur avec le message d'exception
- En développement : Continue le traitement (pour faciliter les tests)

#### Erreur 4 : JSON invalide

```php
if (json_last_error() !== JSON_ERROR_NONE) {
    return response()->json(['error' => 'Invalid JSON'], 400);
}
```

**Comportement** :
- Rejette la requête avec `400 Bad Request`
- Protège contre les payloads malformés

### Mode développement

**Comportement spécial** :
- Si la signature est absente ou invalide en développement, le contrôleur parse quand même le payload
- Permet de tester les webhooks localement sans configuration Stripe complète
- Facilite le développement et les tests

**Détection de l'environnement** :
```php
$isProduction = app()->environment('production');
```

---

## 3. Mapping événements → actions

### Tableau de mapping

| Événement Stripe | Action | Méthode appelée | Paramètres |
|-----------------|--------|-----------------|------------|
| `account.updated` | Synchroniser le statut du compte | `StripeConnectService::syncAccountStatus()` | `$stripeAccountId` (extrait de `data.object.id`) |
| `capability.updated` | Synchroniser le statut du compte | `StripeConnectService::syncAccountStatus()` | `$stripeAccountId` (extrait de `data.object.id`) |
| `account.application.deauthorized` | Marquer le compte comme désactivé | Mise à jour directe en base | `$stripeAccountId` (extrait de `data.object.id`) |
| **Tous les autres** | **Ignorés** | Aucune action | - |

### Détails des actions

#### Action 1 : `account.updated` → `syncAccountStatus()`

**Quand** : Le statut d'un compte Stripe Connect change (onboarding complété, KYC validé, etc.)

**Action** :
```php
$stripeConnectService->syncAccountStatus($stripeAccountId);
```

**Extraction du `stripe_account_id`** :
```php
$stripeAccountId = $eventArray['data']['object']['id'] ?? null;
```

**Résultat** :
- Met à jour `charges_enabled`, `payouts_enabled`, `details_submitted`
- Met à jour `requirements_currently_due`, `requirements_eventually_due`
- Met à jour `capabilities`
- Met à jour `onboarding_status` (via `determineOnboardingStatus()`)
- Met à jour `last_synced_at`

#### Action 2 : `capability.updated` → `syncAccountStatus()`

**Quand** : Une capacité du compte Stripe Connect change (card_payments activée, transfers activés, etc.)

**Action** :
```php
$stripeConnectService->syncAccountStatus($stripeAccountId);
```

**Raison** : Les changements de capacités affectent le statut du compte, donc on synchronise tout le statut.

#### Action 3 : `account.application.deauthorized` → Marquer comme désactivé

**Quand** : L'application est désautorisée par le créateur (déconnexion du compte Stripe)

**Action** :
```php
$creatorAccount->update([
    'onboarding_status' => 'failed',
    'charges_enabled' => false,
    'payouts_enabled' => false,
]);
```

**Raison** : Le compte est désactivé, on marque le statut comme `failed` et on désactive les capacités.

### Événements ignorés

**Tous les autres événements Stripe sont ignorés** :
- `checkout.session.completed` → Ignoré (géré par `WebhookController@stripe`)
- `payment_intent.succeeded` → Ignoré (géré par `WebhookController@stripe`)
- `invoice.paid` → Ignoré (sera géré par `CreatorSubscriptionService`)
- `customer.subscription.updated` → Ignoré (sera géré par `CreatorSubscriptionService`)
- Et tous les autres...

**Logging des événements ignorés** :
```php
Log::debug('Stripe Connect webhook: Event ignored', [
    'event_type' => $eventType,
    'stripe_account_id' => $stripeAccountId,
]);
```

**Raison** : Le contrôleur se concentre uniquement sur les événements liés aux comptes Stripe Connect, pas sur les paiements ou abonnements.

---

## 4. Flux webhook → service

### Flux complet

#### Étape 1 : Réception du webhook

```
Stripe → POST /webhooks/stripe-connect
       → Headers: Stripe-Signature: t=...,v1=...
       → Body: JSON payload
```

#### Étape 2 : Vérification de signature

```php
$payload = $request->getContent(); // Payload brut
$signature = $request->header('Stripe-Signature');
$webhookSecret = config('services.stripe.webhook_secret');

$event = Webhook::constructEvent($payload, $signature, $webhookSecret);
```

**Résultat** : Objet `Event` Stripe validé

#### Étape 3 : Extraction des données

```php
$eventArray = is_object($event) ? json_decode(json_encode($event), true) : $event;
$eventType = $eventArray['type'] ?? null;
$stripeAccountId = $eventArray['data']['object']['id'] ?? null;
```

**Extraction** :
- `event_type` : Type d'événement (ex: `account.updated`)
- `stripe_account_id` : ID du compte Stripe (ex: `acct_xxx`)

#### Étape 4 : Filtrage et traitement

```php
switch ($eventType) {
    case 'account.updated':
    case 'capability.updated':
        $stripeConnectService->syncAccountStatus($stripeAccountId);
        break;
    // ...
}
```

**Appel du service** :
- Injection du service via `app(StripeConnectService::class)`
- Appel de `syncAccountStatus($stripeAccountId)`
- Le service récupère le compte en base et synchronise avec Stripe

#### Étape 5 : Réponse

```php
return response()->json(['status' => 'ok'], 200);
```

**Réponse** : `200 OK` avec `{"status": "ok"}`

### Paramètres passés au service

**Méthode appelée** :
```php
StripeConnectService::syncAccountStatus(string $stripeAccountId): void
```

**Paramètre** :
- `$stripeAccountId` : L'identifiant du compte Stripe (format `acct_xxx`)
- Extrait de : `$eventArray['data']['object']['id']`

**Exemple** :
```php
// Événement Stripe
{
    "type": "account.updated",
    "data": {
        "object": {
            "id": "acct_1ABC123xyz",
            "charges_enabled": true,
            "payouts_enabled": true,
            // ...
        }
    }
}

// Appel du service
$stripeConnectService->syncAccountStatus("acct_1ABC123xyz");
```

### Gestion des erreurs dans le flux

**Erreur : `stripe_account_id` manquant**

```php
if (empty($stripeAccountId)) {
    Log::warning('Stripe Connect webhook: Missing stripe_account_id', [
        'event_type' => $eventType,
        'ip' => $request->ip(),
    ]);
    return response()->json(['error' => 'Missing account ID'], 400);
}
```

**Erreur : Échec de synchronisation**

```php
try {
    $stripeConnectService->syncAccountStatus($stripeAccountId);
} catch (\Exception $e) {
    Log::error('Stripe Connect webhook: Failed to sync account status', [
        'event_type' => $eventType,
        'stripe_account_id' => $stripeAccountId,
        'error' => $e->getMessage(),
    ]);
    // Ne pas retourner d'erreur HTTP pour éviter les retries Stripe
}
```

**Raison** : On ne retourne pas d'erreur HTTP pour éviter que Stripe ne réessaie indéfiniment. Le compte sera synchronisé lors du prochain webhook.

---

## 5. Sécurité

### Protection contre les payloads forgés

**Mécanisme** : Vérification de signature HMAC SHA256

**Implémentation** :
```php
$event = Webhook::constructEvent($payload, $signature, $webhookSecret);
```

**Protection** :
- Le payload est signé par Stripe avec le secret webhook
- La signature est vérifiée avec `Webhook::constructEvent()`
- Si la signature est invalide, la requête est rejetée avec `400 Bad Request`

**Résultat** : Impossible de forger un payload sans connaître le secret webhook.

### Protection contre les replay attacks

**Mécanisme** : Vérification du timestamp dans la signature

**Implémentation** :
- `Webhook::constructEvent()` vérifie automatiquement le timestamp
- Les signatures trop anciennes sont rejetées (par défaut, 5 minutes)
- Évite la réutilisation d'anciennes signatures

**Protection** :
- Un attaquant ne peut pas réutiliser une ancienne requête
- Les requêtes doivent être récentes (timestamp valide)

**Résultat** : Protection contre les replay attacks.

### Protection contre les événements inconnus

**Mécanisme** : Filtrage strict des événements

**Implémentation** :
```php
switch ($eventType) {
    case 'account.updated':
    case 'capability.updated':
    case 'account.application.deauthorized':
        // Traiter
        break;
    default:
        // Ignorer
        Log::debug('Stripe Connect webhook: Event ignored', [...]);
        break;
}
```

**Protection** :
- Seuls 3 événements sont traités
- Tous les autres événements sont ignorés
- Logging en mode debug pour traçabilité

**Résultat** : Aucune action non désirée pour les événements inconnus.

### Protection contre les erreurs de traitement

**Mécanisme** : Try-catch avec logging

**Implémentation** :
```php
try {
    $stripeConnectService->syncAccountStatus($stripeAccountId);
} catch (\Exception $e) {
    Log::error('Stripe Connect webhook: Failed to sync account status', [
        'event_type' => $eventType,
        'stripe_account_id' => $stripeAccountId,
        'error' => $e->getMessage(),
    ]);
    // Ne pas retourner d'erreur HTTP
}
```

**Protection** :
- Les erreurs sont loggées pour debugging
- On retourne toujours `200 OK` pour éviter les retries Stripe
- Le compte sera synchronisé lors du prochain webhook

**Résultat** : Pas de boucle infinie de retries Stripe en cas d'erreur temporaire.

### Protection contre les comptes introuvables

**Mécanisme** : Vérification de l'existence du compte

**Implémentation** :
```php
$creatorAccount = CreatorStripeAccount::where('stripe_account_id', $stripeAccountId)->first();
if ($creatorAccount) {
    // Traiter
} else {
    Log::warning('Stripe Connect webhook: Account not found', [...]);
}
```

**Protection** :
- Vérifie que le compte existe avant de le modifier
- Log un warning si le compte n'existe pas
- Évite les erreurs de base de données

**Résultat** : Pas d'erreur si le compte n'existe pas (webhook reçu avant création du compte).

---

## 6. Ce qui est volontairement exclu

### Logique UI

- **Exclusion** : Le contrôleur ne génère aucune interface utilisateur
- **Raison** : C'est un webhook, appelé directement par Stripe
- **Quand** : L'UI sera gérée par les contrôleurs frontend

### Appel Stripe inutile

- **Exclusion** : Le contrôleur n'appelle pas directement l'API Stripe
- **Raison** : Utilise uniquement `StripeConnectService` qui gère les appels Stripe
- **Quand** : Les appels Stripe sont faits par `syncAccountStatus()` si nécessaire

### Création d'abonnement

- **Exclusion** : Le contrôleur ne crée pas d'abonnement
- **Raison** : Les abonnements sont gérés par `CreatorSubscriptionService`
- **Quand** : L'abonnement sera créé après l'onboarding complet (via webhook ou appel explicite)

### Redirection

- **Exclusion** : Le contrôleur ne fait aucune redirection HTTP
- **Raison** : C'est un webhook, doit retourner une réponse JSON
- **Quand** : Les redirections seront gérées par les contrôleurs frontend

### Notification

- **Exclusion** : Le contrôleur n'envoie pas d'email ou de notification
- **Raison** : Les notifications sont gérées par `NotificationService`
- **Quand** : Les notifications seront envoyées par les jobs ou les contrôleurs

### Persistance des événements webhook

- **Exclusion** : Le contrôleur ne persiste pas les événements webhook dans une table dédiée
- **Raison** : Les événements Stripe Connect sont simples et ne nécessitent pas de persistance
- **Note** : Les événements de paiement sont persistés par `WebhookController@stripe` (infrastructure existante)

### Traitement asynchrone

- **Exclusion** : Le contrôleur ne dispatch pas de job en queue
- **Raison** : Les actions sont rapides (synchronisation de statut)
- **Note** : Si nécessaire, on pourra ajouter un job plus tard

### Gestion des événements de paiement

- **Exclusion** : Le contrôleur ne traite pas les événements de paiement (`checkout.session.completed`, etc.)
- **Raison** : Ces événements sont gérés par `WebhookController@stripe` (infrastructure existante)
- **Quand** : Les paiements sur comptes Connect seront gérés par l'infrastructure existante

---

## 📝 Notes techniques

### Utilisation de `__invoke()`

**Avantage** : Permet d'utiliser le contrôleur comme un callable dans les routes :

```php
// Route simple
Route::post('/webhooks/stripe-connect', StripeConnectWebhookController::class);
```

**Alternative** : Si on utilisait une méthode nommée, il faudrait :

```php
Route::post('/webhooks/stripe-connect', [StripeConnectWebhookController::class, 'handle']);
```

### Injection du service

**Méthode** : `app(StripeConnectService::class)`

**Raison** : Injection via le conteneur Laravel pour bénéficier de l'injection de dépendances.

**Alternative** : Injection via le constructeur (mais `__invoke()` ne permet pas facilement l'injection).

### Normalisation de l'événement

**Méthode** :
```php
$eventArray = is_object($event) ? json_decode(json_encode($event), true) : $event;
```

**Raison** : `Webhook::constructEvent()` peut retourner un objet ou un array selon la version de Stripe PHP SDK. On normalise en array pour faciliter l'extraction.

### Logging structuré

**Niveaux de log** :
- `info` : Réception du webhook, synchronisation réussie
- `warning` : Données manquantes, compte introuvable
- `error` : Erreurs de signature, erreurs de traitement
- `debug` : Événements ignorés

**Contexte** : Tous les logs incluent `event_type`, `stripe_account_id`, et `ip` pour faciliter le debugging.

---

## 🎯 Prochaines étapes

L'ÉTAPE 2.1 est terminée. Le contrôleur `StripeConnectWebhookController` est implémenté et prêt pour les tests.

**Prochaines étapes** :
- Créer la route pour le webhook
- Configurer le webhook dans Stripe Dashboard
- Tester avec `stripe trigger account.updated`

---

## ✅ Checklist de validation

- [x] Contrôleur `StripeConnectWebhookController` créé
- [x] Méthode `__invoke()` implémentée
- [x] Vérification de signature Stripe avec `Webhook::constructEvent()`
- [x] Gestion des erreurs (signature manquante, invalide, JSON invalide)
- [x] Filtrage des événements (3 événements gérés, autres ignorés)
- [x] Appel à `syncAccountStatus()` pour `account.updated` et `capability.updated`
- [x] Marquer le compte comme désactivé pour `account.application.deauthorized`
- [x] Logging structuré (info, warning, error, debug)
- [x] Retour `200 OK` avec `{"status": "ok"}`
- [x] Aucune logique métier exclue (UI, abonnement, redirection, notification)
- [x] Code conforme aux conventions Laravel 12
- [x] Aucune erreur de lint

---

**Rapport terminé. Le contrôleur est prêt pour l'intégration !**




