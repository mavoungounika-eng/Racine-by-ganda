---
paths:
  - "app/Http/Controllers/Payment*"
  - "app/Http/Controllers/Stripe*"
  - "app/Http/Controllers/Webhook*"
  - "app/Services/Payment*"
  - "app/Services/Stripe*"
  - "tests/Feature/Payment*"
  - "tests/Feature/Stripe*"
---

# Règles — Module Paiements / Stripe

## Package utilisé

- stripe/stripe-php v19

## Conventions Stripe

- Initialiser Stripe via config('services.stripe.secret') — jamais en dur
- Toujours utiliser Stripe\Webhook::constructEvent($payload, $sig, $secret) pour valider les webhooks
- Les webhooks entrants doivent être idempotents (vérifier si déjà traité avant d'agir)
- Loguer chaque événement Stripe reçu dans la table stripe_events ou via Sentry
- En cas d'erreur Stripe, catcher \Stripe\Exception\ApiErrorException

## Variables d'environnement requises

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

## Tests Stripe

- Utiliser les fixtures Stripe (JSON) pour simuler les webhooks en test
- Mocker \Stripe\PaymentIntent::create() etc. avec Mockery
- Ne JAMAIS appeler l'API Stripe réelle en test — toujours mocker
- Tester les cas : paiement réussi, paiement refusé, webhook invalide, webhook dupliqué

## Flow paiement standard

1. Frontend crée un PaymentIntent via l'endpoint Laravel
2. Laravel appelle Stripe\PaymentIntent::create()
3. Frontend confirme le paiement avec Stripe.js
4. Stripe envoie un webhook payment_intent.succeeded
5. Laravel traite le webhook et met à jour la commande
