# 🗄️ Schéma DB Paiements Existant — RACINE BY GANDA

**Date :** 2025-12-14  
**Sprint :** Sprint 1 — Audit  
**Ticket :** #PH1-003

---

## 🎯 OBJECTIF

Cartographier les tables et modèles paiements existants pour éviter doublons et incohérences.

---

## 📊 TABLES EXISTANTES

### 1. `payment_transactions` ✅ (Source of truth)

**Migration :** `2025_12_13_215019_create_payment_transactions_table.php`

**Structure :**
```sql
CREATE TABLE payment_transactions (
    id BIGINT PRIMARY KEY,
    provider VARCHAR(255) DEFAULT 'monetbil',  -- monetbil, stripe, etc.
    order_id BIGINT NULLABLE FK -> orders.id,
    payment_ref VARCHAR(255) UNIQUE,           -- Référence unique commande
    item_ref VARCHAR(255) NULLABLE,           -- Référence optionnelle item
    transaction_id VARCHAR(255) NULLABLE UNIQUE, -- Transaction ID Monetbil
    transaction_uuid VARCHAR(255) NULLABLE,   -- Transaction UUID Monetbil
    amount DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'XAF',
    status ENUM('pending', 'success', 'failed', 'cancelled') DEFAULT 'pending',
    operator VARCHAR(255) NULLABLE,           -- Opérateur Mobile Money
    phone VARCHAR(255) NULLABLE,              -- Numéro téléphone
    fee DECIMAL(10,2) NULLABLE,               -- Frais transaction
    raw_payload JSON NULLABLE,                 -- Payload brut notification
    notified_at TIMESTAMP NULLABLE,           -- Date notification
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Index :**
- `payment_ref` (index)
- `transaction_id` (index, unique si présent)
- `order_id` (index)
- `status` (index)

**Modèle :** `App\Models\PaymentTransaction`

**Relations :**
- `belongsTo Order` (via `order_id`)

**Méthodes utiles :**
- `isAlreadySuccessful()` : Vérifie si `status === 'success'` (idempotence)

**Statut :** ✅ **Source of truth pour les transactions**

---

### 2. `stripe_webhook_events` ✅

**Migration :** `2025_12_13_225153_create_stripe_webhook_events_table.php`

**Structure :**
```sql
CREATE TABLE stripe_webhook_events (
    id BIGINT PRIMARY KEY,
    event_id VARCHAR(255) UNIQUE,             -- Stripe event ID (evt_...)
    event_type VARCHAR(255),                  -- checkout.session.completed, etc.
    payment_id BIGINT NULLABLE FK -> payments.id,
    status VARCHAR(255) DEFAULT 'received',   -- received, processed, ignored, failed
    processed_at TIMESTAMP NULLABLE,
    payload_hash VARCHAR(255) NULLABLE,       -- Hash payload vérification
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Index :**
- `payment_id` (index)
- `event_type` (index)
- `status` (index)

**Modèle :** `App\Models\StripeWebhookEvent` (à vérifier si existe)

**Statut :** ✅ **Table événements Stripe (idempotence via event_id)**

---

### 3. `payments` ⚠️ (Legacy ?)

**Migration :** `2025_11_23_000006_create_payments_table.php`

**Structure :**
```sql
CREATE TABLE payments (
    id BIGINT PRIMARY KEY,
    order_id BIGINT FK -> orders.id,
    provider VARCHAR(255) DEFAULT 'stripe',   -- stripe, monetbil, etc.
    provider_payment_id VARCHAR(255) NULLABLE,
    status VARCHAR(255) DEFAULT 'pending',    -- initiated, pending, paid, failed
    amount DECIMAL(10,2),
    currency VARCHAR(255) DEFAULT 'XOF',
    channel VARCHAR(255),                      -- card, mobile_money, cash
    customer_phone VARCHAR(255) NULLABLE,
    external_reference VARCHAR(255) NULLABLE,  -- Session ID Stripe, Transaction ID MoMo
    metadata JSON NULLABLE,
    payload JSON NULLABLE,
    paid_at TIMESTAMP NULLABLE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

**Modèle :** `App\Models\Payment`

**Relations :**
- `belongsTo Order` (via `order_id`)

**Statut :** ⚠️ **Table legacy — Ne pas utiliser comme source of truth**

**Note :** Cette table semble être utilisée pour la compatibilité avec le système existant, mais `payment_transactions` est la source de vérité métier.

---

## 🔗 RELATIONS AVEC `orders`

### Table `orders`

**Modèle :** `App\Models\Order`

**Champs liés aux paiements :**
- `payment_status` : Statut paiement de la commande (`pending`, `paid`, `failed`)
- `payment_method` : Méthode de paiement choisie (`card`, `mobile_money`, `monetbil`, `cash_on_delivery`)

**Relations :**
- `hasMany Payment` (via `payments` table)
- `hasMany PaymentTransaction` (via `payment_transactions.order_id`)

**Méthodes :**
```php
public function payments(): HasMany
{
    return $this->hasMany(Payment::class);
}

// Relation avec payment_transactions (à vérifier si existe)
public function paymentTransactions(): HasMany
{
    return $this->hasMany(PaymentTransaction::class);
}
```

---

## 📋 STATUTS STANDARDISÉS

### Statuts `payment_transactions`

**Enum actuel :** `pending`, `success`, `failed`, `cancelled`

**À standardiser vers (Sprint 1 #PH1-004) :**
- `pending` : En attente
- `processing` : En cours de traitement
- `succeeded` : Réussi (remplace `success`)
- `failed` : Échoué
- `canceled` : Annulé (remplace `cancelled`)
- `refunded` : Remboursé (nouveau)

### Statuts `orders.payment_status`

**Valeurs possibles :**
- `pending` : En attente
- `paid` : Payé
- `failed` : Échoué

---

## 🔍 ANALYSE DES GAPS

### Tables manquantes pour Payments Hub

1. **`payment_providers`** ❌
   - Nécessaire pour pilotage providers (Stripe, Monetbil)
   - Colonnes : `code`, `name`, `is_enabled`, `priority`, `health_status`, etc.

2. **`payment_routing_rules`** ❌
   - Nécessaire pour routage (card → Stripe, mobile_money → Monetbil)
   - Colonnes : `channel`, `currency`, `country`, `primary_provider_id` (FK bigint), `fallback_provider_id` (FK bigint), etc.

3. **`monetbil_callback_events`** ❌
   - Équivalent `stripe_webhook_events` pour Monetbil
   - Colonnes : `event_key` (unique), `payment_ref`, `transaction_id`, `status`, `payload`, etc.

4. **`payment_audit_logs`** ❌
   - Traçabilité admin (reprocess, refund, config)
   - Colonnes : `user_id`, `action`, `target_type`, `target_id`, `diff`, `reason`, etc.

### Index manquants

**À vérifier/ajouter :**
- `payment_transactions(provider, status, created_at)` : Pour filtres admin
- `payment_transactions(created_at)` : Pour tri chronologique
- `stripe_webhook_events(status, created_at)` : Pour monitoring
- `monetbil_callback_events(status, received_at)` : Pour monitoring

---

## ✅ CHECKLIST INTÉGRATION

- [x] `payment_transactions` documentée (source of truth)
- [x] `stripe_webhook_events` documentée
- [x] `payments` identifiée (legacy)
- [x] Relation avec `orders` confirmée
- [x] Statuts existants listés
- [x] Tables manquantes identifiées
- [x] Index manquants identifiés

---

## 📝 NOTES IMPORTANTES

1. **Source of truth** : `payment_transactions` + `orders` = vérité métier. La table `payments` est legacy et ne doit pas être utilisée comme source de vérité.

2. **Standardisation statuts** : Aligner `payment_transactions.status` sur l'enum standardisé (Sprint 1 #PH1-004).

3. **FK bigint** : Les règles de routage utiliseront `primary_provider_id` et `fallback_provider_id` (FK bigint vers `payment_providers.id`), pas de FK string sur `code`.

4. **Idempotence** : `stripe_webhook_events.event_id` et `monetbil_callback_events.event_key` garantissent l'idempotence.

---

**Document créé le :** 2025-12-14  
**Prochaine étape :** Créer les migrations manquantes dans Sprint 1 (#PH2-001 à #PH2-004)








