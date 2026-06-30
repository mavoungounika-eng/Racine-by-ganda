# RACINE BY GANDA

Marketplace e-commerce Congo avec gestion créateurs, boutiques physiques (POS), et backend ERP.

## Stack Technique

- **Framework** : Laravel 12.0 (PHP 8.2+)
- **Frontend** : Blade templates (Server-Side Rendering)
- **Base de données** : MySQL
- **Paiements** : Stripe + Mobile Money (Monetbil)
- **Authentification** : OAuth (Google Socialite), 2FA (Google Authenticator), RBAC
- **File d'attente** : Laravel Queues
- **Tests** : PHPUnit (895 tests)

## Installation Locale

```bash
# Dépendances
composer install
npm install

# Configuration
cp .env.example .env
php artisan key:generate

# Base de données
php artisan migrate --seed

# Assets & serveur
npm run dev
php artisan serve
```

## Architecture

**RACINE BY GANDA** est un monolithe Laravel structuré en modules :
- **Auth/RBAC** : Authentification multi-rôles (Client, Créateur, Staff, Admin)
- **ERP** : Gestion stock, production, mouvements
- **CMS** : Pages publiques dynamiques
- **CRM** : Messagerie client-créateur
- **POS** : Point de vente physique
- **Accounting** : Comptabilité & finances
- **Analytics** : Dashboards & BI

Voir [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) pour détails.

## Structure du dépôt

Ce dépôt contient **deux applications npm distinctes** (mini-monorepo) :

| Chemin                   | Rôle                                 | Node / deps clés                          |
|--------------------------|--------------------------------------|-------------------------------------------|
| `./` (racine)            | Laravel 12 + Blade + Vite (web)      | `vue@^3.5`, `bootstrap@^5.3`, `vite@^7`   |
| `./racine-pos-electron/` | Desktop POS (caisses physiques)      | `electron@^28`, `vue@^3.5`, `vue-router`  |

> **Important** : `electron` n'est **PAS** une dépendance du `package.json` racine.
> Elle est pinée **uniquement** dans `racine-pos-electron/package.json` pour éviter
> les collisions de versions et ne pas télécharger les binaires Electron (~200 Mo)
> lors d'un `npm install` côté serveur web.

Installation séparée :

```bash
# Web (racine)
npm install

# POS desktop (sous-package)
cd racine-pos-electron && npm install
```

Voir [racine-pos-electron/LANCER_POS.md](./racine-pos-electron/LANCER_POS.md) pour le lancement du POS et [docs/pos/](./docs/pos/) pour la doc fonctionnelle.

## Documentation

- **Architecture** : [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md)
- **Déploiement** : [docs/DEPLOYMENT.md](./docs/DEPLOYMENT.md)
- **Sécurité/RBAC** : [docs/security/](./docs/security/)
- **Paiements** : [docs/payments/](./docs/payments/)
- **Guides** : [docs/guides/](./docs/guides/)

## Commandes Essentielles

```bash
# Tests
php artisan test

# Queue workers
php artisan queue:work

# POS (Point de Vente)
# Accessible via /admin/pos après login admin

# Scripts utilitaires
php create-admin.php          # Créer compte admin
php disable-2fa.php           # Désactiver 2FA utilisateur
php test_accounts_login.php   # Tester comptes test
```

## État du Projet

**STATUT : PRODUCTION-READY**

- ✅ Backend DONE (RBAC verrouillé, Auth complet, Paiements opérationnels)
- ✅ Tests CI (895 tests couvrant core features)
- ❄️ **FEATURE FREEZE** actif - Voir [FEATURE_FREEZE.md](./FEATURE_FREEZE.md)

## Production

Pour déploiement production, consulter [docs/DEPLOYMENT.md](./docs/DEPLOYMENT.md).

## Licence

Propriétaire - RACINE BY GANDA © 2026
# test
