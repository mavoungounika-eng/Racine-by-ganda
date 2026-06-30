# Roadmap — Stabilisation Racine by Ganda

## Objectif : mise en production publique

## Phase 1 — Sécurité critique

### Auth 2FA
- [ ] Identifier les tests en échec : php artisan test --filter=TwoFactor
- [ ] Corriger le mock Google2FA dans les tests
- [ ] Vérifier le flow complet : login → TOTP → session
- [ ] Tester les cas limites : code expiré, code invalide, 2FA désactivé

### OAuth providers
- [ ] Identifier les tests en échec : php artisan test --filter=OAuth
- [ ] Corriger le mock Socialite dans les tests
- [ ] Vérifier les callbacks : Google, Facebook
- [ ] Tester : nouvel utilisateur, utilisateur existant, email déjà pris

### Sécurité générale
- [ ] Vérifier toutes les variables d'env sensibles ne sont pas commitées
- [ ] Auditer les permissions par rôle (middleware)
- [ ] Vérifier la protection CSRF sur tous les formulaires
- [ ] Vérifier les règles de validation sur toutes les Form Requests

## Phase 2 — Paiements

### Stripe
- [ ] Identifier les tests en échec : php artisan test --filter=Stripe
- [ ] Corriger les mocks Stripe dans les tests
- [ ] Vérifier le flow complet : PaymentIntent → confirmation → webhook
- [ ] Tester les webhooks : succeeded, failed, refunded
- [ ] Vérifier l'idempotence des webhooks

## Phase 3 — Service Amira

- [ ] Identifier les tests en échec : php artisan test --filter=Amira
- [ ] Corriger le mock OpenAI dans les tests
- [ ] Vérifier la gestion des erreurs et timeouts
- [ ] Tester les cas limites : contexte trop long, modération, quota dépassé

## Phase 4 — POS Electron

- [ ] Vérifier le build Electron
- [ ] Tester le fonctionnement offline
- [ ] Vérifier la communication IPC (preload.js → renderer)
- [ ] Tester les flux de caisse : vente, remboursement, fin de journée

## Phase 5 — Tests et mise en ligne

- [ ] Tous les tests PHPUnit passent
- [ ] Tests Cypress E2E passent
- [ ] Vérifier les performances (N+1 queries, index DB)
- [ ] Configurer Sentry pour la production
- [ ] Préparer les variables d'env de production
- [ ] Déploiement et smoke test

## Journal des décisions

| Date | Module | Décision |
|------|--------|----------|
| — | — | — |
