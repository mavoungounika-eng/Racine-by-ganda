# Configuration Stripe

Ce guide explique comment configurer Stripe pour les paiements par carte bancaire dans RACINE BY GANDA.

## 📋 Prérequis

- Un compte Stripe (créer sur [https://stripe.com](https://stripe.com))
- Accès au Dashboard Stripe ([https://dashboard.stripe.com](https://dashboard.stripe.com))

## 🔑 Clés API Stripe

### 1. Récupérer les clés API

1. Connectez-vous au [Dashboard Stripe](https://dashboard.stripe.com)
2. Allez dans **Developers** → **API keys**
3. Vous trouverez :
   - **Publishable key** (`pk_test_...` ou `pk_live_...`) → Clé publique pour le frontend
   - **Secret key** (`sk_test_...` ou `sk_live_...`) → Clé secrète pour le backend

### 2. Récupérer le Webhook Secret

Le webhook secret (`whsec_...`) est nécessaire pour vérifier l'authenticité des webhooks Stripe en production.

#### Option A : Via Stripe Dashboard (Production)

1. Allez dans **Developers** → **Webhooks**
2. Cliquez sur **Add endpoint**
3. Entrez l'URL de votre webhook : `https://votre-domaine.com/payment/card/webhook`
4. Sélectionnez les événements à écouter (ex: `checkout.session.completed`, `payment_intent.succeeded`)
5. Cliquez sur **Add endpoint**
6. Copiez le **Signing secret** (`whsec_...`) qui s'affiche

#### Option B : Via Stripe CLI (Développement local)

1. Installez [Stripe CLI](https://stripe.com/docs/stripe-cli)
2. Connectez-vous : `stripe login`
3. Écoutez les webhooks localement :
   ```bash
   stripe listen --forward-to localhost:8000/payment/card/webhook
   ```
4. Stripe CLI affichera un `whsec_...` → utilisez-le dans votre `.env`

## ⚙️ Configuration

### 1. Variables d'environnement

Ajoutez les clés Stripe dans votre fichier `.env` :

```env
# Stripe Configuration
STRIPE_KEY=pk_test_...          # Publishable Key (frontend)
STRIPE_SECRET=sk_test_...       # Secret Key (backend)
STRIPE_WEBHOOK_SECRET=whsec_... # Webhook Secret (production)
STRIPE_CURRENCY=XAF             # Devise (XAF = Franc CFA CEMAC)
```

### 2. Vérification de la configuration

La configuration est exposée via `config/services.php` :

```php
'stripe' => [
    'key' => env('STRIPE_KEY'),              // pk_*
    'secret' => env('STRIPE_SECRET'),        // sk_*
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'), // whsec_*
    'currency' => env('STRIPE_CURRENCY', 'XAF'), // XAF = Franc CFA (CEMAC)
],
```

## 🧪 Tests en local avec Stripe CLI

### 1. Installer Stripe CLI

```bash
# macOS
brew install stripe/stripe-cli/stripe

# Windows (via Scoop)
scoop install stripe

# Linux
# Télécharger depuis https://github.com/stripe/stripe-cli/releases
```

### 2. Écouter les webhooks localement

```bash
# Se connecter à Stripe
stripe login

# Écouter les webhooks et les forwarder vers votre app locale
stripe listen --forward-to localhost:8000/payment/card/webhook
```

Stripe CLI affichera un `whsec_...` → copiez-le dans votre `.env` :

```env
STRIPE_WEBHOOK_SECRET=whsec_... # Secret affiché par Stripe CLI
```

### 3. Déclencher des événements de test

Dans un autre terminal :

```bash
# Déclencher un événement checkout.session.completed
stripe trigger checkout.session.completed
```

## 🔒 Sécurité Webhook (Production)

En environnement **production**, le webhook Stripe est sécurisé :

- ✅ **Signature obligatoire** : Toute requête sans `Stripe-Signature` → **401**
- ✅ **Signature invalide** → **401**
- ✅ **Payload invalide** → **400**
- ✅ **Autres erreurs** → **500**

### Endpoint Webhook

**Endpoint unique :** `POST /payment/card/webhook`

> ⚠️ **Note :** L'ancien endpoint `/webhooks/stripe` est redirigé vers `/payment/card/webhook` pour compatibilité. Il sera supprimé dans une future version.

### Idempotence & Protection Race Conditions

Le webhook Stripe est **idempotent** et protégé contre les race conditions :

- ✅ **Table `stripe_webhook_events`** : Tous les événements sont trackés avec `event_id` unique
- ✅ **Insert-first** : Tentative de création atomique, duplicate key = déjà traité
- ✅ **Transaction DB** : Toute la logique dans `DB::transaction()`
- ✅ **Pessimistic Lock** : `Payment::lockForUpdate()` verrouille la ligne pendant le traitement
- ✅ **Statuts** : `received`, `processed`, `ignored`, `failed`

**Comportement :**
- Si un `event.id` est reçu deux fois → deuxième appel retourne 200 immédiatement (idempotent)
- Si un Payment est déjà `paid` → événement marqué `ignored`
- Protection contre les doubles paiements et les race conditions

### Codes de réponse

| Code | Signification |
|------|---------------|
| 200 | Webhook traité avec succès |
| 400 | Payload invalide |
| 401 | Signature manquante ou invalide |
| 500 | Erreur de traitement |

### Logs structurés

Tous les webhooks sont loggés avec :
- `ip` : Adresse IP de la requête
- `route` : URL complète du webhook
- `user_agent` : User-Agent de la requête
- `reason` : Raison du rejet (si applicable)
- `error` : Message d'erreur (si applicable)

## 🚀 Utilisation

### Frontend (Stripe.js)

```javascript
// Utiliser la clé publique (STRIPE_KEY)
const stripe = Stripe('pk_test_...');
```

### Backend (Service)

Le service `CardPaymentService` utilise automatiquement :
- `config('services.stripe.secret')` pour créer les sessions Checkout
- `config('services.stripe.webhook_secret')` pour vérifier les webhooks

## 📝 Notes importantes

1. **Ne jamais logger les secrets** (`sk_*`, `whsec_*`) dans les logs
2. **Utiliser des clés de test** (`test_`) en développement
3. **Utiliser des clés live** (`live_`) uniquement en production
4. **Le webhook secret est différent** entre test et production
5. **En développement**, la signature est optionnelle (mais recommandée)

## 🔗 Ressources

- [Documentation Stripe](https://stripe.com/docs)
- [Stripe Dashboard](https://dashboard.stripe.com)
- [Stripe CLI](https://stripe.com/docs/stripe-cli)
- [Webhooks Stripe](https://stripe.com/docs/webhooks)

