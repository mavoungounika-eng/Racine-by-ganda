# RACINE POS - Local launch

## Required

1. Backend running on `http://127.0.0.1:8000`
2. Database up (MySQL)
3. Dependencies installed in `racine-pos-electron`

## Start (WSL/Linux terminal)

```bash
cd ~/projects/racine-backend
php artisan serve
```

In another terminal:

```bash
cd ~/projects/racine-backend/racine-pos-electron
npm install
npm run electron:dev
```

## Start (Windows)

Double-click:

- `racine-pos-electron/run.bat`

or run:

```bat
cd /d <repo>\racine-pos-electron
npm run electron:dev
```

## Notes

- Do not use old hardcoded paths like `C:\laravel_projects\...`.
- `npm start` now maps to `electron:dev`.
- POS UI must open the Vue app (`#/login`), not backend `/login`.

## Version policy (Electron + renderer)

Le POS est un **sous-package npm isolé**. Ses versions ne doivent PAS être
alignées avec celles du `package.json` racine (Laravel web) :

| Paquet          | Version pinée | Où ?                                   |
|-----------------|---------------|----------------------------------------|
| `electron`      | `^28.0.0`     | `racine-pos-electron/package.json` UNIQUEMENT |
| `electron-builder` | `^24.13.3` | idem                                   |
| `vue`           | `^3.5.30`     | idem (indépendant du web)              |
| `vite`          | (via plugin)  | idem                                   |

### Règles à respecter

1. **Ne JAMAIS ajouter `electron` à la `devDependencies` racine.** Cela déclencherait
   le téléchargement des binaires Electron (~200 Mo) sur les serveurs Laravel en prod
   et créerait un conflit de versions avec le POS.
2. **Mise à jour d'Electron** : toujours dans `racine-pos-electron/` uniquement.
   Tester la compat avec `electron-builder` avant de merge.
3. **Mise à jour de Vue** : le POS et le web peuvent diverger. Pas d'obligation
   d'alignement — chaque app a son cycle.
4. **Node.js requis** : côté POS, Node 18+ (aligné avec Electron 28 runtime).

### Vérification rapide

Depuis la racine du dépôt :

```bash
# Ne doit RIEN retourner :
grep -E '"electron"\s*:' package.json

# Doit retourner la version pinée :
grep -E '"electron"\s*:' racine-pos-electron/package.json
```
