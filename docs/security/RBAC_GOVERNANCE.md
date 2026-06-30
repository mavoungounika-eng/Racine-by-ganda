# GOUVERNANCE RBAC — RACINE BY GANDA

**Version:** 1.0  
**Date:** 2026-01-19  
**Statut:** 🔒 **VERROUILLÉ**

---

## 🎯 DÉFINITIONS

### Role (Rôle)
**Regroupement métier** d'utilisateurs ayant des responsabilités similaires.

**Exemples :**
- `vendeur` : Personnel en contact client
- `caissier` : Personnel gérant les paiements
- `gestionnaire_stock` : Personnel gérant les stocks
- `admin` : Administrateur système

**⚠️ Important :** Un rôle ne décide RIEN. Il porte des permissions.

---

### Permission
**Unité atomique de pouvoir** autorisant une action spécifique.

**Exemples :**
- `view-products` : Voir les produits
- `edit-stock` : Modifier le stock
- `view-sales-analytics` : Voir les analytics ventes

**✅ Règle :** 1 permission = 1 action métier claire.

---

## 🚫 INTERDICTIONS ABSOLUES

### ❌ Code Interdit

```php
// ❌ INTERDIT
if ($user->role === 'admin') {
    // ...
}

// ❌ INTERDIT
if (in_array($user->getRoleSlug(), ['admin', 'staff'])) {
    // ...
}

// ❌ INTERDIT (dans Blade)
@if($user->role === 'admin')
    <!-- ... -->
@endif
```

### ✅ Code Correct

```php
// ✅ CORRECT
if ($user->hasPermission('edit-products')) {
    // ...
}

// ✅ CORRECT (Gate)
Gate::define('edit-products', fn(User $u) => 
    $u->hasPermission('edit-products')
);

// ✅ CORRECT (Blade)
@can('edit-products')
    <!-- ... -->
@endcan
```

---

## 📋 RÈGLES ABSOLUES

### Règle 1 : Permissions = Source Unique de Vérité

**Toute décision d'accès DOIT passer par la table `permissions`.**

❌ Pas de logique rôle dans le code  
❌ Pas de conditions métier dans les gates  
✅ Uniquement `User::hasPermission()`

---

### Règle 2 : 1 Gate = 1 Permission

**Chaque gate doit mapper exactement une permission.**

```php
// ✅ CORRECT
Gate::define('edit-products', fn(User $u) => 
    $u->hasPermission('edit-products')
);

// ❌ INTERDIT
Gate::define('edit-products', function (User $u) {
    return $u->hasPermission('edit-products') 
        || $u->hasPermission('manage-catalog');
});
```

---

### Règle 3 : Masquage UI ≠ Sécurité

**Le masquage UI reflète le backend, il ne le remplace pas.**

```php
// ✅ CORRECT : Backend protégé
Route::post('/admin/products', [ProductController::class, 'store'])
    ->middleware('can:create-products');

// + UI masquée
@can('create-products')
    <a href="{{ route('admin.products.create') }}">Ajouter</a>
@endcan
```

❌ Masquer un bouton sans protéger la route = **FAILLE SÉCURITÉ**

---

### Règle 4 : Super Admin Bypass

**Le super_admin a TOUS les droits via `Gate::before()`.**

```php
Gate::before(function (User $user, string $ability) {
    if ($user->getRoleSlug() === 'super_admin') {
        return true;
    }
});
```

⚠️ **Ne jamais modifier** cette logique sans validation sécurité.

---

## 🔄 PROCESSUS D'ÉVOLUTION

### Ajouter une Permission

**1. Justification Métier**

Documenter dans une PR :
- Quelle action métier ?
- Quels rôles concernés ?
- Quelle zone UI impactée ?

**2. Modification Seeder**

```php
// database/seeders/PermissionsSeeder.php
['slug' => 'nouvelle-permission', 'name' => 'Description', 'category' => 'categorie'],
```

**3. Mapping Rôles**

```php
// database/seeders/RolePermissionSeeder.php
'vendeur' => [
    'view-products',
    'nouvelle-permission', // ← Ajout
],
```

**4. Exécution Seeder**

```bash
php artisan db:seed --class=PermissionsSeeder
php artisan db:seed --class=RolePermissionSeeder
```

**5. Tests**

Créer test Feature vérifiant la permission.

---

### Ajouter un Rôle

**1. Création Rôle**

```sql
INSERT INTO roles (name, slug, created_at, updated_at) 
VALUES ('Nouveau Rôle', 'nouveau_role', NOW(), NOW());
```

**2. Mapping Permissions**

```php
// database/seeders/RolePermissionSeeder.php
'nouveau_role' => [
    'view-products',
    'view-orders',
],
```

**3. Documentation**

Ajouter dans `docs/security/ROLES_MAPPING.md` :
- Responsabilités métier
- Permissions accordées
- Zones UI visibles

---

## ⚠️ CE QUI EST INTERDIT

### ❌ Modifier un Seeder sans PR

**Toute modification de permissions DOIT passer par une Pull Request.**

Raison : Traçabilité et validation sécurité.

---

### ❌ Permission "Temporaire"

**Aucune permission ne doit être "temporaire".**

Si une permission est nécessaire temporairement :
1. Créer la permission
2. Documenter la raison
3. Planifier la suppression
4. Créer une issue de suivi

---

### ❌ Masquer UI sans Permission Backend

**Toute zone masquée DOIT avoir une protection backend.**

```php
// ❌ INTERDIT
@can('edit-products')
    <a href="{{ route('admin.products.create') }}">Ajouter</a>
@endcan

// Route NON protégée
Route::post('/admin/products', ...); // ← FAILLE
```

```php
// ✅ CORRECT
@can('create-products')
    <a href="{{ route('admin.products.create') }}">Ajouter</a>
@endcan

// Route protégée
Route::post('/admin/products', ...)
    ->middleware('can:create-products'); // ← SÉCURISÉ
```

---

## 📊 ARCHITECTURE ACTUELLE

### Tables

```
users
  ├── role_id → roles
                  └── permissions (via permission_role)
```

### Flux Décision

```
User → hasPermission('action')
         ↓
    Role::permissions
         ↓
    Permission (slug)
         ↓
    Gate::define('action')
         ↓
    @can('action') ou middleware
```

---

## 🧪 TESTS OBLIGATOIRES

### Pour Chaque Permission

```php
public function test_vendeur_can_view_products(): void
{
    $user = User::where('email', 'vendeur@racine.cm')->first();
    $this->assertTrue($user->hasPermission('view-products'));
}

public function test_vendeur_cannot_edit_stock(): void
{
    $user = User::where('email', 'vendeur@racine.cm')->first();
    $this->assertFalse($user->hasPermission('edit-stock'));
}
```

### Pour Chaque Route Protégée

```php
public function test_vendeur_cannot_access_stock_route(): void
{
    $user = User::where('email', 'vendeur@racine.cm')->first();
    
    $response = $this->actingAs($user)->get('/admin/stock');
    
    $response->assertStatus(403);
}
```

---

## 📚 DOCUMENTATION ASSOCIÉE

- `database/seeders/PermissionsSeeder.php` : Liste permissions
- `database/seeders/RolePermissionSeeder.php` : Mapping rôles
- `app/Providers/AuthServiceProvider.php` : Gates
- `tests/Feature/Admin/RBACPermissionsTest.php` : Tests

---

## 🔒 RÈGLE D'OR

> **Toute décision RBAC se fait dans les seeders, jamais dans le code métier.**

**Backend = Vérité**  
**UI = Reflet**

---

## ✅ CHECKLIST MODIFICATION RBAC

Avant toute modification RBAC :

- [ ] Justification métier documentée
- [ ] Permission ajoutée dans `PermissionsSeeder`
- [ ] Mapping rôles mis à jour dans `RolePermissionSeeder`
- [ ] Gate créé/modifié dans `AuthServiceProvider`
- [ ] Route protégée par middleware `can:`
- [ ] UI masquée par `@can()`
- [ ] Tests Feature créés
- [ ] Tests passent (CI/CD)
- [ ] PR validée par équipe sécurité

---

**Document créé par:** Antigravity  
**Date:** 2026-01-19  
**Statut:** 🔒 **OFFICIEL — NE PAS MODIFIER SANS VALIDATION**
