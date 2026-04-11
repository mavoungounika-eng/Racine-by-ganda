---
paths:
  - "tests/**"
  - "phpunit.xml"
---
# Règles — Tests PHPUnit

## Référence actuelle (4 avril 2026)
Tests: 895 | Failures: 3 | Skipped: 19 | Incomplete: 0

Toute régression = STOP immédiat avant toute autre action.

## Fichiers PROTÉGÉS — NE JAMAIS MODIFIER
- tests/Feature/Ai/
- tests/Feature/Pos/
- tests/Feature/Erp/
- tests/Feature/SaaSPur/
- tests/Feature/ERPProduction/
- tests/Feature/Currency/
- tests/Feature/Crm/
- tests/Feature/Auth/LogoutTest.php
- tests/Feature/Auth/LoginRedirectTest.php
- tests/Feature/Auth/DashboardAccessTest.php

## Conventions tests
- RefreshDatabase sur tous les tests Feature
- Toujours utiliser Role::firstOrCreate() — jamais Role::find() sans vérification
- Queue::fake() si le test déclenche des jobs (évite exécution sync non voulue)
- withSession(["2fa_verified" => true]) pour les routes admin/ERP protégées
- Utiliser Python pour éditer les fichiers de test (pas sed — UTF-8 WSL)

## Pattern fix test skipped
| Raison | Fix |
|---|---|
| Session 2FA | withSession(["2fa_verified" => true, "auth_version" => $user->auth_version]) |
| Model manquant | Créer model + migration + php artisan migrate |
| Route inexistante | Vérifier php artisan route:list avant d asserter |
| Job en sync | Queue::fake() au début du test |
| Permissions | Role::firstOrCreate() + seed minimal dans setUp() |

## Vérification obligatoire après chaque modification
1. php -l FICHIER_MODIFIE
2. ./vendor/bin/phpunit --filter NomDeLaSuite --testdox 2>&1 | tail -15
3. ./vendor/bin/phpunit 2>&1 | tail -3

## Failures connues (préexistantes)
1. SecurityTest::rate_limiting_is_configured_on_checkout → 302 au lieu de 429
2. ErpGlobalTest::test_erp_cache_ttl_respected → false au lieu de true
3. FinancialBIServiceTest::test_churn_rate_calculated_correctly → 25.0 au lieu de 20
