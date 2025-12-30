# 🔐 GUIDE D'ACCÈS AU PANEL ADMIN

## 📍 URLS D'ACCÈS

### Panel Admin Principal
**URL :** `http://localhost:8000/admin/login`  
**Route :** `admin.login`

### Autres Points d'Entrée
1. **Hub d'Authentification :** `http://localhost:8000/auth`
2. **Login ERP :** `http://localhost:8000/erp/login`
3. **Login Public :** `http://localhost:8000/login`

---

## ✅ VÉRIFICATIONS

### 1. Routes Admin
Les routes suivantes sont disponibles :
- `GET /admin/login` - Formulaire de connexion
- `POST /admin/login` - Traitement de la connexion
- `GET /admin/dashboard` - Dashboard (protégé)
- `POST /admin/logout` - Déconnexion

### 2. Middleware
- Middleware `admin` enregistré : `AdminOnly`
- Protection des routes admin activée

### 3. Vue Login
- Fichier : `resources/views/admin/login.blade.php`
- Existe et est accessible

---

## 🚀 ACCÈS RAPIDE

### Option 1 : Accès Direct
```
http://localhost:8000/admin/login
```

### Option 2 : Via Hub
```
http://localhost:8000/auth
```
Puis sélectionner "Espace Admin"

---

## 👤 CRÉER UN COMPTE ADMIN

Si vous n'avez pas de compte admin, créez-en un :

```bash
php artisan tinker
```

Puis dans Tinker :
```php
$user = \App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@racine.com',
    'password' => bcrypt('password'),
    'is_admin' => true,
    'role_id' => 1, // ou le rôle admin
]);
```

---

## 🔧 DÉPANNAGE

### Problème : Page 404
- Vérifier que le serveur Laravel est démarré : `php artisan serve`
- Vérifier l'URL : doit être `/admin/login` (pas `/admin/login/`)

### Problème : Redirection infinie
- Vérifier le middleware `admin`
- Vérifier que l'utilisateur a `is_admin = true` ou `role_id = 1`

### Problème : Erreur 500
- Vérifier les logs : `storage/logs/laravel.log`
- Vérifier que la vue `admin/login.blade.php` existe

---

## 📝 NOTES

- Le login admin nécessite un compte avec `is_admin = true` OU `role_id = 1`
- Le 2FA peut être requis selon la configuration
- Les tentatives de connexion sont limitées (sécurité)

---

*Guide créé le : 28 novembre 2025*

