# RAPPORT VALIDATION POS - 18 Mars 2026

## Conclusion 1 - Cause racine confirmee

Le retour vers la page de connexion web venait d'un ancien entrypoint Electron (`racine-pos-electron/main.js`) qui chargeait directement:

- `http://127.0.0.1:8000/login?context=equipe&intended=/pos-terminal`

Donc selon la methode de lancement, l'application pouvait bypass le frontend POS Vue et ouvrir le login web.

## Conclusion 2 - Correctifs appliques

Fichiers modifies:

- `racine-pos-electron/main.js`
  - converti en wrapper compatible qui delegue vers `electron/main.js`
- `racine-pos-electron/src/stores/auth.js`
  - correction du parsing reponse login POS (`res.success` / `res.data.operator` / `res.data.token`)
- `racine-pos-electron/src/api/posClient.js`
  - `BASE_URL` lit maintenant `VITE_API_URL` (fallback `http://127.0.0.1:8000`)
- `racine-pos-electron/src/main.js`
  - suppression import duplique (`useStockStore`)
- `racine-pos-electron/package.json`
  - ajout script `start` -> `npm run electron:dev`
- `racine-pos-electron/run.bat`
  - suppression chemin hardcode, lancement local via `%~dp0`
- `racine-pos-electron/start-pos.bat`
  - aligne sur `npm run electron:dev`
- `racine-pos-electron/LANCER_POS.md`
  - documentation de lancement mise a jour

## Conclusion 3 - Migrations

Aucune migration additionnelle necessaire pour ce probleme.

La panne etait liee au lancement/entrypoint Electron + integration frontend/API, pas au schema DB.

## Validation commandes

- `npm run` dans `racine-pos-electron` confirme la presence de:
  - `start`
  - `electron:dev`

## Procedure recommandee de lancement

1. Terminal backend:
   - `cd ~/projects/racine-backend`
   - `php artisan serve`
2. Terminal POS:
   - `cd ~/projects/racine-backend/racine-pos-electron`
   - `npm run electron:dev`

## Point d'attention

Le repo contient des fichiers non suivis anormaux hors POS (noms type `Admin Test,`, `1`, etc.).
Ils n'ont pas ete modifies dans ce correctif.
