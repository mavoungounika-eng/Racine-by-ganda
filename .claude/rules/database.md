---
paths:
  - "database/migrations*"
  - "database/seeders*"
  - "database/factories*"
  - "app/Models*"
---
# Règles — Base de données / Modèles

## Conventions migrations
- Toujours utiliser python3 pour créer les fichiers de migration (pas heredoc WSL)
- Nommage : YYYY_MM_DD_HHMMSS_description_snake_case.php
- Toujours implémenter down() pour rollback propre
- Enums MySQL : gérer le cas SQLite en test (pas de ALTER COLUMN)
- FK avec nullOnDelete() ou cascadeOnDelete() selon le contexte

## Modèles critiques
- User — auth_version, role, role_id, two_factor_secret, professional_email_token
- Order — creator_id, payment_status, status
- CreatorProfile — user_id, subscription
- CreatorSubscription — payment_provider (ajouté avril 2026)
- PosSession / PosSale / PosPayment — voir pos.md
- MonetbilCallbackEvent — event_key (unique), status: received/processed/failed/ignored
- StripeWebhookEvent — event_id (unique), status: received/processed/failed

## Conventions Eloquent
- Toujours déclarer $fillable explicitement
- Utiliser les constantes pour les statuts (STATUS_PENDING, STATUS_COMPLETED etc.)
- Les relations critiques :
  Order → belongsTo(User) + hasMany(OrderItem) + hasMany(Payment)
  User → hasOne(CreatorProfile) + hasMany(Order)
  PosSession → hasMany(PosSale) → hasMany(PosPayment)

## auth_version
- S incrémente automatiquement via User::boot() saved hook
- Déclencheurs : password, role_id, two_factor_secret, two_factor_confirmed_at, status
- Ne PAS dupliquer dans UserObserver (bug double-incrément corrigé avril 2026)

## Seeders utiles en test
- RolesTableSeeder — toujours seeder avant de créer des users avec role
- TestUsersSeeder — comptes de test standards
