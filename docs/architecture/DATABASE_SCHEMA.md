# Database Schema Documentation - RACINE BY GANDA

## Overview

This document provides comprehensive documentation of the RACINE BY GANDA database schema, including all tables, relationships, and key constraints.

**Database Statistics:**
- **Total Models:** 85
- **Database Engine:** MySQL
- **Character Set:** utf8mb4
- **Collation:** utf8mb4_unicode_ci

## Core Modules

### 1. User Management & Authentication

#### `users`
- **Purpose:** Central user table for all platform users
- **Key Fields:**
  - `id` (PK)
  - `name`, `email`, `password`
  - `role` (enum: super_admin, admin, staff, createur, client)
  - `email_verified_at`
  - `two_factor_secret`, `two_factor_recovery_codes`
  - `auth_version` (security revocation)
  
**Relationships:**
- `hasOne` → `creator_profiles`
- `hasMany` → `orders`, `addresses`, `oauth_accounts`
- `hasMany` → `pos_sessions`, `pos_operator_audit_logs`

#### `oauth_accounts`
- **Purpose:** OAuth provider accounts (Google, Facebook)
- **Key Fields:**
  - `user_id` (FK → users)
  - `provider` (google, facebook)
  - `provider_user_id`
  - `access_token`, `refresh_token`

#### `sessions`
- **Purpose:** Laravel session storage
- **Key Fields:**
  - `id` (PK, string)
  - `user_id` (FK → users, nullable)
  - `ip_address`, `user_agent`
  - `payload`, `last_activity`

---

### 2. E-Commerce Core

#### `products`
- **Purpose:** Product catalog
- **Key Fields:**
  - `id` (PK)
  - `creator_profile_id` (FK → creator_profiles)
  - `name`, `description`, `price`
  - `sku`, `stock_quantity`
  - `status` (active, inactive, archived)
  - `is_featured`, `is_digital`
  
**Relationships:**
- `belongsTo` → `creator_profiles`
- `hasMany` → `order_items`, `product_images`, `product_variants`
- `belongsToMany` → `categories`, `tags`

#### `orders`
- **Purpose:** Customer orders
- **Key Fields:**
  - `id` (PK)
  - `user_id` (FK → users)
  - `order_number` (unique)
  - `status` (pending, processing, completed, cancelled, refunded)
  - `total_amount`, `tax_amount`, `shipping_amount`
  - `payment_method`, `payment_status`
  - `stripe_payment_intent_id`
  
**Relationships:**
- `belongsTo` → `users`
- `hasMany` → `order_items`, `transactions`
- `hasOne` → `shipping_address`, `billing_address`

#### `order_items`
- **Purpose:** Line items in orders
- **Key Fields:**
  - `order_id` (FK → orders)
  - `product_id` (FK → products)
  - `quantity`, `unit_price`, `total_price`
  - `creator_profile_id` (FK → creator_profiles)
  
**Relationships:**
- `belongsTo` → `orders`, `products`, `creator_profiles`

---

### 3. Creator Management

#### `creator_profiles`
- **Purpose:** Creator account profiles
- **Key Fields:**
  - `user_id` (FK → users)
  - `business_name`, `bio`, `website`
  - `status` (pending, active, suspended)
  - `commission_rate`
  - `total_sales`, `total_revenue`
  
**Relationships:**
- `belongsTo` → `users`
- `hasMany` → `products`, `creator_subscriptions`, `creator_documents`
- `hasOne` → `creator_bank_info`

#### `creator_subscriptions`
- **Purpose:** Creator subscription plans
- **Key Fields:**
  - `creator_profile_id` (FK → creator_profiles)
  - `creator_plan_id` (FK → creator_plans)
  - `status` (active, canceled, expired, pending)
  - `stripe_subscription_id`
  - `current_period_start`, `current_period_end`
  
**Relationships:**
- `belongsTo` → `creator_profiles`, `creator_plans`

#### `creator_plans`
- **Purpose:** Available subscription plans for creators
- **Key Fields:**
  - `name`, `slug`, `price`
  - `features` (JSON)
  - `max_products`, `max_storage_mb`
  - `is_active`

---

### 4. Point of Sale (POS)

#### `pos_sessions`
- **Purpose:** POS cash register sessions
- **Key Fields:**
  - `id` (PK)
  - `operator_id` (FK → users)
  - `status` (open, closed)
  - `opening_balance`, `closing_balance`
  - `opened_at`, `closed_at`
  
**Relationships:**
- `belongsTo` → `users` (operator)
- `hasMany` → `pos_sales`, `pos_cash_movements`

#### `pos_sales`
- **Purpose:** POS transactions
- **Key Fields:**
  - `pos_session_id` (FK → pos_sessions)
  - `order_id` (FK → orders)
  - `payment_method` (cash, card, mobile_money)
  - `amount_paid`, `change_given`
  
**Relationships:**
- `belongsTo` → `pos_sessions`, `orders`

#### `pos_operator_audit_logs`
- **Purpose:** Audit trail for POS operations
- **Key Fields:**
  - `operator_id` (FK → users)
  - `action` (session_open, session_close, sale, refund, etc.)
  - `details` (JSON)
  - `ip_address`

---

### 5. Financial & Accounting

#### `transactions`
- **Purpose:** Financial transactions
- **Key Fields:**
  - `order_id` (FK → orders)
  - `type` (payment, refund, payout)
  - `amount`, `currency`
  - `status` (pending, completed, failed)
  - `stripe_transaction_id`
  
**Relationships:**
- `belongsTo` → `orders`

#### `creator_payouts`
- **Purpose:** Creator earnings payouts
- **Key Fields:**
  - `creator_profile_id` (FK → creator_profiles)
  - `amount`, `status`
  - `payout_method` (bank_transfer, mobile_money)
  - `processed_at`

#### `accounting_entries` (ERP Module)
- **Purpose:** Double-entry accounting journal
- **Key Fields:**
  - `fiscal_year_id` (FK → fiscal_years)
  - `journal_id` (FK → journals)
  - `account_id` (FK → accounts)
  - `debit`, `credit`
  - `reference`, `description`

---

### 6. Content Management (CMS)

#### `cms_pages`
- **Purpose:** Dynamic website pages
- **Key Fields:**
  - `slug`, `title`, `content`
  - `status` (draft, published)
  - `meta_title`, `meta_description`
  
**Relationships:**
- `hasMany` → `cms_page_sections`

#### `cms_menus`
- **Purpose:** Navigation menus
- **Key Fields:**
  - `name`, `slug`, `items` (JSON)

---

### 7. Analytics & Reporting

#### `analytics_events`
- **Purpose:** Custom analytics tracking
- **Key Fields:**
  - `user_id` (FK → users, nullable)
  - `event_type` (page_view, product_view, add_to_cart, etc.)
  - `event_data` (JSON)
  - `session_id`, `ip_address`

---

### 8. Messaging & Notifications

#### `messages`
- **Purpose:** Internal messaging system
- **Key Fields:**
  - `sender_id` (FK → users)
  - `recipient_id` (FK → users)
  - `subject`, `body`
  - `read_at`
  
**Relationships:**
- `belongsTo` → `users` (sender, recipient)

#### `notifications`
- **Purpose:** Laravel notification system
- **Key Fields:**
  - `type`, `notifiable_type`, `notifiable_id`
  - `data` (JSON)
  - `read_at`

---

### 9. System & Queue Management

#### `jobs`
- **Purpose:** Laravel queue jobs
- **Key Fields:**
  - `queue`, `payload`, `attempts`
  - `reserved_at`, `available_at`

#### `failed_jobs`
- **Purpose:** Failed queue jobs
- **Key Fields:**
  - `connection`, `queue`, `payload`
  - `exception`, `failed_at`

#### `job_batches`
- **Purpose:** Batch job tracking
- **Key Fields:**
  - `id`, `name`, `total_jobs`
  - `pending_jobs`, `failed_jobs`
  - `finished_at`, `cancelled_at`

---

## Key Relationships Summary

### One-to-One
- `users` ↔ `creator_profiles`
- `orders` ↔ `shipping_address`
- `creator_profiles` ↔ `creator_bank_info`

### One-to-Many
- `users` → `orders`, `addresses`, `messages`
- `creator_profiles` → `products`, `creator_subscriptions`
- `orders` → `order_items`, `transactions`
- `pos_sessions` → `pos_sales`, `pos_cash_movements`

### Many-to-Many
- `products` ↔ `categories` (via `category_product`)
- `products` ↔ `tags` (via `product_tag`)
- `users` ↔ `roles` (via `role_user`)

---

## Indexes & Performance

### Primary Indexes
- All tables have primary key indexes on `id`
- Composite primary keys on pivot tables

### Foreign Key Indexes
- All foreign key columns are indexed
- Cascading deletes configured where appropriate

### Custom Indexes
- `orders.order_number` (unique)
- `products.sku` (unique)
- `users.email` (unique)
- `creator_profiles.user_id` (unique)
- Composite indexes on frequently queried columns

---

## Data Integrity Constraints

### NOT NULL Constraints
- All foreign keys are NOT NULL unless explicitly nullable
- Core fields (name, email, status) are NOT NULL

### UNIQUE Constraints
- `users.email`
- `orders.order_number`
- `products.sku`
- `creator_profiles.user_id`

### CHECK Constraints
- Price fields must be >= 0
- Quantity fields must be >= 0
- Status fields use ENUM validation

---

## Security Considerations

### Sensitive Data
- Passwords: bcrypt hashed
- Two-factor secrets: encrypted
- OAuth tokens: encrypted
- Payment tokens: never stored, only Stripe IDs

### Audit Trail
- `pos_operator_audit_logs` for POS operations
- `created_at`, `updated_at` timestamps on all tables
- Soft deletes enabled on critical tables

---

## Migration Notes

### Version Control
- All schema changes tracked via Laravel migrations
- Migration files located in `database/migrations/`
- Rollback capability for all migrations

### Seeding
- Production seeders for essential data (roles, plans)
- Test seeders for development environments
- Faker-based factories for testing

---

## GraphViz ERD Generation

To generate a visual ERD diagram, install GraphViz:

**Windows:**
```bash
choco install graphviz
# or download from: https://graphviz.org/download/
```

**Then generate:**
```bash
php artisan generate:erd database-erd --format=png
php artisan generate:erd database-erd --format=svg
```

---

## Additional Resources

- **Schema Migrations:** `database/migrations/`
- **Model Definitions:** `app/Models/`
- **ERP Module Schema:** `modules/ERP/Database/Migrations/`
- **CMS Module Schema:** `modules/CMS/Database/Migrations/`

---

**Last Updated:** 2026-02-12  
**Database Version:** 1.0.0  
**Total Tables:** ~85  
**Total Relationships:** ~150+
