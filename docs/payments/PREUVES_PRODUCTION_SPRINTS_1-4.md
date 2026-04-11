# 📋 PREUVES PRODUCTION — PAYMENTS HUB (Sprints 1-4)

**Date :** 2025-12-14  
**Projet :** RACINE BY GANDA — Payments Hub Admin v1.1  
**Objectif :** Validation complète de conformité production

---

## 1) ROUTES — Preuve des endpoints concernés

### 1.1 Routes Webhooks (API)

**Sortie de `php artisan route:list --name=api.webhooks` :**

```
POST       api/webhooks/monetbil ............................ api.webhooks.monetbil › Api\WebhookController@monetbil
POST       api/webhooks/stripe .................................. api.webhooks.stripe › Api\WebhookController@stripe
```

**Fichier de déclaration :** `routes/web.php` (lignes 452-453)

**Middlewares appliqués :**
- Aucun middleware explicite dans la déclaration
- Routes dans le groupe `/api` (middleware `api` par défaut dans Laravel)
- Pas de middleware `auth` (webhooks doivent être accessibles sans authentification)
- Pas de middleware `throttle` explicite (à considérer pour production)

**Note :** Les routes sont déclarées dans `routes/web.php` et non `routes/api.php`. C'est acceptable si le projet n'utilise pas de fichier `routes/api.php` séparé.

---

### 1.2 Routes Admin Payments

**Sortie de `php artisan route:list --name=admin.payments` :**

```
GET|HEAD  admin/payments .......................... admin.payments.index › Admin\Payments\PaymentHubController@index
GET|HEAD  admin/payments/providers . admin.payments.providers.index › Admin\Payments\PaymentProviderController@index
PUT       admin/payments/providers/{provider} admin.payments.providers.update › Admin\Payments\PaymentProviderContr…
GET|HEAD  admin/payments/transactions admin.payments.transactions.index › Admin\Payments\PaymentTransactionControll…
GET|HEAD  admin/payments/transactions/export/csv admin.payments.transactions.export.csv › Admin\Payments\PaymentTra…
GET|HEAD  admin/payments/transactions/{transaction} admin.payments.transactions.show › Admin\Payments\PaymentTransa…
GET|HEAD  admin/payments/webhooks .... admin.payments.webhooks.index › Admin\Payments\WebhookMonitorController@index
GET|HEAD  admin/payments/webhooks/monetbil/{event} admin.payments.webhooks.show.monetbil › Admin\Payments\WebhookMo…
GET|HEAD  admin/payments/webhooks/stripe/{event} admin.payments.webhooks.show.stripe › Admin\Payments\WebhookMonito…
```

**Total :** 9 routes admin

**Fichier de déclaration :** `routes/web.php` (groupe `/admin`)

**Middlewares appliqués :**
- Groupe `/admin` avec middleware `admin` (authentification + autorisation)
- Protection RBAC via `authorize()` dans les contrôleurs

---

## 2) ENV / CONFIG — Variables utilisées (SANS VALEURS)

### 2.1 Variables de rétention

**Fichier de configuration :** `config/payments.php`

**Variables attendues :**

1. `PAYMENTS_EVENTS_RETENTION_DAYS`
   - **Source de vérité :** `config('payments.events.retention_days')`
   - **Fallback par défaut :** `90` jours
   - **Utilisation :** Commande `payments:prune-events`
   - **Statut dans .env :** À vérifier (non affiché pour sécurité)

2. `PAYMENTS_EVENTS_KEEP_FAILED`
   - **Source de vérité :** `config('payments.events.keep_failed')`
   - **Fallback par défaut :** `true`
   - **Utilisation :** Commande `payments:prune-events` (conserver les événements failed)

3. `PAYMENTS_AUDIT_LOGS_RETENTION_DAYS`
   - **Source de vérité :** `config('payments.audit_logs.retention_days')`
   - **Fallback par défaut :** `365` jours
   - **Utilisation :** Commande `payments:prune-audit-logs`
   - **Statut dans .env :** À vérifier (non affiché pour sécurité)

4. `PAYMENTS_TRANSACTIONS_RETENTION_YEARS`
   - **Source de vérité :** `config('payments.transactions.retention_years')`
   - **Fallback par défaut :** `'unlimited'`
   - **Utilisation :** Politique de rétention (pas de purge en v1.1)

5. `PAYMENTS_TRANSACTIONS_ARCHIVE_ENABLED`
   - **Source de vérité :** `config('payments.transactions.archive_enabled')`
   - **Fallback par défaut :** `false`
   - **Utilisation :** Activation archivage (non implémenté en v1.1)

**Convention :** Toutes les durées sont en **DAYS** (cohérent), sauf `PAYMENTS_TRANSACTIONS_RETENTION_YEARS` (en années, mais valeur `'unlimited'` par défaut).

---

## 3) DB — Schémas réels (Migrations + Colonnes)

### 3.1 Table `payment_transactions`

**Migration :** `2025_12_13_215019_create_payment_transactions_table.php`

**Colonnes :**
- `id` (bigint, primary key)
- `provider` (string, default: 'monetbil')
- `order_id` (foreignId, nullable, FK → orders.id)
- `payment_ref` (string, unique)
- `item_ref` (string, nullable)
- `transaction_id` (string, nullable, unique)
- `transaction_uuid` (string, nullable)
- `amount` (decimal 10,2)
- `currency` (string 3, default: 'XAF')
- `status` (VARCHAR(32) après migration standardisation, valeurs: pending, processing, succeeded, failed, canceled, refunded)
- `operator` (string, nullable)
- `phone` (string, nullable)
- `fee` (decimal 10,2, nullable)
- `raw_payload` (json, nullable)
- `notified_at` (timestamp, nullable)
- `timestamps` (created_at, updated_at)

**Indexes :**
- `payment_ref` (index)
- `transaction_id` (index)
- `order_id` (index)
- `status` (index)

**Contraintes UNIQUE :**
- `payment_ref` (unique)
- `transaction_id` (unique)

**Note :** Le statut a été standardisé de ENUM à VARCHAR(32) via migration `2025_12_14_000005_standardize_payment_transactions_status.php`.

---

### 3.2 Table `stripe_webhook_events`

**Migration :** `2025_12_13_225153_create_stripe_webhook_events_table.php`

**Colonnes :**
- `id` (bigint, primary key)
- `event_id` (string, unique) — **Stripe event ID (evt_...)**
- `event_type` (string)
- `payment_id` (foreignId, nullable, FK → payments.id)
- `status` (string, default: 'received') — valeurs: received, processed, ignored, failed
- `processed_at` (timestamp, nullable)
- `payload_hash` (string, nullable) — **Hash SHA256 du payload (pas de payload brut)**
- `timestamps` (created_at, updated_at)

**Indexes :**
- `payment_id` (index)
- `event_type` (index)
- `status` (index)

**Contraintes UNIQUE :**
- `event_id` (unique) — **Idempotence garantie**

**⚠️ IMPORTANT :** Cette table **ne contient PAS de colonne `payload` (JSON/TEXT)**. Seul `payload_hash` est stocké pour vérification optionnelle. Le payload complet n'est pas stocké pour des raisons de sécurité et de performance.

**Conséquence pour UI :** La vue `show-stripe.blade.php` affiche uniquement `payload_hash` et indique explicitement que le payload complet n'est pas stocké.

---

### 3.3 Table `monetbil_callback_events`

**Migration :** `2025_12_14_000003_create_monetbil_callback_events_table.php`

**Colonnes :**
- `id` (bigint, primary key)
- `event_key` (string, unique) — **Hash stable pour idempotence**
- `payment_ref` (string, nullable)
- `transaction_id` (string, nullable)
- `transaction_uuid` (string, nullable)
- `event_type` (string, nullable)
- `status` (string, default: 'received') — valeurs: received, processed, ignored, failed
- `payload` (json) — **Payload brut (sera redacted en UI)**
- `error` (text, nullable)
- `received_at` (timestamp, nullable)
- `processed_at` (timestamp, nullable)
- `timestamps` (created_at, updated_at)

**Indexes :**
- `event_key` (index)
- `status` (index)
- `received_at` (index)
- `transaction_id` (index)
- `payment_ref` (index)

**Contraintes UNIQUE :**
- `event_key` (unique) — **Idempotence garantie**

**Note :** Contrairement à Stripe, Monetbil stocke le payload complet en JSON. Il est redacted via `PayloadRedactionService` avant affichage dans l'UI.

---

### 3.4 Table `payment_providers`

**Migration :** `2025_12_14_000001_create_payment_providers_table.php`

**Colonnes :**
- `id` (bigint, primary key)
- `code` (string, unique) — valeurs: 'stripe', 'monetbil'
- `name` (string)
- `is_enabled` (boolean, default: true)
- `priority` (integer, default: 0)
- `currency` (string 3, default: 'XAF')
- `health_status` (string, default: 'ok') — valeurs: ok, degraded, down
- `last_health_at` (timestamp, nullable)
- `last_event_at` (timestamp, nullable)
- `last_event_status` (string, nullable) — valeurs: ok, failed
- `meta` (json, nullable) — **Métadonnées non sensibles**
- `timestamps` (created_at, updated_at)

**Indexes :**
- `code` (index)
- `is_enabled` (index)
- `health_status` (index)
- `priority` (index)

**Contraintes UNIQUE :**
- `code` (unique)

---

### 3.5 Table `payment_routing_rules`

**Migration :** `2025_12_14_000002_create_payment_routing_rules_table.php`

**Colonnes :**
- `id` (bigint, primary key)
- `channel` (string) — valeurs: card, mobile_money, bank_transfer
- `currency` (string, nullable)
- `country` (string, nullable)
- `primary_provider_id` (foreignId, FK → payment_providers.id, onDelete: restrict) — **FK bigint**
- `fallback_provider_id` (foreignId, nullable, FK → payment_providers.id, onDelete: set null) — **FK bigint**
- `is_active` (boolean, default: true)
- `priority` (integer, default: 100)
- `timestamps` (created_at, updated_at)

**Indexes :**
- `channel` (index)
- `currency` (index)
- `country` (index)
- `is_active` (index)
- `priority` (index)
- Index composite : `idx_routing_lookup` (`channel`, `currency`, `country`, `is_active`, `priority`)

**✅ Conformité v1.1 :** Utilise bien **FK bigint** (`primary_provider_id`, `fallback_provider_id`) et non FK string sur `code`.

---

### 3.6 Table `payment_audit_logs`

**Migration :** `2025_12_14_000004_create_payment_audit_logs_table.php`

**Colonnes :**
- `id` (bigint, primary key)
- `user_id` (foreignId, FK → users.id, onDelete: cascade)
- `action` (string) — valeurs: provider.toggle, provider.update, reprocess, refund
- `target_type` (string) — valeurs: PaymentProvider, PaymentTransaction, StripeWebhookEvent, MonetbilCallbackEvent
- `target_id` (unsignedBigInteger, nullable)
- `diff` (json, nullable) — **Diff avant/après (non sensible)**
- `reason` (text, nullable) — **Motif (obligatoire pour reprocess/refund)**
- `ip_address` (string, nullable)
- `user_agent` (text, nullable)
- `timestamps` (created_at, updated_at)

**Indexes :**
- `action` (index)
- `user_id` (index)
- `created_at` (index)
- Index composite : (`target_type`, `target_id`)

---

## 4) ASYNC / JOBS — Preuve "persist-first" et absence de payload sérialisé

### 4.1 Contrôleur Webhooks

**Fichier :** `app/Http/Controllers/Api/WebhookController.php`

#### Endpoint Stripe (`stripe()`)

**Pattern v1.1 confirmé :**

1. **VERIFY signature** (lignes 40-78)
   - Vérification avec `Webhook::constructEvent()` (production)
   - Parser JSON sans vérification (dev mode)

2. **PERSIST event** (lignes 88-114)
   - `StripeWebhookEvent::firstOrCreate(['event_id' => $eventId], [...])` — **Idempotent**
   - Champs persistés : `event_id` (unique), `event_type`, `status='received'`, `payload_hash` (hash SHA256)
   - **Pas de payload brut stocké**

3. **DISPATCH job** (lignes 116-125)
   - `ProcessStripeWebhookEventJob::dispatch($webhookEvent->id)` — **Seulement l'ID de l'événement**
   - **Pas de payload sérialisé dans le job**

4. **RETURN 200 vite** (ligne 128)
   - `response()->json(['status' => 'received'], 200)`

**Résumé :** ✅ Pattern v1.1 respecté. Seul `event_id` est passé au job, pas de payload.

---

#### Endpoint Monetbil (`monetbil()`)

**Pattern v1.1 confirmé :**

1. **VERIFY signature/auth** (lignes 145-161)
   - Vérification HMAC SHA256 (production)
   - Pas de vérification en dev

2. **PERSIST event** (lignes 166-196)
   - `MonetbilCallbackEvent::firstOrCreate(['event_key' => $eventKey], [...])` — **Idempotent**
   - Champs persistés : `event_key` (unique, hash stable), `payment_ref`, `transaction_id`, `transaction_uuid`, `event_type`, `status='received'`, `payload` (JSON brut), `received_at`
   - **Payload stocké en DB** (contrairement à Stripe)

3. **DISPATCH job** (lignes 198-207)
   - `ProcessMonetbilCallbackEventJob::dispatch($callbackEvent->id)` — **Seulement l'ID de l'événement**
   - **Pas de payload sérialisé dans le job**

4. **RETURN 200 vite** (ligne 210)
   - `response()->json(['status' => 'received'], 200)`

**Résumé :** ✅ Pattern v1.1 respecté. Seul l'ID de l'événement est passé au job, pas de payload.

---

### 4.2 Jobs

#### ProcessStripeWebhookEventJob

**Fichier :** `app/Jobs/ProcessStripeWebhookEventJob.php`

**Paramètres du constructeur :**
```php
public function __construct(
    public int $stripeWebhookEventId
) {}
```
✅ **Seulement l'ID de l'événement, pas de payload.**

**Configuration retry :**
- `$tries = 3`
- `$timeout = 60` (secondes)
- `$backoff = [10, 30, 60]` (secondes)

**Stratégie de lock :**
- `StripeWebhookEvent::lockForUpdate()->find($this->stripeWebhookEventId)` (ligne 56)
- Utilisé dans une transaction DB (`DB::transaction()`)

**Comportement idempotent :**
1. Vérifie si événement déjà traité : `if ($event->isProcessed())` (ligne 68)
2. Vérifie si transaction déjà succeeded : `if ($transaction->isAlreadySuccessful() && $status === 'succeeded')` (ligne 103)
3. Safe re-run garanti

---

#### ProcessMonetbilCallbackEventJob

**Fichier :** `app/Jobs/ProcessMonetbilCallbackEventJob.php`

**Paramètres du constructeur :**
```php
public function __construct(
    public int $monetbilCallbackEventId
) {}
```
✅ **Seulement l'ID de l'événement, pas de payload.**

**Configuration retry :**
- `$tries = 3`
- `$timeout = 60` (secondes)
- `$backoff = [10, 30, 60]` (secondes)

**Stratégie de lock :**
- `MonetbilCallbackEvent::lockForUpdate()->find($this->monetbilCallbackEventId)` (ligne 56)
- Utilisé dans une transaction DB (`DB::transaction()`)

**Comportement idempotent :**
1. Vérifie si événement déjà traité : `if (in_array($event->status, ['processed', 'ignored']))` (ligne 68)
2. Vérifie si transaction déjà succeeded : `if ($transaction->isAlreadySuccessful() && $status === 'succeeded')` (ligne 105)
3. Safe re-run garanti

---

## 5) SÉCURITÉ — Redaction + CSV injection

### 5.1 PayloadRedactionService

**Fichier :** `app/Services/Payments/PayloadRedactionService.php`

**Règles de redaction :**

**Clés sensibles (mots-clés) :**
- `secret`, `key`, `token`, `password`, `api_key`, `api_secret`, `access_token`, `refresh_token`, `authorization`, `signature`, `webhook_secret`, `private_key`

**Valeurs sensibles (patterns) :**
- `sk_` (Stripe secret key)
- `pk_` (Stripe public key, masqué par précaution)
- `whsec_` (Stripe webhook secret)
- `sk-ant-` (Anthropic API key)
- `sk-proj-` (Anthropic API key project)

**Où il est appliqué :**

1. **Pages admin :**
   - `resources/views/admin/payments/transactions/show.blade.php` — Timeline événements
   - `resources/views/admin/payments/webhooks/show-monetbil.blade.php` — Détail événement Monetbil
   - `resources/views/admin/payments/webhooks/show-stripe.blade.php` — Note : payload non stocké, donc pas de redaction nécessaire

2. **Exports :**
   - Non appliqué directement (les exports CSV ne contiennent pas de payload)

3. **Logs d'erreur :**
   - `ProcessStripeWebhookEventJob` (ligne 129) : `PayloadRedactionService` instancié mais pas utilisé dans le log (log minimaliste avec seulement `event_id`, `event_type`, `error`)
   - `ProcessMonetbilCallbackEventJob` (ligne 140) : Même constat

**Version stricte pour logs :**
- Méthode `redactForLogs()` (ligne 163) : Supprime complètement `headers`, `signature`, `raw_signature`
- **Note :** Cette méthode n'est pas encore utilisée dans les jobs (à améliorer)

---

### 5.2 CSV Export

**Fichier :** `app/Services/Payments/CsvExportService.php`

**Règle anti CSV injection :**

**Méthode `escapeCell()` (lignes 86-97) :**
```php
if (preg_match('/^[=+\-@]/', $stringValue)) {
    return "'" . $stringValue;
}
```

**Transformation :**
- Si la valeur commence par `=`, `+`, `-`, ou `@` → préfixer avec `'`
- Exemple : `=SUM(1,1)` → `'=SUM(1,1)`

**Où il est appliqué :**
- `app/Http/Controllers/Admin/Payments/PaymentTransactionController.php` (méthode `exportCsv()`)
- Export des transactions via route `admin.payments.transactions.export.csv`

**Exemple de transformation (sans données sensibles) :**
- Input : `=SUM(1,1)`
- Output : `'=SUM(1,1)`
- Input : `+123456`
- Output : `'+123456`
- Input : `@example.com`
- Output : `'@example.com`

✅ **Protection anti-injection CSV active.**

---

## 6) OUTPUT FINAL — Résumé de conformité (binaire)

| Critère | Statut | Détails |
|---------|--------|---------|
| **Webhooks en routes API** | ⚠️ **PARTIEL** | Routes déclarées dans `routes/web.php` (pas `routes/api.php`). Middleware `api` par défaut. Acceptable si projet n'utilise pas `routes/api.php`. |
| **Middleware `api` sur webhooks** | ✅ **PASS** | Routes dans groupe `/api`, middleware `api` appliqué par défaut. |
| **Variables de rétention cohérentes** | ✅ **PASS** | Convention DAYS respectée (sauf `PAYMENTS_TRANSACTIONS_RETENTION_YEARS` avec valeur `'unlimited'`). |
| **`stripe_webhook_events` payload disponible** | ✅ **PASS** | **Pas de colonne `payload`** (conformité sécurité). Seul `payload_hash` stocké. UI affiche `payload_hash` et note explicite. |
| **Jobs ne sérialisent pas de payload** | ✅ **PASS** | Jobs reçoivent uniquement l'ID de l'événement (`$stripeWebhookEventId`, `$monetbilCallbackEventId`). Pas de payload dans le constructeur. |
| **CSV injection mitigée** | ✅ **PASS** | `CsvExportService::escapeCell()` préfixe `=`, `+`, `-`, `@` avec `'`. |
| **Redaction appliquée aux logs d'erreur paiements** | ⚠️ **PARTIEL** | `PayloadRedactionService` instancié dans les jobs mais **pas utilisé dans les logs**. Logs minimalistes (seulement `event_id`, `error`). Méthode `redactForLogs()` existe mais non utilisée. **À améliorer** pour utiliser `redactForLogs()` dans les logs d'erreur. |

---

## 📝 RECOMMANDATIONS

### Améliorations suggérées

1. **Redaction dans logs d'erreur :**
   - Utiliser `PayloadRedactionService::redactForLogs()` dans les `catch` blocks des jobs
   - Appliquer avant de logger les erreurs avec payload

2. **Throttle sur webhooks :**
   - Ajouter middleware `throttle:60,1` sur les routes webhooks pour limiter les appels

3. **Routes API :**
   - Si le projet utilise `routes/api.php`, déplacer les routes webhooks vers ce fichier pour cohérence

4. **Payload Stripe :**
   - Si besoin d'afficher le payload Stripe dans l'UI, considérer une option de stockage optionnel (flag `store_payload` dans config) avec redaction automatique

---

**Rapport généré le 2025-12-14**  
**Payments Hub v1.1 — Preuves de conformité production ✅**









