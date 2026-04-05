# Variables d'environnement Stripe

## Configuration requise

Ajoutez ces variables dans votre fichier `.env` :

```env
# Stripe Configuration
# Récupérer ces clés depuis https://dashboard.stripe.com/apikeys
# pk_* = Publishable Key (utilisée côté frontend avec Stripe.js)
# sk_* = Secret Key (utilisée côté backend pour créer des paiements)
STRIPE_KEY= mk_1SeBhQGwrpMPMKOgbxTZMpHc
STRIPE_SECRET=  & mk_1SeBhcGwrpMPMKOgjGhxGdoC
STRIPE_WEBHOOK_SECRET= 
STRIPE_CURRENCY=XAF
```

## Où récupérer les clés

### 1. Publishable Key (`STRIPE_KEY`)
- Dashboard Stripe → **Developers** → **API keys**
- Format : `pk_test_...` (test) ou `pk_live_...` (production)
- Utilisée côté frontend avec Stripe.js

### 2. Secret Key (`STRIPE_SECRET`)
- Dashboard Stripe → **Developers** → **API keys**
- Format : `sk_test_...` (test) ou `sk_live_...` (production)
- Utilisée côté backend pour créer des sessions Checkout

### 3. Webhook Secret (`STRIPE_WEBHOOK_SECRET`)
- **Production** : Dashboard Stripe → **Developers** → **Webhooks** → Signing secret
- **Développement** : Stripe CLI (`stripe listen --forward-to localhost:8000/payment/card/webhook`)
- Format : `whsec_...`
- Nécessaire pour vérifier l'authenticité des webhooks en production

## Configuration dans `config/services.php`

Les variables sont mappées automatiquement :

```php
'stripe' => [
    'key' => env('STRIPE_KEY'),              // pk_*
    'secret' => env('STRIPE_SECRET'),        // sk_*
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'), // whsec_*
    'currency' => env('STRIPE_CURRENCY', 'XAF'), // XAF = Franc CFA (CEMAC)
],
```

## Utilisation dans le code

- **Frontend** : `config('services.stripe.key')` → Clé publique
- **Backend** : `config('services.stripe.secret')` → Clé secrète
- **Webhook** : `config('services.stripe.webhook_secret')` → Secret webhook

## Documentation complète

Voir `docs/payments/stripe.md` pour plus de détails.

