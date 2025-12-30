# PATCH PLAN V1 — PAYMENT SOURCE OF TRUTH
**Projet :** racine-backend (Laravel 12)  
**Date :** 2025-01-XX  
**Objectif :** Migrer webhooks v1.1 de `PaymentTransaction` vers `Payment` comme source of truth unique

---

## A. PATCH PLAN V1 (Étapes ordonnées)

### Phase 1 : Migration base de données

**Étape 1.1** : Créer migration pour ajouter colonnes à `stripe_webhook_events`
- Ajouter `checkout_session_id` (string, nullable, index)
- Ajouter `payment_intent_id` (string, nullable, index)
- **Complexité :** S

**Étape 1.2** : Exécuter migration en environnement de développement
- Vérifier que les colonnes sont créées correctement
- **Complexité :** S

---

### Phase 2 : Extraction données Stripe

**Étape 2.1** : Modifier `WebhookController@stripe()` pour extraire IDs du payload
- Extraire `checkout_session_id` depuis `event.data.object.id` (si `event_type` = `checkout.session.*`)
- Extraire `payment_intent_id` depuis `event.data.object.payment_intent` ou `event.data.object.id` (si `event_type` = `payment_intent.*`)
- Stocker dans `StripeWebhookEvent` lors de `firstOrCreate()`
- **Complexité :** M

**Étape 2.2** : Ajouter méthode helper dans `WebhookController`
- `extractStripeIdentifiers(array $event): array` retourne `['checkout_session_id' => ..., 'payment_intent_id' => ...]`
- Gérer les différents formats d'événements Stripe
- **Complexité :** M

---

### Phase 3 : Refactor mapping vers Payment

**Étape 3.1** : Modifier `ProcessStripeWebhookEventJob@findTransaction()`
- Renommer en `findPayment()`
- Retourner `Payment|null` au lieu de `PaymentTransaction|null`
- Priorité 1 : `Payment::where('provider_payment_id', $paymentIntentId)->where('provider', 'stripe')->where('channel', 'card')`
- Priorité 2 : `Payment::where('external_reference', $checkoutSessionId)->where('provider', 'stripe')->where('channel', 'card')`
- Supprimer tous les `LIKE` et fallback "dernière transaction récente"
- Si Payment introuvable : marquer event `failed` avec error explicite
- **Complexité :** M

**Étape 3.2** : Modifier `ProcessMonetbilCallbackEventJob@findTransaction()`
- Renommer en `findPayment()`
- Retourner `Payment|null` au lieu de `PaymentTransaction|null`
- `Payment::where('external_reference', $transactionId)->where('channel', 'mobile_money')`
- Si Payment introuvable : marquer event `failed` avec error explicite
- **Complexité :** S

**Étape 3.3** : Modifier `ProcessStripeWebhookEventJob@handle()`
- Remplacer `$transaction = $this->findTransaction($event)` par `$payment = $this->findPayment($event)`
- Remplacer vérification `$transaction->isAlreadySuccessful()` par `$payment->status === 'paid'`
- Remplacer appel `$mapperService->updateTransactionAndOrder($transaction, $status)` par `$mapperService->updatePaymentAndOrder($payment, $status)`
- **Complexité :** S

**Étape 3.4** : Modifier `ProcessMonetbilCallbackEventJob@handle()`
- Remplacer `$transaction = $this->findTransaction($event)` par `$payment = $this->findPayment($event)`
- Remplacer vérification `$transaction->isAlreadySuccessful()` par `$payment->status === 'paid'`
- Remplacer appel `$mapperService->updateTransactionAndOrder($transaction, $status)` par `$mapperService->updatePaymentAndOrder($payment, $status)`
- **Complexité :** S

---

### Phase 4 : Refactor PaymentEventMapperService

**Étape 4.1** : Créer nouvelle méthode `updatePaymentAndOrder(Payment $payment, string $newStatus)`
- Copier logique de `updateTransactionAndOrder()` mais utiliser `Payment` au lieu de `PaymentTransaction`
- Mettre à jour `Payment.status` (source of truth)
- Mettre à jour `Order.payment_status` et `Order.status` via mapping
- Utiliser `Payment.order_id` pour récupérer `Order`
- **Complexité :** M

**Étape 4.2** : Marquer `updateTransactionAndOrder()` comme déprécié
- Ajouter `@deprecated` dans PHPDoc
- Garder méthode pour compatibilité temporaire (si utilisée ailleurs)
- **Complexité :** S

**Étape 4.3** : Vérifier mapping Payment.status → Order.status/payment_status
- Confirmer mapping : `paid` → `Order.payment_status='paid'`, `Order.status='processing'`
- Confirmer mapping : `failed` → `Order.payment_status='failed'`, `Order.status='pending'`
- Confirmer mapping : `refunded` → `Order.payment_status='refunded'`, `Order.status='cancelled'`
- **Complexité :** S

---

### Phase 5 : Mise à jour StripeWebhookEvent

**Étape 5.1** : Modifier `StripeWebhookEvent::markAsProcessed()`
- Accepter `Payment $payment` au lieu de `?int $paymentId`
- Mettre à jour `payment_id` avec `$payment->id`
- **Complexité :** S

**Étape 5.2** : Mettre à jour appels dans Jobs
- `ProcessStripeWebhookEventJob` : Passer `$payment` à `markAsProcessed($payment)`
- **Complexité :** S

---

### Phase 6 : Tests et validation

**Étape 6.1** : Ajouter tests feature Stripe
- Test idempotence + dispatched_at exactly-once
- Test payment_intent → Payment → Order paid
- Test Payment introuvable → event failed, Order inchangé
- **Complexité :** M

**Étape 6.2** : Ajouter tests feature Monetbil
- Test transaction_id → Payment → Order paid
- **Complexité :** S

**Étape 6.3** : Validation manuelle en environnement de test
- Tester webhook Stripe avec payment_intent
- Tester webhook Stripe avec checkout_session
- Tester callback Monetbil
- Vérifier que Order.payment_status est mis à jour correctement
- **Complexité :** M

---

## B. LISTE DES FICHIERS À MODIFIER

### Contrôleurs

**Fichier :** `app/Http/Controllers/Api/WebhookController.php`

**Méthodes à modifier :**
- `stripe(Request $request)` : Extraire `checkout_session_id` et `payment_intent_id` du payload, stocker dans `StripeWebhookEvent`

**Méthodes à ajouter :**
- `extractStripeIdentifiers(array $event): array` : Helper pour extraire IDs depuis payload Stripe

**Lignes concernées :**
- Lignes 111-120 : Modifier `firstOrCreate()` pour inclure `checkout_session_id` et `payment_intent_id`
- Après ligne 109 : Ajouter extraction des IDs avant `firstOrCreate()`

---

### Jobs

**Fichier :** `app/Jobs/ProcessStripeWebhookEventJob.php`

**Méthodes à modifier :**
- `handle(PaymentEventMapperService $mapperService)` : Remplacer `findTransaction()` par `findPayment()`, utiliser `updatePaymentAndOrder()`
- `findTransaction(StripeWebhookEvent $event)` : Renommer en `findPayment()`, retourner `Payment|null`, supprimer LIKE/fallback

**Lignes concernées :**
- Ligne 90 : Remplacer `$transaction = $this->findTransaction($event)` par `$payment = $this->findPayment($event)`
- Lignes 103-112 : Remplacer vérification `$transaction->isAlreadySuccessful()` par `$payment->status === 'paid'`
- Ligne 115 : Remplacer `updateTransactionAndOrder($transaction, $status)` par `updatePaymentAndOrder($payment, $status)`
- Lignes 154-199 : Réécrire complètement `findTransaction()` → `findPayment()` avec mapping déterministe

---

**Fichier :** `app/Jobs/ProcessMonetbilCallbackEventJob.php`

**Méthodes à modifier :**
- `handle(PaymentEventMapperService $mapperService)` : Remplacer `findTransaction()` par `findPayment()`, utiliser `updatePaymentAndOrder()`
- `findTransaction(MonetbilCallbackEvent $event)` : Renommer en `findPayment()`, retourner `Payment|null`

**Lignes concernées :**
- Ligne 92 : Remplacer `$transaction = $this->findTransaction($event)` par `$payment = $this->findPayment($event)`
- Lignes 103-114 : Remplacer vérification `$transaction->isAlreadySuccessful()` par `$payment->status === 'paid'`
- Ligne 117 : Remplacer `updateTransactionAndOrder($transaction, $status)` par `updatePaymentAndOrder($payment, $status)`
- Lignes 159-194 : Réécrire complètement `findTransaction()` → `findPayment()` avec mapping déterministe

---

### Services

**Fichier :** `app/Services/Payments/PaymentEventMapperService.php`

**Méthodes à modifier :**
- `updateTransactionAndOrder(PaymentTransaction $transaction, string $newStatus)` : Marquer comme `@deprecated`

**Méthodes à ajouter :**
- `updatePaymentAndOrder(Payment $payment, string $newStatus): void` : Nouvelle méthode utilisant `Payment`

**Lignes concernées :**
- Après ligne 136 : Ajouter nouvelle méthode `updatePaymentAndOrder()`
- Ligne 80 : Ajouter `@deprecated` dans PHPDoc

---

### Modèles

**Fichier :** `app/Models/StripeWebhookEvent.php`

**Méthodes à modifier :**
- `markAsProcessed(?int $paymentId = null)` : Accepter `Payment $payment` au lieu de `?int $paymentId`

**Propriétés à ajouter :**
- `checkout_session_id` dans `$fillable`
- `payment_intent_id` dans `$fillable`

**Lignes concernées :**
- Ligne 13 : Ajouter `'checkout_session_id', 'payment_intent_id'` dans `$fillable`
- Ligne 71 : Modifier signature `markAsProcessed(Payment $payment = null)` ou `markAsProcessed(?Payment $payment = null)`

---

## C. MIGRATIONS À AJOUTER

### Migration : Ajouter colonnes à stripe_webhook_events

**Fichier :** `database/migrations/2025_01_XX_XXXXXX_add_stripe_identifiers_to_webhook_events_table.php`

**Contenu :**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stripe_webhook_events', function (Blueprint $table) {
            $table->string('checkout_session_id')->nullable()->after('event_type');
            $table->string('payment_intent_id')->nullable()->after('checkout_session_id');
            
            // Indexes pour recherche rapide
            $table->index('checkout_session_id');
            $table->index('payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('stripe_webhook_events', function (Blueprint $table) {
            $table->dropIndex(['checkout_session_id']);
            $table->dropIndex(['payment_intent_id']);
            $table->dropColumn(['checkout_session_id', 'payment_intent_id']);
        });
    }
};
```

**Recommandation :** Minimal (colonnes uniquement, pas de payload JSON)

**Raisons :**
- Les IDs (`checkout_session_id`, `payment_intent_id`) suffisent pour le mapping déterministe
- Le payload JSON n'est pas nécessaire car on peut toujours récupérer les données depuis Stripe API si besoin
- Économie de stockage (payload peut être volumineux)
- `payload_hash` existe déjà pour vérification d'intégrité

**Alternative (si payload nécessaire) :**
- Ajouter colonne `payload` (json, nullable) si besoin de rejouer des événements
- Non recommandé pour l'instant (complexité supplémentaire)

---

## D. TESTS À AJOUTER

### Tests Feature

#### 1. `tests/Feature/Payments/StripeWebhookIdempotenceTest.php`

**Intention :** Vérifier que le même `event_id` ne déclenche qu'un seul traitement

**Tests :**
- `test_stripe_webhook_event_idempotent()` : Envoyer 2 fois le même webhook, vérifier qu'un seul job est dispatché
- `test_stripe_webhook_dispatched_at_exactly_once()` : Vérifier que `dispatched_at` est mis à jour atomiquement

---

#### 2. `tests/Feature/Payments/StripeWebhookPaymentMappingTest.php`

**Intention :** Vérifier le mapping payment_intent → Payment → Order

**Tests :**
- `test_stripe_webhook_maps_payment_intent_to_payment()` : Webhook avec `payment_intent.succeeded`, vérifier que Payment est trouvé via `provider_payment_id`
- `test_stripe_webhook_maps_checkout_session_to_payment()` : Webhook avec `checkout.session.completed`, vérifier que Payment est trouvé via `external_reference`
- `test_stripe_webhook_updates_order_payment_status()` : Vérifier que `Order.payment_status` passe à `'paid'` et `Order.status` à `'processing'`

---

#### 3. `tests/Feature/Payments/StripeWebhookPaymentNotFoundTest.php`

**Intention :** Vérifier le comportement si Payment introuvable

**Tests :**
- `test_stripe_webhook_fails_when_payment_not_found()` : Webhook avec payment_intent inexistant, vérifier que `StripeWebhookEvent.status = 'failed'`
- `test_stripe_webhook_does_not_update_order_when_payment_not_found()` : Vérifier que `Order.payment_status` reste inchangé si Payment introuvable

---

#### 4. `tests/Feature/Payments/MonetbilWebhookPaymentMappingTest.php`

**Intention :** Vérifier le mapping transaction_id → Payment → Order

**Tests :**
- `test_monetbil_webhook_maps_transaction_id_to_payment()` : Callback avec `transaction_id`, vérifier que Payment est trouvé via `external_reference`
- `test_monetbil_webhook_updates_order_payment_status()` : Vérifier que `Order.payment_status` passe à `'paid'` et `Order.status` à `'processing'`

---

### Tests Unit

#### 5. `tests/Unit/Services/PaymentEventMapperServiceTest.php`

**Intention :** Vérifier le mapping Payment.status → Order.status/payment_status

**Tests :**
- `test_map_payment_paid_to_order_processing()` : Payment.status='paid' → Order.payment_status='paid', Order.status='processing'
- `test_map_payment_failed_to_order_pending()` : Payment.status='failed' → Order.payment_status='failed', Order.status='pending'
- `test_map_payment_refunded_to_order_cancelled()` : Payment.status='refunded' → Order.payment_status='refunded', Order.status='cancelled'

---

## E. RISQUES & MITIGATIONS

### 🔴 P0 — Critique (à traiter immédiatement)

#### R1 : Perte de données si Payment introuvable

**Risque :** Si le mapping échoue (Payment introuvable), le webhook est marqué `failed` mais l'Order reste `pending`, même si le paiement a réellement été effectué.

**Mitigation :**
- Logger explicitement quand Payment introuvable avec `payment_intent_id` / `checkout_session_id` / `transaction_id`
- Créer dashboard admin pour visualiser les événements `failed` avec Payment introuvable
- Alerte si > X événements failed dans les dernières 24h

**Complexité :** M

---

#### R2 : Race condition si webhook arrive avant création Payment

**Risque :** Webhook Stripe peut arriver avant que `CardPaymentService::createCheckoutSession()` ait terminé la création du Payment.

**Mitigation :**
- Implémenter retry avec backoff dans le job (déjà en place : 3 tentatives, backoff [10, 30, 60])
- Vérifier que `CardPaymentService::createCheckoutSession()` crée le Payment AVANT de retourner l'URL Stripe
- Ajouter délai de grâce (5-10 secondes) avant de marquer `failed` si Payment introuvable

**Complexité :** M

---

### 🟡 P1 — Important (à traiter rapidement)

#### R3 : Incompatibilité avec données existantes

**Risque :** Les `StripeWebhookEvent` existants n'ont pas `checkout_session_id` / `payment_intent_id`, donc les requeue échoueront.

**Mitigation :**
- Migration rétroactive : Extraire IDs depuis `payload_hash` si possible (non recommandé, trop complexe)
- Accepter que les anciens événements ne peuvent pas être requeued (acceptable)
- Documenter que seuls les nouveaux événements bénéficient du mapping amélioré

**Complexité :** S

---

#### R4 : Legacy routes toujours actives

**Risque :** Les routes legacy (`/webhooks/stripe`, `/payment/card/webhook`) continuent de fonctionner et peuvent créer des doublons.

**Mitigation :**
- Vérifier que les routes legacy ne sont plus configurées dans Stripe Dashboard
- Ajouter logs pour détecter si elles sont encore utilisées
- Planifier suppression après période de transition (1-2 semaines)

**Complexité :** S

---

### 🟢 P2 — Mineur (à améliorer)

#### R5 : Pas de monitoring des échecs

**Risque :** Pas de visibilité sur les webhooks qui échouent à mapper vers Payment.

**Mitigation :**
- Ajouter métriques (nombre d'événements failed par jour)
- Dashboard admin pour visualiser les échecs
- Alertes proactives

**Complexité :** M

---

## F. CHECKLIST DE VALIDATION MANUELLE

### Pré-déploiement

- [ ] Migration exécutée en dev : colonnes `checkout_session_id` et `payment_intent_id` créées
- [ ] Tests unitaires passent : `PaymentEventMapperServiceTest`
- [ ] Tests feature passent : Tous les tests Stripe/Monetbil
- [ ] Vérifier que `ProcessStripeWebhookEventJob` utilise `findPayment()` au lieu de `findTransaction()`
- [ ] Vérifier que `ProcessMonetbilCallbackEventJob` utilise `findPayment()` au lieu de `findTransaction()`
- [ ] Vérifier que `PaymentEventMapperService::updatePaymentAndOrder()` existe et fonctionne

---

### Tests manuels (environnement de test)

#### Stripe Webhook

- [ ] Créer commande avec paiement Stripe
- [ ] Vérifier que `Payment` est créé avec `external_reference` (session_id) et `provider_payment_id` (payment_intent)
- [ ] Simuler webhook `checkout.session.completed` via Stripe CLI
- [ ] Vérifier que `StripeWebhookEvent.checkout_session_id` est rempli
- [ ] Vérifier que `Payment.status` passe à `'paid'`
- [ ] Vérifier que `Order.payment_status` passe à `'paid'` et `Order.status` à `'processing'`

- [ ] Simuler webhook `payment_intent.succeeded` via Stripe CLI
- [ ] Vérifier que `StripeWebhookEvent.payment_intent_id` est rempli
- [ ] Vérifier que `Payment` est trouvé via `provider_payment_id`
- [ ] Vérifier que `Order.payment_status` est mis à jour

- [ ] Simuler webhook avec `payment_intent` inexistant
- [ ] Vérifier que `StripeWebhookEvent.status` = `'failed'`
- [ ] Vérifier que `Order.payment_status` reste inchangé

---

#### Monetbil Callback

- [ ] Créer commande avec paiement Mobile Money
- [ ] Vérifier que `Payment` est créé avec `external_reference` (transaction_id)
- [ ] Simuler callback Monetbil avec `transaction_id` valide
- [ ] Vérifier que `Payment.status` passe à `'paid'`
- [ ] Vérifier que `Order.payment_status` passe à `'paid'` et `Order.status` à `'processing'`

- [ ] Simuler callback Monetbil avec `transaction_id` inexistant
- [ ] Vérifier que `MonetbilCallbackEvent.status` = `'failed'`
- [ ] Vérifier que `Order.payment_status` reste inchangé

---

#### Idempotence

- [ ] Envoyer 2 fois le même webhook Stripe (même `event_id`)
- [ ] Vérifier qu'un seul job est dispatché (vérifier `dispatched_at`)
- [ ] Vérifier que `Order.payment_status` n'est mis à jour qu'une fois

- [ ] Envoyer 2 fois le même callback Monetbil (même `event_key`)
- [ ] Vérifier qu'un seul job est dispatché
- [ ] Vérifier que `Order.payment_status` n'est mis à jour qu'une fois

---

### Post-déploiement (production)

- [ ] Monitorer logs pour détecter événements `failed` avec Payment introuvable
- [ ] Vérifier que les webhooks Stripe arrivent et sont traités correctement
- [ ] Vérifier que les callbacks Monetbil arrivent et sont traités correctement
- [ ] Vérifier qu'aucun événement n'utilise le fallback "dernière transaction récente" (vérifier logs)
- [ ] Dashboard admin : Vérifier que les nouveaux événements ont `checkout_session_id` / `payment_intent_id` remplis

---

## G. COMPLEXITÉ PAR BLOC

| Bloc | Complexité | Justification |
|------|------------|---------------|
| Migration DB | S | Ajout de 2 colonnes + indexes simples |
| Extraction IDs Stripe | M | Logique conditionnelle selon event_type, gestion différents formats |
| Refactor mapping Stripe | M | Suppression fallback, nouvelle logique de recherche, gestion erreurs |
| Refactor mapping Monetbil | S | Logique simple : recherche par external_reference |
| Refactor PaymentEventMapperService | M | Nouvelle méthode, mapping statuts à valider |
| Mise à jour StripeWebhookEvent | S | Modification signature méthode, ajout fillable |
| Tests Feature | M | 4 fichiers de tests, scénarios variés |
| Tests Unit | S | 1 fichier, tests de mapping simples |
| Validation manuelle | M | Scénarios multiples à tester |

---

**FIN DU PLAN DE PATCH V1**

