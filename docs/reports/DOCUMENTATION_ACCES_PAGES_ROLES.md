# 🔐 DOCUMENTATION ACCÈS PAGES & RÔLES — RACINE BY GANDA

**Date :** 2025  
**Projet :** RACINE-BACKEND  
**Version :** 1.0.0

---

## 📋 RÉSUMÉ

Ce document liste tous les accès aux pages selon les rôles utilisateurs et les routes protégées par middleware.

---

## 👥 RÔLES DISPONIBLES

| Rôle | Slug | ID | Description |
|------|------|----|----|
| **Super Administrateur** | `super_admin` | 1 | Accès complet, gestion admins |
| **Administrateur** | `admin` | 2 | Gestion standard |
| **Staff** | `staff` | 3 | Personnel interne (vendeur, caissier, etc.) |
| **Créateur** | `createur` / `creator` | 4 | Vendeur marketplace |
| **Client** | `client` | 5 | Client boutique |

---

## 🛡️ MIDDLEWARES DISPONIBLES

| Middleware | Fichier | Protection |
|------------|---------|------------|
| `auth` | Laravel natif | Authentification requise |
| `admin` | `AdminOnly.php` | Admin ou Super Admin uniquement |
| `staff` | `StaffMiddleware.php` | Staff, Admin ou Super Admin |
| `role.creator` | `EnsureCreatorRole.php` | Rôle créateur uniquement |
| `creator.active` | `EnsureCreatorActive.php` | Créateur avec profil actif |

---

## 📍 ACCÈS PAR RÔLE

### 1️⃣ SUPER ADMINISTRATEUR

**Rôle Slug :** `super_admin`  
**ID :** 1

#### ✅ Pages Accessibles

**Admin Dashboard :**
- `/admin/dashboard` — Dashboard administrateur
- `/admin/users` — Gestion utilisateurs (CRUD)
- `/admin/roles` — Gestion rôles (CRUD)
- `/admin/products` — Gestion produits (CRUD)
- `/admin/orders` — Gestion commandes
- `/admin/categories` — Gestion catégories (CRUD)
- `/admin/cms/pages` — Gestion CMS pages
- `/admin/cms/sections` — Gestion CMS sections
- `/admin/orders/scan` — Scanner QR Code
- `/admin/stock-alerts` — Alertes stock

**Frontend :**
- `/` — Accueil (lecture seule)
- `/boutique` — Boutique (lecture seule)
- Toutes les pages publiques

**Authentification :**
- `/login` — Connexion
- `/logout` — Déconnexion

#### ❌ Pages Inaccessibles

- `/createur/*` — Espace créateur (réservé aux créateurs)
- `/compte` — Espace client (réservé aux clients)

---

### 2️⃣ ADMINISTRATEUR

**Rôle Slug :** `admin`  
**ID :** 2

#### ✅ Pages Accessibles

**Identique à Super Admin :**
- `/admin/dashboard` — Dashboard administrateur
- `/admin/users` — Gestion utilisateurs
- `/admin/products` — Gestion produits
- `/admin/orders` — Gestion commandes
- `/admin/categories` — Gestion catégories
- `/admin/cms/*` — Gestion CMS
- `/admin/orders/scan` — Scanner QR Code
- `/admin/stock-alerts` — Alertes stock

**Frontend :**
- Toutes les pages publiques

#### ❌ Pages Inaccessibles

- `/createur/*` — Espace créateur
- `/compte` — Espace client
- Gestion des autres administrateurs (selon permissions)

---

### 3️⃣ STAFF (Personnel)

**Rôle Slug :** `staff`  
**ID :** 3

#### ✅ Pages Accessibles

**Admin Dashboard :**
- `/admin/dashboard` — Dashboard (accès limité selon `staff_role`)
- `/admin/orders` — Gestion commandes (lecture/modification)
- `/admin/orders/scan` — Scanner QR Code
- `/admin/products` — Gestion produits (selon permissions)

**Frontend :**
- Toutes les pages publiques

#### ❌ Pages Inaccessibles

- `/admin/users` — Gestion utilisateurs
- `/admin/roles` — Gestion rôles
- `/createur/*` — Espace créateur
- `/compte` — Espace client

#### 📝 Sous-rôles Staff

- **Vendeur** (`staff_role: vendeur`) — Gestion ventes
- **Caissier** (`staff_role: caissier`) — Gestion caisse
- **Gestionnaire Stock** (`staff_role: gestionnaire_stock`) — Gestion stocks
- **Comptable** (`staff_role: comptable`) — Gestion finances

---

### 4️⃣ CRÉATEUR

**Rôle Slug :** `createur` / `creator`  
**ID :** 4

#### ✅ Pages Accessibles

**Espace Créateur :**
- `/createur/login` — Connexion créateur
- `/createur/register` — Inscription créateur
- `/createur/dashboard` — Dashboard créateur (si actif)
- `/createur/produits` — Gestion produits (CRUD)
- `/createur/produits/nouveau` — Créer produit
- `/createur/produits/{id}/edit` — Éditer produit
- `/createur/commandes` — Liste commandes
- `/createur/commandes/{id}` — Détail commande
- `/createur/finances` — Vue finances
- `/createur/stats` — Statistiques avancées
- `/createur/notifications` — Notifications
- `/createur/profil` — Profil créateur

**Pages de Statut :**
- `/createur/pending` — Compte en attente
- `/createur/suspended` — Compte suspendu

**Frontend :**
- Toutes les pages publiques

#### ❌ Pages Inaccessibles

- `/admin/*` — Back-office admin
- `/compte` — Espace client (lecture possible)

#### ⚠️ Restrictions

- **Statut `pending`** → Redirection vers `/createur/pending`
- **Statut `suspended`** → Redirection vers `/createur/suspended`
- **Profil manquant** → Redirection vers `/createur/register`

---

### 5️⃣ CLIENT

**Rôle Slug :** `client`  
**ID :** 5

#### ✅ Pages Accessibles

**Espace Client :**
- `/compte` — Dashboard client
- `/profil` — Profil utilisateur
- `/profil/commandes` — Liste commandes
- `/profil/commandes/{id}` — Détail commande
- `/profil/adresses` — Gestion adresses

**E-commerce :**
- `/boutique` — Catalogue produits
- `/produit/{id}` — Détail produit
- `/cart` — Panier
- `/cart/add` — Ajouter au panier
- `/checkout` — Checkout
- `/checkout/process` — Traitement commande

**Frontend :**
- Toutes les pages publiques

#### ❌ Pages Inaccessibles

- `/admin/*` — Back-office admin
- `/createur/*` — Espace créateur

---

## 🔄 REDIRECTIONS APRÈS CONNEXION

### Logique de Redirection

La redirection après connexion est gérée par :
- `app/Http/Controllers/Auth/Traits/HandlesAuthRedirect.php`
- Méthode : `getRedirectPath(User $user)`

### Routes de Redirection

| Rôle | Route de Redirection | URL |
|------|---------------------|-----|
| `super_admin` | `admin.dashboard` | `/admin/dashboard` |
| `admin` | `admin.dashboard` | `/admin/dashboard` |
| `staff` | `staff.dashboard` | `/staff/dashboard` |
| `createur` / `creator` | `creator.dashboard` | `/createur/dashboard` |
| `client` | `account.dashboard` | `/compte` |
| **Défaut** | `frontend.home` | `/` |

---

## 🔒 PROTECTION DES ROUTES

### Routes Admin (`/admin/*`)

```php
Route::middleware('admin')->group(function () {
    // Routes protégées
});
```

**Protection :**
- Middleware `admin` → Vérifie `admin` ou `super_admin`
- Authentification requise (`auth`)
- Redirection vers `/login` si non authentifié
- Erreur 403 si rôle incorrect

---

### Routes Créateur (`/createur/*`)

```php
Route::middleware(['auth', 'role.creator', 'creator.active'])->group(function () {
    // Routes protégées
});
```

**Protection :**
- `auth` → Authentification requise
- `role.creator` → Vérifie rôle créateur
- `creator.active` → Vérifie profil actif (pas pending/suspended)

**Redirections :**
- Non authentifié → `/createur/login`
- Pas de profil → `/createur/register`
- Statut `pending` → `/createur/pending`
- Statut `suspended` → `/createur/suspended`

---

### Routes Client (`/compte`, `/profil`)

```php
Route::middleware('auth')->group(function () {
    // Routes protégées
});
```

**Protection :**
- Authentification requise seulement
- Tous les rôles authentifiés peuvent accéder (avec vérification dans contrôleurs si nécessaire)

---

### Routes Publiques

```php
Route::middleware('throttle:60,1')->group(function () {
    // Routes publiques
});
```

**Protection :**
- Rate limiting : 60 requêtes/minute
- Pas d'authentification requise

---

## 📊 MATRICE D'ACCÈS COMPLÈTE

| Route | Super Admin | Admin | Staff | Créateur | Client | Public |
|-------|-------------|-------|-------|----------|--------|--------|
| `/` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/boutique` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| `/admin/dashboard` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `/admin/users` | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `/admin/products` | ✅ | ✅ | ⚠️ | ❌ | ❌ | ❌ |
| `/admin/orders` | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| `/createur/dashboard` | ❌ | ❌ | ❌ | ✅* | ❌ | ❌ |
| `/createur/produits` | ❌ | ❌ | ❌ | ✅* | ❌ | ❌ |
| `/createur/commandes` | ❌ | ❌ | ❌ | ✅* | ❌ | ❌ |
| `/compte` | ❌ | ❌ | ❌ | ❌ | ✅ | ❌ |
| `/profil` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| `/cart` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |
| `/checkout` | ✅ | ✅ | ✅ | ✅ | ✅ | ❌ |

**Légende :**
- ✅ Accès autorisé
- ❌ Accès refusé
- ⚠️ Accès conditionnel (selon permissions)
- ✅* Accès si statut `active` uniquement

---

## 🔧 VÉRIFICATION DES ACCÈS

### Méthodes du Modèle User

```php
// Vérifier un rôle spécifique
$user->hasRole('admin');

// Vérifier plusieurs rôles
$user->hasAnyRole(['admin', 'super_admin']);

// Vérifier si admin
$user->isAdmin();

// Vérifier si créateur
$user->isCreator();

// Vérifier si client
$user->isClient();

// Vérifier si staff/ERP
$user->isStaffOrAdmin();

// Obtenir le slug du rôle
$user->getRoleSlug(); // Retourne 'admin', 'client', etc.
```

### Dans les Contrôleurs

```php
// Vérifier le rôle
if (!$user->isAdmin()) {
    abort(403, 'Accès administrateur requis.');
}

// Redirection selon rôle
if ($user->isCreator()) {
    return redirect()->route('creator.dashboard');
}
```

### Dans les Vues Blade

```blade
{{-- Afficher selon le rôle --}}
@auth
    @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}">Admin</a>
    @endif
    
    @if(auth()->user()->isCreator())
        <a href="{{ route('creator.dashboard') }}">Créateur</a>
    @endif
    
    @if(auth()->user()->isClient())
        <a href="{{ route('account.dashboard') }}">Mon compte</a>
    @endif
@endauth
```

---

## ⚠️ PROBLÈMES POTENTIELS

### 1. Redirection incorrecte après connexion

**Symptôme :** Utilisateur redirigé vers une page incorrecte

**Solution :**
- Vérifier `getRoleSlug()` retourne le bon slug
- Vérifier la méthode `getRedirectPath()` dans `HandlesAuthRedirect`
- Vérifier que les routes existent

### 2. Accès refusé (403) alors que le rôle est correct

**Symptôme :** Erreur 403 même avec le bon rôle

**Solution :**
- Vérifier le middleware appliqué
- Vérifier `roleRelation` chargé : `$user->load('roleRelation')`
- Vérifier la valeur de `role` dans la table `users`

### 3. Créateur ne peut pas accéder au dashboard

**Symptôme :** Redirection vers `/createur/pending`

**Solution :**
- Vérifier `creator_profiles.status = 'active'`
- Vérifier que `creatorProfile` existe pour l'utilisateur
- Vérifier le middleware `creator.active`

---

## ✅ CHECKLIST DE VÉRIFICATION

### Configuration Base

- [ ] Table `roles` peuplée avec tous les rôles
- [ ] Table `users` avec `role_id` et `role` corrects
- [ ] Relations `User->roleRelation` fonctionnelles
- [ ] Méthodes `User->getRoleSlug()` fonctionnelles

### Middlewares

- [ ] Middleware `admin` enregistré dans `bootstrap/app.php`
- [ ] Middleware `role.creator` enregistré
- [ ] Middleware `creator.active` enregistré
- [ ] Middleware `staff` enregistré

### Routes

- [ ] Routes admin protégées par `admin`
- [ ] Routes créateur protégées par `role.creator` + `creator.active`
- [ ] Routes client protégées par `auth`
- [ ] Routes publiques avec rate limiting

### Redirections

- [ ] Redirection après login fonctionne pour tous les rôles
- [ ] Redirection créateur pending fonctionne
- [ ] Redirection créateur suspended fonctionne
- [ ] Redirection client fonctionne

---

## 🧪 TESTS À EFFECTUER

### Test Super Admin

```bash
# Connexion
Email: superadmin@racine.cm
Password: password

# Vérifier accès
- ✅ /admin/dashboard
- ✅ /admin/users
- ✅ /admin/products
- ❌ /createur/dashboard (doit être refusé)
- ❌ /compte (peut accéder mais pas recommandé)
```

### Test Admin

```bash
# Connexion
Email: admin@racine.cm
Password: password

# Vérifier accès
- ✅ /admin/dashboard
- ✅ /admin/users
- ❌ /createur/dashboard
```

### Test Staff

```bash
# Connexion
Email: staff@racine.cm
Password: password

# Vérifier accès
- ✅ /admin/dashboard (accès limité)
- ✅ /admin/orders
- ❌ /admin/users
- ❌ /createur/dashboard
```

### Test Créateur Actif

```bash
# Connexion
Email: createur@racine.cm
Password: password

# Vérifier accès
- ✅ /createur/dashboard
- ✅ /createur/produits
- ✅ /createur/commandes
- ❌ /admin/dashboard
```

### Test Créateur Pending

```bash
# Connexion
Email: createur.pending@racine.cm
Password: password

# Vérifier redirection
- → /createur/pending (automatique)
- ❌ /createur/dashboard (bloqué)
```

### Test Client

```bash
# Connexion
Email: client@racine.cm
Password: password

# Vérifier accès
- ✅ /compte
- ✅ /profil
- ✅ /boutique
- ✅ /cart
- ❌ /admin/dashboard
- ❌ /createur/dashboard
```

---

## 📝 COMMANDES UTILES

### Vérifier un utilisateur

```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'admin@racine.cm')->first();
$user->getRoleSlug(); // 'admin'
$user->isAdmin(); // true
$user->isCreator(); // false
```

### Vérifier les rôles

```php
\App\Models\Role::all();
```

### Corriger un rôle

```php
$user = \App\Models\User::where('email', 'user@example.com')->first();
$user->role_id = 1; // Super admin
$user->role = 'super_admin';
$user->is_admin = true;
$user->save();
```

---

**Dernière mise à jour :** 2025


