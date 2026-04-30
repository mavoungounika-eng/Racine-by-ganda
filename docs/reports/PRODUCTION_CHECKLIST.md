# 📋 CHECKLIST PRODUCTION — RACINE BY GANDA

**Date :** 2025-12-XX  
**Version Laravel :** 12  
**PHP :** 8.2+  
**Statut :** ✅ LIVE-READY

---

## 🎯 PRÉ-REQUIS

### Serveur

- [ ] PHP 8.2+ avec extensions requises
- [ ] Composer 2.x
- [ ] Node.js 18+ et npm (pour assets)
- [ ] Base de données MySQL/MariaDB 10.3+ ou PostgreSQL 13+
- [ ] Redis (recommandé pour cache et queues)
- [ ] Certificat SSL/TLS (HTTPS obligatoire)

### Services Externes

- [ ] Compte Stripe configuré avec clés **production** (`pk_live_*`, `sk_live_*`)
- [ ] Webhook Stripe enregistré en production (`whsec_*`)
- [ ] Compte Monetbil configuré avec clés **production**
- [ ] SMTP configuré pour l'envoi d'emails transactionnels
- [ ] Domaine avec certificat SSL valide

---

## 📝 1. CONFIGURATION ENVIRONNEMENT

### 1.1. Variables `.env` Critiques

```env
# ============================================
# APPLICATION (CRITIQUE)
# ============================================
APP_NAME="RACINE BY GANDA"
APP_ENV=production
APP_KEY=base64:... # Générer avec: php artisan key:generate
APP_DEBUG=false  # ⚠️ OBLIGATOIRE : false en production
APP_URL=https://votre-domaine.com

# ============================================
# BASE DE DONNÉES
# ============================================
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=racine_production
DB_USERNAME=votre_user
DB_PASSWORD=votre_password_secure

# ============================================
# CACHE & QUEUE (CRITIQUE)
# ============================================
CACHE_DRIVER=redis  # ou 'file' si Redis non disponible
QUEUE_CONNECTION=redis  # ou 'database' si Redis non disponible
SESSION_DRIVER=redis  # ou 'file'

# ============================================
# REDIS (si utilisé)
# ============================================
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# ============================================
# STRIPE (CRITIQUE - PRODUCTION)
# ============================================
STRIPE_KEY=pk_live_...  # ⚠️ Clé PUBLIQUE production
STRIPE_SECRET=sk_live_...  # ⚠️ Clé SECRÈTE production
STRIPE_WEBHOOK_SECRET=whsec_...  # ⚠️ Secret webhook production

# ============================================
# MONETBIL (CRITIQUE - PRODUCTION)
# ============================================
MONETBIL_SERVICE_KEY=pk_live_...  # ⚠️ Clé production
MONETBIL_SERVICE_SECRET=sk_live_...  # ⚠️ Secret production
MONETBIL_NOTIFY_URL=https://votre-domaine.com/api/webhooks/monetbil
MONETBIL_RETURN_URL=https://votre-domaine.com/checkout/success

# ============================================
# MAIL (CRITIQUE)
# ============================================
MAIL_MAILER=smtp
MAIL_HOST=smtp.votre-provider.com
MAIL_PORT=587
MAIL_USERNAME=votre_email@domaine.com
MAIL_PASSWORD=votre_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@votre-domaine.com
MAIL_FROM_NAME="${APP_NAME}"

# ============================================
# LOGS (RECOMMANDÉ)
# ============================================
LOG_CHANNEL=stack
LOG_STACK=daily,errors
LOG_LEVEL=info
LOG_WEBHOOKS_DAYS=30
LOG_PAYMENTS_DAYS=90
LOG_QUEUE_DAYS=30
LOG_ERRORS_DAYS=90
```

### 1.2. Vérifications Critiques

- [ ] `APP_ENV=production` (pas `local`, pas `testing`)
- [ ] `APP_DEBUG=false` (obligatoire en production)
- [ ] `APP_KEY` généré et unique
- [ ] `APP_URL` pointe vers le domaine de production (HTTPS)
- [ ] Clés Stripe sont **production** (`pk_live_*`, `sk_live_*`)
- [ ] Clés Monetbil sont **production** (pas de test)
- [ ] `STRIPE_WEBHOOK_SECRET` configuré (webhook production)
- [ ] `CACHE_DRIVER` et `QUEUE_CONNECTION` configurés (Redis recommandé)

---

## 🔒 2. SÉCURITÉ

### 2.1. HTTPS

- [ ] Certificat SSL/TLS valide et à jour
- [ ] Redirection HTTP → HTTPS configurée
- [ ] Cookies sécurisés (`SESSION_SECURE_COOKIE=true` si disponible)
- [ ] HSTS activé (recommandé)

### 2.2. CSRF

- [ ] CSRF activé sur toutes les routes web
- [ ] Token CSRF présent dans les formulaires
- [ ] Middleware `validateCsrfTokens` actif

### 2.3. Secrets

- [ ] Aucun secret dans les logs
- [ ] Aucun secret dans le code source
- [ ] `.env` non versionné (dans `.gitignore`)
- [ ] Permissions `.env` : `chmod 600 .env`

---

## 📊 3. LOGS & OBSERVABILITÉ

### 3.1. Canaux de Logs

- [ ] Logs structurés activés
- [ ] Rotation des logs configurée (daily)
- [ ] Canaux dédiés :
  - [ ] `webhooks` (30 jours)
  - [ ] `payments` (90 jours)
  - [ ] `queue` (30 jours)
  - [ ] `errors` (90 jours)
  - [ ] `security` (30 jours)

### 3.2. Monitoring (Préparer)

- [ ] Sentry/Bugsnag configuré (optionnel mais recommandé)
- [ ] Slack/Email alerts préparés (non automatiques)
- [ ] Détection erreurs 5xx configurée
- [ ] Détection jobs en échec configurée

---

## 🔄 4. QUEUE & JOBS

### 4.1. Configuration Queue

- [ ] `QUEUE_CONNECTION` configuré (Redis ou database)
- [ ] Worker queue démarré : `php artisan queue:work`
- [ ] Supervisor configuré pour redémarrer automatiquement (recommandé)

### 4.2. Jobs Critiques Vérifiés

- [ ] `ProcessStripeWebhookEventJob` :
  - [ ] `tries = 3`
  - [ ] `timeout = 60s`
  - [ ] `backoff = [10, 30, 60]`
  - [ ] `ShouldBeUnique` implémenté

- [ ] `ProcessMonetbilCallbackEventJob` :
  - [ ] `tries = 3`
  - [ ] `timeout = 60s`
  - [ ] `backoff = [10, 30, 60]`
  - [ ] `ShouldBeUnique` implémenté

- [ ] Aucun job critique n'est `sync` par erreur

---

## 💰 5. MONÉTISATION — ACTIVATION SAFE

### 5.1. Stripe

- [ ] Compte Stripe en mode **Live** activé
- [ ] Clés production configurées (`pk_live_*`, `sk_live_*`)
- [ ] Webhook production enregistré :
  - [ ] URL : `https://votre-domaine.com/api/webhooks/stripe`
  - [ ] Secret : `whsec_...` configuré dans `.env`
  - [ ] Événements sélectionnés : `payment_intent.*`, `checkout.session.*`
- [ ] Test avec transaction réelle (montant minimal)

### 5.2. Monetbil

- [ ] Compte Monetbil en mode **Production**
- [ ] Clés production configurées
- [ ] URLs production configurées :
  - [ ] `MONETBIL_NOTIFY_URL` : `https://votre-domaine.com/api/webhooks/monetbil`
  - [ ] `MONETBIL_RETURN_URL` : `https://votre-domaine.com/checkout/success`
- [ ] Test avec transaction réelle (montant minimal)

### 5.3. Switch Test → Live

- [ ] Checklist complétée
- [ ] Tests de bout en bout réussis
- [ ] Rollback plan préparé
- [ ] Monitoring activé
- [ ] Équipe alertée

---

## 🚀 6. DÉPLOIEMENT

### 6.1. Commandes Pré-Déploiement

```bash
# Installation dépendances
composer install --optimize-autoloader --no-dev

# Génération clé application
php artisan key:generate

# Migrations
php artisan migrate --force

# Cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissions
chmod -R 755 storage bootstrap/cache
```

### 6.2. Vérifications Post-Déploiement

- [ ] Application accessible (HTTPS)
- [ ] Routes fonctionnelles
- [ ] Base de données connectée
- [ ] Cache fonctionnel
- [ ] Queue worker démarré
- [ ] Logs générés correctement

---

## 🔍 7. TESTS FINAUX

### 7.1. Tests Fonctionnels

- [ ] Inscription utilisateur
- [ ] Connexion utilisateur
- [ ] Création commande
- [ ] Paiement Stripe (test avec carte de test)
- [ ] Paiement Monetbil (test avec transaction réelle)
- [ ] Webhook Stripe reçu et traité
- [ ] Webhook Monetbil reçu et traité
- [ ] Email transactionnel envoyé

### 7.2. Tests Sécurité

- [ ] Routes admin protégées (2FA requis)
- [ ] Routes ERP protégées (permissions)
- [ ] CSRF fonctionnel
- [ ] Rate limiting actif
- [ ] Logs ne contiennent pas de secrets

---

## ✅ VALIDATION FINALE

- [ ] Toutes les cases cochées
- [ ] Tests finaux réussis
- [ ] Monitoring configuré
- [ ] Équipe formée et alertée
- [ ] Documentation accessible

---

## 🚨 EN CAS DE PROBLÈME

### Rollback Rapide

1. Restaurer backup base de données
2. Revenir à version précédente du code
3. Vider cache : `php artisan cache:clear`
4. Redémarrer workers : `php artisan queue:restart`

### Diagnostic

1. Vérifier logs : `storage/logs/errors.log`
2. Vérifier jobs échoués : `php artisan queue:failed`
3. Vérifier webhooks : `storage/logs/webhooks.log`
4. Vérifier paiements : `storage/logs/payments.log`

---

**✅ PROJET PRÊT POUR PRODUCTION**

