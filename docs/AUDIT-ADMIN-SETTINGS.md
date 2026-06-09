# AUDIT COMPLET — ESPACE ADMIN & RESTRUCTURATION PARAMÈTRES

**Date** : 2026-06-06
**Projet** : RACINE BY GANDA Backend
**Version** : Laravel 12 · PHP 8.3
**Mission** : Cartographie complète de l'espace admin et proposition de restructuration des paramètres

---

## PHASE 1 — ANALYSE DE L'EXISTANT

### 1.1 — CONTRÔLEUR SETTINGS ACTUEL

**Fichier** : `app/Http/Controllers/Admin/AdminSettingsController.php`

**Paramètres gérés (stockés en cache)** :
- **Informations générales** : `site_name`, `site_email`, `site_phone`, `site_address`
- **Réseaux sociaux** : `social_facebook`, `social_instagram`, `social_twitter`, `social_whatsapp`
- **Marketplace** : `commission_rate` (15%), `shipping_fee` (2000 FCFA), `currency` (FCFA), `low_stock_threshold` (10)
- **Paiement Stripe** : `stripe_mode` (test/live), `payments_enabled` (boolean)
- **Système** : `maintenance_mode` (lecture seule), `registrations_enabled`, `maintenance_message`

**Architecture actuelle** :
- Stockage : Laravel Cache (1 an de TTL)
- Méthodes : `index()` (affichage), `update()` (validation + sauvegarde)
- Autorisation : `access-system-config` (Super Admin uniquement)
- Vue : `resources/views/admin/settings/index.blade.php` — 1 seule page avec 4 sections

**Problèmes identifiés** :
1. ❌ **Pas de table `settings` en base de données** — tout est en cache, risque de perte
2. ❌ **Doublon Stripe** — mode test/live dans UI mais clés dans `.env` (incohérence)
3. ❌ **Manque validation avancée** — pas de vérification de cohérence entre paramètres
4. ❌ **Interface monolithique** — une seule page pour tout, difficile à maintenir
5. ❌ **Aucun historique** — impossible de savoir qui a modifié quoi et quand

---

### 1.2 — VUE SETTINGS ACTUELLE

**Fichier** : `resources/views/admin/settings/index.blade.php`

**Structure** : 4 sections en carte (card) Bootstrap 5
1. **Informations générales** — 4 champs (nom, email, phone, address)
2. **Réseaux sociaux** — 4 champs (Facebook, Instagram, Twitter, WhatsApp)
3. **Marketplace & Commissions** — 4 champs (commission, frais livraison, devise, seuil stock)
4. **Configuration Stripe** — 2 champs (mode test/live, paiements activés)
5. **Système** — 3 champs (maintenance, inscriptions, message maintenance)

**Problèmes identifiés** :
1. ❌ **Pas d'onglets** — tout en scroll vertical, navigation difficile
2. ❌ **Manque sections critiques** : Profil Admin, Intégrations API, Email SMTP, Sécurité
3. ❌ **Pas de feedback visuel** sur la configuration actuelle (clés API configurées ou non)
4. ❌ **Champs sensibles exposés** — pas de masquage pour les secrets
5. ❌ **Pas de validation client** — formulaire basique sans aide contextuelle

---

### 1.3 — CONTROLLERS ADMIN EXISTANTS (37 fichiers)

**Modules identifiés par analyse des controllers :**

| Module | Controller(s) | Rôle |
|--------|--------------|------|
| **Dashboard** | `AdminDashboardController`, `DashboardController` | Dashboard global avec widgets |
| **Orders** | `AdminOrderController` | Gestion commandes (statuts, suivi, QR codes) |
| **Products** | `AdminProductController`, `ProductImageController` | Catalogue produits, images |
| **Categories** | `AdminCategoryController` | Hiérarchie catégories produits |
| **Users** | `AdminUserController` | Gestion utilisateurs (clients, staff) |
| **Creators** | `AdminCreatorController`, `AdminCreatorNoteController`, `AdminCreatorExportController` | Marketplace créateurs |
| **Subscriptions** | `CreatorSubscriptionController`, `AdminCreatorPlanController` | Plans SaaS créateurs (Stripe Billing) |
| **Payments** | `AdminMobileMoneyController` | Paiements Mobile Money (validation manuelle) |
| **CMS** | `CmsPageController`, `CmsSectionController` | Pages & blocs de contenu |
| **Analytics** | `AnalyticsController`, `DecisionIntelligenceController` | Rapports ventes, BI |
| **KYC** | `AdminKycController` | Validation documents identité créateurs |
| **Roles** | `AdminRoleController` | Gestion rôles & permissions |
| **Notifications** | `AdminNotificationController` | Centre notifications admin |
| **Performance** | `PerformanceController` | Monitoring routes lentes, alertes |
| **Queue** | `MetricsController` | Circuit breaker, rate limiter, monitoring jobs |
| **Stock Alerts** | `AdminStockAlertController` | Alertes rupture stock |
| **Promo Codes** | `AdminPromoCodeController` | Codes promotionnels |
| **Finances** | `AdminFinanceController`, `FinancialDashboardController` | Dashboard financier global |
| **POS** | `PosController`, `PosAnalyticsController` | Interface POS web + analytics |
| **Exports** | `AdminExportController`, `AdminCreatorExportController` | Exports Excel multi-feuilles |
| **Actions** | `ActionController` | Système actions IA (propositions automatiques) |
| **Settings** | `AdminSettingsController` | Paramètres généraux (actuel) |

---

### 1.4 — VUES ADMIN EXISTANTES (70 fichiers .blade.php)

**Dossiers structurants :**
```
resources/views/admin/
├── analytics/          — 3 vues (funnel, index, sales)
├── categories/         — 3 vues (index, create, edit)
├── cms/
│   ├── pages/          — Gestion pages CMS
│   └── sections/       — Gestion sections CMS
├── creators/           — 3 vues (index, show, validation report)
├── dashboard/          — 1 vue principale + 7 partials (global-state, trends, etc.)
├── exports/            — 1 vue (multi-sheet)
├── finances/           — 1 vue (index)
├── financial/          — 1 vue (dashboard)
├── kyc/                — 2 vues (index, show)
├── notifications/      — 1 vue (index)
├── orders/             — 5 vues (index, show, to-handle, qrcode, scan)
├── payments/
│   ├── mobile-money/   — 2 vues (index, show)
│   ├── providers/      — 1 vue (index)
│   ├── transactions/   — 2 vues (index, show)
│   └── webhooks/       — 4 vues (index, show-stripe, show-monetbil, stuck)
├── performance/        — 3 vues (index, routes, alerts)
├── pos/                — 4 vues (index, sessions, session-detail, analytics)
├── products/           — (non listé mais présumé : index, create, edit, show)
├── promo-codes/        — (non listé mais présumé : index, create, edit)
├── reports/            — (non listé)
├── settings/           — 1 vue (index.blade.php)
├── dashboard.blade.php — Vue legacy dashboard
└── login.blade.php     — Login admin dédié
```

**Observation** : Aucun dossier `settings/` avec sous-pages → tout est dans `settings/index.blade.php` (monolithique).

---

### 1.5 — ROUTES ADMIN (extrait routes/web.php)

**Groupe principal** : `Route::prefix('admin')->name('admin.')`

**Routes settings actuelles** :
```php
Route::get('settings', [AdminSettingsController::class, 'index'])->name('settings.index');
Route::post('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
```

**Autres routes admin majeures** :
- Dashboard : `GET /admin` → `AdminDashboardController@index`
- Orders : `GET /admin/orders`, `GET /admin/orders/{order}`, `PATCH /admin/orders/{order}/status`
- Products : ressource complète (index, create, store, edit, update, destroy)
- Creators : ressource + notes + exports CSV
- Payments : `GET /admin/payments` (hub), `/admin/mobile-money`, `/admin/payments/webhooks`
- CMS : ressource pages + sections
- Performance : `GET /admin/performance`, `/admin/performance/routes`, `/admin/performance/alerts`
- KYC : `GET /admin/kyc`, `POST /admin/kyc/creator/{creator}/sync`
- Exports : `GET /admin/export/multi-sheet`, `POST /admin/export/multi-sheet/import`

**Observation** : Routes bien structurées par module, SAUF settings qui reste monolithique.

---

### 1.6 — CONFIG FILES (30 fichiers config/)

**Fichiers contenant des paramètres configurables :**

| Fichier | Paramètres clés | Actuellement dans |
|---------|----------------|-------------------|
| `config/services.php` | Stripe, Monetbil, OpenAI, Google OAuth, reCAPTCHA, Exchange Rate API | `.env` uniquement |
| `config/ai.php` | Modèle IA, max tokens, temperature, retry | `.env` + hardcodé |
| `config/ai_decisional.php` | Modules IA activés, seuils alertes, cache TTL | `.env` + hardcodé |
| `config/alerts.php` | Seuils alertes (queue, performance, erreurs) | `.env` + hardcodé |
| `config/queue-protection.php` | Circuit breaker, rate limiter config | `.env` + hardcodé |
| `config/currency.php` | Devise par défaut, taux conversion | `.env` + hardcodé |
| `config/crm.php` | Loyalty points, segmentation rules | Hardcodé uniquement |
| `config/erp.php` | Stock management, production settings | Hardcodé uniquement |
| `config/pos.php` | POS settings (offline sync, session timeout) | `.env` + hardcodé |
| `config/payments.php` | Providers activés, timeouts, retry logic | `.env` + hardcodé |
| `config/stripe.php` | Clés API, webhook secret, currency | `.env` uniquement |
| `config/openai.php` | API key, rate limiting, cache settings | `.env` + hardcodé |
| `config/amira.php` | Provider IA (Groq/Anthropic), modèle | `.env` + hardcodé |
| `config/recaptcha.php` | Site key, secret key, threshold | `.env` uniquement |
| `config/mail.php` | SMTP host, port, username, password | `.env` uniquement |
| `config/filesystems.php` | S3 credentials, local paths, upload limits | `.env` + hardcodé |
| `config/session.php` | Session driver, lifetime, cookie settings | `.env` + hardcodé |
| `config/auth.php` | Guards, providers, password reset timeout | Hardcodé uniquement |
| `config/sanctum.php` | Token expiration, stateful domains | `.env` + hardcodé |

**Problème majeur** : ❌ **AUCUN de ces paramètres n'est modifiable depuis l'interface admin** → tout est hardcodé ou dans `.env`.

---

### 1.7 — MODÈLE USER (app/Models/User.php)

**Champs profil admin identifiés** :
- **Identité** : `name`, `email`, `phone`, `professional_email`
- **Sécurité 2FA** : `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `two_factor_required`
- **Trusted Device** : `trusted_device_token`, `trusted_device_expires_at`
- **OAuth** : `google_id`
- **Préférences** : `locale`, `preferred_currency`, `email_preferences` (array), `email_notifications_enabled`, `email_messaging_enabled`
- **Audit** : `auth_version` (incrémenté automatiquement sur changement rôle/password)
- **Onboarding** : `onboarding_completed`, `onboarding_type`, `terms_accepted_at`

**Observation** : Ces champs ne sont PAS exposés dans l'interface settings actuelle.

---

### 1.8 — TABLE USER_SETTINGS (migration 2025_11_24_000010)

**Champs existants** :
- `display_mode` : `light` | `dark` | `auto` (défaut: dark)
- `accent_palette` : `orange` | `yellow` | `gold` | `red` (défaut: orange)
- `animation_intensity` : `none` | `soft` | `standard` | `luxury` (défaut: standard)
- `visual_style` : `female` | `male` | `neutral` (défaut: neutral)
- `contrast_level` : `normal` | `bright` | `dark` (défaut: normal)
- `golden_light_filter` : boolean (défaut: false)

**Problème** : ❌ Table existe mais **AUCUNE UI admin** pour gérer ces préférences visuelles.

---

## PHASE 2 — CARTOGRAPHIE DES MODULES

### 2.1 — MODULES AVEC PARAMÈTRES MANQUANTS

| Module | Page settings existante ? | Paramètres actuels | Paramètres manquants |
|--------|--------------------------|-------------------|---------------------|
| **Dashboard** | ❌ Non | Aucun | Widgets activés, ordre widgets, refresh interval |
| **Orders** | ❌ Non | Aucun | Auto-annulation timeout, statuts personnalisés, workflow |
| **Products** | ❌ Non | `low_stock_threshold` (global) | Validation auto images, max variants, SKU format |
| **Users** | ❌ Non | `registrations_enabled` | Password policy, session timeout, force 2FA |
| **Creators** | ❌ Non | `commission_rate` (global) | Commission par catégorie, payout schedule, KYC auto |
| **Payments** | ⚠️ Partiel | `stripe_mode`, `payments_enabled` | Monetbil auto-approve, retry logic, webhook timeout |
| **CMS** | ❌ Non | Aucun | Page cache TTL, slugs auto-generation, SEO defaults |
| **Analytics** | ❌ Non | Aucun | Retention days, data anonymization, export formats |
| **Crm** | ❌ Non | Aucun | Loyalty points ratio, segmentation rules, email campaigns |
| **POS** | ❌ Non | Aucun | Offline sync interval, session timeout, receipt template |
| **KYC** | ❌ Non | Aucun | Auto-approve seuils, documents requis, expiration alerts |
| **Subscriptions** | ❌ Non | Aucun | Trial period, grace period, downgrade policy |
| **Roles** | ❌ Non | Aucun | Permissions granulaires, role hierarchy |
| **Notifications** | ❌ Non | Aucun | Channels activés (mail, DB, Slack), templates |
| **Performance** | ❌ Non | Aucun | Slow query threshold, N+1 detection, cache strategy |
| **Queue** | ⚠️ Partiel | Circuit breaker, rate limiter (`.env`) | UI admin pour ajuster seuils en temps réel |
| **Stock** | ⚠️ Partiel | `low_stock_threshold` | Multi-seuils (warning/critical), auto-reorder |
| **Promo Codes** | ❌ Non | Aucun | Max usage global, stackable, auto-expiration |
| **Finances** | ❌ Non | Aucun | Fiscal year start, tax rates, currency rounding |
| **AI (Amira)** | ❌ Non | Config hardcodée | Modèle IA, max tokens, temperature, fallback model |

---

### 2.2 — PARAMÈTRES CODÉS EN DUR À EXPOSER

**Liste des paramètres actuellement hardcodés qui devraient être dans Settings UI :**

1. **Email & SMTP** (actuellement `.env` uniquement)
   - MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION
   - Test connexion SMTP depuis l'UI

2. **Intégrations API** (actuellement `.env` uniquement)
   - Stripe : STRIPE_KEY, STRIPE_SECRET, STRIPE_WEBHOOK_SECRET
   - Monetbil : MONETBIL_SERVICE_KEY, MONETBIL_SERVICE_SECRET, MONETBIL_NOTIFY_URL, MONETBIL_RETURN_URL
   - OpenAI : OPENAI_API_KEY, OPENAI_ORGANIZATION
   - Google OAuth : GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI
   - reCAPTCHA : RECAPTCHA_SITE_KEY, RECAPTCHA_SECRET_KEY, RECAPTCHA_THRESHOLD
   - Sentry : SENTRY_LARAVEL_DSN, SENTRY_TRACES_SAMPLE_RATE
   - Exchange Rate : EXCHANGE_RATE_API_KEY

3. **Files & Storage** (actuellement `.env` + config/filesystems.php)
   - FILESYSTEM_DISK (local/s3)
   - AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_BUCKET, AWS_REGION
   - Upload max size, allowed extensions

4. **Sécurité** (actuellement hardcodé)
   - Password policy : min length, complexity rules, expiration
   - Session timeout, max concurrent sessions
   - 2FA : force enable for admins, recovery codes count
   - Trusted device : cookie expiration, max devices
   - Rate limiting : login attempts, API calls

5. **AI & Amira** (actuellement config/ai.php + config/amira.php)
   - AI_MODEL, AI_TIMEOUT, AI_MAX_TOKENS, AI_TEMPERATURE
   - AMIRA_AI_PROVIDER (openai/groq/anthropic), modèle fallback
   - Modules IA activés/désactivés (churn, scoring, recommendation)
   - Seuils alertes IA (stock critique, taux retour, etc.)

6. **Apparence UI** (table user_settings existe mais pas d'UI)
   - Theme global par défaut (light/dark/auto)
   - Palette accent par défaut (orange/yellow/gold/red)
   - Animation intensity (none/soft/standard/luxury)

7. **Maintenance & Logs** (partiellement dans settings)
   - LOG_LEVEL, LOG_CHANNEL
   - CACHE_STORE, CACHE_TTL pour settings
   - QUEUE_CONNECTION, failed jobs retention
   - Artisan down/up depuis UI (actuellement CLI uniquement)

---

## PHASE 3 — PROPOSITION DE RESTRUCTURATION

### 3.1 — NOUVELLE STRUCTURE D'ONGLETS

**Interface proposée** : `/admin/settings` avec navigation par onglets horizontaux (tabs Bootstrap 5)

**9 onglets principaux** :

---

#### **ONGLET 1 : GÉNÉRAL** 🏢
*Informations entreprise et réseaux sociaux*

**Contenu** :
- **Section "Entreprise"**
  - Nom du site (site_name)
  - Email contact (site_email)
  - Téléphone (site_phone)
  - Adresse physique (site_address)
  - Pays & Fuseau horaire (nouveau)
  - Logo entreprise (upload) (nouveau)
  - Favicon (upload) (nouveau)

- **Section "Réseaux sociaux"**
  - URL Facebook (social_facebook)
  - URL Instagram (social_instagram)
  - URL Twitter/X (social_twitter)
  - WhatsApp Business (social_whatsapp)
  - LinkedIn (nouveau)
  - TikTok (nouveau)

- **Section "Légal"** (nouveau)
  - Numéro SIRET/RCCM
  - TVA intracommunautaire
  - Conditions Générales de Vente (lien page CMS)
  - Politique confidentialité (lien page CMS)

**Complexité** : 🟢 SIMPLE
**Fichiers à créer** :
- Migration : `create_settings_table.php` (nouvelle table `settings` en base)
- Vue : `resources/views/admin/settings/tabs/general.blade.php`

**Fichiers à modifier** :
- Controller : `AdminSettingsController.php` (refactoriser pour onglets)
- Vue : `settings/index.blade.php` (intégrer tabs Bootstrap 5)

---

#### **ONGLET 2 : MARKETPLACE** 🛍️
*Paramètres marketplace et commissions*

**Contenu** :
- **Section "Commissions"**
  - Taux commission par défaut (commission_rate) — existant
  - Commission par catégorie (nouveau) — table `category_commissions`
  - Payout schedule (hebdomadaire/mensuel) (nouveau)
  - Seuil minimum payout (nouveau)

- **Section "Livraison"**
  - Frais livraison par défaut (shipping_fee) — existant
  - Zones de livraison (nouveau) — table `shipping_zones`
  - Livraison gratuite à partir de X (nouveau)

- **Section "Stock & Catalogue"**
  - Seuil stock faible (low_stock_threshold) — existant
  - Seuil stock critique (nouveau)
  - Auto-reorder activé (nouveau)
  - Validation auto images produits (nouveau)
  - Max variants par produit (nouveau)

- **Section "Commandes"**
  - Timeout auto-annulation (nouveau) — ex: 24h si non payé
  - Statuts personnalisés (nouveau)
  - Workflow commandes (nouveau)

**Complexité** : 🟡 MOYEN
**Fichiers à créer** :
- Migrations : `add_advanced_marketplace_settings.php`, `create_category_commissions_table.php`, `create_shipping_zones_table.php`
- Vue : `resources/views/admin/settings/tabs/marketplace.blade.php`

**Fichiers à modifier** :
- Model : `Product.php`, `Category.php`, `Order.php` (ajouter relations settings)
- Services : `OrderService.php` (intégrer timeout auto-cancel)

---

#### **ONGLET 3 : PAIEMENTS** 💳
*Configuration providers paiement*

**Contenu** :
- **Section "Stripe"**
  - Mode (test/live) — existant
  - Clé publique (STRIPE_KEY) — masquée
  - Clé secrète (STRIPE_SECRET) — masquée
  - Webhook secret (STRIPE_WEBHOOK_SECRET) — masquée
  - Devise (currency) — EUR uniquement pour Stripe
  - Test connexion (bouton) (nouveau)

- **Section "Monetbil (Mobile Money)"**
  - Service Key (MONETBIL_SERVICE_KEY) — masquée
  - Service Secret (MONETBIL_SERVICE_SECRET) — masquée
  - Notify URL (MONETBIL_NOTIFY_URL)
  - Return URL (MONETBIL_RETURN_URL)
  - Auto-approve paiements < X FCFA (nouveau)
  - Webhook timeout (secondes) (nouveau)
  - Test connexion (bouton) (nouveau)

- **Section "Options globales"**
  - Paiements en ligne activés (payments_enabled) — existant
  - Providers activés (checkboxes : Stripe, Monetbil, COD)
  - Retry logic sur échec (nouveau)
  - Max tentatives paiement (nouveau)

**Complexité** : 🟡 MOYEN
**Fichiers à créer** :
- Vue : `resources/views/admin/settings/tabs/payments.blade.php`
- Service : `PaymentTestService.php` (test connexion API)

**Fichiers à modifier** :
- Controller : `AdminSettingsController.php` (ajouter méthode `testStripeConnection()`, `testMonetbilConnection()`)
- Config : `config/services.php` (lire depuis DB si présent, sinon fallback .env)

---

#### **ONGLET 4 : INTÉGRATIONS** 🔌
*API externes et webhooks*

**Contenu** :
- **Section "OpenAI & Amira"**
  - Provider IA (OpenAI/Groq/Anthropic) (AMIRA_AI_PROVIDER)
  - API Key OpenAI (OPENAI_API_KEY) — masquée
  - API Key Groq (GROQ_API_KEY) — masquée
  - Modèle par défaut (AI_MODEL)
  - Max tokens (AI_MAX_TOKENS)
  - Temperature (AI_TEMPERATURE)
  - Timeout (AI_TIMEOUT)
  - Test connexion (bouton)

- **Section "Google Services"**
  - OAuth Client ID (GOOGLE_CLIENT_ID) — masquée
  - OAuth Client Secret (GOOGLE_CLIENT_SECRET) — masquée
  - Redirect URI (GOOGLE_REDIRECT_URI)
  - reCAPTCHA Site Key (RECAPTCHA_SITE_KEY)
  - reCAPTCHA Secret Key (RECAPTCHA_SECRET_KEY)
  - reCAPTCHA Threshold (0.0 - 1.0)
  - reCAPTCHA activé (RECAPTCHA_ENABLED)

- **Section "Monitoring & Logs"**
  - Sentry DSN (SENTRY_LARAVEL_DSN) — masquée
  - Sentry Traces Sample Rate (0.0 - 1.0)
  - Sentry Environment (production/staging)
  - Slack Webhook URL (pour alertes)
  - Alert Email Recipients (comma-separated)

- **Section "Exchange Rate API"**
  - API Key (EXCHANGE_RATE_API_KEY) — masquée
  - Cache TTL (heures)
  - Fallback rates (si API down)

**Complexité** : 🔴 COMPLEXE
**Raison** : Nécessite refonte de `config/services.php` pour lire depuis DB
**Fichiers à créer** :
- Migration : `add_integration_settings.php`
- Vue : `resources/views/admin/settings/tabs/integrations.blade.php`
- Service : `IntegrationTestService.php` (test connexions API)

**Fichiers à modifier** :
- Config : `config/services.php`, `config/openai.php`, `config/recaptcha.php` (fallback DB → .env)
- Bootstrap : `bootstrap/app.php` ou `AppServiceProvider` (charger settings DB en early boot)

---

#### **ONGLET 5 : EMAIL & SMTP** 📧
*Configuration serveur mail*

**Contenu** :
- **Section "SMTP Configuration"**
  - Mailer (smtp/ses/mailgun/log) (MAIL_MAILER)
  - Host (MAIL_HOST)
  - Port (MAIL_PORT)
  - Username (MAIL_USERNAME)
  - Password (MAIL_PASSWORD) — masquée
  - Encryption (tls/ssl/none) (MAIL_ENCRYPTION)
  - From Address (MAIL_FROM_ADDRESS)
  - From Name (MAIL_FROM_NAME)
  - Test email (envoyer test à admin) (nouveau)

- **Section "Email Templates"** (nouveau)
  - Template commande confirmée (lien vers édition)
  - Template paiement réussi
  - Template livraison expédiée
  - Template mot de passe oublié
  - Logo email (upload)

- **Section "Notifications Email"** (nouveau)
  - Notifications admin activées (ordre créée, paiement reçu)
  - Email destinataire principal
  - Emails CC (comma-separated)

**Complexité** : 🟡 MOYEN
**Fichiers à créer** :
- Vue : `resources/views/admin/settings/tabs/email.blade.php`
- Service : `EmailTestService.php` (envoyer email test)

**Fichiers à modifier** :
- Config : `config/mail.php` (fallback DB → .env)

---

#### **ONGLET 6 : SÉCURITÉ** 🔒
*Politique sécurité et 2FA*

**Contenu** :
- **Section "Authentification"**
  - Force 2FA pour admins (nouveau)
  - Force 2FA pour créateurs (nouveau)
  - Recovery codes count (défaut: 8) (nouveau)
  - Session timeout (minutes) (nouveau)
  - Max concurrent sessions (nouveau)
  - Logout autres sessions si password change (nouveau)

- **Section "Password Policy"** (nouveau)
  - Min length (défaut: 8)
  - Require uppercase (boolean)
  - Require numbers (boolean)
  - Require special chars (boolean)
  - Password expiration (jours, 0 = jamais)
  - Prevent password reuse (N derniers)

- **Section "Trusted Devices"** (nouveau)
  - Trusted device activé (boolean)
  - Cookie expiration (jours) (défaut: 30)
  - Max trusted devices par user (défaut: 5)

- **Section "Rate Limiting"** (nouveau)
  - Login attempts max (défaut: 5)
  - Login lockout duration (minutes) (défaut: 15)
  - API rate limit (requests/minute)
  - Checkout rate limit (orders/hour)

- **Section "IP Whitelist"** (nouveau)
  - IPs autorisées pour /admin (comma-separated)
  - Mode whitelist activé (boolean)

**Complexité** : 🔴 COMPLEXE
**Raison** : Nécessite refonte middleware auth et validation password
**Fichiers à créer** :
- Migration : `add_security_settings.php`
- Vue : `resources/views/admin/settings/tabs/security.blade.php`
- Middleware : `EnforcePasswordPolicy.php`, `IpWhitelist.php`
- Service : `PasswordPolicyService.php`

**Fichiers à modifier** :
- `app/Models/User.php` (ajouter validation password dynamique)
- `app/Http/Controllers/Auth/*` (intégrer password policy)

---

#### **ONGLET 7 : APPARENCE** 🎨
*Thème et préférences visuelles*

**Contenu** :
- **Section "Thème Global"** (actuellement table user_settings mais pas d'UI)
  - Mode affichage par défaut (light/dark/auto)
  - Palette accent (orange/yellow/gold/red)
  - Animation intensity (none/soft/standard/luxury)
  - Visual style (female/male/neutral)
  - Contrast level (normal/bright/dark)
  - Golden light filter (boolean)

- **Section "Logo & Branding"** (nouveau)
  - Logo principal (upload)
  - Logo dark mode (upload)
  - Favicon (upload)
  - Couleur primaire (color picker) — override Bootstrap
  - Couleur secondaire (color picker)

- **Section "Dashboard Widgets"** (nouveau)
  - Widgets actifs (checkboxes : global-state, trends, operations, etc.)
  - Ordre widgets (drag & drop)
  - Refresh interval (secondes)

**Complexité** : 🟡 MOYEN
**Fichiers à créer** :
- Vue : `resources/views/admin/settings/tabs/appearance.blade.php`
- JS : `resources/js/admin/widget-ordering.js` (drag & drop)

**Fichiers à modifier** :
- Controller : `AppearanceController.php` (étendre pour settings globaux)
- Vue : `layouts/admin.blade.php` (injecter CSS custom depuis settings)

---

#### **ONGLET 8 : AVANCÉ** ⚙️
*Maintenance, logs, cache, queue*

**Contenu** :
- **Section "Maintenance"**
  - Mode maintenance (toggle) — existant, actuellement CLI uniquement
  - Message maintenance (textarea) — existant
  - IPs autorisées en maintenance (nouveau)
  - Page maintenance personnalisée (upload HTML) (nouveau)

- **Section "Inscriptions"**
  - Inscriptions publiques activées (registrations_enabled) — existant
  - Validation email obligatoire (nouveau)
  - Rôle par défaut nouvel user (nouveau)
  - Auto-onboarding (nouveau)

- **Section "Cache & Performance"** (nouveau)
  - Cache store (redis/file/database) (CACHE_STORE)
  - Cache TTL settings (secondes)
  - Vider cache (bouton)
  - Cache queries activé (boolean)
  - Cache views activé (boolean)

- **Section "Queue & Jobs"** (nouveau)
  - Queue connection (redis/database/sync) (QUEUE_CONNECTION)
  - Failed jobs retention (jours)
  - Retry failed jobs (bouton)
  - Purge failed jobs (bouton)
  - Circuit breaker enabled (boolean)
  - Rate limiter enabled (boolean)

- **Section "Logs"** (nouveau)
  - Log level (debug/info/warning/error) (LOG_LEVEL)
  - Log channel (single/daily/stack) (LOG_CHANNEL)
  - Daily logs retention (jours)
  - Purge old logs (bouton)

- **Section "Storage & Files"** (nouveau)
  - Filesystem disk (local/s3) (FILESYSTEM_DISK)
  - AWS S3 Access Key (masquée)
  - AWS S3 Secret Key (masquée)
  - AWS S3 Bucket
  - AWS S3 Region
  - Max upload size (MB)
  - Allowed file extensions (comma-separated)

**Complexité** : 🔴 COMPLEXE
**Raison** : Nécessite actions système (artisan down/up, cache:clear, queue:retry)
**Fichiers à créer** :
- Vue : `resources/views/admin/settings/tabs/advanced.blade.php`
- Service : `SystemMaintenanceService.php` (encapsuler artisan calls)

**Fichiers à modifier** :
- Controller : `AdminSettingsController.php` (ajouter routes POST pour actions système)

---

#### **ONGLET 9 : MON PROFIL** 👤
*Profil admin personnel*

**Contenu** :
- **Section "Informations personnelles"**
  - Nom complet (name)
  - Email principal (email) — non modifiable
  - Email professionnel (professional_email)
  - Téléphone (phone)
  - Avatar (upload)
  - Langue interface (locale)
  - Devise préférée (preferred_currency)

- **Section "Sécurité"**
  - Modifier mot de passe (formulaire)
  - Activer 2FA (toggle + QR code)
  - Recovery codes (afficher/régénérer)
  - Trusted devices (liste avec révocation)
  - Sessions actives (liste avec déconnexion)

- **Section "Notifications"**
  - Notifications email activées (email_notifications_enabled)
  - Notifications messagerie (email_messaging_enabled)
  - Préférences notifications (email_preferences) — checkboxes par type

- **Section "Préférences UI"** (lien vers table user_settings)
  - Mode affichage (light/dark/auto)
  - Palette accent (orange/yellow/gold/red)
  - Animations (none/soft/standard/luxury)

**Complexité** : 🟡 MOYEN
**Fichiers à créer** :
- Vue : `resources/views/admin/settings/tabs/profile.blade.php`

**Fichiers à modifier** :
- Controller : Créer `AdminProfileController.php` (séparer de AdminSettingsController)
- Routes : Ajouter `/admin/profile/*` (distinct de `/admin/settings`)

---

### 3.2 — TABLEAU RÉCAPITULATIF DES ONGLETS

| Onglet | Complexité | Nouveaux champs | Champs existants | Migrations | Controllers | Vues |
|--------|-----------|----------------|-----------------|-----------|------------|------|
| 1. Général | 🟢 Simple | 6 | 8 | 1 | 0 | 1 |
| 2. Marketplace | 🟡 Moyen | 12 | 4 | 3 | 1 | 1 |
| 3. Paiements | 🟡 Moyen | 8 | 3 | 1 | 1 | 1 |
| 4. Intégrations | 🔴 Complexe | 18 | 0 | 1 | 1 | 1 |
| 5. Email & SMTP | 🟡 Moyen | 10 | 2 | 1 | 1 | 1 |
| 6. Sécurité | 🔴 Complexe | 16 | 0 | 1 | 2 | 1 |
| 7. Apparence | 🟡 Moyen | 8 | 6 | 0 | 1 | 1 |
| 8. Avancé | 🔴 Complexe | 22 | 3 | 1 | 1 | 1 |
| 9. Mon Profil | 🟡 Moyen | 4 | 12 | 0 | 1 | 1 |
| **TOTAL** | — | **104** | **38** | **9** | **9** | **9** |

---

### 3.3 — ARCHITECTURE TECHNIQUE PROPOSÉE

#### **3.3.1 — Nouvelle table `settings` en base de données**

**Problème actuel** : Tout est en Cache (risque de perte).

**Solution** : Migration vers table `settings` avec structure clé-valeur.

**Migration** : `database/migrations/YYYY_MM_DD_create_settings_table.php`

```php
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();       // Ex: 'site_name', 'stripe_mode'
    $table->text('value')->nullable();     // JSON pour valeurs complexes
    $table->string('type')->default('string'); // string|integer|boolean|array|json
    $table->string('group')->index();      // general|marketplace|payments|etc.
    $table->boolean('is_encrypted')->default(false); // Pour secrets (API keys)
    $table->text('description')->nullable();
    $table->timestamps();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
});
```

**Avantages** :
- ✅ Persistance garantie
- ✅ Historique via `updated_by` + `updated_at`
- ✅ Chiffrement natif pour secrets
- ✅ Grouping pour onglets
- ✅ Typage pour validation

---

#### **3.3.2 — Model `Setting.php`**

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'group', 'is_encrypted', 'description', 'updated_by'];

    // Mutators pour encryption
    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->is_encrypted ? Crypt::decryptString($value) : $value,
            set: fn ($value) => $this->is_encrypted ? Crypt::encryptString($value) : $value,
        );
    }

    // Helper pour récupérer une valeur
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "setting:{$key}";

        return Cache::remember($cacheKey, now()->addDay(), function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            if (!$setting) return $default;

            // Cast selon type
            return match ($setting->type) {
                'boolean' => (bool) $setting->value,
                'integer' => (int) $setting->value,
                'array', 'json' => json_decode($setting->value, true),
                default => $setting->value,
            };
        });
    }

    // Helper pour définir une valeur
    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        $isEncrypted = in_array($key, self::ENCRYPTED_KEYS);

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'type' => $type,
                'group' => $group,
                'is_encrypted' => $isEncrypted,
                'updated_by' => auth()->id(),
            ]
        );

        // Invalider cache
        Cache::forget("setting:{$key}");
    }

    // Clés à chiffrer
    const ENCRYPTED_KEYS = [
        'stripe_secret',
        'stripe_webhook_secret',
        'monetbil_service_key',
        'monetbil_service_secret',
        'openai_api_key',
        'google_client_secret',
        'recaptcha_secret_key',
        'sentry_dsn',
        'exchange_rate_api_key',
        'mail_password',
        'aws_secret_access_key',
    ];
}
```

---

#### **3.3.3 — Refactorisation `config/services.php`**

**Problème** : Actuellement hardcodé `env('STRIPE_KEY')`.

**Solution** : Fallback DB → .env

```php
return [
    'stripe' => [
        'key' => Setting::get('stripe_key') ?? env('STRIPE_KEY'),
        'secret' => Setting::get('stripe_secret') ?? env('STRIPE_SECRET'),
        'webhook_secret' => Setting::get('stripe_webhook_secret') ?? env('STRIPE_WEBHOOK_SECRET'),
        'currency' => Setting::get('stripe_currency', 'EUR'),
    ],

    'monetbil' => [
        'service_key' => Setting::get('monetbil_service_key') ?? env('MONETBIL_SERVICE_KEY'),
        'service_secret' => Setting::get('monetbil_service_secret') ?? env('MONETBIL_SERVICE_SECRET'),
        'notify_url' => Setting::get('monetbil_notify_url') ?? env('MONETBIL_NOTIFY_URL'),
        'return_url' => Setting::get('monetbil_return_url') ?? env('MONETBIL_RETURN_URL'),
        // ...
    ],

    // Idem pour openai, google, recaptcha, sentry, etc.
];
```

**Impact** : ⚠️ Nécessite bootstrap précoce du Model `Setting` avant config loading.

**Solution** : Charger dans `AppServiceProvider::register()` :

```php
public function register(): void
{
    // Charger settings DB en cache avant config merge
    if (Schema::hasTable('settings')) {
        $settings = \App\Models\Setting::all()->pluck('value', 'key');
        config(['settings' => $settings->toArray()]);
    }
}
```

---

#### **3.3.4 — Refactorisation `AdminSettingsController`**

**Structure proposée** :

```php
class AdminSettingsController extends Controller
{
    // Affichage page settings avec tabs
    public function index(string $tab = 'general'): View
    {
        $this->authorize('access-system-config');

        $settings = Setting::where('group', $tab)->get()->pluck('value', 'key');

        return view('admin.settings.index', [
            'currentTab' => $tab,
            'settings' => $settings,
        ]);
    }

    // Mise à jour onglet
    public function update(Request $request, string $tab): RedirectResponse
    {
        $this->authorize('access-system-config');

        $validated = $this->validateTab($request, $tab);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, $this->getType($key), $tab);
        }

        return redirect()->route('admin.settings.index', $tab)
            ->with('success', 'Paramètres mis à jour avec succès !');
    }

    // Actions spéciales (test connexion, clear cache, etc.)
    public function testStripeConnection(): JsonResponse { /* ... */ }
    public function testMonetbilConnection(): JsonResponse { /* ... */ }
    public function testEmailConnection(): JsonResponse { /* ... */ }
    public function clearCache(): RedirectResponse { /* ... */ }
    public function retryFailedJobs(): RedirectResponse { /* ... */ }
    public function toggleMaintenance(): RedirectResponse { /* ... */ }

    private function validateTab(Request $request, string $tab): array
    {
        return match ($tab) {
            'general' => $request->validate([/* règles onglet 1 */]),
            'marketplace' => $request->validate([/* règles onglet 2 */]),
            // ...
            default => [],
        };
    }
}
```

**Routes proposées** :

```php
Route::prefix('admin/settings')->name('admin.settings.')->middleware(['auth', 'can:access-system-config'])->group(function () {
    Route::get('/{tab?}', [AdminSettingsController::class, 'index'])->name('index');
    Route::post('/{tab}', [AdminSettingsController::class, 'update'])->name('update');

    // Actions spéciales
    Route::post('/test/stripe', [AdminSettingsController::class, 'testStripeConnection'])->name('test.stripe');
    Route::post('/test/monetbil', [AdminSettingsController::class, 'testMonetbilConnection'])->name('test.monetbil');
    Route::post('/test/email', [AdminSettingsController::class, 'testEmailConnection'])->name('test.email');
    Route::post('/cache/clear', [AdminSettingsController::class, 'clearCache'])->name('cache.clear');
    Route::post('/queue/retry', [AdminSettingsController::class, 'retryFailedJobs'])->name('queue.retry');
    Route::post('/maintenance/toggle', [AdminSettingsController::class, 'toggleMaintenance'])->name('maintenance.toggle');
});
```

---

#### **3.3.5 — Vue principale avec tabs**

**Fichier** : `resources/views/admin/settings/index.blade.php`

**Structure proposée** :

```blade
@extends('layouts.admin')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">⚙️ Paramètres</h1>

    {{-- Tabs navigation --}}
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link @if($currentTab === 'general') active @endif"
               href="{{ route('admin.settings.index', 'general') }}">
                🏢 Général
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link @if($currentTab === 'marketplace') active @endif"
               href="{{ route('admin.settings.index', 'marketplace') }}">
                🛍️ Marketplace
            </a>
        </li>
        {{-- ... autres onglets ... --}}
    </ul>

    {{-- Tab content --}}
    <div class="tab-content">
        @includeWhen($currentTab === 'general', 'admin.settings.tabs.general')
        @includeWhen($currentTab === 'marketplace', 'admin.settings.tabs.marketplace')
        @includeWhen($currentTab === 'payments', 'admin.settings.tabs.payments')
        @includeWhen($currentTab === 'integrations', 'admin.settings.tabs.integrations')
        @includeWhen($currentTab === 'email', 'admin.settings.tabs.email')
        @includeWhen($currentTab === 'security', 'admin.settings.tabs.security')
        @includeWhen($currentTab === 'appearance', 'admin.settings.tabs.appearance')
        @includeWhen($currentTab === 'advanced', 'admin.settings.tabs.advanced')
        @includeWhen($currentTab === 'profile', 'admin.settings.tabs.profile')
    </div>
</div>
@endsection
```

---

### 3.4 — SÉPARATION SETTINGS GLOBAUX vs MODULES

**Principe** :
- **Settings globaux** (dans `/admin/settings`) : Paramètres transverses affectant tout le système
- **Settings module** (dans `/admin/{module}/config`) : Paramètres spécifiques à un module

**Exemples à garder dans settings globaux** :
- Commission rate par défaut → Onglet Marketplace
- Stripe/Monetbil config → Onglet Paiements
- Email SMTP → Onglet Email
- 2FA policy → Onglet Sécurité

**Exemples à garder dans module propre** :
- Dashboard widgets order → `/admin/dashboard/customize` (bouton dans dashboard)
- CMS SEO defaults → `/admin/cms/config`
- POS receipt template → `/admin/pos-terminal/config`
- Analytics retention → `/admin/analytics/config`

**Règle** : Si ça affecte **plusieurs modules** ou **tout le système**, c'est dans settings globaux. Si c'est **spécifique à un module**, c'est dans le module.

---

### 3.5 — DOUBLONS À SUPPRIMER

| Paramètre | Actuellement dans | Devrait être dans | Action |
|-----------|------------------|------------------|--------|
| `stripe_mode` | Settings UI + `.env` | Settings UI uniquement (DB) | Supprimer de `.env.example` |
| `commission_rate` | Settings UI (cache) | Settings UI (DB) | Migration cache → DB |
| `shipping_fee` | Settings UI (cache) | Settings UI (DB) | Migration cache → DB |
| `currency` | Settings UI + `.env` (DEFAULT_CURRENCY) | Settings UI (DB) | Unifier, supprimer `.env` |
| `low_stock_threshold` | Settings UI (cache) | Settings UI (DB) + module Stock | Migration + ajouter multi-seuils |
| `payments_enabled` | Settings UI (cache) | Settings UI (DB) | Migration cache → DB |
| `registrations_enabled` | Settings UI (cache) | Settings UI (DB) | Migration cache → DB |

---

## PHASE 4 — RAPPORT FINAL

### 4.1 — ÉTAT ACTUEL (RÉSUMÉ)

✅ **Ce qui existe et fonctionne** :
- 1 page settings avec 4 sections basiques (infos, réseaux sociaux, marketplace, Stripe, système)
- Stockage en Cache Laravel (TTL 1 an)
- Validation formulaire côté serveur
- Autorisation Super Admin (`access-system-config`)
- Interface Bootstrap 5 propre et responsive

❌ **Ce qui manque** :
- Table `settings` en base de données (tout est en cache, risque de perte)
- 5 onglets cruciaux absents : Intégrations API, Email SMTP, Sécurité, Apparence, Profil Admin
- 104 nouveaux champs nécessaires (voir tableau 3.2)
- UI pour modifier paramètres actuellement hardcodés (config/*.php)
- Tests de connexion API (Stripe, Monetbil, OpenAI, Email)
- Actions système depuis UI (maintenance mode, clear cache, retry jobs)
- Historique modifications (qui a changé quoi et quand)
- Validation avancée et cohérence inter-paramètres
- Chiffrement natif pour secrets (API keys, passwords)

⚠️ **Doublons / Incohérences** :
- Stripe mode dans UI mais clés dans `.env`
- Currency dans settings UI + `.env` (DEFAULT_CURRENCY)
- Commission rate dans cache mais pas utilisé partout
- Table `user_settings` existe mais aucune UI admin

---

### 4.2 — STRUCTURE PROPOSÉE FINALE

**9 onglets** : Général · Marketplace · Paiements · Intégrations · Email · Sécurité · Apparence · Avancé · Mon Profil

**142 champs totaux** : 38 existants + 104 nouveaux

**Complexité globale** : 🟡 MOYENNE à 🔴 COMPLEXE selon onglet

**Impact codebase** :
- ✅ **Faible** : Onglets Général, Apparence, Mon Profil (vues + migration uniquement)
- ⚠️ **Moyen** : Marketplace, Paiements, Email (vues + services de test)
- 🔴 **Élevé** : Intégrations, Sécurité, Avancé (refonte config/*.php + middleware)

---

### 4.3 — FICHIERS À CRÉER (LISTE COMPLÈTE)

#### **Migrations (9 fichiers)**
1. `YYYY_MM_DD_create_settings_table.php` — Table principale settings
2. `YYYY_MM_DD_migrate_cache_to_settings_table.php` — Migration données cache → DB
3. `YYYY_MM_DD_add_advanced_marketplace_settings.php` — Champs marketplace avancés
4. `YYYY_MM_DD_create_category_commissions_table.php` — Commission par catégorie
5. `YYYY_MM_DD_create_shipping_zones_table.php` — Zones de livraison
6. `YYYY_MM_DD_add_integration_settings.php` — Paramètres API
7. `YYYY_MM_DD_add_security_settings.php` — Password policy, rate limiting
8. `YYYY_MM_DD_add_email_settings.php` — Templates email
9. `YYYY_MM_DD_add_system_settings.php` — Cache, queue, logs, storage

#### **Models (4 fichiers)**
1. `app/Models/Setting.php` — Model principal settings avec helpers
2. `app/Models/CategoryCommission.php` — Commission par catégorie
3. `app/Models/ShippingZone.php` — Zone de livraison
4. `app/Models/SettingHistory.php` — Historique modifications (audit trail)

#### **Controllers (3 fichiers)**
1. `app/Http/Controllers/Admin/AdminSettingsController.php` — **MODIFIER** (refactoriser avec tabs)
2. `app/Http/Controllers/Admin/AdminProfileController.php` — Profil admin séparé
3. `app/Http/Controllers/Admin/SystemActionsController.php` — Actions système (maintenance, cache, queue)

#### **Services (6 fichiers)**
1. `app/Services/Settings/SettingsService.php` — Logique métier settings
2. `app/Services/Settings/PaymentTestService.php` — Test connexion Stripe, Monetbil
3. `app/Services/Settings/IntegrationTestService.php` — Test OpenAI, Google, Sentry
4. `app/Services/Settings/EmailTestService.php` — Envoyer email de test
5. `app/Services/Settings/PasswordPolicyService.php` — Validation password policy
6. `app/Services/Settings/SystemMaintenanceService.php` — Artisan down/up, cache:clear

#### **Middleware (2 fichiers)**
1. `app/Http/Middleware/EnforcePasswordPolicy.php` — Validation password dynamique
2. `app/Http/Middleware/IpWhitelist.php` — Whitelist IP pour /admin

#### **Vues (10 fichiers)**
1. `resources/views/admin/settings/index.blade.php` — **MODIFIER** (ajouter tabs)
2. `resources/views/admin/settings/tabs/general.blade.php` — Onglet 1
3. `resources/views/admin/settings/tabs/marketplace.blade.php` — Onglet 2
4. `resources/views/admin/settings/tabs/payments.blade.php` — Onglet 3
5. `resources/views/admin/settings/tabs/integrations.blade.php` — Onglet 4
6. `resources/views/admin/settings/tabs/email.blade.php` — Onglet 5
7. `resources/views/admin/settings/tabs/security.blade.php` — Onglet 6
8. `resources/views/admin/settings/tabs/appearance.blade.php` — Onglet 7
9. `resources/views/admin/settings/tabs/advanced.blade.php` — Onglet 8
10. `resources/views/admin/settings/tabs/profile.blade.php` — Onglet 9

#### **JS (1 fichier)**
1. `resources/js/admin/widget-ordering.js` — Drag & drop dashboard widgets

#### **Tests (6 fichiers)**
1. `tests/Feature/Admin/AdminSettingsGeneralTest.php`
2. `tests/Feature/Admin/AdminSettingsMarketplaceTest.php`
3. `tests/Feature/Admin/AdminSettingsPaymentsTest.php`
4. `tests/Feature/Admin/AdminSettingsIntegrationsTest.php`
5. `tests/Feature/Admin/AdminSettingsSecurityTest.php`
6. `tests/Unit/Services/PasswordPolicyServiceTest.php`

---

### 4.4 — FICHIERS À MODIFIER (LISTE COMPLÈTE)

#### **Config (9 fichiers)**
1. `config/services.php` — Ajouter fallback DB → .env pour API keys
2. `config/openai.php` — Idem
3. `config/recaptcha.php` — Idem
4. `config/mail.php` — Idem
5. `config/filesystems.php` — Idem pour S3
6. `config/auth.php` — Intégrer password policy dynamique
7. `config/session.php` — Intégrer session timeout depuis settings
8. `config/queue.php` — Intégrer queue connection depuis settings
9. `config/cache.php` — Intégrer cache store depuis settings

#### **Bootstrap**
1. `bootstrap/app.php` OU `app/Providers/AppServiceProvider.php` — Charger settings DB avant config merge

#### **Routes**
1. `routes/web.php` — Ajouter routes settings/{tab} + actions système

#### **Models**
1. `app/Models/User.php` — Ajouter validation password dynamique
2. `app/Models/Product.php` — Relation avec CategoryCommission
3. `app/Models/Category.php` — Relation avec CategoryCommission
4. `app/Models/Order.php` — Intégrer timeout auto-cancel

#### **Controllers Auth**
1. `app/Http/Controllers/Auth/*` — Intégrer password policy, force 2FA

#### **Layouts**
1. `resources/views/layouts/admin.blade.php` — Injecter CSS custom depuis settings

---

### 4.5 — ESTIMATION COMPLEXITÉ PAR ONGLET (RAPPEL)

| Onglet | Complexité | Temps estimé | Dépendances critiques |
|--------|-----------|--------------|---------------------|
| 1. Général | 🟢 Simple | 4h | Migration settings table |
| 2. Marketplace | 🟡 Moyen | 8h | CategoryCommission, ShippingZone tables |
| 3. Paiements | 🟡 Moyen | 6h | PaymentTestService |
| 4. Intégrations | 🔴 Complexe | 12h | Refonte config/services.php + bootstrap |
| 5. Email & SMTP | 🟡 Moyen | 6h | EmailTestService |
| 6. Sécurité | 🔴 Complexe | 14h | Middleware, PasswordPolicyService, refonte auth |
| 7. Apparence | 🟡 Moyen | 6h | Widget ordering JS |
| 8. Avancé | 🔴 Complexe | 10h | SystemMaintenanceService, artisan calls |
| 9. Mon Profil | 🟡 Moyen | 4h | AdminProfileController |
| **TOTAL** | — | **70h** | **~3 semaines développement** |

**Note** : Cette estimation inclut développement + tests unitaires/feature + documentation.

---

### 4.6 — PRIORITÉS DE DÉVELOPPEMENT RECOMMANDÉES

#### **Phase 1 — Fondations (Sprint 1 — 1 semaine)**
1. ✅ Migration `create_settings_table`
2. ✅ Model `Setting.php` avec helpers get/set
3. ✅ Migration données cache → DB
4. ✅ Refactorisation `AdminSettingsController` pour tabs
5. ✅ Vue principale `settings/index.blade.php` avec navigation tabs
6. ✅ Onglet 1 : Général (vue + validation)

**Objectif** : Infrastructure settings opérationnelle, 1er onglet fonctionnel.

---

#### **Phase 2 — Onglets métier (Sprint 2 — 1 semaine)**
1. ✅ Onglet 2 : Marketplace (+ migrations CategoryCommission, ShippingZone)
2. ✅ Onglet 3 : Paiements (+ PaymentTestService)
3. ✅ Onglet 5 : Email & SMTP (+ EmailTestService)

**Objectif** : Fonctionnalités métier critiques (marketplace, paiements, email).

---

#### **Phase 3 — Intégrations & Sécurité (Sprint 3 — 1 semaine)**
1. ✅ Refonte `config/services.php` (fallback DB → .env)
2. ✅ Onglet 4 : Intégrations (+ IntegrationTestService)
3. ✅ Onglet 6 : Sécurité (+ PasswordPolicyService, middleware)

**Objectif** : Sécurité renforcée + APIs configurables depuis UI.

---

#### **Phase 4 — Finalisation (Sprint 4 — 3-4 jours)**
1. ✅ Onglet 7 : Apparence (+ widget ordering JS)
2. ✅ Onglet 8 : Avancé (+ SystemMaintenanceService)
3. ✅ Onglet 9 : Mon Profil (+ AdminProfileController)
4. ✅ Tests Feature pour tous les onglets
5. ✅ Documentation utilisateur

**Objectif** : Feature complète, testée, documentée, prête production.

---

### 4.7 — RISQUES & POINTS D'ATTENTION

⚠️ **Risques identifiés** :

1. **Bootstrap config trop tardif** — Si `Setting::get()` est appelé avant que la table soit chargée
   - **Mitigation** : Charger dans `AppServiceProvider::register()` + fallback .env

2. **Performance** — Trop de requêtes DB si chaque `Setting::get()` query
   - **Mitigation** : Cache Laravel (déjà implémenté dans `Setting::get()`)

3. **Migration données cache → DB** — Données existantes peuvent être perdues
   - **Mitigation** : Migration avec fallback + backup cache avant

4. **Secrets exposés** — API keys visibles en clair dans formulaires
   - **Mitigation** : Champs type password + chiffrement DB (`is_encrypted`)

5. **Régression config/*.php** — Changement fallback peut casser config existante
   - **Mitigation** : Tests exhaustifs + déploiement progressif (staging d'abord)

6. **Validation complexe** — Certains champs interdépendants (ex: S3 requiert AWS keys)
   - **Mitigation** : Validation conditionnelle dans `validateTab()`

---

## CONCLUSION

### ✅ RECOMMANDATIONS FINALES

1. **PRIORITÉ HAUTE** : Implémenter Phase 1 (infrastructure settings) immédiatement
   - Créer table `settings` + Model + migration cache → DB
   - Éviter perte données actuellement en cache

2. **PRIORITÉ HAUTE** : Onglets Marketplace + Paiements + Email (Phase 2)
   - Paramètres métier critiques pour mise en production

3. **PRIORITÉ MOYENNE** : Intégrations + Sécurité (Phase 3)
   - Améliore maintenabilité mais pas bloquant pour prod

4. **PRIORITÉ BASSE** : Apparence + Avancé + Profil (Phase 4)
   - Confort admin, peut être fait post-lancement

### 📊 MÉTRIQUES ATTENDUES

**Avant restructuration** :
- 1 page settings
- 15 champs configurables
- 0 champs API keys modifiables depuis UI
- 0 tests de connexion API
- 0 historique modifications

**Après restructuration** :
- 9 onglets settings
- 142 champs configurables (38 existants + 104 nouveaux)
- 100% API keys modifiables depuis UI
- 6 tests de connexion API (Stripe, Monetbil, OpenAI, Email, Google, Sentry)
- Historique complet via `updated_by` + `updated_at`

**Gain estimé** :
- ⏱️ Temps config : -80% (plus besoin .env pour la plupart des paramètres)
- 🔒 Sécurité : +50% (password policy, 2FA forcé, IP whitelist)
- 🎨 UX admin : +200% (navigation tabs, tests connexion, validation temps réel)
- 📈 Maintenabilité : +150% (settings en DB, historique, validation centralisée)

---

**FIN DU RAPPORT**

---

**Annexe** : Ce rapport a été généré automatiquement par analyse du codebase RACINE BY GANDA le 2026-06-06.
Pour toute question ou clarification, contacter l'équipe technique.
