# Configuration des clés API Stripe

## Variables d'environnement à configurer

Ajoutez ou modifiez les lignes suivantes dans votre fichier `.env` :

```env
# Clé publique Stripe (utilisée côté client)
STRIPE_KEY=mk_1SeBhQGwrpMPMKOgbxTZMpHc

# Clé secrète Stripe (utilisée côté serveur)
STRIPE_SECRET=mk_1SeBhcGwrpMPMKOgjGhxGdoC

# Activer Stripe
STRIPE_ENABLED=true

# Devise (XAF = Franc CFA)
STRIPE_CURRENCY=XAF

# Secret du webhook
# - En développement : Optionnel (peut être laissé vide pour les tests)
# - En production : OBLIGATOIRE (doit être configuré)
STRIPE_WEBHOOK_SECRET=whsec_cc9c08595d466e1d75482e0b624321dcc8c0d2b7b540415c93c3a0d7d7d76957
```

## ⚠️ Note importante

Les clés fournies commencent par `mk_`, ce qui est inhabituel pour Stripe. Normalement, les clés Stripe commencent par :
- `pk_test_...` ou `pk_live_...` pour les clés publiques
- `sk_test_...` ou `sk_live_...` pour les clés secrètes

**Veuillez vérifier que ces clés sont bien des clés Stripe valides.**

## Après configuration

1. Vider le cache de configuration :
```bash
php artisan config:clear
php artisan cache:clear
```

2. Vérifier la configuration :
```bash
php artisan tinker
>>> config('services.stripe')
```

## Configuration du webhook

### URL du webhook Stripe

L'URL du webhook dans votre application Laravel est :

**Route principale (recommandée) :**
```
https://votre-domaine.com/payment/card/webhook
```

**Route alternative (legacy) :**
```
https://votre-domaine.com/webhooks/stripe
```

### Exemples d'URL selon l'environnement

- **Production** : `https://racine-by-ganda.com/payment/card/webhook`
- **Staging** : `https://staging.racine-by-ganda.com/payment/card/webhook`
- **Local avec ngrok** : `https://abc123.ngrok.io/payment/card/webhook`
- **Local avec Stripe CLI** : `localhost:8000/payment/card/webhook` (voir section "Test en local")

### Créer l'endpoint dans Stripe Dashboard

1. Connectez-vous à votre [Stripe Dashboard](https://dashboard.stripe.com)
2. Allez dans **Developers → Webhooks**
3. Cliquez sur **"Add endpoint"** ou **"Add webhook endpoint"**
4. Dans **"Endpoint URL"**, entrez votre URL complète :
   - Exemple : `https://votre-domaine.com/payment/card/webhook`
5. Sélectionnez les événements à écouter (recommandé) :
   - `checkout.session.completed`
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `payment_method.attached` (optionnel)
6. Cliquez sur **"Add endpoint"**
7. Une fois créé, cliquez sur l'endpoint pour voir les détails
8. Dans la section **"Signing secret"**, cliquez sur **"Reveal"** ou **"Click to reveal"**
9. Copiez le secret (commence par `whsec_...`)
10. Ajoutez-le dans votre `.env` :
    ```env
    STRIPE_WEBHOOK_SECRET=whsec_cc9c08595d466e1d75482e0b624321dcc8c0d2b7b540415c93c3a0d7d7d76957
    ```
    
**✅ Secret déjà configuré :** Le secret du webhook est déjà documenté ci-dessus dans les variables d'environnement.

## Test en local

Pour tester les webhooks en local avec Stripe CLI :

```bash
stripe listen --forward-to localhost:8000/payment/card/webhook
```

Cette commande affichera le secret du webhook (commence par `whsec_...`) que vous pouvez utiliser temporairement dans votre `.env` :

```env
STRIPE_WEBHOOK_SECRET=whsec_... # Secret affiché par Stripe CLI
```

**Note :** En développement, vous pouvez aussi laisser cette variable vide - le webhook fonctionnera quand même (mais sans vérification de signature).
