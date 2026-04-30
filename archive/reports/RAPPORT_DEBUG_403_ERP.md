# 🔍 RAPPORT DE DEBUG - Erreur 403 Module ERP

**Date :** 8 décembre 2025  
**Problème :** Accès refusé (403) sur `/erp`

---

## 🔍 DIAGNOSTIC

### Erreur
```
403 THIS ACTION IS UNAUTHORIZED
Route: /erp
```

### Cause probable
L'utilisateur connecté n'a pas un rôle autorisé pour accéder au module ERP.

---

## ✅ CORRECTIONS APPLIQUÉES

### 1. Page d'erreur 403 personnalisée
**Fichier créé :** `resources/views/errors/403.blade.php`

**Fonctionnalités :**
- ✅ Message d'erreur personnalisé selon la route (`/erp`, `/admin`, etc.)
- ✅ Affichage du rôle actuel de l'utilisateur
- ✅ Liste des rôles autorisés pour le module ERP
- ✅ Boutons de navigation (retour dashboard, retour arrière)
- ✅ Design moderne et professionnel

### 2. Gate `access-erp` vérifié
**Fichier :** `app/Providers/AuthServiceProvider.php` (ligne 170-173)

```php
Gate::define('access-erp', function (User $user) {
    $role = $user->getRoleSlug();
    return in_array($role, ['super_admin', 'admin', 'staff']);
});
```

**Rôles autorisés :**
- ✅ `super_admin`
- ✅ `admin`
- ✅ `staff`

---

## 🔧 SOLUTIONS POUR RÉSOUDRE LE PROBLÈME

### Option 1 : Vérifier le rôle de l'utilisateur

1. **Vérifier dans la base de données :**
```sql
SELECT id, name, email, role, role_id FROM users WHERE id = [VOTRE_ID];
```

2. **Vérifier via Tinker :**
```bash
php artisan tinker
```
```php
$user = User::find([VOTRE_ID]);
$user->getRoleSlug(); // Doit retourner 'admin', 'super_admin' ou 'staff'
```

### Option 2 : Assigner un rôle autorisé

Si l'utilisateur n'a pas le bon rôle, vous pouvez :

1. **Via la base de données :**
```sql
UPDATE users 
SET role = 'admin' 
WHERE id = [VOTRE_ID];
```

2. **Via Tinker :**
```php
$user = User::find([VOTRE_ID]);
$user->role = 'admin';
$user->save();
```

3. **Via l'interface admin :**
- Aller sur `/admin/users`
- Modifier l'utilisateur
- Changer le rôle vers `admin`, `super_admin` ou `staff`

### Option 3 : Vérifier la relation roleRelation

Si l'utilisateur a un `role_id` mais pas de `role` direct :

```php
$user = User::find([VOTRE_ID]);
$user->load('roleRelation');
echo $user->roleRelation->slug; // Doit être 'admin', 'super_admin' ou 'staff'
```

---

## 📋 CHECKLIST DE VÉRIFICATION

- [ ] L'utilisateur est bien connecté
- [ ] L'utilisateur a un rôle défini (`role` ou `role_id`)
- [ ] Le rôle est parmi : `admin`, `super_admin`, `staff`
- [ ] La relation `roleRelation` est chargée si `role_id` existe
- [ ] La méthode `getRoleSlug()` retourne le bon slug

---

## 🧪 TEST

Pour tester l'accès ERP :

1. **Se connecter avec un utilisateur admin :**
```php
// Dans Tinker
$user = User::where('email', 'admin@example.com')->first();
Auth::login($user);
```

2. **Vérifier le Gate :**
```php
Gate::allows('access-erp'); // Doit retourner true
```

3. **Accéder à la route :**
```
GET /erp
```

---

## 📝 NOTES

- La page d'erreur 403 affiche maintenant le rôle actuel de l'utilisateur
- Le message est personnalisé selon la route accédée
- Les boutons de navigation permettent de revenir facilement

---

**Prochaine étape :** Vérifier le rôle de l'utilisateur connecté et s'assurer qu'il a un rôle autorisé.

