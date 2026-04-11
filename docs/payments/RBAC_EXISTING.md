# 🔐 RBAC Existant — RACINE BY GANDA

**Date :** 2025-12-14  
**Sprint :** Sprint 1 — Audit  
**Ticket :** #PH1-002

---

## 🎯 OBJECTIF

Comprendre le système RBAC existant pour intégrer les permissions Payments Hub sans casser l'existant.

---

## 🔍 SYSTÈME RBAC IDENTIFIÉ

### Type : Gates (Laravel natif)

**Pas de Spatie Permission détecté** dans le projet.

**Fichier principal :** `app/Providers/AuthServiceProvider.php`

**Méthode :** Utilisation de `Gate::define()` pour créer des permissions granulaires.

---

## 👥 RÔLES EXISTANTS

### Rôles identifiés

| Slug | ID | Description |
|------|----|-----------|
| `super_admin` | 1 | Super administrateur (accès complet) |
| `admin` | 2 | Administrateur (gestion standard) |
| `staff` | 3 | Personnel interne (vendeur, caissier, etc.) |
| `createur` / `creator` | 4 | Vendeur marketplace |
| `client` | 5 | Client boutique |

### Modèle User

**Fichier :** `app/Models/User.php`

**Méthodes RBAC disponibles :**
- `getRoleSlug()` : Retourne le slug du rôle
- `hasRole(string $role)` : Vérifie un rôle spécifique
- `hasAnyRole(array $roles)` : Vérifie plusieurs rôles
- `isAdmin()` : Vérifie si admin ou super_admin
- `isCreator()` : Vérifie si créateur
- `isClient()` : Vérifie si client
- `isStaffOrAdmin()` : Vérifie si staff/admin/super_admin

---

## 🛡️ GATES EXISTANTS

### Gates produits

```php
Gate::define('view-products', function (User $user) {
    return true; // Tous peuvent voir
});

Gate::define('create-products', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
});

Gate::define('edit-products', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
});

Gate::define('delete-products', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['admin', 'super_admin']);
});
```

### Gates commandes

```php
Gate::define('view-orders', function (User $user) {
    return true; // Tous peuvent voir leurs commandes
});

Gate::define('view-all-orders', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
});

Gate::define('edit-orders', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
});
```

### Gates utilisateurs

```php
Gate::define('view-users', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
});

Gate::define('create-users', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['admin', 'super_admin']);
});
```

### Gates dashboards

```php
Gate::define('view-dashboard', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['admin', 'moderator', 'super_admin']);
});

Gate::define('view-analytics', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['admin', 'super_admin']);
});
```

### Gates accès par rôle

```php
Gate::define('access-super-admin', function (User $user) {
    return $user->getRoleSlug() === 'super_admin';
});

Gate::define('access-admin', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['super_admin', 'admin']);
});

Gate::define('access-staff', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['super_admin', 'admin', 'staff']);
});
```

---

## 🔒 MIDDLEWARE D'ACCÈS ADMIN

### Middleware `admin`

**Fichier :** `app/Http/Middleware/AdminOnly.php`

**Fonction :** Vérifie que l'utilisateur est admin ou super_admin.

**Utilisation :** Appliqué sur toutes les routes admin via `Route::middleware('admin')`.

---

## 📋 PERMISSIONS PAYMENTS HUB À CRÉER

### Permissions requises (Sprint 2)

**À ajouter dans `AuthServiceProvider.php` :**

```php
// Payments Hub Permissions
Gate::define('payments.view', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['super_admin', 'admin', 'staff']); // + finance si existe
});

Gate::define('payments.config', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['super_admin', 'admin']);
});

Gate::define('payments.reprocess', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['super_admin', 'admin', 'staff']); // + finance si existe
});

Gate::define('payments.refund', function (User $user) {
    $roleSlug = $user->getRoleSlug();
    return in_array($roleSlug, ['super_admin', 'admin']);
});
```

### Mapping rôles → permissions

| Permission | Super Admin | Admin | Staff | Finance* |
|------------|-------------|-------|-------|----------|
| `payments.view` | ✅ | ✅ | ✅ | ✅ |
| `payments.config` | ✅ | ✅ | ❌ | ❌ |
| `payments.reprocess` | ✅ | ✅ | ✅ | ✅ |
| `payments.refund` | ✅ | ✅ | ❌ | ❌ |

*Note : Rôle "finance" à vérifier s'il existe dans le système.*

---

## 🔗 POINTS D'INTÉGRATION

### 1. Routes

**Protection des routes :**
```php
Route::middleware(['admin', 'can:payments.view'])->group(function () {
    Route::get('payments', [PaymentHubController::class, 'index'])->name('payments.index');
});
```

### 2. Controllers

**Vérification dans les méthodes :**
```php
public function index()
{
    $this->authorize('payments.view');
    // ...
}
```

### 3. Vues Blade

**Protection des menus et actions :**
```blade
@can('payments.view')
    <a href="{{ route('admin.payments.index') }}">Paiements</a>
@endcan

@can('payments.config')
    <button>Configurer</button>
@endcan
```

---

## ✅ CHECKLIST INTÉGRATION

- [x] Système RBAC identifié (Gates Laravel)
- [x] Rôles existants listés
- [x] Gates existants documentés
- [x] Middleware admin identifié
- [x] Permissions Payments Hub définies
- [x] Mapping rôles → permissions documenté
- [x] Points d'intégration identifiés

---

## 📝 NOTES IMPORTANTES

1. **Pas de Spatie Permission** : Utiliser uniquement les Gates Laravel natifs.

2. **Méthode `getRoleSlug()`** : Utiliser cette méthode pour obtenir le slug du rôle de l'utilisateur.

3. **Middleware `admin`** : Déjà appliqué sur toutes les routes admin, donc les routes Payments Hub seront automatiquement protégées.

4. **Permissions granulaires** : Créer 4 permissions distinctes pour un contrôle fin (`view`, `config`, `reprocess`, `refund`).

5. **Rôle Finance** : Vérifier si un rôle "finance" existe dans le système. Si oui, l'ajouter aux permissions `payments.view` et `payments.reprocess`.

---

**Document créé le :** 2025-12-14  
**Prochaine étape :** Créer les Gates Payments Hub dans Sprint 2 (#PH3-001)









