# 🔧 GUIDE DE DÉPANNAGE — ACCÈS AUX COMPTES UTILISATEUR

**Date :** 1 Décembre 2025  
**Problème :** Impossible d'accéder aux comptes utilisateur

---

## 🔍 DIAGNOSTIC RAPIDE

### 1. Vérifier les routes d'authentification

```bash
php artisan route:list --name=login
```

**Routes attendues :**
- `/login` → `LoginController@showLoginForm`
- `/login` (POST) → `LoginController@login`
- `/auth` → `AuthHubController@index`

### 2. Vérifier que les utilisateurs existent

```bash
php artisan tinker
```

Dans tinker :
```php
\App\Models\User::count(); // Doit retourner > 0
\App\Models\User::pluck('email'); // Liste des emails
```

### 3. Vérifier les logs d'erreur

```bash
tail -f storage/logs/laravel.log
```

Puis essayez de vous connecter et regardez les erreurs.

---

## 🛠️ SOLUTIONS PAR PROBLÈME

### Problème 1 : "Route non trouvée" ou 404

**Cause :** Routes non enregistrées ou cache de routes

**Solution :**
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list --name=login
```

### Problème 2 : "Identifiants incorrects"

**Causes possibles :**
- Mauvais email/mot de passe
- Utilisateur n'existe pas
- Mot de passe mal hashé

**Solution :**
```bash
# Corriger tous les comptes de test
php artisan accounts:fix-test

# OU créer un compte manuellement
php artisan tinker
```

Dans tinker :
```php
$user = \App\Models\User::firstOrCreate(
    ['email' => 'test@racine.cm'],
    [
        'name' => 'Test User',
        'password' => bcrypt('password'),
        'status' => 'active',
    ]
);

// Assigner un rôle
$role = \App\Models\Role::where('slug', 'client')->first();
$user->roleRelation()->associate($role);
$user->save();
```

### Problème 3 : Redirection en boucle

**Cause :** Middleware qui bloque ou redirection incorrecte

**Solution :**
1. Vérifier `bootstrap/app.php` - les middlewares ne doivent pas bloquer `/login`
2. Vérifier que la route `/login` n'a PAS le middleware `auth`

### Problème 4 : "Compte désactivé"

**Cause :** Statut utilisateur ≠ 'active'

**Solution :**
```bash
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'votre@email.com')->first();
$user->status = 'active';
$user->save();
```

### Problème 5 : Erreur "roleRelation" ou rôle manquant

**Cause :** Utilisateur sans rôle assigné

**Solution :**
```bash
php artisan accounts:fix-test
```

OU manuellement :
```php
$user = \App\Models\User::where('email', 'votre@email.com')->first();
$role = \App\Models\Role::where('slug', 'client')->first();
$user->roleRelation()->associate($role);
$user->save();
```

---

## 📋 CHECKLIST COMPLÈTE

### Vérifications de base

- [ ] Routes d'authentification enregistrées
- [ ] Vues de login existent (`resources/views/auth/login-neutral.blade.php`)
- [ ] Base de données accessible
- [ ] Utilisateurs existent en base
- [ ] Sessions fonctionnent (vérifier `storage/framework/sessions`)

### Vérifications utilisateur

- [ ] Email existe en base
- [ ] Mot de passe correct (hashé avec bcrypt)
- [ ] Statut = 'active'
- [ ] Rôle assigné (relation `roleRelation`)
- [ ] Pas de 2FA activé (ou bypass en local)

### Vérifications configuration

- [ ] `.env` configuré correctement
- [ ] `APP_KEY` généré (`php artisan key:generate`)
- [ ] Cache nettoyé
- [ ] Permissions sur `storage/` et `bootstrap/cache/`

---

## 🚀 SOLUTION RAPIDE (Recommandée)

Si vous voulez une solution rapide, exécutez cette commande qui corrige tout automatiquement :

```bash
php artisan accounts:fix-test
```

Cette commande :
- ✅ Vérifie tous les comptes de test
- ✅ Corrige les mots de passe
- ✅ Assigne les rôles
- ✅ Active les comptes
- ✅ Désactive la 2FA
- ✅ Crée les profils manquants

---

## 📝 COMPTES DE TEST DISPONIBLES

Voir le fichier `COMPTES_TEST_TOUS_ROLES.md` pour la liste complète.

**Comptes principaux :**
- **Client :** `client@racine.cm` / `password`
- **Créateur :** `createur@racine.cm` / `password`
- **Admin :** `admin@racine.cm` / `password`
- **Super Admin :** `superadmin@racine.cm` / `password`

**URLs de connexion :**
- `/login` - Connexion générale
- `/createur/login` - Connexion créateur
- `/admin/login` - Connexion admin (si activée)

---

## 🔍 COMMANDES DE DIAGNOSTIC

### Vérifier l'état du système

```bash
# Nettoyer tous les caches
php artisan optimize:clear

# Vérifier les routes
php artisan route:list | grep login

# Vérifier la configuration
php artisan config:show app

# Vérifier les migrations
php artisan migrate:status
```

### Vérifier les utilisateurs

```bash
php artisan tinker
```

```php
// Compter les utilisateurs
\App\Models\User::count();

// Lister les emails
\App\Models\User::pluck('email');

// Vérifier un utilisateur spécifique
$user = \App\Models\User::where('email', 'client@racine.cm')->first();
$user->status; // Doit être 'active'
$user->roleRelation; // Doit retourner un rôle
$user->getRoleSlug(); // Doit retourner 'client', 'createur', etc.
```

---

## ⚠️ PROBLÈMES COURANTS

### 1. Session non fonctionnelle

**Symptôme :** Connexion réussie mais déconnexion immédiate

**Solution :**
```bash
# Vérifier les permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Vérifier la configuration session dans .env
SESSION_DRIVER=database
```

### 2. Middleware qui bloque

**Symptôme :** Redirection vers login même après connexion

**Solution :**
Vérifier que les routes dashboard n'ont pas de middleware trop restrictif.

### 3. Base de données vide

**Symptôme :** Aucun utilisateur en base

**Solution :**
```bash
# Exécuter les seeders
php artisan db:seed --class=TestUsersSeeder

# OU créer manuellement
php artisan accounts:fix-test
```

---

## 📞 SUPPORT

Si le problème persiste après avoir essayé toutes ces solutions :

1. **Vérifier les logs :**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Activer le mode debug :**
   ```env
   APP_DEBUG=true
   ```

3. **Vérifier les erreurs PHP :**
   - Vérifier `php.ini` pour les erreurs
   - Vérifier les logs du serveur web

---

**Dernière mise à jour :** 1 Décembre 2025


