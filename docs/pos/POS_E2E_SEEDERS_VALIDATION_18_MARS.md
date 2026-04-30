# RAPPORT POS E2E - Comptes seeders (18 Mars 2026)

## Comptes seeders verifies

Source: `database/seeders/TestUsersSeeder.php`

- `admin@racine.test` / `Admin123!`
- `staff@racine.test` / `Staff123!`

## Scenario execute

1. `POST /api/pos/register` avec machine UUID unique
2. `POST /api/pos/auth/operator/login` avec `staff@racine.test`
3. `GET /api/pos/auth/operator/me`
4. `POST /api/pos/auth/operator/logout`

## Resultats

- `register` => `201` JSON success
- `login` => `200` JSON success
- `me` => `401` JSON `UNAUTHENTICATED`
- `logout` => `401` JSON `UNAUTHENTICATED`

## Conclusion technique

Les credentials seeders sont valides (login OK).

Le blocage restant est structurel sur `me/logout`:

- routes sous `pos.auth` (exige Bearer device JWT via `PosDeviceAuth`)
- puis `auth:sanctum` (exige Bearer operator token)

Or un seul header `Authorization: Bearer ...` ne peut pas porter les deux tokens en meme temps.
Donc `me/logout` deviennent inatteignables dans la forme actuelle.

## Recommandation de correction (prochaine etape)

Choisir un seul modele d'auth pour `me/logout`:

1. soit `auth:sanctum` uniquement (retirer `pos.auth` de ces 2 routes),
2. soit garder `pos.auth` et authentifier l'operateur via `X-Operator-Token` sans `auth:sanctum`.
