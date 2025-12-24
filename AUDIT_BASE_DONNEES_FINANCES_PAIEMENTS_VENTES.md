# AUDIT BASE DE DONNÉES — FINANCES, PAIEMENTS & VENTES
**Projet :** racine-backend (Laravel 12)  
**Date :** 2025-01-XX  
**Phase :** PASS 1/3 — Analyse structure de données uniquement

---

## 1. MIGRATIONS IDENTIFIÉES

### 1.1. Commandes (Orders)

#### Migration principale
- **`2025_11_23_000004_create_orders_table.php`**

#### Migrations de modification
- `2025_11_23_000007_add_payment_status_to_orders_table.php`
- `2025_11_23_135947_add_qr_token_to_orders_table.php`
- `2025_11_29_175037_add_address_id_to_orders_table.php`
- `2025_01_27_000009_add_promo_code_to_orders_table.php`
- `2025_01_27_000010_add_payment_method_to_orders_table.php`
- `2025_12_08_000001_add_order_number_to_orders_table.php`

---

### 1.2. Items de commande (Order Items)

#### Migration principale
- **`2025_11_23_000005_create_order_items_table.php`**

#### Migrations de modification
- `2025_12_06_130001_add_vendor_to_order_items_table.php`

---

### 1.3. Transactions de paiement

#### Migrations principales
- **`2025_11_23_000006_create_payments_table.php`**
- **`2025_12_13_215019_create_payment_transactions_table.php`**

#### Migrations de modification
- `2025_11_23_142839_add_card_payment_fields_to_payments_table.php`
- `2025_12_14_000104_update_payments_currency_default_to_xaf.php`
- `2025_12_14_000005_standardize_payment_transactions_status.php`

#### Migrations système de paiement
- `2025_12_14_000001_create_payment_providers_table.php`
- `2025_12_14_000002_create_payment_routing_rules_table.php`
- `2025_12_14_000004_create_payment_audit_logs_table.php`

---

### 1.4. Webhooks & Callbacks

#### Migrations principales
- **`2025_12_13_225153_create_stripe_webhook_events_table.php`**
- **`2025_12_14_000003_create_monetbil_callback_events_table.php`**

#### Migrations de modification
- `2025_12_15_015923_add_dispatched_at_to_stripe_webhook_events_table.php`
- `2025_12_15_015924_add_dispatched_at_to_monetbil_callback_events_table.php`
- `2025_12_15_160000_add_requeue_tracking_to_webhook_events.php`
- `2025_12_15_170000_add_blocked_status_to_webhook_events.php`

---

### 1.5. Produits & Stock

#### Migration principale
- **`2025_11_23_000001_create_products_table.php`**

#### Migrations de modification
- `2025_11_24_000003_add_collection_id_and_user_id_to_products_table.php`
- `2025_12_06_120000_add_product_type_to_products_table.php`

#### Stock
- `2025_11_28_032922_create_stock_alerts_table.php`

---

### 1.6. Utilisateurs & Rôles

#### Migration principale
- **`0001_01_01_000000_create_users_table.php`**

#### Migrations de modification
- `2024_01_01_000003_add_admin_fields_to_users_table.php`
- `2024_01_01_000005_add_foreign_key_to_users_role_id.php`
- `2025_11_26_122515_add_role_and_staff_role_to_users_table.php`
- `2025_11_27_000001_add_two_factor_columns_to_users_table.php`
- `2025_11_28_034646_add_locale_to_users_table.php`
- `2025_12_08_165705_add_professional_email_to_users_table.php`

#### Rôles
- **`2024_01_01_000004_create_roles_table.php`**

---

### 1.7. Profils créateurs

#### Migration principale
- **`2024_11_24_000001_create_creator_profiles_table.php`**

#### Migrations de modification
- `2025_11_29_220150_add_creator_profile_fields_to_creator_profiles_table.php`
- `2025_01_27_000006_add_scoring_fields_to_creator_profiles_table.php`

---

### 1.8. Codes promo

#### Migrations principales
- **`2025_01_27_000007_create_promo_codes_table.php`**
- **`2025_01_27_000008_create_promo_code_usages_table.php`**

---

### 1.9. Marketplace (Vendeurs)

#### Migration principale
- **`2025_12_06_130000_create_order_vendors_table.php`**

---

### 1.10. Indexes de performance

- `2025_12_08_000001_add_indexes_for_performance.php`
- `2025_12_10_105138_add_missing_indexes_for_orders_and_payments.php`

---

## 2. STRUCTURE DES TABLES

### 2.1. Table `orders`

#### Colonnes principales
- `id` (bigint, PK)
- `order_number` (string, unique, nullable) — Numéro de commande unique
- `qr_token` (string, unique, nullable) — Token QR pour suivi
- `user_id` (bigint, FK → users.id, nullable, nullOnDelete)
- `address_id` (bigint, FK → addresses.id, nullable, nullOnDelete)
- `promo_code_id` (bigint, FK → promo_codes.id, nullable, setNullOnDelete)
- `status` (string, default: 'pending') — pending, paid, shipped, completed, cancelled
- `payment_status` (string, default: 'pending') — pending, paid, failed, refunded
- `payment_method` (string, nullable) — Méthode de paiement
- `total_amount` (decimal 10,2)
- `discount_amount` (decimal 10,2, default: 0)
- `shipping_method` (string, nullable)
- `shipping_cost` (decimal 10,2, default: 0)
- `customer_name` (string)
- `customer_email` (string)
- `customer_phone` (string, nullable)
- `customer_address` (string)
- `timestamps` (created_at, updated_at)
- `deleted_at` (soft deletes)

#### Foreign Keys
- `user_id` → `users.id` (nullOnDelete)
- `address_id` → `addresses.id` (nullOnDelete)
- `promo_code_id` → `promo_codes.id` (setNullOnDelete)

#### Indexes
- `order_number` (unique)
- `qr_token` (unique)
- `user_id`
- `payment_method` (ajouté par migration 2025_12_10_105138)

#### Champs uniques
- `order_number`
- `qr_token`

---

### 2.2. Table `order_items`

#### Colonnes principales
- `id` (bigint, PK)
- `order_id` (bigint, FK → orders.id, cascadeOnDelete)
- `product_id` (bigint, FK → products.id, cascadeOnDelete)
- `vendor_id` (bigint, FK → users.id, nullable, nullOnDelete) — Vendeur (brand/créateur)
- `vendor_type` (enum: 'brand', 'creator', nullable) — Type de vendeur
- `quantity` (integer)
- `price` (decimal 10,2) — Prix au moment de la commande
- `timestamps`

#### Foreign Keys
- `order_id` → `orders.id` (cascadeOnDelete)
- `product_id` → `products.id` (cascadeOnDelete)
- `vendor_id` → `users.id` (nullOnDelete)

#### Indexes
- `vendor_id`
- `order_id, vendor_id` (composite)

---

### 2.3. Table `payments`

#### Colonnes principales
- `id` (bigint, PK)
- `order_id` (bigint, FK → orders.id, cascadeOnDelete)
- `provider` (string, default: 'stripe') — stripe, monetbil, etc.
- `provider_payment_id` (string, nullable) — ID paiement fournisseur
- `channel` (string, default: 'card') — card, mobile_money, bank_transfer
- `status` (string, default: 'pending')
- `amount` (decimal 10,2)
- `currency` (string, default: 'XAF')
- `customer_phone` (string, nullable)
- `external_reference` (string, nullable)
- `metadata` (json, nullable)
- `payload` (json, nullable)
- `paid_at` (timestamp, nullable)
- `timestamps`

#### Foreign Keys
- `order_id` → `orders.id` (cascadeOnDelete)

#### Indexes
- `order_id`
- `provider` (ajouté par migration 2025_12_10_105138)
- `channel` (ajouté par migration 2025_12_10_105138)

---

### 2.4. Table `payment_transactions`

#### Colonnes principales
- `id` (bigint, PK)
- `provider` (string, default: 'monetbil') — monetbil, stripe, etc.
- `order_id` (bigint, FK → orders.id, nullable, setNullOnDelete)
- `payment_ref` (string, unique) — Référence unique de la commande
- `item_ref` (string, nullable) — Référence optionnelle de l'item
- `transaction_id` (string, unique, nullable) — Transaction ID Monetbil
- `transaction_uuid` (string, nullable) — Transaction UUID Monetbil
- `amount` (decimal 10,2)
- `currency` (string, default: 'XAF')
- `status` (enum: 'pending', 'succeeded', 'failed', 'cancelled', default: 'pending')
- `operator` (string, nullable) — Opérateur Mobile Money (MTN, Orange, etc.)
- `phone` (string, nullable) — Numéro de téléphone
- `fee` (decimal 10,2, nullable) — Frais de transaction
- `raw_payload` (json, nullable) — Payload brut de la notification
- `notified_at` (timestamp, nullable)
- `timestamps`

#### Foreign Keys
- `order_id` → `orders.id` (setNullOnDelete)

#### Indexes
- `payment_ref` (unique)
- `transaction_id` (unique)
- `order_id`
- `status`

#### Champs uniques
- `payment_ref`
- `transaction_id`

---

### 2.5. Table `stripe_webhook_events`

#### Colonnes principales
- `id` (bigint, PK)
- `event_id` (string, unique) — Stripe event ID (evt_...)
- `event_type` (string) — checkout.session.completed, payment_intent.succeeded, etc.
- `payment_id` (bigint, FK → payments.id, nullable, setNullOnDelete)
- `status` (string, default: 'received') — received, processed, ignored, failed, blocked
- `processed_at` (timestamp, nullable)
- `dispatched_at` (timestamp, nullable) — Pour exactly-once dispatch
- `payload_hash` (string, nullable) — Hash du payload pour vérification
- `requeue_count` (unsigned integer, default: 0) — Nombre de tentatives de requeue
- `last_requeue_at` (timestamp, nullable)
- `timestamps`

#### Foreign Keys
- `payment_id` → `payments.id` (setNullOnDelete)

#### Indexes
- `event_id` (unique)
- `payment_id`
- `event_type`
- `status`
- `dispatched_at`
- `requeue_count`
- `last_requeue_at`

#### Champs uniques
- `event_id`

---

### 2.6. Table `monetbil_callback_events`

#### Colonnes principales
- `id` (bigint, PK)
- `event_key` (string, unique) — Hash stable pour idempotence
- `payment_ref` (string, nullable) — Référence paiement
- `transaction_id` (string, nullable) — Transaction ID Monetbil
- `transaction_uuid` (string, nullable) — Transaction UUID Monetbil
- `event_type` (string, nullable) — Type d'événement
- `status` (string, default: 'received') — received, processed, ignored, failed, blocked
- `payload` (json) — Payload brut (sera redacted en UI)
- `error` (text, nullable) — Message d'erreur si échec
- `received_at` (timestamp, nullable)
- `processed_at` (timestamp, nullable)
- `dispatched_at` (timestamp, nullable) — Pour exactly-once dispatch
- `requeue_count` (unsigned integer, default: 0)
- `last_requeue_at` (timestamp, nullable)
- `timestamps`

#### Foreign Keys
- Aucune FK directe (relation via `payment_ref` vers `payment_transactions.payment_ref`)

#### Indexes
- `event_key` (unique)
- `status`
- `received_at`
- `transaction_id`
- `payment_ref`
- `dispatched_at`
- `requeue_count`
- `last_requeue_at`

#### Champs uniques
- `event_key`

---

### 2.7. Table `products`

#### Colonnes principales
- `id` (bigint, PK)
- `category_id` (bigint, FK → categories.id, cascadeOnDelete)
- `collection_id` (bigint, FK → collections.id, nullable, nullOnDelete)
- `user_id` (bigint, FK → users.id, nullable, nullOnDelete) — Créateur du produit
- `product_type` (enum: 'brand', 'marketplace', default: 'brand') — Type de produit
- `title` (string)
- `slug` (string, unique)
- `description` (longText, nullable)
- `price` (decimal 10,2)
- `stock` (integer, default: 0)
- `is_active` (boolean, default: true)
- `main_image` (string, nullable)
- `timestamps`
- `deleted_at` (soft deletes)

#### Foreign Keys
- `category_id` → `categories.id` (cascadeOnDelete)
- `collection_id` → `collections.id` (nullOnDelete)
- `user_id` → `users.id` (nullOnDelete)

#### Indexes
- `slug` (unique)
- `collection_id`
- `user_id`
- `product_type`
- `product_type, is_active` (composite)

#### Champs uniques
- `slug`

---

### 2.8. Table `users`

#### Colonnes principales (finance/paiement/vente)
- `id` (bigint, PK)
- `name` (string)
- `email` (string, unique)
- `email_verified_at` (timestamp, nullable)
- `professional_email` (string, nullable)
- `professional_email_verified` (boolean, default: false)
- `professional_email_verified_at` (timestamp, nullable)
- `password` (string)
- `role_id` (bigint, FK → roles.id, nullable)
- `role` (string, nullable) — Legacy
- `staff_role` (string, nullable) — Legacy
- `phone` (string, nullable)
- `status` (string, nullable)
- `is_admin` (boolean, default: false)
- `locale` (string, nullable)
- `timestamps`
- `deleted_at` (soft deletes)

#### Foreign Keys
- `role_id` → `roles.id` (nullable)

#### Indexes
- `email` (unique)
- `role_id`

#### Champs uniques
- `email`

---

### 2.9. Table `roles`

#### Colonnes principales
- `id` (bigint, PK)
- `name` (string, unique)
- `slug` (string, unique)
- `description` (text, nullable)
- `is_active` (boolean, default: true)
- `timestamps`

#### Indexes
- `name` (unique)
- `slug` (unique)

#### Champs uniques
- `name`
- `slug`

---

### 2.10. Table `creator_profiles`

#### Colonnes principales
- `id` (bigint, PK)
- `user_id` (bigint, FK → users.id, cascadeOnDelete)
- `brand_name` (string)
- `slug` (string, unique)
- `bio` (text, nullable)
- `logo_path` (string, nullable)
- `banner_path` (string, nullable)
- `location` (string, nullable)
- `website` (string, nullable)
- `instagram_url` (string, nullable)
- `tiktok_url` (string, nullable)
- `type` (string, nullable) — prêt-à-porter, sur mesure, accessoires...
- `legal_status` (string, nullable) — particulier, auto-entrepreneur, SARL...
- `registration_number` (string, nullable) — RCCM / NIU / autre
- `payout_method` (enum: 'bank', 'mobile_money', 'other', nullable)
- `payout_details` (text, nullable) — JSON ou texte
- `status` (enum: 'pending', 'active', 'suspended', default: 'pending')
- `is_verified` (boolean, default: false)
- `is_active` (boolean, default: true)
- `quality_score` (decimal 5,2, nullable)
- `completeness_score` (decimal 5,2, nullable)
- `performance_score` (decimal 5,2, nullable)
- `overall_score` (decimal 5,2, nullable)
- `last_score_calculated_at` (timestamp, nullable)
- `timestamps`

#### Foreign Keys
- `user_id` → `users.id` (cascadeOnDelete)

#### Indexes
- `slug` (unique)
- `status`
- `is_active`
- `is_verified`

#### Champs uniques
- `slug`

---

### 2.11. Table `order_vendors`

#### Colonnes principales
- `id` (bigint, PK)
- `order_id` (bigint, FK → orders.id, cascadeOnDelete)
- `vendor_id` (bigint, FK → users.id, nullable, nullOnDelete)
- `vendor_type` (enum: 'brand', 'creator', default: 'creator')
- `subtotal` (decimal 10,2, default: 0)
- `commission_rate` (decimal 5,2, default: 15.00) — Taux de commission en %
- `commission_amount` (decimal 10,2, default: 0) — Montant de la commission
- `vendor_payout` (decimal 10,2, default: 0) — Montant à verser au vendeur
- `status` (enum: 'pending', 'processing', 'shipped', 'delivered', 'cancelled', default: 'pending')
- `payout_status` (enum: 'pending', 'processing', 'paid', 'failed', default: 'pending')
- `shipped_at` (timestamp, nullable)
- `delivered_at` (timestamp, nullable)
- `payout_at` (timestamp, nullable)
- `timestamps`

#### Foreign Keys
- `order_id` → `orders.id` (cascadeOnDelete)
- `vendor_id` → `users.id` (nullOnDelete)

#### Indexes
- `order_id, vendor_id` (composite)
- `status`
- `payout_status`
- `vendor_id, status` (composite)

---

### 2.12. Table `promo_codes`

#### Colonnes principales
- `id` (bigint, PK)
- `code` (string, unique)
- `name` (string)
- `description` (text, nullable)
- `type` (string) — percentage, fixed, free_shipping
- `value` (decimal 10,2)
- `min_amount` (decimal 10,2, nullable)
- `max_uses` (integer, nullable)
- `used_count` (integer, default: 0)
- `max_uses_per_user` (integer, nullable)
- `starts_at` (timestamp, nullable)
- `expires_at` (timestamp, nullable)
- `is_active` (boolean, default: true)
- `timestamps`

#### Indexes
- `code` (unique)

#### Champs uniques
- `code`

---

### 2.13. Table `promo_code_usages`

#### Colonnes principales
- `id` (bigint, PK)
- `promo_code_id` (bigint, FK → promo_codes.id, cascadeOnDelete)
- `user_id` (bigint, nullable) — Pas de FK directe (évite dépendance migrations)
- `order_id` (bigint, nullable) — Pas de FK directe
- `email` (string, nullable) — Pour utilisateurs non connectés
- `discount_amount` (decimal 10,2)
- `timestamps`

#### Foreign Keys
- `promo_code_id` → `promo_codes.id` (cascadeOnDelete)

#### Indexes
- `promo_code_id, user_id` (composite)
- `promo_code_id, email` (composite)

---

### 2.14. Table `payment_providers`

#### Colonnes principales
- `id` (bigint, PK)
- `code` (string, unique) — stripe, monetbil
- `name` (string) — Stripe, Monetbil
- `is_enabled` (boolean, default: true)
- `priority` (integer, default: 0) — Ordre de priorité
- `currency` (string, default: 'XAF')
- `health_status` (string, default: 'ok') — ok, degraded, down
- `last_health_at` (timestamp, nullable)
- `last_event_at` (timestamp, nullable)
- `last_event_status` (string, nullable) — ok, failed
- `meta` (json, nullable) — Métadonnées non sensibles
- `timestamps`

#### Indexes
- `code` (unique)
- `is_enabled`
- `health_status`
- `priority`

#### Champs uniques
- `code`

---

### 2.15. Table `payment_routing_rules`

#### Colonnes principales
- `id` (bigint, PK)
- `channel` (string) — card, mobile_money, bank_transfer
- `currency` (string, nullable)
- `country` (string, nullable)
- `primary_provider_id` (bigint, FK → payment_providers.id, restrictOnDelete)
- `fallback_provider_id` (bigint, FK → payment_providers.id, nullable, setNullOnDelete)
- `is_active` (boolean, default: true)
- `priority` (integer, default: 100) — Ordre d'évaluation
- `timestamps`

#### Foreign Keys
- `primary_provider_id` → `payment_providers.id` (restrictOnDelete)
- `fallback_provider_id` → `payment_providers.id` (setNullOnDelete)

#### Indexes
- `channel`
- `currency`
- `country`
- `is_active`
- `priority`
- `channel, currency, country, is_active, priority` (composite, nom: idx_routing_lookup)

---

### 2.16. Table `payment_audit_logs`

#### Colonnes principales
- `id` (bigint, PK)
- `user_id` (bigint, FK → users.id, cascadeOnDelete)
- `action` (string) — provider.toggle, provider.update, reprocess, refund
- `target_type` (string) — PaymentProvider, PaymentTransaction, StripeWebhookEvent, MonetbilCallbackEvent
- `target_id` (bigint, nullable) — ID de la cible
- `diff` (json, nullable) — Diff avant/après (non sensible)
- `reason` (text, nullable) — Motif (obligatoire pour reprocess/refund)
- `ip_address` (string, nullable)
- `user_agent` (text, nullable)
- `timestamps`

#### Foreign Keys
- `user_id` → `users.id` (cascadeOnDelete)

#### Indexes
- `action`
- `user_id`
- `created_at`
- `target_type, target_id` (composite)

---

## 3. RELATIONS ELOQUENT

### 3.1. Modèle `Order`

```php
// Relations
belongsTo(User::class) → user()
hasMany(OrderItem::class) → items()
hasMany(Payment::class) → payments()
belongsTo(Address::class) → address()
belongsTo(PromoCode::class) → promoCode()
```

---

### 3.2. Modèle `OrderItem`

```php
// Relations
belongsTo(Order::class) → order()
belongsTo(Product::class) → product()
// NOTE: Pas de relation vendor() définie dans le modèle
// mais vendor_id et vendor_type existent en base
```

---

### 3.3. Modèle `PaymentTransaction`

```php
// Relations
belongsTo(Order::class) → order()
```

---

### 3.4. Modèle `Payment`

```php
// Relations
belongsTo(Order::class) → order()
```

---

### 3.5. Modèle `Product`

```php
// Relations
belongsTo(Category::class) → category()
belongsTo(Collection::class) → collection()
belongsTo(User::class, 'user_id') → creator()
hasMany(StockAlert::class) → stockAlerts()
hasMany(Review::class) → reviews()
hasMany(Wishlist::class) → wishlists()
hasOne(ErpProductDetail::class, 'product_id') → erpDetails()
```

---

### 3.6. Modèle `User`

```php
// Relations (finance/paiement/vente)
belongsTo(Role::class, 'role_id') → roleRelation() / role()
hasOne(CreatorProfile::class) → creatorProfile()
hasMany(Order::class) → orders()
hasMany(Address::class) → addresses()
hasOne(Address::class)->where('is_default', true) → defaultAddress()
hasOne(LoyaltyPoint::class) → loyaltyPoints()
hasMany(LoyaltyTransaction::class) → loyaltyTransactions()
hasMany(Wishlist::class) → wishlist()
belongsToMany(Product::class, 'wishlists') → wishlistProducts()
```

---

### 3.7. Modèle `CreatorProfile`

```php
// Relations
belongsTo(User::class) → user()
hasMany(Product::class, 'user_id', 'user_id') → products()
hasMany(Collection::class, 'user_id', 'user_id') → collections()
hasMany(CreatorDocument::class) → documents()
hasMany(CreatorValidationChecklist::class) → validationChecklist()
hasMany(CreatorActivityLog::class) → activityLogs()
hasMany(CreatorAdminNote::class) → adminNotes()
hasMany(CreatorValidationStep::class) → validationSteps()
```

---

### 3.8. Modèle `OrderVendor`

```php
// Relations
belongsTo(Order::class) → order()
belongsTo(User::class, 'vendor_id') → vendor()
hasMany(OrderItem::class, 'order_id', 'order_id')
    ->where('vendor_id', $this->vendor_id) → items()
```

---

### 3.9. Modèle `PromoCode`

```php
// Relations
hasMany(PromoCodeUsage::class) → usages()
```

---

### 3.10. Modèle `StripeWebhookEvent`

```php
// Relations
belongsTo(Payment::class) → payment()
```

---

### 3.11. Modèle `MonetbilCallbackEvent`

```php
// Relations
belongsTo(PaymentTransaction::class, 'payment_ref', 'payment_ref') 
    → paymentTransaction()
```

---

### 3.12. Modèle `Role`

```php
// Relations
hasMany(User::class) → users()
```

---

## 4. IDENTIFICATION BOUTIQUE vs MARKETPLACE

### 4.1. Tables pour la BOUTIQUE RACINE (Brand)

#### Tables principales
- **`products`** — Produits avec `product_type = 'brand'` et `user_id` = utilisateur brand
- **`orders`** — Toutes les commandes (boutique + marketplace)
- **`order_items`** — Items avec `vendor_type = 'brand'` ou produits brand
- **`order_vendors`** — Entrées avec `vendor_type = 'brand'`
- **`payments`** — Paiements pour toutes les commandes
- **`payment_transactions`** — Transactions pour toutes les commandes
- **`promo_codes`** — Codes promo applicables à la boutique
- **`promo_code_usages`** — Utilisations des codes promo

#### Critères d'identification
- **Produits** : `product_type = 'brand'` OU `user_id` = ID utilisateur brand (email: brand@racinebyganda.com)
- **Order Items** : `vendor_type = 'brand'` OU produit associé avec `product_type = 'brand'`
- **Order Vendors** : `vendor_type = 'brand'`

---

### 4.2. Tables pour le MARKETPLACE CRÉATEURS

#### Tables principales
- **`products`** — Produits avec `product_type = 'marketplace'` et `user_id` = ID créateur
- **`creator_profiles`** — Profils des créateurs/vendeurs
- **`order_items`** — Items avec `vendor_type = 'creator'` OU produits marketplace
- **`order_vendors`** — Entrées avec `vendor_type = 'creator'`
- **`orders`** — Commandes contenant des produits marketplace (peuvent être mixtes)
- **`payments`** — Paiements pour commandes marketplace (partagés avec boutique)
- **`payment_transactions`** — Transactions pour commandes marketplace

#### Critères d'identification
- **Produits** : `product_type = 'marketplace'` ET `user_id` = ID créateur (≠ brand user)
- **Créateurs** : `creator_profiles.user_id` = ID utilisateur avec rôle 'createur'/'creator'
- **Order Items** : `vendor_type = 'creator'` OU produit associé avec `product_type = 'marketplace'`
- **Order Vendors** : `vendor_type = 'creator'` ET `vendor_id` = ID créateur

---

### 4.3. Tables partagées (Boutique + Marketplace)

#### Tables communes
- **`orders`** — Peuvent contenir produits brand ET marketplace (commandes mixtes)
- **`payments`** — Paiements uniques par commande (peuvent être mixtes)
- **`payment_transactions`** — Transactions uniques par paiement
- **`stripe_webhook_events`** — Webhooks Stripe pour tous les paiements
- **`monetbil_callback_events`** — Callbacks Monetbil pour tous les paiements
- **`users`** — Utilisateurs (clients, créateurs, admins)
- **`roles`** — Rôles système
- **`promo_codes`** — Codes promo (peuvent s'appliquer aux deux)
- **`payment_providers`** — Fournisseurs de paiement
- **`payment_routing_rules`** — Règles de routage des paiements
- **`payment_audit_logs`** — Logs d'audit des paiements

---

### 4.4. Tables spécifiques Marketplace

#### Tables dédiées créateurs
- **`creator_profiles`** — Profils créateurs uniquement
- **`order_vendors`** — Répartition par vendeur (brand ou créateur)
  - Colonnes importantes : `commission_rate`, `commission_amount`, `vendor_payout`, `payout_status`

---

## 5. OBSERVATIONS IMPORTANTES

### 5.1. Relations manquantes dans les modèles

- **`OrderItem`** : Pas de relation `vendor()` vers `User` malgré la présence de `vendor_id` et `vendor_type` en base
- **`OrderItem`** : Pas de relation vers `OrderVendor` pour lier l'item à sa répartition vendeur

---

### 5.2. Doublons potentiels

- **`payments`** et **`payment_transactions`** : Deux tables pour gérer les paiements
  - `payments` : Paiements Stripe principalement (relation directe avec `orders`)
  - `payment_transactions` : Transactions Monetbil principalement (relation directe avec `orders`)
  - Les deux peuvent coexister pour une même commande selon le provider

---

### 5.3. Gestion des commandes mixtes

- Une commande peut contenir des produits **brand** ET **marketplace**
- La table **`order_vendors`** permet de répartir les montants par vendeur
- Chaque `order_item` a un `vendor_id` et `vendor_type` pour identifier son vendeur

---

### 5.4. Système de commission marketplace

- **`order_vendors`** contient :
  - `commission_rate` : Taux de commission (default: 15%)
  - `commission_amount` : Montant de la commission
  - `vendor_payout` : Montant à verser au vendeur
  - `payout_status` : Statut du versement (pending, processing, paid, failed)

---

### 5.5. Webhooks et idempotence

- **`stripe_webhook_events`** : `event_id` unique pour éviter les doublons
- **`monetbil_callback_events`** : `event_key` unique (hash stable) pour idempotence
- Colonnes `requeue_count` et `last_requeue_at` pour gérer les retentatives
- Statut `blocked` pour limiter les requeues infinis

---

### 5.6. Codes promo

- Support des utilisateurs non connectés via `email` dans `promo_code_usages`
- Pas de FK directe vers `users` et `orders` dans `promo_code_usages` (évite dépendance migrations)
- Limites par utilisateur via `max_uses_per_user`

---

## 6. RÉSUMÉ PAR DOMAINE

### 6.1. Commandes
- **1 table principale** : `orders`
- **1 table items** : `order_items`
- **1 table répartition vendeurs** : `order_vendors`
- **Relations** : User, Address, PromoCode, OrderItems, Payments

### 6.2. Paiements
- **2 tables transactions** : `payments`, `payment_transactions`
- **2 tables webhooks** : `stripe_webhook_events`, `monetbil_callback_events`
- **2 tables système** : `payment_providers`, `payment_routing_rules`
- **1 table audit** : `payment_audit_logs`
- **Relations** : Order (directe)

### 6.3. Produits
- **1 table principale** : `products`
- **1 table stock** : `stock_alerts` (non détaillée ici)
- **Relations** : Category, Collection, User (créateur), StockAlerts, Reviews, Wishlists

### 6.4. Utilisateurs & Rôles
- **1 table utilisateurs** : `users`
- **1 table rôles** : `roles`
- **1 table profils créateurs** : `creator_profiles`
- **Relations** : Role, CreatorProfile, Orders, Addresses, Wishlists

### 6.5. Codes promo
- **1 table codes** : `promo_codes`
- **1 table usages** : `promo_code_usages`
- **Relations** : Orders (via promo_code_id), Users (via user_id, pas de FK)

---

**FIN DU RAPPORT — PASS 1/3**

