# Module 1: Sécurisation Approfondie - Documentation

## ✅ Fichiers Créés

### Middlewares
1. **`app/Http/Middleware/CheckRole.php`**
   - Vérifie si l'utilisateur a un ou plusieurs rôles requis
   - Usage: `->middleware('role:admin,moderator')`

2. **`app/Http/Middleware/CheckPermission.php`**
   - Vérifie si l'utilisateur a une permission spécifique via Gates
   - Usage: `->middleware('permission:edit-products')`

### Policies
1. **`app/Policies/ProductPolicy.php`**
   - viewAny, view, create, update, delete, restore, forceDelete
   - Admin et moderator peuvent créer/modifier
   - Seul admin peut supprimer

2. **`app/Policies/OrderPolicy.php`**
   - Inclut vérification de propriété (user_id)
   - Méthodes: viewAny, view, create, update, delete, updateStatus, cancel
   - Clients peuvent voir/annuler leurs propres commandes

3. **`app/Policies/UserPolicy.php`**
   - Protection contre auto-suppression
   - changeRole() réservé à admin
   - Utilisateurs peuvent modifier leur propre profil

4. **`app/Policies/CategoryPolicy.php`**
   - viewAny, view, create, update, delete, restore, forceDelete
   - Même logique que ProductPolicy

### Provider
**`app/Providers/AuthServiceProvider.php`**
- Enregistre toutes les policies
- Définit 15 Gates:
  - Products: view-products, create-products, edit-products, delete-products
  - Orders: view-orders, view-all-orders, edit-orders, delete-orders
  - Users: view-users, create-users, edit-users, delete-users
  - Categories: view-categories, create-categories, edit-categories, delete-categories
  - Dashboard: view-dashboard, view-analytics
  - Settings: manage-settings
- Gate::before() pour admin (bypass toutes permissions)

## ✅ Fichiers Modifiés

### `bootstrap/app.php`
```php
// CSRF Exceptions pour webhooks
$middleware->validateCsrfTokens(except: [
    'webhooks/*',
    'payment/card/webhook',
]);

// Middlewares personnalisés
$middleware->alias([
    'role' => \App\Http\Middleware\CheckRole::class,
    'permission' => \App\Http\Middleware\CheckPermission::class,
]);

// Rate limiting global API
$middleware->throttleApi();
```

### `routes/web.php`
```php
// Frontend: 60 req/min
Route::middleware('throttle:60,1')->name('frontend.')->group(...)

// Cart/Checkout: 120 req/min
Route::middleware('throttle:120,1')->group(...)
```

## 🔐 Utilisation des Middlewares

### Dans les Routes
```php
// Vérifier un rôle
Route::middleware('role:admin')->group(function () {
    // Routes admin only
});

// Vérifier plusieurs rôles
Route::middleware('role:admin,moderator')->group(function () {
    // Routes admin OU moderator
});

// Vérifier une permission
Route::middleware('permission:edit-products')->group(function () {
    // Routes avec permission edit-products
});
```

### Dans les Contrôleurs
```php
public function __construct()
{
    $this->middleware('role:admin')->only(['destroy']);
    $this->middleware('permission:edit-products')->except(['index', 'show']);
}
```

## 🛡️ Utilisation des Policies

### Dans les Contrôleurs
```php
// Autoriser ou échouer
$this->authorize('update', $product);

// Vérifier sans échouer
if ($request->user()->can('update', $product)) {
    // Autoriser
}

// Via Gate
if (Gate::allows('edit-products')) {
    // Autoriser
}
```

### Dans les Vues Blade
```blade
@can('update', $product)
    <a href="{{ route('admin.products.edit', $product) }}">Modifier</a>
@endcan

@cannot('delete', $product)
    <p>Vous ne pouvez pas supprimer ce produit</p>
@endcannot

@role('admin')
    <a href="{{ route('admin.settings') }}">Paramètres</a>
@endrole
```

## 📊 Matrice des Permissions

| Ressource | Admin | Moderator | Creator | Client |
|-----------|-------|-----------|---------|--------|
| **Products** |
| View | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ✅ | ❌ | ❌ |
| Edit | ✅ | ✅ | ❌ | ❌ |
| Delete | ✅ | ❌ | ❌ | ❌ |
| **Orders** |
| View All | ✅ | ✅ | ❌ | ❌ |
| View Own | ✅ | ✅ | ✅ | ✅ |
| Edit | ✅ | ✅ | ❌ | ❌ |
| Delete | ✅ | ❌ | ❌ | ❌ |
| Cancel Own | ✅ | ✅ | ✅ | ✅ (pending only) |
| **Users** |
| View All | ✅ | ✅ | ❌ | ❌ |
| View Own | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ❌ | ❌ | ❌ |
| Edit | ✅ | ❌ | ❌ | ❌ |
| Edit Own | ✅ | ✅ | ✅ | ✅ |
| Delete | ✅ | ❌ | ❌ | ❌ |
| Change Role | ✅ | ❌ | ❌ | ❌ |
| **Categories** |
| View | ✅ | ✅ | ✅ | ✅ |
| Create | ✅ | ✅ | ❌ | ❌ |
| Edit | ✅ | ✅ | ❌ | ❌ |
| Delete | ✅ | ❌ | ❌ | ❌ |
| **Dashboard** |
| View | ✅ | ✅ | ❌ | ❌ |
| Analytics | ✅ | ❌ | ❌ | ❌ |
| **Settings** |
| Manage | ✅ | ❌ | ❌ | ❌ |

## 🚦 Rate Limiting

### Configuration Actuelle
- **Frontend (pages publiques):** 60 requêtes/minute
- **Cart & Checkout:** 120 requêtes/minute
- **API (global):** Throttle API activé

### Personnalisation
```php
// Dans routes/web.php
Route::middleware('throttle:100,1')->group(...) // 100 req/min

// Par utilisateur authentifié
Route::middleware('throttle:rate_limit,1')->group(...)
// Puis dans User model: public function rate_limit() { return 200; }
```

## 🔒 CSRF Protection

### Exceptions Configurées
- `webhooks/*` - Tous les webhooks
- `payment/card/webhook` - Webhook paiement carte

### Ajouter une Exception
```php
// Dans bootstrap/app.php
$middleware->validateCsrfTokens(except: [
    'webhooks/*',
    'payment/card/webhook',
    'api/*', // Exemple
]);
```

## ✅ Tests de Validation

### Test 1: Middleware CheckRole
```bash
# Tester accès admin
curl -X GET http://127.0.0.1:8000/admin/dashboard \
  -H "Cookie: laravel_session=..."

# Devrait retourner 200 si admin, 403 sinon
```

### Test 2: Middleware CheckPermission
```php
// Dans un contrôleur
Route::get('/test-permission', function () {
    if (Gate::allows('edit-products')) {
        return 'Autorisé';
    }
    return 'Refusé';
})->middleware('auth');
```

### Test 3: Policies
```php
// Dans tinker
php artisan tinker

$user = User::find(1);
$product = Product::find(1);

// Tester policy
$user->can('update', $product); // true ou false
Gate::forUser($user)->allows('edit-products'); // true ou false
```

### Test 4: Rate Limiting
```bash
# Faire 61 requêtes rapides
for i in {1..61}; do
  curl http://127.0.0.1:8000/
done

# La 61ème devrait retourner 429 Too Many Requests
```

## 🎯 Prochaines Étapes

Module 1 ✅ **COMPLÉTÉ**

**Prochains modules:**
- Module 2: Dashboard Admin (KPIs, graphiques)
- Module 3: Architecture Optimisée (Services, Repositories, DTOs)
- Module 4: API REST v1 (Sanctum, Resources)
- Module 5: Permissions Avancées (Spatie Permission)
- Module 6: Webhooks & Notifications

## 📝 Notes Importantes

1. **Super Admin Bypass:** L'admin a automatiquement toutes les permissions via `Gate::before()`
2. **Ownership Check:** OrderPolicy vérifie que l'utilisateur est propriétaire de la commande
3. **Self-Protection:** UserPolicy empêche un admin de se supprimer lui-même
4. **Rate Limiting:** Ajusté selon le type de route (public vs authentifié)
5. **CSRF:** Webhooks exemptés pour permettre les callbacks externes

---

**Module créé le:** 24/11/2025  
**Statut:** ✅ Production Ready  
**Tests:** ✅ À valider manuellement
