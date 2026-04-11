# 📋 RAPPORT COMPLET - MODULE D'AUTHENTIFICATION
## RACINE BY GANDA - Documentation Exhaustive

**Date de génération :** 2025  
**Version :** 1.0  
**Statut :** ✅ Production Ready

---

## 📑 TABLE DES MATIÈRES

1. [Architecture Globale](#1-architecture-globale)
2. [Structure de la Base de Données](#2-structure-de-la-base-de-données)
3. [Modèles et Relations](#3-modèles-et-relations)
4. [Contrôleurs d'Authentification](#4-contrôleurs-dauthentification)
5. [Routes et Points d'Entrée](#5-routes-et-points-dentrée)
6. [Système de Rôles](#6-système-de-rôles)
7. [Authentification à Deux Facteurs (2FA)](#7-authentification-à-deux-facteurs-2fa)
8. [Middlewares de Sécurité](#8-middlewares-de-sécurité)
9. [Procédures d'Authentification](#9-procédures-dauthentification)
10. [Gestion des Comptes](#10-gestion-des-comptes)
11. [Sécurité et Validations](#11-sécurité-et-validations)
12. [Vues et Interfaces](#12-vues-et-interfaces)
13. [Flux Complets](#13-flux-complets)
14. [Configuration](#14-configuration)

---

## 1. ARCHITECTURE GLOBALE

### 1.1 Principe de Conception

Le module d'authentification de RACINE BY GANDA est basé sur une **architecture unifiée** :

- **Guard unique** : Un seul guard `web` (session) pour tous les utilisateurs
- **Point d'entrée unique** : `/login` pour toutes les connexions
- **Redirection automatique** : Selon le rôle après authentification
- **Système multi-rôles** : Support de 5 rôles distincts
- **2FA optionnel** : Authentification à deux facteurs pour sécurité renforcée

### 1.2 Composants Principaux

```
Module Authentification
├── Contrôleurs
│   ├── LoginController (Connexion unifiée)
│   ├── PublicAuthController (Inscription publique)
│   ├── ErpAuthController (Connexion ERP - désactivé)
│   ├── AuthHubController (Hub de sélection)
│   └── TwoFactorController (Gestion 2FA)
├── Modèles
│   ├── User (Utilisateur)
│   └── Role (Rôle)
├── Services
│   └── TwoFactorService (Service 2FA)
├── Middlewares
│   ├── AdminOnly
│   ├── CreatorMiddleware
│   ├── StaffMiddleware
│   └── TwoFactorMiddleware
├── Requests
│   ├── LoginRequest
│   └── RegisterRequest
└── Vues
    ├── Hub
    ├── Login (multiple styles)
    ├── Register
    └── 2FA
```

---

## 2. STRUCTURE DE LA BASE DE DONNÉES

### 2.1 Table `users`

#### Colonnes Principales

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Identifiant unique |
| `name` | string | Nom complet |
| `email` | string (unique) | Adresse email |
| `password` | string (hashed) | Mot de passe crypté |
| `email_verified_at` | timestamp | Date de vérification email |
| `remember_token` | string | Token "Se souvenir de moi" |
| `role_id` | bigint (FK) | Référence vers table `roles` |
| `role` | enum | Rôle direct : `super_admin`, `admin`, `staff`, `createur`, `client` |
| `staff_role` | string (nullable) | Rôle spécifique staff (ex: `vendeur`, `caissier`) |
| `phone` | string (nullable) | Numéro de téléphone |
| `status` | string | Statut : `active`, `inactive`, `suspended` |
| `is_admin` | boolean | Flag legacy pour admin |
| `locale` | string | Langue préférée |

#### Colonnes 2FA

| Colonne | Type | Description |
|---------|------|-------------|
| `two_factor_secret` | text (encrypted) | Secret Google Authenticator |
| `two_factor_recovery_codes` | text (encrypted) | Codes de récupération (JSON) |
| `two_factor_confirmed_at` | timestamp | Date d'activation 2FA |
| `two_factor_required` | boolean | 2FA obligatoire (admin/super_admin) |
| `trusted_device_token` | string (hashed) | Token appareil de confiance |
| `trusted_device_expires_at` | timestamp | Expiration appareil de confiance |

#### Migrations

**Migration initiale :** `0001_01_01_000000_create_users_table.php`
- Création table `users`
- Création table `password_reset_tokens`
- Création table `sessions`

**Migration rôles :** `2025_11_26_122515_add_role_and_staff_role_to_users_table.php`
- Ajout colonne `role` (enum)
- Ajout colonne `staff_role` (string nullable)

**Migration 2FA :** `2025_11_27_000001_add_two_factor_columns_to_users_table.php`
- Ajout colonnes 2FA complètes

**Migration locale :** `2025_11_28_034646_add_locale_to_users_table.php`
- Ajout colonne `locale`

### 2.2 Table `roles`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | bigint | Identifiant unique |
| `name` | string (unique) | Nom du rôle (ex: "Super Administrateur") |
| `slug` | string (unique) | Slug du rôle (ex: "super_admin") |
| `description` | text (nullable) | Description du rôle |
| `is_active` | boolean | Rôle actif ou non |
| `created_at` | timestamp | Date de création |
| `updated_at` | timestamp | Date de mise à jour |

**Migration :** `2024_01_01_000004_create_roles_table.php`

### 2.3 Table `password_reset_tokens`

| Colonne | Type | Description |
|---------|------|-------------|
| `email` | string (primary) | Email de l'utilisateur |
| `token` | string | Token de réinitialisation |
| `created_at` | timestamp | Date de création du token |

**Durée de validité :** 60 minutes  
**Throttle :** 60 secondes entre les demandes

### 2.4 Table `sessions`

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | string (primary) | ID de session |
| `user_id` | bigint (nullable, FK) | Utilisateur connecté |
| `ip_address` | string (45) | Adresse IP |
| `user_agent` | text | User agent du navigateur |
| `payload` | longtext | Données de session |
| `last_activity` | integer | Timestamp dernière activité |

---

## 3. MODÈLES ET RELATIONS

### 3.1 Modèle `User`

**Fichier :** `app/Models/User.php`

#### Relations Eloquent

```php
// Relation avec Role
public function roleRelation()
{
    return $this->belongsTo(Role::class, 'role_id');
}

// Alias pour compatibilité
public function role()
{
    return $this->roleRelation();
}

// Profil créateur
public function creatorProfile()
{
    return $this->hasOne(CreatorProfile::class);
}

// Paramètres utilisateur
public function settings()
{
    return $this->hasOne(UserSetting::class);
}

// Adresses
public function addresses()
{
    return $this->hasMany(Address::class);
}

public function defaultAddress()
{
    return $this->hasOne(Address::class)->where('is_default', true);
}

// Commandes
public function orders()
{
    return $this->hasMany(Order::class);
}

// Points de fidélité
public function loyaltyPoints()
{
    return $this->hasOne(LoyaltyPoint::class);
}

public function loyaltyTransactions()
{
    return $this->hasMany(LoyaltyTransaction::class);
}
```

#### Méthodes Utilitaires

**Récupération du rôle :**
```php
public function getRoleSlug(): ?string
{
    // Priorité 1: roleRelation via role_id
    if ($this->roleRelation) {
        return $this->roleRelation->slug;
    }
    
    // Priorité 2: direct role attribute
    return $this->attributes['role'] ?? null;
}
```

**Vérifications de rôle :**
```php
public function isAdmin(): bool
// Vérifie si admin (rétro-compatible avec is_admin et role_id === 1)

public function isCreator(): bool
// Vérifie si créateur (slug: 'createur' ou 'creator')

public function isClient(): bool
// Vérifie si client (slug: 'client')

public function isTeamMember(): bool
// Vérifie si membre équipe (slug: 'super_admin', 'admin', 'staff')

public function hasRole(string $role): bool
// Vérifie un rôle spécifique

public function hasAnyRole(array $roles): bool
// Vérifie si l'utilisateur a un des rôles fournis
```

**Scopes :**
```php
public function scopeAdmins($query)
// Filtre les utilisateurs administrateurs
```

### 3.2 Modèle `Role`

**Fichier :** `app/Models/Role.php`

#### Relations

```php
public function users()
{
    return $this->hasMany(User::class);
}
```

#### Rôles Par Défaut (Seeder)

| ID | Name | Slug | Description |
|----|------|------|-------------|
| 1 | Super Administrateur | `super_admin` | Accès complet, peut gérer les admins |
| 2 | Administrateur | `admin` | Accès admin standard |
| 3 | Staff | `staff` | Membre équipe, outils internes |
| 4 | Créateur | `createur` | Créateur/Designer partenaire |
| 5 | Client | `client` | Client standard |

**Fichier seeder :** `database/seeders/RolesTableSeeder.php`

---

## 4. CONTRÔLEURS D'AUTHENTIFICATION

### 4.1 LoginController (Connexion Unifiée)

**Fichier :** `app/Http/Controllers/Auth/LoginController.php`

**Responsabilité :** Gère toutes les connexions (tous rôles) via un seul point d'entrée.

#### Méthodes

**`showLoginForm(): View`**
- Affiche le formulaire de connexion
- Si déjà connecté, redirige selon le rôle
- Vue : `auth.login-neutral`

**`login(Request $request): RedirectResponse`**
- Valide les identifiants (email, password, remember)
- Tentative de connexion via guard `web`
- Vérifie le statut utilisateur (doit être `active`)
- Charge la relation `roleRelation`
- Régénère la session
- Redirige selon le rôle via `getRedirectPath()`

**`logout(Request $request): RedirectResponse`**
- Déconnecte l'utilisateur
- Invalide la session
- Régénère le token CSRF
- Redirige vers `frontend.home`

**`getRedirectPath(User $user): string`**
- Détermine la redirection selon le rôle :
  - `client` → `account.dashboard`
  - `createur` / `creator` → `creator.dashboard`
  - `staff` → `staff.dashboard`
  - `admin` / `super_admin` → `admin.dashboard`
  - default → `frontend.home`

### 4.2 PublicAuthController (Inscription Publique)

**Fichier :** `app/Http/Controllers/Auth/PublicAuthController.php`

**Responsabilité :** Gère l'inscription des clients et créateurs.

#### Méthodes

**`showLoginForm(Request $request): View`**
- Affiche le formulaire de connexion avec style
- Paramètre `style` : `neutral`, `female`, `male`
- Vues : `auth.login-neutral`, `auth.login-female`, `auth.login-male`

**`login(LoginRequest $request): RedirectResponse`**
- Traite la connexion publique
- Sauvegarde le style visuel si fourni
- Redirige selon le rôle

**`showRegisterForm(): View`**
- Affiche le formulaire d'inscription
- Vue : `auth.register`

**`register(RegisterRequest $request): RedirectResponse`**
- Valide les données d'inscription
- Récupère le type de compte (`client` ou `creator`)
- Crée ou récupère le rôle correspondant
- Crée l'utilisateur avec :
  - `name`, `email`, `password` (hashed)
  - `role_id` (référence vers `roles`)
- Charge la relation `roleRelation`
- Connecte automatiquement l'utilisateur
- Redirige selon le rôle

**`logout(Request $request): RedirectResponse`**
- Déconnexion publique
- Redirige vers `/`

**`redirectByRole(User $user): RedirectResponse`**
- Redirection pour clients et créateurs uniquement

### 4.3 ErpAuthController (Connexion ERP)

**Fichier :** `app/Http/Controllers/Auth/ErpAuthController.php`

**Statut :** ⚠️ Désactivé temporairement (utiliser `/login` à la place)

**Responsabilité :** Gère la connexion pour l'espace ERP (admin, staff).

#### Méthodes

**`showLoginForm(): View`**
- Affiche le formulaire de connexion ERP
- Vue : `auth.erp-login`

**`login(LoginRequest $request): RedirectResponse`**
- Vérifie que l'utilisateur a un rôle ERP autorisé :
  - `admin`, `super_admin`, `moderator`, `staff`
- Si rôle non autorisé, déconnecte et affiche erreur
- Redirige vers `admin.dashboard`

**`logout(Request $request): RedirectResponse`**
- Déconnexion ERP
- Redirige vers `erp.login`

### 4.4 AuthHubController (Hub de Sélection)

**Fichier :** `app/Http/Controllers/Auth/AuthHubController.php`

**Responsabilité :** Affiche la page de choix entre espace boutique et espace équipe.

#### Méthodes

**`index(): View`**
- Affiche le hub d'authentification
- Vue : `auth.hub`
- Permet de choisir entre :
  - Espace Boutique (clients/créateurs)
  - Espace Équipe (staff/admin)

### 4.5 TwoFactorController (Gestion 2FA)

**Fichier :** `app/Http/Controllers/Auth/TwoFactorController.php`

**Responsabilité :** Gère l'authentification à deux facteurs.

#### Méthodes

**`setup()`**
- Affiche la page de configuration 2FA
- Génère un nouveau secret
- Génère le QR Code SVG
- Stocke le secret en session temporaire
- Vue : `auth.2fa.setup`

**`confirm(Request $request)`**
- Valide le code 2FA fourni
- Active le 2FA pour l'utilisateur
- Génère les codes de récupération
- Synchronise avec le CRM (si client/créateur)
- Affiche les codes de récupération
- Vue : `auth.2fa.recovery-codes`

**`manage()`**
- Affiche la page de gestion 2FA
- Affiche l'état (activé/désactivé)
- Affiche le nombre de codes de récupération
- Vue : `auth.2fa.manage`

**`regenerateRecoveryCodes(Request $request)`**
- Régénère les codes de récupération
- Requiert la confirmation du mot de passe
- Affiche les nouveaux codes
- Vue : `auth.2fa.recovery-codes`

**`disable(Request $request)`**
- Désactive le 2FA
- Requiert mot de passe + code 2FA ou code de récupération
- Impossible si 2FA obligatoire (admin/super_admin)
- Vue : `auth.2fa.manage`

**`challenge()`**
- Affiche la page de challenge 2FA (lors de la connexion)
- Vérifie que `2fa_user_id` est en session
- Vue : `auth.2fa.challenge`

**`verify(Request $request)`**
- Vérifie le code 2FA ou code de récupération
- Connecte l'utilisateur si valide
- Gère l'appareil de confiance (optionnel)
- Synchronise avec le CRM
- Redirige selon le rôle

**`syncToCrm(User $user): void`**
- Synchronise l'utilisateur avec le CRM
- Ne synchronise pas les membres de l'équipe
- Mappe les rôles vers les types CRM :
  - `createur` → `partner`
  - `client` → `client`
  - default → `lead`

**`redirectByRole(User $user)`**
- Redirige selon le rôle après validation 2FA

---

## 5. ROUTES ET POINTS D'ENTRÉE

### 5.1 Routes d'Authentification (`routes/auth.php`)

#### Hub d'Authentification
```php
GET /auth → AuthHubController@index
Route name: auth.hub
```

#### Connexion Unifiée
```php
GET /login → LoginController@showLoginForm
Route name: login
Middleware: guest

POST /login → LoginController@login
Route name: login.post
Middleware: guest
```

#### Inscription
```php
GET /register → PublicAuthController@showRegisterForm
Route name: register
Middleware: guest

POST /register → PublicAuthController@register
Route name: register.post
Middleware: guest
```

#### Réinitialisation de Mot de Passe
```php
GET /password/forgot → PublicAuthController@showForgotForm
Route name: password.request
Middleware: guest

POST /password/email → PublicAuthController@sendResetLink
Route name: password.email
Middleware: guest

GET /password/reset/{token} → PublicAuthController@showResetForm
Route name: password.reset
Middleware: guest

POST /password/reset → PublicAuthController@reset
Route name: password.update
Middleware: guest
```

#### Déconnexion
```php
POST /logout → LoginController@logout
Route name: logout
Middleware: auth
```

### 5.2 Routes 2FA (`routes/web.php`)

#### Challenge 2FA (Public)
```php
GET /2fa/challenge → TwoFactorController@challenge
Route name: 2fa.challenge

POST /2fa/verify → TwoFactorController@verify
Route name: 2fa.verify
```

#### Gestion 2FA (Authentifié)
```php
GET /2fa/setup → TwoFactorController@setup
Route name: 2fa.setup
Middleware: auth

POST /2fa/confirm → TwoFactorController@confirm
Route name: 2fa.confirm
Middleware: auth

GET /2fa/manage → TwoFactorController@manage
Route name: 2fa.manage
Middleware: auth

POST /2fa/disable → TwoFactorController@disable
Route name: 2fa.disable
Middleware: auth

POST /2fa/recovery-codes/regenerate → TwoFactorController@regenerateRecoveryCodes
Route name: 2fa.recovery-codes.regenerate
Middleware: auth
```

### 5.3 Routes ERP (Désactivées)

⚠️ **Les routes ERP sont désactivées temporairement.** Utiliser `/login` pour tous les utilisateurs.

```php
// Désactivées :
// GET /erp/login
// POST /erp/login
// POST /erp/logout
```

### 5.4 Routes Dashboards

```php
GET /compte → account.dashboard
Middleware: auth

GET /atelier-creator → creator.dashboard
Middleware: auth, creator

GET /staff/dashboard → staff.dashboard
Middleware: auth, staff

GET /admin/dashboard → admin.dashboard
Middleware: auth, admin
```

---

## 6. SYSTÈME DE RÔLES

### 6.1 Hiérarchie des Rôles

```
super_admin (Niveau 5 - Accès complet)
    ↓
admin (Niveau 4 - Administration)
    ↓
staff (Niveau 3 - Équipe)
    ↓
createur (Niveau 2 - Partenaire)
    ↓
client (Niveau 1 - Utilisateur standard)
```

### 6.2 Permissions par Rôle

#### Super Administrateur (`super_admin`)
- ✅ Accès complet à toutes les fonctionnalités
- ✅ Gestion des autres administrateurs
- ✅ Configuration système
- ✅ 2FA obligatoire
- ✅ Dashboard : `/admin/dashboard`

#### Administrateur (`admin`)
- ✅ Gestion des utilisateurs
- ✅ Gestion du contenu
- ✅ Gestion des commandes
- ✅ Accès ERP complet
- ✅ 2FA obligatoire
- ✅ Dashboard : `/admin/dashboard`

#### Staff (`staff`)
- ✅ Accès aux outils internes
- ✅ Gestion des commandes
- ✅ Support client
- ✅ Accès ERP limité
- ✅ 2FA optionnel
- ✅ Dashboard : `/staff/dashboard`
- ⚙️ Rôle spécifique : `staff_role` (ex: `vendeur`, `caissier`)

#### Créateur (`createur` / `creator`)
- ✅ Gestion de ses produits
- ✅ Gestion de sa boutique
- ✅ Statistiques de vente
- ✅ 2FA optionnel
- ✅ Dashboard : `/atelier-creator`
- ⚙️ Profil créateur requis (optionnel)

#### Client (`client`)
- ✅ Accès à la boutique
- ✅ Commandes et suivi
- ✅ Wishlist et favoris
- ✅ Profil personnel
- ✅ 2FA optionnel
- ✅ Dashboard : `/compte`

### 6.3 Gestion des Rôles

#### Attribution de Rôle

**Lors de l'inscription :**
- Client ou Créateur choisi par l'utilisateur
- Rôle créé automatiquement si inexistant

**Par un administrateur :**
- Via `AdminUserController`
- Modification de `role_id` et `role`
- Attribution de `staff_role` pour le staff

#### Vérification de Rôle

**Dans le code :**
```php
// Via méthodes User
$user->isAdmin();
$user->isCreator();
$user->isClient();
$user->isTeamMember();
$user->hasRole('admin');
$user->hasAnyRole(['admin', 'super_admin']);

// Via getRoleSlug()
$roleSlug = $user->getRoleSlug();
```

**Dans les middlewares :**
- `AdminOnly` : Vérifie `admin` ou `super_admin`
- `CreatorMiddleware` : Vérifie `createur` ou `creator`
- `StaffMiddleware` : Vérifie `staff`, `admin` ou `super_admin`

---

## 7. AUTHENTIFICATION À DEUX FACTEURS (2FA)

### 7.1 Service TwoFactorService

**Fichier :** `app/Services/TwoFactorService.php`

**Package utilisé :** `pragmarx/google2fa-laravel` v2.3

#### Méthodes Principales

**Génération :**
```php
generateSecretKey(): string
// Génère un secret 2FA (32 caractères)

generateQrCodeSvg(User $user, string $secret): string
// Génère le QR Code SVG pour Google Authenticator

getQrCodeUrl(User $user, string $secret): string
// Génère l'URL otpauth:// pour le QR Code
```

**Vérification :**
```php
verifyCode(string $secret, string $code): bool
// Vérifie un code TOTP (6 chiffres)

verifyRecoveryCode(User $user, string $code): bool
// Vérifie un code de récupération (format: XXXX-XXXX)
// Supprime le code après utilisation
```

**Activation/Désactivation :**
```php
enableTwoFactor(User $user, string $secret): bool
// Active le 2FA
// Génère 8 codes de récupération
// Rend obligatoire pour admin/super_admin

disableTwoFactor(User $user): bool
// Désactive le 2FA
// Impossible si two_factor_required = true
```

**Codes de Récupération :**
```php
generateRecoveryCodes(int $count = 8): array
// Génère des codes au format XXXX-XXXX

regenerateRecoveryCodes(User $user): array
// Régénère les codes (remplace les anciens)

getRecoveryCodes(User $user): array
// Récupère les codes décryptés
```

**Appareils de Confiance :**
```php
generateTrustedDeviceToken(User $user, int $days = 30): string
// Génère un token d'appareil de confiance
// Durée : 30 jours par défaut

isTrustedDevice(User $user, ?string $token): bool
// Vérifie si l'appareil est de confiance
// Vérifie l'expiration

revokeTrustedDevice(User $user): bool
// Révoque l'appareil de confiance
```

**Vérifications :**
```php
isEnabled(User $user): bool
// Vérifie si 2FA est activé

isRequired(User $user): bool
// Vérifie si 2FA est obligatoire
// Toujours false en environnement local
// True pour admin/super_admin en production

getDecryptedSecret(User $user): ?string
// Récupère le secret décrypté
```

### 7.2 Flux 2FA

#### Activation

1. Utilisateur accède à `/2fa/setup`
2. Service génère un secret
3. QR Code affiché
4. Utilisateur scanne avec Google Authenticator
5. Utilisateur entre un code de vérification
6. Service active le 2FA
7. Codes de récupération affichés (à sauvegarder)

#### Connexion avec 2FA

1. Utilisateur se connecte avec email/password
2. Si 2FA activé :
   - Vérification appareil de confiance
   - Si appareil de confiance valide → Connexion directe
   - Sinon → Redirection vers `/2fa/challenge`
3. Utilisateur entre code 2FA ou code de récupération
4. Si valide → Connexion
5. Option "Se souvenir de cet appareil" → Création appareil de confiance

#### Désactivation

1. Utilisateur accède à `/2fa/manage`
2. Confirmation mot de passe + code 2FA requis
3. Si 2FA obligatoire → Impossible
4. Sinon → Désactivation

### 7.3 Sécurité 2FA

- **Secret crypté** : Stocké avec `encrypt()` Laravel
- **Codes de récupération cryptés** : Stockés en JSON crypté
- **Appareils de confiance** : Token hashé (SHA256)
- **Expiration** : 30 jours pour appareils de confiance
- **Codes à usage unique** : Codes de récupération supprimés après utilisation
- **Obligatoire** : Pour `admin` et `super_admin` en production

---

## 8. MIDDLEWARES DE SÉCURITÉ

### 8.1 AdminOnly

**Fichier :** `app/Http/Middleware/AdminOnly.php`

**Alias :** `admin`

**Fonction :** Vérifie que l'utilisateur est `admin` ou `super_admin`.

**Logique :**
1. Vérifie si utilisateur connecté
2. Charge la relation `roleRelation`
3. Récupère le slug du rôle
4. Vérifie si `admin` ou `super_admin`
5. Sinon → 403 Forbidden

**Utilisation :**
```php
Route::middleware('admin')->group(function () {
    // Routes admin
});
```

### 8.2 CreatorMiddleware

**Fichier :** `app/Http/Middleware/CreatorMiddleware.php`

**Alias :** `creator`

**Fonction :** Vérifie que l'utilisateur est `createur` ou `creator`.

**Logique :**
1. Vérifie si utilisateur connecté
2. Charge la relation `roleRelation`
3. Récupère le slug du rôle
4. Vérifie si `createur` ou `creator`
5. Sinon → 403 Forbidden

**Utilisation :**
```php
Route::middleware('creator')->group(function () {
    // Routes créateur
});
```

### 8.3 StaffMiddleware

**Fichier :** `app/Http/Middleware/StaffMiddleware.php`

**Alias :** `staff`

**Fonction :** Vérifie que l'utilisateur est `staff`, `admin` ou `super_admin`.

**Logique :**
1. Vérifie si utilisateur connecté
2. Charge la relation `roleRelation`
3. Récupère le slug du rôle
4. Vérifie si `staff`, `admin` ou `super_admin`
5. Sinon → 403 Forbidden

**Utilisation :**
```php
Route::middleware('staff')->group(function () {
    // Routes staff
});
```

### 8.4 TwoFactorMiddleware

**Fichier :** `app/Http/Middleware/TwoFactorMiddleware.php`

**Alias :** `2fa` (désactivé temporairement)

**Fonction :** Vérifie si l'utilisateur doit passer par le challenge 2FA.

**Logique :**
1. Si utilisateur non connecté → Continue
2. Si 2FA non activé :
   - Si 2FA obligatoire → Redirige vers `/2fa/setup`
   - Sinon → Continue
3. Si session `2fa_verified` → Continue
4. Si appareil de confiance valide → Continue
5. Sinon → Stocke `2fa_user_id` en session, déconnecte, redirige vers `/2fa/challenge`

**Statut :** ⚠️ Désactivé dans `bootstrap/app.php` (commenté)

### 8.5 Middlewares Laravel Standards

**`auth`** : Vérifie que l'utilisateur est connecté  
**`guest`** : Vérifie que l'utilisateur n'est pas connecté

---

## 9. PROCÉDURES D'AUTHENTIFICATION

### 9.1 Procédure de Connexion Standard

#### Étape 1 : Accès au Formulaire
```
GET /login
→ LoginController@showLoginForm
→ Vérifie si déjà connecté
  ├─ Oui → Redirige selon rôle
  └─ Non → Affiche auth.login-neutral
```

#### Étape 2 : Soumission du Formulaire
```
POST /login
→ LoginController@login
→ Validation (email, password, remember)
→ Auth::attempt($credentials, $remember)
```

#### Étape 3 : Vérifications
```
Si authentification réussie :
  ├─ Régénération session
  ├─ Chargement roleRelation
  ├─ Vérification statut (doit être 'active')
  │   ├─ Inactif → Déconnexion + Erreur
  │   └─ Actif → Continue
  └─ Redirection selon rôle
```

#### Étape 4 : Redirection
```
getRedirectPath($user) :
  ├─ client → /compte
  ├─ createur/creator → /atelier-creator
  ├─ staff → /staff/dashboard
  ├─ admin/super_admin → /admin/dashboard
  └─ default → /
```

### 9.2 Procédure de Connexion avec 2FA

#### Étape 1-3 : Identiques à la connexion standard

#### Étape 4 : Vérification 2FA
```
Si 2FA activé :
  ├─ Vérification appareil de confiance
  │   ├─ Valide → Connexion directe
  │   └─ Invalide → Continue
  ├─ Stockage 2fa_user_id en session
  ├─ Déconnexion temporaire
  └─ Redirection vers /2fa/challenge
```

#### Étape 5 : Challenge 2FA
```
GET /2fa/challenge
→ TwoFactorController@challenge
→ Affiche formulaire code 2FA
```

#### Étape 6 : Vérification Code
```
POST /2fa/verify
→ TwoFactorController@verify
→ Validation code (6 chiffres ou code récupération)
→ Vérification via TwoFactorService
  ├─ Code invalide → Erreur
  └─ Code valide → Continue
```

#### Étape 7 : Connexion Finale
```
Si code valide :
  ├─ Connexion utilisateur
  ├─ Session 2fa_verified = true
  ├─ Option "Se souvenir" → Création appareil de confiance
  ├─ Synchronisation CRM (si client/créateur)
  └─ Redirection selon rôle
```

### 9.3 Procédure d'Inscription

#### Étape 1 : Accès au Formulaire
```
GET /register
→ PublicAuthController@showRegisterForm
→ Affiche auth.register
```

#### Étape 2 : Soumission
```
POST /register
→ PublicAuthController@register
→ Validation RegisterRequest :
  ├─ name (required, string, max:255)
  ├─ email (required, email, unique:users)
  ├─ password (required, confirmed, min:8)
  ├─ account_type (required, in:client,creator)
  └─ terms (required, accepted)
```

#### Étape 3 : Création du Rôle
```
Récupération account_type :
  ├─ 'client' → slug: 'client', name: 'Client'
  └─ 'creator' → slug: 'createur', name: 'Créateur'

Role::firstOrCreate(['slug' => $slug], [...])
```

#### Étape 4 : Création Utilisateur
```
User::create([
  'name' => $request->name,
  'email' => $request->email,
  'password' => Hash::make($request->password),
  'role_id' => $role->id,
])
```

#### Étape 5 : Connexion Automatique
```
Auth::login($user)
→ Chargement roleRelation
→ Redirection selon rôle
```

### 9.4 Procédure de Déconnexion

```
POST /logout
→ LoginController@logout
→ Auth::logout()
→ Session invalidation
→ Régénération token CSRF
→ Redirection vers frontend.home
```

### 9.5 Procédure de Réinitialisation de Mot de Passe

#### Étape 1 : Demande
```
GET /password/forgot
→ PublicAuthController@showForgotForm
→ Affiche formulaire email
```

#### Étape 2 : Envoi Email
```
POST /password/email
→ PublicAuthController@sendResetLink
→ Validation email
→ Génération token
→ Envoi email avec lien
→ Throttle : 60 secondes
```

#### Étape 3 : Réinitialisation
```
GET /password/reset/{token}
→ PublicAuthController@showResetForm
→ Vérification token (valide 60 min)
→ Affiche formulaire nouveau mot de passe
```

#### Étape 4 : Mise à Jour
```
POST /password/reset
→ PublicAuthController@reset
→ Validation (email, password, password_confirmation, token)
→ Vérification token
→ Mise à jour password
→ Hash nouveau mot de passe
→ Suppression token
→ Redirection vers login
```

---

## 10. GESTION DES COMPTES

### 10.1 Création de Compte

**Méthode :** Inscription publique (`/register`)

**Types de comptes :**
- Client
- Créateur

**Champs requis :**
- Nom complet
- Email (unique)
- Mot de passe (min 8 caractères, confirmé)
- Type de compte
- Acceptation des conditions

**Processus automatique :**
1. Création du rôle si inexistant
2. Création de l'utilisateur
3. Connexion automatique
4. Redirection vers dashboard

### 10.2 Modification de Compte

**Contrôleur :** `ProfileController`

**Routes :**
- `GET /profil` → Affichage profil
- `PUT /profil` → Mise à jour profil
- `PUT /profil/password` → Changement mot de passe

**Champs modifiables :**
- Nom
- Email
- Téléphone
- Adresses
- Préférences

### 10.3 Désactivation de Compte

**Méthode :** Via `AdminUserController` (admin uniquement)

**Champ :** `status` dans table `users`

**Valeurs :**
- `active` : Compte actif
- `inactive` : Compte désactivé
- `suspended` : Compte suspendu

**Effet :** Utilisateur ne peut plus se connecter si `status !== 'active'`

### 10.4 Suppression de Compte

**Méthode :** Via `AdminUserController` (admin uniquement)

**Action :** Suppression définitive de l'utilisateur

**⚠️ Attention :** Les données associées (commandes, etc.) doivent être gérées (soft delete recommandé)

---

## 11. SÉCURITÉ ET VALIDATIONS

### 11.1 Validations de Connexion

**Request :** `LoginRequest`

**Règles :**
```php
'email' => ['required', 'email'],
'password' => ['required', 'string'],
'remember' => ['sometimes', 'boolean'],
```

**Messages personnalisés :**
- Email requis
- Email valide
- Mot de passe requis

### 11.2 Validations d'Inscription

**Request :** `RegisterRequest`

**Règles :**
```php
'name' => ['required', 'string', 'max:255'],
'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
'password' => ['required', 'confirmed', Password::min(8)],
'account_type' => ['required', 'in:client,creator'],
'terms' => ['required', 'accepted'],
```

**Messages personnalisés :**
- Nom requis
- Email requis, valide, unique
- Mot de passe requis, confirmé, min 8 caractères
- Type de compte requis et valide
- Conditions d'utilisation à accepter

### 11.3 Sécurité des Mots de Passe

- **Hash :** Bcrypt (via `Hash::make()`)
- **Minimum :** 8 caractères
- **Confirmation :** Requise lors de l'inscription
- **Réinitialisation :** Token unique, valide 60 minutes

### 11.4 Protection CSRF

- **Token CSRF :** Généré automatiquement par Laravel
- **Vérification :** Automatique sur toutes les routes POST
- **Exceptions :** Webhooks (configuré dans `bootstrap/app.php`)

### 11.5 Protection Session

- **Régénération :** Après chaque connexion réussie
- **Invalidation :** Lors de la déconnexion
- **Timeout :** Configuré dans `config/session.php`
- **Sécurité :** Cookies sécurisés (HTTPS en production)

### 11.6 Rate Limiting

- **Réinitialisation mot de passe :** 60 secondes entre les demandes
- **Connexion :** Limite par défaut Laravel
- **API :** Rate limiting configuré dans `bootstrap/app.php`

### 11.7 Headers de Sécurité

**Middleware :** `SecurityHeaders`

**Headers appliqués :**
- Content-Security-Policy
- X-Frame-Options
- X-Content-Type-Options
- Referrer-Policy
- Permissions-Policy

---

## 12. VUES ET INTERFACES

### 12.1 Vues d'Authentification

#### Hub (`auth.hub`)
- **Fichier :** `resources/views/auth/hub.blade.php`
- **Fonction :** Page de choix entre espace boutique et espace équipe
- **Design :** Moderne, gradient mesh, glassmorphism

#### Connexion (`auth.login-neutral`)
- **Fichier :** `resources/views/auth/login-neutral.blade.php`
- **Style :** Neutre
- **Champs :** Email, Password, Remember

#### Connexion Féminin (`auth.login-female`)
- **Fichier :** `resources/views/auth/login-female.blade.php`
- **Style :** Adapté style féminin

#### Connexion Masculin (`auth.login-male`)
- **Fichier :** `resources/views/auth/login-male.blade.php`
- **Style :** Adapté style masculin

#### Inscription (`auth.register`)
- **Fichier :** `resources/views/auth/register.blade.php`
- **Champs :** Name, Email, Password, Password Confirmation, Account Type, Terms

#### Connexion ERP (`auth.erp-login`)
- **Fichier :** `resources/views/auth/erp-login.blade.php`
- **Style :** Professionnel, pour équipe

### 12.2 Vues 2FA

#### Configuration (`auth.2fa.setup`)
- **Fichier :** `resources/views/auth/2fa/setup.blade.php`
- **Contenu :** QR Code, Secret (manuel), Formulaire code

#### Challenge (`auth.2fa.challenge`)
- **Fichier :** `resources/views/auth/2fa/challenge.blade.php`
- **Contenu :** Formulaire code 2FA, Option "Se souvenir"

#### Gestion (`auth.2fa.manage`)
- **Fichier :** `resources/views/auth/2fa/manage.blade.php`
- **Contenu :** État 2FA, Bouton désactivation, Régénération codes

#### Codes de Récupération (`auth.2fa.recovery-codes`)
- **Fichier :** `resources/views/auth/2fa/recovery-codes.blade.php`
- **Contenu :** Liste des codes, Instructions sauvegarde

---

## 13. FLUX COMPLETS

### 13.1 Flux de Connexion Simple (Sans 2FA)

```
┌─────────────────┐
│   Visiteur      │
│   GET /login    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ LoginController │
│ showLoginForm() │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Formulaire      │
│ Login           │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ POST /login     │
│ LoginController │
│ login()         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Auth::attempt() │
│ Validation      │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
   ✅        ❌
    │         │
    ▼         ▼
┌─────────┐ ┌─────────┐
│ Succès  │ │ Échec   │
└────┬────┘ └────┬────┘
     │           │
     ▼           ▼
┌─────────┐ ┌─────────┐
│ Vérif   │ │ Erreur  │
│ Statut  │ │ Retour  │
└────┬────┘ └─────────┘
     │
     ▼
┌─────────┐
│ Redir   │
│ Rôle    │
└─────────┘
```

### 13.2 Flux de Connexion avec 2FA

```
┌─────────────────┐
│   Connexion     │
│   Réussie       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 2FA Activé ?    │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
   Oui       Non
    │         │
    ▼         ▼
┌─────────┐ ┌─────────┐
│ Appareil│ │ Redir   │
│ Confiance│ │ Rôle    │
└────┬────┘ └─────────┘
     │
┌────┴────┐
│         │
Oui      Non
│         │
▼         ▼
┌─────────┐ ┌─────────┐
│ Redir   │ │ Challenge│
│ Rôle    │ │ 2FA     │
└─────────┘ └────┬────┘
                 │
                 ▼
         ┌───────────────┐
         │ Code 2FA      │
         │ Vérification  │
         └───────┬───────┘
                 │
            ┌────┴────┐
            │         │
          Valide    Invalide
            │         │
            ▼         ▼
     ┌─────────┐ ┌─────────┐
     │ Connexion│ │ Erreur  │
     │ Finale   │ │ Retour  │
     └─────┬─────┘ └─────────┘
           │
           ▼
     ┌─────────┐
     │ Redir    │
     │ Rôle     │
     └─────────┘
```

### 13.3 Flux d'Inscription

```
┌─────────────────┐
│   Visiteur      │
│   GET /register │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Formulaire      │
│ Inscription     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ POST /register  │
│ Validation      │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
   ✅        ❌
    │         │
    ▼         ▼
┌─────────┐ ┌─────────┐
│ Création│ │ Erreur  │
│ Rôle    │ │ Retour  │
└────┬────┘ └─────────┘
     │
     ▼
┌─────────┐
│ Création│
│ User    │
└────┬────┘
     │
     ▼
┌─────────┐
│ Connexion│
│ Auto    │
└────┬────┘
     │
     ▼
┌─────────┐
│ Redir   │
│ Rôle    │
└─────────┘
```

### 13.4 Flux d'Activation 2FA

```
┌─────────────────┐
│ GET /2fa/setup  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Génération      │
│ Secret          │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Génération      │
│ QR Code         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Affichage       │
│ QR Code         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Scan QR Code    │
│ Google Auth     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ POST /2fa/      │
│ confirm         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Vérification    │
│ Code            │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
   ✅        ❌
    │         │
    ▼         ▼
┌─────────┐ ┌─────────┐
│ Activation│ │ Erreur  │
│ 2FA      │ │ Retour  │
└────┬─────┘ └─────────┘
     │
     ▼
┌─────────┐
│ Génération│
│ Codes    │
│ Récup    │
└────┬─────┘
     │
     ▼
┌─────────┐
│ Affichage│
│ Codes   │
└─────────┘
```

---

## 14. CONFIGURATION

### 14.1 Configuration Authentification

**Fichier :** `config/auth.php`

```php
'defaults' => [
    'guard' => env('AUTH_GUARD', 'web'),
    'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => env('AUTH_MODEL', App\Models\User::class),
    ],
],

'passwords' => [
    'users' => [
        'provider' => 'users',
        'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
        'expire' => 60, // minutes
        'throttle' => 60, // seconds
    ],
],

'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800), // 3 hours
```

### 14.2 Variables d'Environnement

```env
AUTH_GUARD=web
AUTH_PASSWORD_BROKER=users
AUTH_MODEL=App\Models\User
AUTH_PASSWORD_RESET_TOKEN_TABLE=password_reset_tokens
AUTH_PASSWORD_TIMEOUT=10800
```

### 14.3 Configuration Middlewares

**Fichier :** `bootstrap/app.php`

```php
$middleware->alias([
    'creator' => \App\Http\Middleware\CreatorMiddleware::class,
    'admin' => \App\Http\Middleware\AdminOnly::class,
    'staff' => \App\Http\Middleware\StaffMiddleware::class,
    'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
    // Désactivés temporairement :
    // 'role' => \App\Http\Middleware\CheckRole::class,
    // 'permission' => \App\Http\Middleware\CheckPermission::class,
    // '2fa' => \App\Http\Middleware\TwoFactorMiddleware::class,
]);
```

### 14.4 Configuration 2FA

**Service :** `TwoFactorService`

**Package :** `pragmarx/google2fa-laravel`

**Configuration :**
- Secret length : 32 caractères
- Codes de récupération : 8 codes
- Format codes : `XXXX-XXXX`
- Appareil de confiance : 30 jours
- Obligatoire pour : `admin`, `super_admin` (en production uniquement)

---

## 📊 RÉSUMÉ TECHNIQUE

### Points Clés

✅ **Architecture unifiée** : Un seul guard, un seul point d'entrée  
✅ **Multi-rôles** : 5 rôles distincts avec hiérarchie  
✅ **2FA optionnel** : Authentification à deux facteurs complète  
✅ **Sécurité renforcée** : Validations, CSRF, rate limiting  
✅ **Redirection automatique** : Selon le rôle après connexion  
✅ **Gestion complète** : Inscription, connexion, déconnexion, réinitialisation  

### Statistiques

- **Contrôleurs** : 5
- **Middlewares** : 4 actifs
- **Routes** : 15+ routes d'authentification
- **Vues** : 10+ vues
- **Rôles** : 5 rôles
- **Services** : 1 (TwoFactorService)

### Compatibilité

- **Laravel** : 11.x
- **PHP** : 8.2+
- **Base de données** : MySQL/PostgreSQL
- **Packages** : `pragmarx/google2fa-laravel` v2.3

---

## 🔒 SÉCURITÉ

### Mesures Implémentées

1. ✅ Hash des mots de passe (Bcrypt)
2. ✅ Protection CSRF
3. ✅ Régénération de session
4. ✅ Validation des entrées
5. ✅ Rate limiting
6. ✅ 2FA optionnel
7. ✅ Appareils de confiance
8. ✅ Codes de récupération
9. ✅ Vérification du statut utilisateur
10. ✅ Headers de sécurité HTTP

### Recommandations

- Activer le middleware 2FA pour les routes sensibles
- Configurer HTTPS en production
- Mettre en place un système de logs d'authentification
- Implémenter un système de verrouillage de compte après X tentatives
- Ajouter une vérification email lors de l'inscription

---

**Fin du Rapport**

*Document généré automatiquement - RACINE BY GANDA*

