# 📋 RAPPORT ÉTAPE 5 — canCreatorReceivePayments() IMPLÉMENTATION

**Date** : 2025-12-19  
**Service** : `StripeConnectService`  
**Méthode** : `canCreatorReceivePayments(CreatorProfile $creator)`  
**Phase** : PHASE 1.1 — Implémentation Progressive

---

## ✅ ÉTAPE 5 TERMINÉE

### 🔧 Signature exacte de la méthode

```php
public function canCreatorReceivePayments(CreatorProfile $creator): bool
```

**Paramètres** :
- `CreatorProfile $creator` : Le profil du créateur à vérifier

**Valeur de retour** :
- `bool` : `true` si le créateur peut recevoir des paiements, `false` sinon

**Exceptions lancées** :
- Aucune : La méthode ne lève jamais d'exception, elle retourne toujours `false` en cas d'échec

**Rôle de la méthode** :
Vérifier si un créateur est éligible pour recevoir des paiements sur la plateforme en effectuant toutes les vérifications nécessaires (compte Stripe, statuts, abonnement, etc.).

---

## 🔍 Règles de validation détaillées

### Vérification 1 : Compte Stripe Connect existant

**Condition** :
```php
$stripeAccount = CreatorStripeAccount::where('creator_profile_id', $creator->id)->first();
if (!$stripeAccount) {
    return false;
}
```

**Règle** :
- Le créateur doit posséder un compte Stripe Connect en base de données
- Le compte doit être lié au créateur via `creator_profile_id`

**Raison** :
- Un créateur sans compte Stripe Connect ne peut pas recevoir de paiements
- Le compte Stripe Connect est nécessaire pour router les paiements vers le créateur

**Cas de refus** :
- Aucun compte Stripe Connect trouvé pour ce créateur → `return false`

### Vérification 2 : charges_enabled === true

**Condition** :
```php
if (!$stripeAccount->charges_enabled) {
    return false;
}
```

**Règle** :
- Le compte Stripe Connect doit avoir `charges_enabled === true`
- Cette valeur indique que Stripe autorise le créateur à recevoir des paiements

**Raison** :
- Si `charges_enabled === false`, Stripe bloque les paiements vers ce compte
- Le créateur ne peut pas recevoir de paiements même si le compte existe

**Cas de refus** :
- `charges_enabled === false` → `return false`

**Note** : Cette valeur est synchronisée depuis Stripe via `syncAccountStatus()`.

### Vérification 3 : payouts_enabled === true

**Condition** :
```php
if (!$stripeAccount->payouts_enabled) {
    return false;
}
```

**Règle** :
- Le compte Stripe Connect doit avoir `payouts_enabled === true`
- Cette valeur indique que Stripe autorise le créateur à recevoir des versements

**Raison** :
- Si `payouts_enabled === false`, Stripe bloque les versements vers ce compte
- Le créateur ne peut pas recevoir d'argent même si les paiements sont acceptés

**Cas de refus** :
- `payouts_enabled === false` → `return false`

**Note** : Cette valeur est synchronisée depuis Stripe via `syncAccountStatus()`.

### Vérification 4 : onboarding_status === 'complete'

**Condition** :
```php
if ($stripeAccount->onboarding_status !== 'complete') {
    return false;
}
```

**Règle** :
- Le statut d'onboarding doit être exactement `'complete'`
- Les autres statuts (`'pending'`, `'in_progress'`, `'failed'`) sont refusés

**Raison** :
- Un onboarding incomplet signifie que le créateur n'a pas terminé le processus KYC
- Seul un onboarding `'complete'` garantit que le compte est fonctionnel

**Cas de refus** :
- `onboarding_status === 'pending'` → `return false`
- `onboarding_status === 'in_progress'` → `return false`
- `onboarding_status === 'failed'` → `return false`
- `onboarding_status === null` → `return false`

**Note** : Le statut est déterminé par `determineOnboardingStatus()` dans `syncAccountStatus()`.

### Vérification 5 : Créateur actif (non suspendu)

**Condition** :
```php
if (!$creator->is_active || $creator->status !== 'active') {
    return false;
}
```

**Règle** :
- Le créateur doit avoir `is_active === true` **ET** `status === 'active'`
- Les créateurs suspendus (`status === 'suspended'`) sont refusés
- Les créateurs en attente (`status === 'pending'`) sont refusés

**Raison** :
- Un créateur suspendu ne peut pas recevoir de paiements (suspension administrative)
- Un créateur en attente n'a pas encore été validé par l'équipe
- Seuls les créateurs actifs peuvent recevoir des paiements

**Cas de refus** :
- `is_active === false` → `return false`
- `status === 'suspended'` → `return false`
- `status === 'pending'` → `return false`
- `status !== 'active'` → `return false`

**Note** : Cette vérification protège contre les créateurs suspendus ou non validés.

### Vérification 6 : Abonnement actif

**Condition** :
```php
$subscription = CreatorSubscription::where('creator_profile_id', $creator->id)->first();
if (!$subscription || $subscription->status !== 'active') {
    return false;
}
```

**Règle** :
- Le créateur doit posséder un abonnement en base de données
- L'abonnement doit avoir `status === 'active'`
- Les autres statuts (`'incomplete'`, `'past_due'`, `'unpaid'`, etc.) sont refusés

**Raison** :
- Un créateur doit payer un abonnement mensuel pour pouvoir vendre
- Si l'abonnement n'est pas actif, le créateur ne peut pas recevoir de paiements
- C'est une règle métier de la plateforme

**Cas de refus** :
- Aucun abonnement trouvé → `return false`
- `status === 'incomplete'` → `return false`
- `status === 'incomplete_expired'` → `return false`
- `status === 'trialing'` → `return false` (période d'essai non acceptée)
- `status === 'past_due'` → `return false` (paiement en retard)
- `status === 'canceled'` → `return false` (abonnement annulé)
- `status === 'unpaid'` → `return false` (abonnement impayé)
- `status !== 'active'` → `return false`

**Note** : Seul le statut `'active'` est accepté. Les périodes d'essai (`'trialing'`) ne sont pas acceptées pour garantir que l'abonnement est payé.

---

## 🧠 Ordre des vérifications (et pourquoi)

### Ordre d'exécution

1. **Compte Stripe Connect existant** (Vérification 1)
2. **charges_enabled === true** (Vérification 2)
3. **payouts_enabled === true** (Vérification 3)
4. **onboarding_status === 'complete'** (Vérification 4)
5. **Créateur actif** (Vérification 5)
6. **Abonnement actif** (Vérification 6)

### Justification de l'ordre

#### 1. Compte Stripe Connect en premier

**Raison** :
- Si le créateur n'a pas de compte Stripe Connect, toutes les autres vérifications sont inutiles
- Cette vérification est la plus rapide (une seule requête)
- Elle évite de charger des données inutiles si le compte n'existe pas

**Performance** :
- Échec rapide si le compte n'existe pas
- Pas besoin de charger l'abonnement si le compte Stripe n'existe pas

#### 2. charges_enabled et payouts_enabled avant onboarding_status

**Raison** :
- `charges_enabled` et `payouts_enabled` sont des indicateurs directs de Stripe
- Si ces valeurs sont `false`, le compte ne peut pas recevoir de paiements, peu importe le statut d'onboarding
- Ces vérifications sont plus rapides (propriétés boolean simples)

**Performance** :
- Échec rapide si les capacités ne sont pas activées
- Pas besoin de vérifier le statut d'onboarding si les capacités sont désactivées

#### 3. onboarding_status après les capacités

**Raison** :
- Le statut d'onboarding est une synthèse des capacités Stripe
- Si `charges_enabled` et `payouts_enabled` sont `true`, le statut devrait être `'complete'`
- Cette vérification ajoute une couche de sécurité supplémentaire

**Cohérence** :
- Garantit que le statut d'onboarding est cohérent avec les capacités Stripe
- Protège contre les incohérences de données

#### 4. Créateur actif avant l'abonnement

**Raison** :
- La vérification du créateur est plus rapide (propriétés du modèle déjà chargé)
- Si le créateur est suspendu, l'abonnement n'a pas d'importance
- Cette vérification protège contre les créateurs suspendus administrativement

**Performance** :
- Échec rapide si le créateur est suspendu
- Pas besoin de charger l'abonnement si le créateur n'est pas actif

#### 5. Abonnement actif en dernier

**Raison** :
- L'abonnement nécessite une requête supplémentaire en base de données
- C'est la vérification la plus coûteuse en termes de performance
- Si toutes les autres vérifications passent, alors on vérifie l'abonnement

**Performance** :
- Évite de charger l'abonnement si les autres vérifications échouent
- Optimise les requêtes en base de données

### Principe de "fail-fast"

**Stratégie** :
- Les vérifications les plus rapides sont effectuées en premier
- Les vérifications les plus coûteuses sont effectuées en dernier
- Dès qu'une vérification échoue, la méthode retourne `false` immédiatement

**Avantages** :
- Performance optimale (moins de requêtes si échec précoce)
- Code clair et lisible (ordre logique)
- Maintenance facilitée (facile d'ajouter de nouvelles vérifications)

---

## ⚠️ Cas de refus (return false)

### Tableau récapitulatif des cas de refus

| Vérification | Condition de refus | Raison |
|--------------|-------------------|--------|
| **1. Compte Stripe** | Aucun compte trouvé | Le créateur n'a pas de compte Stripe Connect |
| **2. charges_enabled** | `charges_enabled === false` | Stripe bloque les paiements vers ce compte |
| **3. payouts_enabled** | `payouts_enabled === false` | Stripe bloque les versements vers ce compte |
| **4. onboarding_status** | `onboarding_status !== 'complete'` | L'onboarding n'est pas terminé ou a échoué |
| **5. Créateur actif** | `is_active === false` OU `status !== 'active'` | Le créateur est suspendu ou en attente |
| **6. Abonnement actif** | Aucun abonnement OU `status !== 'active'` | L'abonnement n'est pas payé ou est annulé |

### Exemples de scénarios de refus

#### Scénario 1 : Créateur sans compte Stripe

```
CreatorProfile :
  - is_active = true
  - status = 'active'
  - Aucun CreatorStripeAccount

Résultat : return false (Vérification 1 échoue)
```

#### Scénario 2 : Compte Stripe non activé

```
CreatorStripeAccount :
  - charges_enabled = false
  - payouts_enabled = false
  - onboarding_status = 'in_progress'

Résultat : return false (Vérification 2 échoue)
```

#### Scénario 3 : Onboarding incomplet

```
CreatorStripeAccount :
  - charges_enabled = true
  - payouts_enabled = true
  - onboarding_status = 'in_progress' (pas 'complete')

Résultat : return false (Vérification 4 échoue)
```

#### Scénario 4 : Créateur suspendu

```
CreatorProfile :
  - is_active = true
  - status = 'suspended' (pas 'active')

Résultat : return false (Vérification 5 échoue)
```

#### Scénario 5 : Abonnement impayé

```
CreatorSubscription :
  - status = 'unpaid' (pas 'active')

Résultat : return false (Vérification 6 échoue)
```

#### Scénario 6 : Toutes les vérifications passent

```
CreatorStripeAccount :
  - charges_enabled = true
  - payouts_enabled = true
  - onboarding_status = 'complete'

CreatorProfile :
  - is_active = true
  - status = 'active'

CreatorSubscription :
  - status = 'active'

Résultat : return true ✅
```

---

## ❌ Ce qui est volontairement exclu

### Appel Stripe

- **Exclusion** : La méthode n'appelle jamais l'API Stripe
- **Raison** : Utilise uniquement les données en base de données (synchronisées via `syncAccountStatus()`)
- **Avantage** : Performance optimale, pas de latence réseau, pas de dépendance à Stripe

### Écriture en base de données

- **Exclusion** : La méthode ne modifie jamais la base de données
- **Raison** : C'est une méthode de vérification pure (read-only)
- **Avantage** : Pas de side effects, méthode idempotente, facile à tester

### Log métier

- **Exclusion** : La méthode ne logge aucune information
- **Raison** : C'est une méthode de vérification appelée fréquemment (checkout, etc.)
- **Avantage** : Pas de pollution des logs, performance optimale

### Levée d'exception

- **Exclusion** : La méthode ne lève jamais d'exception
- **Raison** : Retourne toujours `false` en cas d'échec (comportement prévisible)
- **Avantage** : Facile à utiliser dans des conditions, pas besoin de try-catch

### Logique UI / Webhook

- **Exclusion** : La méthode ne contient aucune logique d'interface ou de webhook
- **Raison** : C'est une méthode de service pure (sans dépendances)
- **Avantage** : Réutilisable partout (checkout, API, webhooks, etc.)

### Vérification de l'expiration de l'abonnement

- **Exclusion** : La méthode ne vérifie pas si l'abonnement est expiré (`current_period_end`)
- **Raison** : Le statut `'active'` de l'abonnement est suffisant
- **Note** : L'expiration est gérée par les webhooks Stripe qui mettent à jour le statut

### Vérification de la période d'essai

- **Exclusion** : La méthode n'accepte pas les abonnements en période d'essai (`'trialing'`)
- **Raison** : Seuls les abonnements payés (`'active'`) sont acceptés
- **Note** : Cela garantit que le créateur paie réellement son abonnement

---

## 🛡️ Rôle de la méthode dans la sécurité checkout

### Protection contre les paiements non autorisés

**Rôle principal** :
- Cette méthode est la **barrière de sécurité principale** avant de permettre un checkout
- Elle garantit que seul un créateur éligible peut recevoir des paiements

**Utilisation dans le checkout** :
```php
// Dans le contrôleur de checkout
if (!$stripeConnectService->canCreatorReceivePayments($creator)) {
    return redirect()->back()->with('error', 'Ce créateur ne peut pas recevoir de paiements.');
}

// Créer la session Stripe Checkout avec le compte Connect
```

### Vérifications de sécurité

1. **Sécurité Stripe** :
   - Vérifie que le compte Stripe est activé et fonctionnel
   - Garantit que Stripe autorise les paiements vers ce compte

2. **Sécurité métier** :
   - Vérifie que le créateur est actif (non suspendu)
   - Garantit que le créateur paie son abonnement

3. **Sécurité KYC** :
   - Vérifie que l'onboarding est complété
   - Garantit que le créateur a fourni toutes les informations nécessaires

### Protection contre les cas limites

**Cas protégés** :
- Créateur suspendu qui essaie de vendre → Refusé
- Créateur avec abonnement impayé qui essaie de vendre → Refusé
- Créateur avec onboarding incomplet qui essaie de vendre → Refusé
- Compte Stripe désactivé qui essaie de recevoir des paiements → Refusé

**Avantages** :
- Empêche les paiements vers des comptes non autorisés
- Protège la plateforme contre les créateurs non conformes
- Garantit la qualité et la conformité des transactions

### Intégration dans le flux de checkout

**Étape 1 : Vérification préalable**
```php
if (!$stripeConnectService->canCreatorReceivePayments($creator)) {
    // Refuser le checkout
}
```

**Étape 2 : Création de la session Stripe**
```php
// Si la vérification passe, créer la session avec le compte Connect
$checkoutSession = CheckoutSession::create([
    'payment_intent_data' => [
        'application_fee_amount' => 0, // Pas de commission
        'on_behalf_of' => $creator->stripeAccount->stripe_account_id,
        'transfer_data' => [
            'destination' => $creator->stripeAccount->stripe_account_id,
        ],
    ],
]);
```

**Avantages** :
- Vérification rapide avant de créer la session Stripe
- Évite les appels Stripe inutiles si le créateur n'est pas éligible
- Message d'erreur clair pour l'utilisateur

---

## 📝 Notes techniques

### Performance

**Optimisations** :
- Vérifications dans l'ordre optimal (fail-fast)
- Une seule requête pour le compte Stripe
- Une seule requête pour l'abonnement (seulement si nécessaire)
- Pas d'appel Stripe (données en cache en base)

**Complexité** :
- Temps : O(1) pour les vérifications simples, O(1) pour les requêtes DB
- Espace : O(1) (pas de stockage temporaire)

### Idempotence

**Propriété** :
- La méthode est idempotente : appeler plusieurs fois avec les mêmes données retourne le même résultat
- Pas de side effects : ne modifie jamais l'état du système

**Avantages** :
- Facile à tester
- Peut être appelée plusieurs fois sans risque
- Comportement prévisible

### Testabilité

**Facilité de test** :
- Méthode pure (pas de dépendances externes)
- Retour booléen simple
- Pas d'exceptions à gérer
- Facile à mocker (modèles Eloquent)

**Exemple de test** :
```php
// Test : créateur éligible
$creator = CreatorProfile::factory()->create(['is_active' => true, 'status' => 'active']);
CreatorStripeAccount::factory()->create([
    'creator_profile_id' => $creator->id,
    'charges_enabled' => true,
    'payouts_enabled' => true,
    'onboarding_status' => 'complete',
]);
CreatorSubscription::factory()->create([
    'creator_profile_id' => $creator->id,
    'status' => 'active',
]);

$result = $service->canCreatorReceivePayments($creator);
$this->assertTrue($result);
```

---

## 🎯 Prochaines étapes

L'ÉTAPE 5 est terminée. La méthode `canCreatorReceivePayments()` est implémentée et prête pour les tests.

**Toutes les étapes du StripeConnectService sont maintenant terminées !**

---

## ✅ Checklist de validation

- [x] Méthode `canCreatorReceivePayments()` implémentée
- [x] Vérification 1 : Compte Stripe Connect existant
- [x] Vérification 2 : charges_enabled === true
- [x] Vérification 3 : payouts_enabled === true
- [x] Vérification 4 : onboarding_status === 'complete'
- [x] Vérification 5 : Créateur actif (is_active ET status === 'active')
- [x] Vérification 6 : Abonnement actif (status === 'active')
- [x] Ordre des vérifications optimisé (fail-fast)
- [x] Aucun appel Stripe
- [x] Aucune écriture en base
- [x] Aucun log métier
- [x] Aucune exception levée
- [x] Retour booléen strict
- [x] Documentation PHPDoc complète
- [x] Code conforme aux conventions Laravel 12
- [x] Aucune erreur de lint

---

**Rapport terminé. Toutes les étapes du StripeConnectService sont complètes !**




