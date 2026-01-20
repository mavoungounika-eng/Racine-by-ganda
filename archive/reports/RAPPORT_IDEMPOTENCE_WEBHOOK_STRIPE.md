# 📊 RAPPORT — Idempotence et Protection Race Conditions (Webhook Stripe)

**Date :** 2025-12-13  
**Objectif :** Implémenter l'idempotence et la protection contre les race conditions pour les webhooks Stripe  
**Résultat :** ✅ **Idempotence complète, protection race conditions, 39 tests passent (167 assertions)**

---

## 1. Problème Identifié

### 1.1. Problèmes Avant Correction

- ❌ **Pas d'idempotence** : Un même `event.id` Stripe pouvait être traité plusieurs fois
- ❌ **Race conditions** : Plusieurs webhooks simultanés pouvaient causer des doubles paiements
- ❌ **Pas de verrouillage** : Les lectures/écritures Payment n'étaient pas protégées
- ❌ **Pas de tracking** : Aucun historique des événements webhook traités

### 1.2. Risques Production

- **Double validation** : Un même événement pouvait valider un paiement deux fois
- **Double décrément stock** : Risque de décrémenter le stock plusieurs fois
- **Incohérence données** : États incohérents entre Payment et Order

---

## 2. Solution Implémentée

### 2.1. Table `stripe_webhook_events`

**Fichier :** `database/migrations/2025_12_13_225153_create_stripe_webhook_events_table.php`

**Structure :**
- `id` : Identifiant unique
- `event_id` : Stripe event ID (`evt_...`) - **UNIQUE**
- `event_type` : Type d'événement (checkout.session.completed, etc.)
- `payment_id` : Référence au Payment (nullable, FK)
- `status` : Statut (received, processed, ignored, failed)
- `processed_at` : Date de traitement (nullable)
- `payload_hash` : Hash SHA256 du payload (optionnel)
- `timestamps` : created_at, updated_at

**Index :**
- `event_id` (unique) : Pour l'idempotence
- `payment_id` : Pour les requêtes par Payment
- `event_type` : Pour les statistiques
- `status` : Pour le monitoring

### 2.2. Modèle `StripeWebhookEvent`

**Fichier :** `app/Models/StripeWebhookEvent.php`

**Méthodes :**
- `isProcessed()` : Vérifie si l'événement a déjà été traité
- `markAsProcessed(?int $paymentId)` : Marque comme traité
- `markAsIgnored()` : Marque comme ignoré
- `markAsFailed()` : Marque comme échoué

### 2.3. Service `CardPaymentService`

**Fichier :** `app/Services/Payments/CardPaymentService.php`

**Modifications :**

1. **Extraction `event.id` et `event.type`** :
   - Vérification obligatoire de la présence de `event.id` et `event.type`
   - Log warning si absents → retourne `null`

2. **Insert-first (Idempotence)** :
   ```php
   try {
       $webhookEvent = StripeWebhookEvent::create([
           'event_id' => $eventId,
           'event_type' => $eventType,
           'status' => 'received',
           'payload_hash' => hash('sha256', $payload),
       ]);
   } catch (QueryException $e) {
       // Duplicate key = événement déjà traité
       if (duplicate entry) {
           return existing payment or null;
       }
       throw $e;
   }
   ```

3. **Transaction + Lock** :
   ```php
   return DB::transaction(function () use ($webhookEvent, ...) {
       // Recherche Payment avec lockForUpdate()
       $payment = Payment::where(...)->lockForUpdate()->first();
       
       // Recharger pour avoir les dernières données
       $payment->refresh();
       
       // Vérifier si déjà payé (après lock)
       if ($payment->status === 'paid') {
           $webhookEvent->markAsIgnored();
           return $payment;
       }
       
       // Traiter l'événement
       // ...
       
       // Marquer comme traité
       $webhookEvent->markAsProcessed($payment->id);
   });
   ```

4. **Gestion d'erreurs** :
   - En cas d'exception, marquer l'événement comme `failed`
   - Relancer l'exception pour que le controller renvoie 500

---

## 3. Fichiers Créés/Modifiés

### 3.1. Fichiers Créés

| Fichier | Description |
|---------|-------------|
| `database/migrations/2025_12_13_225153_create_stripe_webhook_events_table.php` | Migration table `stripe_webhook_events` |
| `app/Models/StripeWebhookEvent.php` | Modèle Eloquent pour les événements webhook |
| `tests/Feature/StripeWebhookIdempotencyTest.php` | Tests d'idempotence (3 tests) |

### 3.2. Fichiers Modifiés

| Fichier | Modifications |
|---------|--------------|
| `app/Services/Payments/CardPaymentService.php` | Ajout idempotence, transaction, lockForUpdate |

---

## 4. Détails Techniques

### 4.1. Idempotence (Insert-First)

**Principe :** Tenter de créer l'enregistrement `stripe_webhook_events` avec `event_id` unique.

**Avantages :**
- ✅ **Atomicité** : L'insertion est atomique (pas de SELECT puis INSERT)
- ✅ **Race condition safe** : Si deux webhooks arrivent simultanément, un seul réussit
- ✅ **Performance** : Pas de SELECT inutile avant l'insertion

**Comportement :**
- Si `event_id` existe déjà → événement déjà traité → retourne 200 immédiatement
- Si `event_id` n'existe pas → création → traitement normal

### 4.2. Protection Race Conditions

**Mécanismes :**

1. **Transaction DB** : Toute la logique est dans `DB::transaction()`
2. **Pessimistic Lock** : `Payment::lockForUpdate()` verrouille la ligne
3. **Rechargement** : `$payment->refresh()` après lock pour avoir les dernières données
4. **Vérification après lock** : Vérifier `status === 'paid'` après le lock

**Flux :**
```
1. Insert-first (idempotence)
2. Transaction start
3. Lock Payment
4. Refresh Payment
5. Check if already paid
6. Process event
7. Update webhook_event status
8. Transaction commit
```

### 4.3. Gestion des Statuts

| Statut | Signification | Quand |
|--------|---------------|-------|
| `received` | Événement reçu | À la création |
| `processed` | Événement traité avec succès | Après traitement réussi |
| `ignored` | Événement ignoré | Payment déjà payé ou pas de Payment associé |
| `failed` | Événement échoué | Exception lors du traitement |

---

## 5. Tests

### 5.1. Tests Créés

**Fichier :** `tests/Feature/StripeWebhookIdempotencyTest.php`

**Tests :**

1. **`test_webhook_is_idempotent_for_same_event_id`** :
   - Envoie le même `event.id` deux fois
   - Vérifie qu'il n'y a qu'un seul enregistrement dans `stripe_webhook_events`
   - Vérifie que le Payment n'est pas modifié deux fois

2. **`test_webhook_handles_duplicate_key_gracefully`** :
   - Simule un duplicate key (événement déjà existant)
   - Vérifie que le webhook retourne 200 (idempotent)
   - Vérifie qu'il n'y a toujours qu'un seul enregistrement

3. **`test_webhook_prevents_double_payment_with_lock`** :
   - Envoie un webhook pour un Payment déjà payé
   - Vérifie que l'événement est marqué comme `ignored`
   - Vérifie que le Payment n'est pas modifié

### 5.2. Helper de Test

**Méthode `generateStripeSignature()`** :
- Génère une signature Stripe valide pour les tests
- Format : `t={timestamp},v1={signature}`
- Utilise HMAC-SHA256

**Méthode `createStripeEventPayload()`** :
- Crée un payload JSON d'événement Stripe
- Format standard Stripe avec `id`, `type`, `data.object`

### 5.3. Résultats

```bash
php artisan test --filter StripeWebhookIdempotencyTest
```

**Résultat :** ✅ **3 tests passent (14 assertions)**

```bash
php artisan test
```

**Résultat :** ✅ **39 tests passent (167 assertions)**

---

## 6. Impact Production

### 6.1. Sécurité

- ✅ **Idempotence garantie** : Un même `event.id` ne peut être traité qu'une fois
- ✅ **Protection race conditions** : Verrouillage pessimiste sur Payment
- ✅ **Atomicité** : Transaction DB garantit la cohérence

### 6.2. Monitoring

- ✅ **Historique complet** : Tous les événements webhook sont trackés
- ✅ **Statuts clairs** : received, processed, ignored, failed
- ✅ **Traçabilité** : `payload_hash` pour vérification optionnelle

### 6.3. Performance

- ✅ **Insert-first** : Pas de SELECT inutile
- ✅ **Index optimisés** : Requêtes rapides sur `event_id`, `payment_id`
- ✅ **Transaction courte** : Lock maintenu uniquement pendant le traitement

---

## 7. Commandes de Validation

```bash
# Migration
php artisan migrate
# ✅ OK

# Tests idempotence
php artisan test --filter StripeWebhookIdempotencyTest
# ✅ 3 passed (14 assertions)

# Tous les tests
php artisan test
# ✅ 39 passed (167 assertions)
```

---

## 8. Différences Clés (Avant/Après)

### 8.1. Avant

```php
// Pas d'idempotence
$payment = Payment::where(...)->first();
if ($payment->status === 'paid') {
    return; // Mais peut être appelé plusieurs fois
}
// Traitement...
```

### 8.2. Après

```php
// Idempotence insert-first
try {
    $webhookEvent = StripeWebhookEvent::create(['event_id' => $eventId, ...]);
} catch (DuplicateEntry) {
    return existing payment; // Déjà traité
}

// Transaction + Lock
DB::transaction(function () use ($webhookEvent) {
    $payment = Payment::where(...)->lockForUpdate()->first();
    $payment->refresh();
    if ($payment->status === 'paid') {
        $webhookEvent->markAsIgnored();
        return $payment;
    }
    // Traitement...
    $webhookEvent->markAsProcessed($payment->id);
});
```

---

## 9. Points d'Attention

### 9.1. Production

- ✅ **Migration exécutée** : Table `stripe_webhook_events` créée
- ✅ **Index créés** : Performance optimale
- ✅ **Contrainte unique** : `event_id` unique garantit l'idempotence

### 9.2. Monitoring

- Surveiller les événements en `failed` (erreurs de traitement)
- Surveiller les événements en `ignored` (déjà traités, normal)
- Surveiller le nombre d'événements par `event_type`

### 9.3. Maintenance

- Nettoyer les anciens événements si nécessaire (après X jours)
- Vérifier les `payload_hash` en cas de doute
- Analyser les événements `failed` pour corriger les bugs

---

## 10. Conclusion

**Objectif atteint :** ✅ **Idempotence et protection race conditions complètes**

- ✅ **Table `stripe_webhook_events`** : Tracking complet des événements
- ✅ **Insert-first** : Idempotence garantie au niveau DB
- ✅ **Transaction + Lock** : Protection contre les race conditions
- ✅ **Tests complets** : 3 tests d'idempotence passent
- ✅ **Aucune régression** : 39 tests passent (167 assertions)

**Le webhook Stripe est maintenant production-ready avec idempotence et protection race conditions.**

---

**Rapport généré le :** 2025-12-13  
**Durée totale :** ~21 secondes pour l'exécution complète des tests







