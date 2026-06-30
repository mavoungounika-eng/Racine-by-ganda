---
description: Déboguer et corriger des failures ou tests skipped PHPUnit
triggers:
  - test qui échoue
  - failure PHPUnit
  - débloquer un test
  - test skipped
  - régression
---
# Skill — Debug PHPUnit

## Ordre de diagnostic
1. Lire le message d'erreur COMPLET
2. Identifier la catégorie
3. Appliquer UN seul fix
4. Vérifier : php -l → run ciblé → run global

## Tableau de fix par catégorie
| Symptôme | Fix |
|-|-|
| Session 2FA | `withSession(['2fa_verified'=>true,'auth_version'=>$user->auth_version])` |
| Socialite OAuth | `Socialite::shouldReceive('driver->user')->andReturn(...)` |
| Stripe webhook | Signer avec secret test + constructEvent() |
| OpenAI / Amira | `OpenAI::fake()` en setUp() |
| Job sync | `Queue::fake()` avant l'action |
| Route 404 | `php artisan route:list | grep NomRoute` |
| Permissions | `Role::firstOrCreate(['name'=>'admin'])` dans setUp() |
| Redis flaky | `redis-cli FLUSHDB` avant le run |

## Commandes
```bash
./vendor/bin/phpunit --filter NomDuTest --testdox 2>&1
./vendor/bin/phpunit 2>&1 | grep -A 5 "FARL\uT E�Error"
redis-cli FLUSHDB && ./vendor/bin/phpunit 2>&1 | tail -5
```

## Règles absolues
- Ne jamais supprimer un test pour faire passer le build
- Ne jamais commenter du code pour masquer une erreur
- Si 2 tentatives échouent → expliquer et demander décision humaine
- STOP immédiat si régression détectée sur la suite globale
