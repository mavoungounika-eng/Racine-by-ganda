# 🔑 API Keys — RACINE BY GANDA

> **⚠️ Ce fichier ne contient que les noms des variables. Ne jamais commiter les vraies valeurs.**  
> Copier dans `.env` et remplir avec les vraies clés.

---

## 🔴 Critiques (bloquantes en production)

| Variable `.env` | Service | Où l'obtenir |
|-----------------|---------|-------------|
| `APP_KEY` | Laravel encryption | `php artisan key:generate` |
| `STRIPE_KEY` | Stripe — clé publique (`pk_live_...`) | dashboard.stripe.com → API Keys |
| `STRIPE_SECRET` | Stripe — clé secrète (`sk_live_...`) | dashboard.stripe.com → API Keys |
| `STRIPE_WEBHOOK_SECRET` | Stripe — signature webhooks (`whsec_...`) | Stripe → Webhooks → Signing secret |
| `MONETBIL_SERVICE_KEY` | Monetbil — HMAC signature POS ⚠️ | Dashboard Monetbil → Service Key |
| `DB_PASSWORD` | MySQL production | `openssl rand -base64 32` |
| `REDIS_PASSWORD` | Redis production | `openssl rand -base64 32` |

---

## 🟠 Importants (fonctionnalités dégradées sinon)

| Variable `.env` | Service | Où l'obtenir |
|-----------------|---------|-------------|
| `GOOGLE_CLIENT_ID` | OAuth Google | console.cloud.google.com → Credentials |
| `GOOGLE_CLIENT_SECRET` | OAuth Google | Idem |
| `MAIL_PASSWORD` | SendGrid SMTP (`SG.xxx`) | app.sendgrid.com → API Keys |
| `SENTRY_LARAVEL_DSN` | Sentry monitoring erreurs | sentry.io → Project → Client Keys → DSN |
| `SLACK_WEBHOOK_URL` | Alertes production | Slack → Apps → Incoming Webhooks → URL |

---

## 🟡 Optionnels

| Variable `.env` | Service |
|-----------------|---------|
| `AWS_ACCESS_KEY_ID` | Stockage fichiers S3 |
| `AWS_SECRET_ACCESS_KEY` | Stockage fichiers S3 |
| `PUSHER_APP_ID` / `PUSHER_APP_KEY` / `PUSHER_APP_SECRET` | WebSockets temps réel |

---

## ⚠️ Note critique — MONETBIL_SERVICE_KEY

Ajoutée le 25 fév. 2026 (Fix 3 POS). Sans cette variable en production, **tous les callbacks de confirmation de paiement mobile POS seront rejetés (403)**.

```env
# Exemple de format attendu dans .env
MONETBIL_SERVICE_KEY=votre_cle_secrete_monetbil
```

---

## Checklist avant déploiement

- [ ] `APP_KEY` généré (`php artisan key:generate`)
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Toutes les clés 🔴 renseignées
- [ ] `MONETBIL_SERVICE_KEY` renseigné
- [ ] `php artisan migrate` exécuté en staging (table `pos_offline_queue`)
- [ ] HTTPS configuré (certificats SSL valides)
