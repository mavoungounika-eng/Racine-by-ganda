# ✅ SOLUTION — ACCÈS AUX COMPTES UTILISATEUR

**Date :** 1 Décembre 2025  
**Problème :** Impossible d'accéder aux comptes utilisateur

---

## 🚀 SOLUTION RAPIDE

### Étape 1 : Corriger les comptes de test

Exécutez cette commande pour corriger automatiquement tous les comptes :

```bash
php artisan accounts:fix-test
```

Cette commande va :
- ✅ Créer/corriger tous les comptes de test
- ✅ Réinitialiser les mots de passe à `password`
- ✅ Assigner les rôles corrects
- ✅ Activer tous les comptes
- ✅ Désactiver la 2FA
- ✅ Créer les profils créateurs manquants

### Étape 2 : Nettoyer les caches

```bash
php artisan optimize:clear
```

### Étape 3 : Tester la connexion

1. Aller sur `/login`
2. Utiliser un compte de test :
   - **Email :** `client@racine.cm`
   - **Mot de passe :** `password`

---

## 📋 COMPTES DE TEST DISPONIBLES

Après avoir exécuté `php artisan accounts:fix-test`, vous pouvez utiliser :

### Client
- **Email :** `client@racine.cm`
- **Mot de passe :** `password`
- **URL :** `/login`

### Créateur
- **Email :** `createur@racine.cm`
- **Mot de passe :** `password`
- **URL :** `/createur/login` ou `/login`

### Admin
- **Email :** `admin@racine.cm`
- **Mot de passe :** `password`
- **URL :** `/login` (redirige vers `/admin/dashboard`)

### Super Admin
- **Email :** `superadmin@racine.cm`
- **Mot de passe :** `password`
- **URL :** `/login` (redirige vers `/admin/dashboard`)

---

## 🔍 DIAGNOSTIC SI ÇA NE FONCTIONNE TOUJOURS PAS

### Vérifier que les routes fonctionnent

```bash
php artisan route:list --name=login
```

Vous devriez voir :
- `GET /login` → `LoginController@showLoginForm`
- `POST /login` → `LoginController@login`

### Vérifier que les utilisateurs existent

```bash
php artisan tinker
```

```php
\App\Models\User::count(); // Doit être > 0
\App\Models\User::pluck('email'); // Liste des emails
```

### Vérifier un utilisateur spécifique

```php
$user = \App\Models\User::where('email', 'client@racine.cm')->first();

// Vérifications
$user->status; // Doit être 'active'
$user->roleRelation; // Doit retourner un rôle
$user->getRoleSlug(); // Doit retourner 'client'
\Hash::check('password', $user->password); // Doit être true
```

### Vérifier les logs

```bash
tail -f storage/logs/laravel.log
```

Puis essayez de vous connecter et regardez les erreurs.

---

## ⚠️ PROBLÈMES COURANTS

### 1. "Identifiants incorrects"

**Solution :**
```bash
php artisan accounts:fix-test
```

### 2. "Compte désactivé"

**Solution :**
```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'votre@email.com')->first();
$user->status = 'active';
$user->save();
```

### 3. Redirection en boucle

**Solution :**
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### 4. Erreur "roleRelation"

**Solution :**
```bash
php artisan accounts:fix-test
```

---

## 📝 CRÉER UN NOUVEAU COMPTE MANUELLEMENT

Si vous voulez créer un compte manuellement :

```bash
php artisan tinker
```

```php
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

// Créer l'utilisateur
$user = User::create([
    'name' => 'Test User',
    'email' => 'test@racine.cm',
    'password' => Hash::make('password'),
    'status' => 'active',
    'email_verified_at' => now(),
]);

// Assigner un rôle
$role = Role::where('slug', 'client')->first();
$user->roleRelation()->associate($role);
$user->save();

echo "Compte créé : test@racine.cm / password";
```

---

## 🎯 RÉSUMÉ

**Action immédiate :**
```bash
php artisan accounts:fix-test
php artisan optimize:clear
```

**Puis tester avec :**
- Email : `client@racine.cm`
- Mot de passe : `password`
- URL : `/login`

Si ça ne fonctionne toujours pas, consultez `GUIDE_DEPANNAGE_ACCES_COMPTES.md` pour un diagnostic plus approfondi.

---

**Dernière mise à jour :** 1 Décembre 2025


