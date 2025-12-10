# ✅ CHECKLIST DE VÉRIFICATION — ACCÈS & RÔLES

**Date :** 2025  
**Projet :** RACINE BY GANDA

---

## 📋 RÉSUMÉ EXÉCUTIF

Ce document liste les vérifications à effectuer pour s'assurer que tous les accès aux pages et les rôles sont correctement configurés.

---

## ✅ VÉRIFICATIONS BASE DE DONNÉES

### 1. Table `roles`

```sql
SELECT * FROM roles ORDER BY id;
```

**Résultat attendu :**
- `id: 1, slug: super_admin, name: Super Administrateur`
- `id: 2, slug: admin, name: Administrateur`
- `id: 3, slug: staff, name: Staff`
- `id: 4, slug: createur, name: Créateur`
- `id: 5, slug: client, name: Client`

**Action si manquant :**
```bash
php artisan db:seed --class=RolesTableSeeder
```

---

### 2. Table `users` — Rôles assignés

```sql
SELECT id, email, role_id, role, is_admin, status FROM users;
```

**Vérifications :**
- [ ] Chaque utilisateur a un `role_id` valide (1-5)
- [ ] Le champ `role` correspond au slug du rôle
- [ ] Les admins ont `is_admin = 1` OU `role_id = 1 ou 2`
- [ ] Les utilisateurs actifs ont `status = 'active'`

---

### 3. Table `creator_profiles` — Profils créateurs

```sql
SELECT user_id, status, brand_name FROM creator_profiles;
```

**Vérifications :**
- [ ] Tous les utilisateurs avec `role_id = 4` ont un `creator_profile`
- [ ] Le statut est `pending`, `active` ou `suspended`

---

## ✅ VÉRIFICATIONS MIDDLEWARES

### 1. Enregistrement des middlewares

**Fichier :** `bootstrap/app.php`

**Vérifier :**
```php
$middleware->alias([
    'admin' => \App\Http\Middleware\AdminOnly::class,
    'role.creator' => \App\Http\Middleware\EnsureCreatorRole::class,
    'creator.active' => \App\Http\Middleware\EnsureCreatorActive::class,
    'staff' => \App\Http\Middleware\StaffMiddleware::class,
]);
```

**Action si manquant :** Ajouter les alias manquants.

---

### 2. Test des middlewares

#### Test `admin`

```bash
# Connexion admin
curl -X POST http://127.0.0.1:8000/login \
  -d "email=admin@racine.cm&password=password"

# Accès admin
curl http://127.0.0.1:8000/admin/dashboard

# Doit retourner 200 OK (si authentifié) ou 302 redirect vers /login
```

#### Test `role.creator`

```bash
# Connexion créateur
curl -X POST http://127.0.0.1:8000/createur/login \
  -d "email=createur@racine.cm&password=password"

# Accès créateur
curl http://127.0.0.1:8000/createur/dashboard

# Doit retourner 200 OK (si authentifié) ou 302 redirect vers /createur/login
```

#### Test `creator.active`

```bash
# Connexion créateur pending
curl -X POST http://127.0.0.1:8000/createur/login \
  -d "email=createur.pending@racine.cm&password=password"

# Accès créateur
curl http://127.0.0.1:8000/createur/dashboard

# Doit retourner 302 redirect vers /createur/pending
```

---

## ✅ VÉRIFICATIONS ROUTES

### 1. Routes Admin

**Fichier :** `routes/web.php`

**Vérifier :**
```php
Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
    Route::get('dashboard', ...)->name('dashboard');
    // ...
});
```

**Routes à vérifier :**
- [ ] `/admin/dashboard` → `admin.dashboard`
- [ ] `/admin/users` → `admin.users.index`
- [ ] `/admin/products` → `admin.products.index`
- [ ] `/admin/orders` → `admin.orders.index`
- [ ] `/admin/cms/pages` → `admin.cms.pages.index`

**Test :**
```bash
php artisan route:list --path=admin
```

---

### 2. Routes Créateur

**Vérifier :**
```php
Route::prefix('createur')->name('creator.')->middleware(['auth', 'role.creator', 'creator.active'])->group(function () {
    Route::get('dashboard', ...)->name('dashboard');
    // ...
});
```

**Routes à vérifier :**
- [ ] `/createur/dashboard` → `creator.dashboard`
- [ ] `/createur/produits` → `creator.products.index`
- [ ] `/createur/commandes` → `creator.orders.index`
- [ ] `/createur/finances` → `creator.finances.index`

**Test :**
```bash
php artisan route:list --path=createur
```

---

### 3. Routes Client

**Vérifier :**
```php
Route::middleware('auth')->group(function () {
    Route::get('/compte', ...)->name('account.dashboard');
    Route::get('/profil', ...)->name('profile.index');
});
```

**Routes à vérifier :**
- [ ] `/compte` → `account.dashboard`
- [ ] `/profil` → `profile.index`
- [ ] `/profil/commandes` → `profile.orders`

**Test :**
```bash
php artisan route:list --path=compte
php artisan route:list --path=profil
```

---

## ✅ VÉRIFICATIONS REDIRECTIONS

### 1. Après Connexion

**Fichier :** `app/Http/Controllers/Auth/LoginController.php`

**Logique :**
```php
protected function getRedirectPath(User $user): string
{
    $roleSlug = $user->getRoleSlug() ?? 'client';
    
    return match($roleSlug) {
        'client' => route('account.dashboard'),
        'createur', 'creator' => route('creator.dashboard'),
        'staff' => route('staff.dashboard'),
        'admin', 'super_admin' => route('admin.dashboard'),
        default => route('frontend.home'),
    };
}
```

**Tests :**

#### Test Super Admin
```bash
# Connexion
POST /login
email=superadmin@racine.cm&password=password

# Résultat attendu : Redirect vers /admin/dashboard
```

#### Test Admin
```bash
# Connexion
POST /login
email=admin@racine.cm&password=password

# Résultat attendu : Redirect vers /admin/dashboard
```

#### Test Staff
```bash
# Connexion
POST /login
email=staff@racine.cm&password=password

# Résultat attendu : Redirect vers /staff/dashboard
```

#### Test Créateur
```bash
# Connexion
POST /createur/login
email=createur@racine.cm&password=password

# Résultat attendu : Redirect vers /createur/dashboard (si actif)
# OU Redirect vers /createur/pending (si pending)
# OU Redirect vers /createur/login avec erreur (si suspended)
```

#### Test Client
```bash
# Connexion
POST /login
email=client@racine.cm&password=password

# Résultat attendu : Redirect vers /compte
```

---

### 2. Redirections Créateur (Statut)

**Fichier :** `app/Http/Middleware/EnsureCreatorActive.php`

**Logique :**
- Si pas de profil → `/createur/register`
- Si `pending` → `/createur/pending`
- Si `suspended` → `/createur/suspended`
- Si `active` → Continuer

**Tests :**
- [ ] Créateur sans profil → Redirect `/createur/register`
- [ ] Créateur `pending` → Redirect `/createur/pending`
- [ ] Créateur `suspended` → Redirect `/createur/suspended`
- [ ] Créateur `active` → Accès autorisé

---

## ✅ VÉRIFICATIONS MÉTHODES USER MODEL

### 1. `getRoleSlug()`

**Fichier :** `app/Models/User.php`

**Test :**
```php
$user = User::where('email', 'admin@racine.cm')->first();
$user->load('roleRelation');
$roleSlug = $user->getRoleSlug();

// Doit retourner 'admin'
```

**Vérifier :**
- [ ] Retourne le bon slug pour tous les rôles
- [ ] Gère le cas où `roleRelation` n'existe pas
- [ ] Gère le cas où `role` est défini directement

---

### 2. `isAdmin()`

**Test :**
```php
$admin = User::where('email', 'admin@racine.cm')->first();
$admin->isAdmin(); // Doit retourner true

$client = User::where('email', 'client@racine.cm')->first();
$client->isAdmin(); // Doit retourner false
```

---

### 3. `isCreator()`

**Test :**
```php
$creator = User::where('email', 'createur@racine.cm')->first();
$creator->isCreator(); // Doit retourner true

$client = User::where('email', 'client@racine.cm')->first();
$client->isCreator(); // Doit retourner false
```

---

### 4. `hasRole()`

**Test :**
```php
$user = User::where('email', 'admin@racine.cm')->first();
$user->hasRole('admin'); // Doit retourner true
$user->hasRole('client'); // Doit retourner false
```

---

## ✅ VÉRIFICATIONS ACCÈS PAGES

### Test Manuel — Navigation

#### Super Admin
1. [ ] Connexion → `/admin/dashboard` ✅
2. [ ] Accès `/admin/users` ✅
3. [ ] Accès `/admin/products` ✅
4. [ ] Accès `/admin/orders` ✅
5. [ ] Accès `/createur/dashboard` ❌ (doit être refusé)
6. [ ] Accès `/compte` ❌ (peut accéder mais pas recommandé)

#### Admin
1. [ ] Connexion → `/admin/dashboard` ✅
2. [ ] Accès `/admin/users` ✅
3. [ ] Accès `/admin/products` ✅
4. [ ] Accès `/createur/dashboard` ❌

#### Staff
1. [ ] Connexion → `/staff/dashboard` ✅
2. [ ] Accès `/admin/dashboard` ✅ (accès limité)
3. [ ] Accès `/admin/orders` ✅
4. [ ] Accès `/admin/users` ❌
5. [ ] Accès `/createur/dashboard` ❌

#### Créateur Actif
1. [ ] Connexion → `/createur/dashboard` ✅
2. [ ] Accès `/createur/produits` ✅
3. [ ] Accès `/createur/commandes` ✅
4. [ ] Accès `/createur/finances` ✅
5. [ ] Accès `/admin/dashboard` ❌

#### Créateur Pending
1. [ ] Connexion → Redirect `/createur/pending` ✅
2. [ ] Accès `/createur/dashboard` ❌ (bloqué)

#### Créateur Suspended
1. [ ] Connexion → Redirect `/createur/suspended` ✅
2. [ ] Accès `/createur/dashboard` ❌ (bloqué)

#### Client
1. [ ] Connexion → `/compte` ✅
2. [ ] Accès `/profil` ✅
3. [ ] Accès `/boutique` ✅
4. [ ] Accès `/cart` ✅
5. [ ] Accès `/admin/dashboard` ❌
6. [ ] Accès `/createur/dashboard` ❌

---

## 🔧 COMMANDES DE CORRECTION

### Corriger un rôle utilisateur

```bash
php artisan tinker
```

```php
$user = User::where('email', 'user@example.com')->first();
$user->role_id = 1; // Super admin
$user->role = 'super_admin';
$user->is_admin = true;
$user->save();
```

### Créer un profil créateur

```php
$user = User::where('email', 'createur@example.com')->first();
CreatorProfile::create([
    'user_id' => $user->id,
    'brand_name' => 'Ma Marque',
    'status' => 'active',
]);
```

### Changer le statut d'un créateur

```php
$profile = CreatorProfile::where('user_id', $user->id)->first();
$profile->status = 'active'; // ou 'pending', 'suspended'
$profile->save();
```

---

## 📝 NOTES IMPORTANTES

1. **Toujours charger `roleRelation` avant d'utiliser `getRoleSlug()`**
   ```php
   $user->load('roleRelation');
   $roleSlug = $user->getRoleSlug();
   ```

2. **Vérifier les redirections dans les contrôleurs**
   - `LoginController` utilise `HandlesAuthRedirect`
   - `CreatorAuthController` a sa propre logique

3. **Middleware `creator.active` doit être appliqué APRÈS `role.creator`**
   ```php
   Route::middleware(['auth', 'role.creator', 'creator.active'])
   ```

4. **Routes legacy à éviter**
   - `/atelier-creator` → Utiliser `/createur/dashboard`
   - `/dashboard/client` → Utiliser `/compte`

---

## ✅ CHECKLIST FINALE

- [ ] Tous les rôles existent dans la table `roles`
- [ ] Tous les utilisateurs ont un `role_id` valide
- [ ] Les middlewares sont enregistrés
- [ ] Les routes sont protégées par les bons middlewares
- [ ] Les redirections fonctionnent pour tous les rôles
- [ ] Les méthodes du modèle `User` fonctionnent
- [ ] Les tests manuels passent

---

**Dernière mise à jour :** 2025


