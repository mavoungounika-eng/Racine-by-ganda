# SKILL — ENV-CHECKLIST
# Usage : invoquer avant tout deploiement ou changement de config

## LES 4 FICHIERS ENV DE RACINE
.env                  → developpement local
.env.testing          → tests PHPUnit (CACHE_STORE=array obligatoire)
.env.production       → production (jamais commite)
.env.production.local → secrets prod (jamais commite)

## VARIABLES CRITIQUES PAR CONTEXTE

### PAIEMENTS
STRIPE_KEY=pk_test_*        → dev/testing
STRIPE_SECRET=sk_test_*     → dev/testing
STRIPE_KEY=pk_live_*        → prod uniquement
STRIPE_SECRET=sk_live_*     → prod uniquement — JAMAIS dans le code

MONETBIL_NOTIFY_URL         → ENCORE localhost en prod — BLOCKER
MONETBIL_RETURN_URL         → ENCORE localhost en prod — BLOCKER

### MONITORING
SENTRY_LARAVEL_DSN          → ABSENT — prod aveugle sans ca
SENTRY_TRACES_SAMPLE_RATE=0.1
SENTRY_ENVIRONMENT=production

### AUTH EXTERNE
RECAPTCHA_SITE_KEY          → OK configure
RECAPTCHA_SECRET_KEY        → OK configure
EXCHANGE_RATE_API_KEY       → OK configure

### QUEUE / CACHE
CACHE_STORE=array           → OBLIGATOIRE dans .env.testing
QUEUE_CONNECTION=sync       → .env.testing
REDIS_HOST=127.0.0.1        → dev/prod

## CHECKLIST AVANT DEPLOIEMENT
- [ ] SENTRY_LARAVEL_DSN configure sur sentry.io
- [ ] MONETBIL_NOTIFY_URL = vrai domaine (pas localhost)
- [ ] MONETBIL_RETURN_URL = vrai domaine (pas localhost)
- [ ] STRIPE_SECRET = sk_live (pas sk_test)
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] .env.production.local dans .gitignore

## REGLE ABSOLUE
Ne jamais copier une variable de .env.testing vers .env.production.
Ne jamais hardcoder une cle API dans le code source.
