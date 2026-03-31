# Installation pilote Windows - Racine POS

## Prérequis

- Backend Laravel accessible (LAN ou internet)
- Base de données et services backend opérationnels
- URL API configurée (`VITE_API_URL`)

## Build installateur

Dans `racine-pos-electron`:

```bash
npm install
npm run electron:build:win
```

Sortie installateur:

- dossier `racine-pos-electron/release/`

## Déploiement client

1. Copier l'installateur sur le poste client.
2. Lancer l'installation NSIS.
3. Démarrer l'application `Racine POS`.
4. Vérifier l'auto-enregistrement terminal.
5. Activer le terminal côté admin (endpoint activation).
6. Connexion opérateur staff.

## Branding installateur

- `productName`: `Racine POS`
- `appId`: `com.racinebyganda.pos`
- Raccourcis bureau/menu démarrer activés.

> Note: pour icône personnalisée installateur, ajouter un fichier `.ico` validé puis renseigner la clé `build.win.icon`.
