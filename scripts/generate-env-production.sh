#!/bin/bash
# Script de génération .env.production.local
# Usage: bash scripts/generate-env-production.sh

set -e

TARGET_FILE=".env.production.local"

echo "Génération de ${TARGET_FILE}..."

cat > "${TARGET_FILE}" << 'EOF'
# ============================================
# RACINE BY GANDA - Production Environment
# ============================================
# ⚠️  NE JAMAIS COMMITER CE FICHIER
# Remplir toutes les variables marquées TODO avant déploiement

# ── Application ─────────────────────────────
APP_NAME="RACINE BY GANDA"
APP_ENV=production
APP_KEY=                            # TODO: générer avec php artisan key:generate
APP_DEBUG=false
APP_URL=                            # TODO: https://votre-domaine.com
TRUSTED_PROXIES=*                   # TODO: IP spécifique si possible (nginx/load balancer)

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=fr_FR

APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

# ── Logging ─────────────────────────────────
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

# ── Database ────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=                        # TODO: nom base de données production
DB_USERNAME=                        # TODO: user MySQL production
DB_PASSWORD=                        # TODO: password MySQL production

# ── Session ─────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=                     # TODO: .votre-domaine.com (avec le point)

# ── Storage ─────────────────────────────────
FILESYSTEM_DISK=                    # TODO: s3 ou local
QUEUE_CONNECTION=redis              # Production: redis recommandé

# ── Cache ───────────────────────────────────
CACHE_STORE=redis
CACHE_PREFIX=racine_

# ── Redis ───────────────────────────────────
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=                     # TODO: password Redis si configuré
REDIS_PORT=6379

# ── Mail ────────────────────────────────────
MAIL_MAILER=                        # TODO: smtp, ses, mailgun, etc.
MAIL_SCHEME=
MAIL_HOST=                          # TODO: smtp host
MAIL_PORT=                          # TODO: smtp port (587, 465, etc.)
MAIL_USERNAME=                      # TODO: smtp username
MAIL_PASSWORD=                      # TODO: smtp password
MAIL_FROM_ADDRESS=contact@racinebyganda.com
MAIL_FROM_NAME="RACINE BY GANDA"
MAIL_ENCRYPTION=tls

# ── Company Information ─────────────────────
COMPANY_NAME="RACINE BY GANDA"
COMPANY_EMAIL=contact@racinebyganda.com
COMPANY_PHONE=                      # TODO: téléphone entreprise

# ── AWS S3 (si FILESYSTEM_DISK=s3) ──────────
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

# ── Vite ────────────────────────────────────
VITE_APP_NAME="${APP_NAME}"

# ── Stripe Payment ──────────────────────────
STRIPE_ENABLED=true
STRIPE_PUBLIC_KEY=                  # TODO: pk_live_XXXXX (clé LIVE, pas test)
STRIPE_SECRET_KEY=                  # TODO: sk_live_XXXXX (clé LIVE)
STRIPE_WEBHOOK_SECRET=              # TODO: whsec_XXXXX (webhook signing secret)
STRIPE_CURRENCY=XAF

# ── POS / Device JWT ────────────────────────
JWT_SECRET=                         # TODO: générer aléatoire 64 chars
JWT_ALGO=HS256
POS_JWT_TTL=604800
POS_ADMIN_EMAIL=admin@racinebyganda.com

# ── IA Décisionnelle ────────────────────────
AI_DECISIONAL_ENABLED=true
AI_MODULE_CHURN_PREDICTION=true
AI_MODULE_CREATOR_SCORING=true
AI_MODULE_RECOMMENDATION=true
AI_MODULE_PRODUCT_PERFORMANCE=false
AI_MODULE_STOCK_PREDICTION=false
AI_MODULE_ANOMALY_DETECTION=false
AI_MODULE_CONVERSION_OPT=false

AI_STOCK_CRITICAL_DAYS=7
AI_STOCK_WARNING_DAYS=14
AI_CREATOR_PROCESSING_MAX=3
AI_CREATOR_RETURN_RATE_MAX=15
AI_CREATOR_DISPUTE_RATE_MAX=5
AI_PRODUCT_MIN_SCORE=40
AI_PRODUCT_ROTATION_MIN=0.5
AI_SALES_ANOMALY_DROP=50
AI_CONVERSION_RATE_MIN=2
AI_CHURN_RISK_THRESHOLD=0.7

AI_LOGGING_ENABLED=true
AI_ALERTS_ENABLED=true
AI_ALERT_CRITICAL_EMAILS=          # TODO: emails alertes critiques (comma-separated)
AI_ALERT_WARNING_EMAILS=           # TODO: emails alertes warnings

AI_CACHE_ENABLED=true
AI_CACHE_TTL=3600
AI_QUEUE=ai-processing

# ── Sentry Error Tracking ───────────────────
SENTRY_LARAVEL_DSN=                 # TODO: CRITIQUE — obtenir sur sentry.io
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_PROFILES_SAMPLE_RATE=0.1
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=
SENTRY_SEND_DEFAULT_PII=false
SENTRY_ENABLE_LOGS=false

# ── Queue Protection ────────────────────────
QUEUE_CIRCUIT_BREAKER_ENABLED=true
QUEUE_CIRCUIT_BREAKER_FAILURE_THRESHOLD=5
QUEUE_CIRCUIT_BREAKER_SUCCESS_THRESHOLD=2
QUEUE_CIRCUIT_BREAKER_TIMEOUT=60
QUEUE_CIRCUIT_BREAKER_TTL=3600

QUEUE_RATE_LIMIT_ENABLED=true
QUEUE_RATE_LIMIT_WEBHOOKS_MAX=100
QUEUE_RATE_LIMIT_WEBHOOKS_WINDOW=60
QUEUE_RATE_LIMIT_EMAILS_MAX=50
QUEUE_RATE_LIMIT_EMAILS_WINDOW=60
QUEUE_RATE_LIMIT_NOTIFICATIONS_MAX=200
QUEUE_RATE_LIMIT_NOTIFICATIONS_WINDOW=60

QUEUE_MONITOR_ENABLED=true
QUEUE_MONITOR_SIZE_WARNING=500
QUEUE_MONITOR_SIZE_CRITICAL=1000
QUEUE_MONITOR_PROCESSING_TIME_WARNING=5.0
QUEUE_MONITOR_PROCESSING_TIME_CRITICAL=10.0
QUEUE_MONITOR_FAILURE_RATE_WARNING=0.05
QUEUE_MONITOR_FAILURE_RATE_CRITICAL=0.10

# ── Alert System ────────────────────────────
SLACK_WEBHOOK_URL=                  # TODO: webhook Slack si utilisé
SLACK_ALERT_CHANNEL=#alerts
SLACK_ALERT_USERNAME="RACINE Monitoring"

ALERT_EMAIL_RECIPIENTS=             # TODO: emails alertes (comma-separated)
ALERTS_ENABLED=true

ALERT_QUEUE_SIZE_WARNING=500
ALERT_QUEUE_SIZE_CRITICAL=1000
ALERT_PROCESSING_TIME_WARNING=5.0
ALERT_PROCESSING_TIME_CRITICAL=10.0
ALERT_FAILURE_RATE_WARNING=0.05
ALERT_FAILURE_RATE_CRITICAL=0.10
ALERT_ERROR_RATE_WARNING=0.01
ALERT_ERROR_RATE_CRITICAL=0.05

# ── Google reCAPTCHA v3 ─────────────────────
RECAPTCHA_ENABLED=true
RECAPTCHA_SITE_KEY=                 # TODO: clé site reCAPTCHA v3
RECAPTCHA_SECRET_KEY=               # TODO: clé secrète reCAPTCHA v3
RECAPTCHA_THRESHOLD=0.5
RECAPTCHA_SKIP_FOR_TESTING=false

# ── Broadcasting (Reverb) ───────────────────
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=                      # TODO: générer avec php artisan reverb:install
REVERB_APP_KEY=                     # TODO: générer avec php artisan reverb:install
REVERB_APP_SECRET=                  # TODO: générer avec php artisan reverb:install

REVERB_HOST=                        # TODO: votre-domaine.com
REVERB_PORT=443
REVERB_SCHEME=https

REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

REVERB_ALLOWED_ORIGINS=             # TODO: https://votre-domaine.com

VITE_REVERB_APP_KEY=                # TODO: même valeur que REVERB_APP_KEY
VITE_REVERB_HOST=                   # TODO: votre-domaine.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https

# ── Sanctum ─────────────────────────────────
SANCTUM_TOKEN_EXPIRATION=10080      # 7 jours

# ── OpenAI / Amira ──────────────────────────
OPENAI_API_KEY=                     # TODO: clé API OpenAI
OPENAI_ORGANIZATION=
AI_DRIVER=openai
AI_MODEL=gpt-4o
AI_TIMEOUT=30

# ── Exchange Rate API ───────────────────────
EXCHANGE_RATE_API_KEY=              # TODO: clé API exchangerate-api.com

# ── Monetbil (Mobile Money XAF) ─────────────
MONETBIL_SERVICE_KEY=               # TODO: clé service Monetbil
MONETBIL_SERVICE_SECRET=            # TODO: secret Monetbil
MONETBIL_NOTIFY_URL=                # TODO: https://votre-domaine.com/payments/monetbil/notify
MONETBIL_RETURN_URL=                # TODO: https://votre-domaine.com/checkout/success

# ── Shipping Costs ──────────────────────────
SHIPPING_HOME_DELIVERY_COST=2000
EOF

echo "✅ ${TARGET_FILE} créé avec succès"
echo ""
echo "Prochaines étapes :"
echo "1. Remplir toutes les variables marquées TODO"
echo "2. Générer APP_KEY : php artisan key:generate --env=production"
echo "3. Vérifier que ${TARGET_FILE} est dans .gitignore"
echo "4. Configurer SENTRY_LARAVEL_DSN (monitoring production)"
echo "5. Remplacer toutes les clés Stripe test par les clés LIVE"
echo "6. Configurer les URLs Monetbil avec le vrai domaine"
echo ""
