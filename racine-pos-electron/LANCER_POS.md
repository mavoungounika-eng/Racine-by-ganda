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
