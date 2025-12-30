# 🔐 CODES D'ACCÈS - PAGES D'AUTHENTIFICATION
## RACINE BY GANDA - Liste Complète des Routes

**Date :** 2025  
**Base URL :** `http://localhost` (ou votre domaine)

---

## 📋 TABLE DES MATIÈRES

1. [Hub d'Authentification](#1-hub-dauthentification)
2. [Connexion](#2-connexion)
3. [Inscription](#3-inscription)
4. [Réinitialisation de Mot de Passe](#4-réinitialisation-de-mot-de-passe)
5. [Déconnexion](#5-déconnexion)
6. [Authentification à Deux Facteurs (2FA)](#6-authentification-à-deux-facteurs-2fa)
7. [Dashboards par Rôle](#7-dashboards-par-rôle)
8. [Profil Utilisateur](#8-profil-utilisateur)
9. [Pages Publiques](#9-pages-publiques)
10. [Administration](#10-administration)

---

## 1. HUB D'AUTHENTIFICATION

### Page Hub (Choix Espace)

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/auth` | GET | `auth.hub` | - | Page de choix entre Espace Boutique et Espace Équipe |

**Comportement :**
- Si **connecté** → Redirige vers le dashboard selon le rôle
- Si **non connecté** → Affiche le hub avec deux cartes

**Utilisation dans le code :**
```php
route('auth.hub')
// ou
url('/auth')
```

---

## 2. CONNEXION

### Formulaire de Connexion

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/login` | GET | `login` | `guest` | Formulaire de connexion unifié |
| `/login?context=boutique` | GET | `login` | `guest` | Connexion avec contexte boutique |
| `/login?context=equipe` | GET | `login` | `guest` | Connexion avec contexte équipe |
| `/login` | POST | `login.post` | `guest` | Traitement de la connexion |

**Comportement :**
- Si **connecté** → Redirige vers le dashboard selon le rôle
- Si **non connecté** → Affiche le formulaire de login
- Le paramètre `context` adapte l'UI (badge, titres, sous-titres)

**Utilisation dans le code :**
```php
route('login')
route('login', ['context' => 'boutique'])
route('login', ['context' => 'equipe'])
route('login.post')
```

---

## 3. INSCRIPTION

### Formulaire d'Inscription

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/register` | GET | `register` | `guest` | Formulaire d'inscription (Client & Créateur) |
| `/register` | POST | `register.post` | `guest` | Traitement de l'inscription |

**Comportement :**
- Permet de créer un compte **Client** ou **Créateur**
- Connexion automatique après inscription
- Redirection vers le dashboard selon le type de compte

**Utilisation dans le code :**
```php
route('register')
route('register.post')
```

---

## 4. RÉINITIALISATION DE MOT DE PASSE

### Demande de Réinitialisation

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/password/forgot` | GET | `password.request` | `guest` | Formulaire "Mot de passe oublié" |
| `/password/email` | POST | `password.email` | `guest` | Envoi de l'email de réinitialisation |

### Réinitialisation

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/password/reset/{token}` | GET | `password.reset` | `guest` | Formulaire de réinitialisation (avec token) |
| `/password/reset` | POST | `password.update` | `guest` | Traitement de la réinitialisation |

**Comportement :**
- Token valide 60 minutes
- Throttle : 60 secondes entre les demandes

**Utilisation dans le code :**
```php
route('password.request')
route('password.email')
route('password.reset', ['token' => $token])
route('password.update')
```

---

## 5. DÉCONNEXION

### Déconnexion

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/logout` | POST | `logout` | `auth` | Déconnexion de l'utilisateur |

**Comportement :**
- Invalide la session
- Régénère le token CSRF
- Redirige vers la page d'accueil

**Utilisation dans le code :**
```php
route('logout')
```

**Note :** Utiliser un formulaire POST avec token CSRF :
```blade
<form method="POST" action="{{ route('logout') }}">
    @csrf
    <button type="submit">Déconnexion</button>
</form>
```

---

## 6. AUTHENTIFICATION À DEUX FACTEURS (2FA)

### Challenge 2FA (Public - lors de la connexion)

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/2fa/challenge` | GET | `2fa.challenge` | - | Page de challenge 2FA (code à saisir) |
| `/2fa/verify` | POST | `2fa.verify` | - | Vérification du code 2FA |

### Gestion 2FA (Authentifié)

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/2fa/setup` | GET | `2fa.setup` | `auth` | Configuration initiale du 2FA (QR Code) |
| `/2fa/confirm` | POST | `2fa.confirm` | `auth` | Confirmation de l'activation 2FA |
| `/2fa/manage` | GET | `2fa.manage` | `auth` | Page de gestion du 2FA |
| `/2fa/disable` | POST | `2fa.disable` | `auth` | Désactivation du 2FA |
| `/2fa/recovery-codes/regenerate` | POST | `2fa.recovery-codes.regenerate` | `auth` | Régénération des codes de récupération |

**Utilisation dans le code :**
```php
route('2fa.challenge')
route('2fa.verify')
route('2fa.setup')
route('2fa.confirm')
route('2fa.manage')
route('2fa.disable')
route('2fa.recovery-codes.regenerate')
```

---

## 7. DASHBOARDS PAR RÔLE

### Dashboard Client

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/compte` | GET | `account.dashboard` | `auth` | Dashboard client (commandes, profil, etc.) |

### Dashboard Créateur

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/atelier-creator` | GET | `creator.dashboard` | `auth`, `creator` | Dashboard créateur (produits, statistiques) |

### Dashboard Staff

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/staff/dashboard` | GET | `staff.dashboard` | `auth`, `staff` | Dashboard staff (outils internes) |

### Dashboard Admin

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/admin/dashboard` | GET | `admin.dashboard` | `auth`, `admin` | Dashboard administrateur (gestion complète) |

**Redirections automatiques après connexion :**
- `client` → `/compte`
- `createur` / `creator` → `/atelier-creator`
- `staff` → `/staff/dashboard`
- `admin` / `super_admin` → `/admin/dashboard`

**Utilisation dans le code :**
```php
route('account.dashboard')
route('creator.dashboard')
route('staff.dashboard')
route('admin.dashboard')
```

---

## 8. PROFIL UTILISATEUR

### Profil

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/profil` | GET | `profile.index` | `auth` | Page de profil utilisateur |
| `/profil` | PUT | `profile.update` | `auth` | Mise à jour du profil |
| `/profil/password` | PUT | `profile.password` | `auth` | Changement de mot de passe |

### Commandes

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/profil/commandes` | GET | `profile.orders` | `auth` | Liste des commandes |

### Adresses

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/profil/adresses` | GET | `profile.addresses` | `auth` | Liste des adresses |
| `/profil/adresses` | POST | `profile.addresses.store` | `auth` | Ajout d'une adresse |
| `/profil/adresses/{address}` | DELETE | `profile.addresses.delete` | `auth` | Suppression d'une adresse |

### Fidélité

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/profil/fidelite` | GET | `profile.loyalty` | `auth` | Points de fidélité |

**Utilisation dans le code :**
```php
route('profile.index')
route('profile.update')
route('profile.password')
route('profile.orders')
route('profile.addresses')
route('profile.addresses.store')
route('profile.addresses.delete', ['address' => $address])
route('profile.loyalty')
```

---

## 9. PAGES PUBLIQUES

### Accueil et Navigation

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/` | GET | `frontend.home` | - | Page d'accueil |
| `/boutique` | GET | `frontend.shop` | - | Catalogue produits |
| `/search` | GET | `frontend.search` | - | Recherche produits |
| `/showroom` | GET | `frontend.showroom` | - | Showroom |
| `/atelier` | GET | `frontend.atelier` | - | Atelier |
| `/contact` | GET | `frontend.contact` | - | Contact |
| `/produit/{id}` | GET | `frontend.product` | - | Fiche produit |
| `/createurs` | GET | `frontend.creators` | - | Liste des créateurs |

### Pages Informatives

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/aide` | GET | `frontend.help` | - | Aide |
| `/livraison` | GET | `frontend.shipping` | - | Livraison |
| `/retours-echanges` | GET | `frontend.returns` | - | Retours et échanges |
| `/cgv` | GET | `frontend.terms` | - | Conditions générales |
| `/confidentialite` | GET | `frontend.privacy` | - | Confidentialité |
| `/a-propos` | GET | `frontend.about` | - | À propos |

### Panier et Checkout

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/cart` | GET | `cart.index` | - | Panier |
| `/cart/add` | POST | `cart.add` | - | Ajout au panier |
| `/cart/update` | POST | `cart.update` | - | Mise à jour panier |
| `/cart/remove` | POST | `cart.remove` | - | Suppression du panier |
| `/checkout` | GET | `checkout` | - | Page de checkout |
| `/checkout/place-order` | POST | `checkout.place` | - | Création de commande |
| `/checkout/success` | GET | `checkout.success` | - | Succès de commande |

---

## 10. ADMINISTRATION

### Dashboard Admin

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/admin/dashboard` | GET | `admin.dashboard` | `auth`, `admin` | Dashboard administrateur |
| `/admin/logout` | POST | `admin.logout` | `auth`, `admin` | Déconnexion admin |

### Gestion Utilisateurs

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/admin/users` | GET | `admin.users.index` | `auth`, `admin` | Liste des utilisateurs |
| `/admin/users/create` | GET | `admin.users.create` | `auth`, `admin` | Création utilisateur |
| `/admin/users` | POST | `admin.users.store` | `auth`, `admin` | Stockage utilisateur |
| `/admin/users/{user}` | GET | `admin.users.show` | `auth`, `admin` | Détails utilisateur |
| `/admin/users/{user}/edit` | GET | `admin.users.edit` | `auth`, `admin` | Édition utilisateur |
| `/admin/users/{user}` | PUT | `admin.users.update` | `auth`, `admin` | Mise à jour utilisateur |
| `/admin/users/{user}` | DELETE | `admin.users.destroy` | `auth`, `admin` | Suppression utilisateur |

### Gestion Rôles

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/admin/roles` | GET | `admin.roles.index` | `auth`, `admin` | Liste des rôles |
| `/admin/roles/create` | GET | `admin.roles.create` | `auth`, `admin` | Création rôle |
| `/admin/roles` | POST | `admin.roles.store` | `auth`, `admin` | Stockage rôle |
| `/admin/roles/{role}/edit` | GET | `admin.roles.edit` | `auth`, `admin` | Édition rôle |
| `/admin/roles/{role}` | PUT | `admin.roles.update` | `auth`, `admin` | Mise à jour rôle |
| `/admin/roles/{role}` | DELETE | `admin.roles.destroy` | `auth`, `admin` | Suppression rôle |

### Gestion Produits

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/admin/products` | GET | `admin.products.index` | `auth`, `admin` | Liste des produits |
| `/admin/products/create` | GET | `admin.products.create` | `auth`, `admin` | Création produit |
| `/admin/products` | POST | `admin.products.store` | `auth`, `admin` | Stockage produit |
| `/admin/products/{product}` | GET | `admin.products.show` | `auth`, `admin` | Détails produit |
| `/admin/products/{product}/edit` | GET | `admin.products.edit` | `auth`, `admin` | Édition produit |
| `/admin/products/{product}` | PUT | `admin.products.update` | `auth`, `admin` | Mise à jour produit |
| `/admin/products/{product}` | DELETE | `admin.products.destroy` | `auth`, `admin` | Suppression produit |

### Gestion Commandes

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/admin/orders` | GET | `admin.orders.index` | `auth`, `admin` | Liste des commandes |
| `/admin/orders/{order}` | GET | `admin.orders.show` | `auth`, `admin` | Détails commande |
| `/admin/orders/{order}` | PUT | `admin.orders.update` | `auth`, `admin` | Mise à jour commande |
| `/admin/orders/scan` | GET | `admin.orders.scan` | `auth`, `admin` | Formulaire scan QR |
| `/admin/orders/scan` | POST | `admin.orders.scan.handle` | `auth`, `admin` | Traitement scan QR |
| `/admin/orders/{order}/qrcode` | GET | `admin.orders.qr` | `auth`, `admin` | QR Code commande |

### Gestion CMS

| URL | Méthode | Nom Route | Middleware | Description |
|-----|---------|-----------|------------|-------------|
| `/admin/cms/pages` | GET | `admin.cms.pages.index` | `auth`, `admin` | Liste des pages CMS |
| `/admin/cms/pages/create` | GET | `admin.cms.pages.create` | `auth`, `admin` | Création page CMS |
| `/admin/cms/pages` | POST | `admin.cms.pages.store` | `auth`, `admin` | Stockage page CMS |
| `/admin/cms/pages/{page}` | GET | `admin.cms.pages.show` | `auth`, `admin` | Détails page CMS |
| `/admin/cms/pages/{page}/edit` | GET | `admin.cms.pages.edit` | `auth`, `admin` | Édition page CMS |
| `/admin/cms/pages/{page}` | PUT | `admin.cms.pages.update` | `auth`, `admin` | Mise à jour page CMS |
| `/admin/cms/pages/{page}` | DELETE | `admin.cms.pages.destroy` | `auth`, `admin` | Suppression page CMS |

---

## 🔑 RÉSUMÉ RAPIDE

### Pages Publiques (Sans Authentification)

```
/auth                    → Hub d'authentification
/login                   → Connexion
/login?context=boutique  → Connexion (contexte boutique)
/login?context=equipe    → Connexion (contexte équipe)
/register                → Inscription
/password/forgot         → Mot de passe oublié
/password/reset/{token}  → Réinitialisation
```

### Pages Authentifiées

```
/compte                  → Dashboard client
/atelier-creator         → Dashboard créateur
/staff/dashboard         → Dashboard staff
/admin/dashboard         → Dashboard admin
/profil                  → Profil utilisateur
/2fa/setup               → Configuration 2FA
/2fa/manage              → Gestion 2FA
```

### Actions

```
POST /login              → Connexion
POST /register           → Inscription
POST /logout             → Déconnexion
POST /password/email     → Envoi email réinitialisation
POST /password/reset     → Réinitialisation mot de passe
POST /2fa/verify         → Vérification code 2FA
```

---

## 📝 NOTES IMPORTANTES

### Middlewares

- **`guest`** : Accessible uniquement si **non connecté**
- **`auth`** : Accessible uniquement si **connecté**
- **`admin`** : Accessible uniquement aux **admin** et **super_admin**
- **`staff`** : Accessible aux **staff**, **admin** et **super_admin**
- **`creator`** : Accessible uniquement aux **createur** et **creator**

### Redirections Automatiques

- Utilisateur **connecté** accédant à `/auth` ou `/login` → Redirigé vers son dashboard
- Utilisateur **non connecté** accédant à une page protégée → Redirigé vers `/login`

### Paramètres de Contexte

- `?context=boutique` : Adapte l'UI pour l'espace boutique
- `?context=equipe` : Adapte l'UI pour l'espace équipe
- Sans paramètre : UI neutre par défaut

---

**Fin du Document**

*Toutes les routes sont testées et fonctionnelles. Utilisez les noms de routes dans votre code pour une meilleure maintenabilité.*


