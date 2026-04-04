---
paths:
  - "app/Http/Controllers/Auth/**"
  - "app/Services/Auth*"
  - "app/Http/Requests/Auth*"
  - "tests/Feature/Auth*"
  - "tests/Unit/Auth*"
---

# Règles — Module Auth

## Packages utilisés

- laravel/sanctum — tokens API et sessions SPA
- laravel/socialite — OAuth (Google, Facebook, etc.)
- pragmarx/google2fa + pragmarx/google2fa-laravel — TOTP 2FA
- bacon/bacon-qr-code — génération QR pour setup 2FA
- anhskohbo/no-captcha + google/recaptcha — protection anti-bot

## Conventions Auth

- Le flow 2FA : login standard → vérification TOTP → session complète
- Stocker le secret 2FA chiffré dans la colonne two_factor_secret (users)
- Utiliser Google2FA::verifyKey($secret, $code) pour la validation TOTP
- Les providers OAuth sont configurés dans config/services.php
- Socialite callback : toujours gérer le cas user not found → créer ou lier le compte

## Tests Auth

- Mocker Google2FA en test pour éviter la génération réelle de codes TOTP
- Mocker Socialite::driver() pour les tests OAuth
- Tester les cas : login valide, 2FA invalide, token expiré, provider OAuth refusé
- Utiliser RefreshDatabase sur tous les tests d'authentification

## Problèmes connus

- Les tests 2FA échouent probablement sur la vérification du code TOTP — vérifier le mock
- Les tests OAuth échouent probablement sur le callback Socialite — vérifier la config de test
