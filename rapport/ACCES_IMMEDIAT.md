# 🚀 ACCÈS IMMÉDIAT - PANEL ADMIN

## ✅ COMPTE DÉVELOPPEUR CRÉÉ/MIS À JOUR

### 🔑 Identifiants

**Email :** `dev@racine.com`  
**Password :** `dev123`  
**Nom :** `Developer`

### 🌐 URL d'Accès

```
http://localhost:8000/admin/login
```

---

## ⚡ CRÉATION/MISE À JOUR

### Méthode 1 : Script PHP (RAPIDE)

```bash
php update-dev-account.php
```

### Méthode 2 : Commande Artisan

```bash
php artisan dev:account
```

### Méthode 3 : Seeder

```bash
php artisan db:seed --class=DevAccountSeeder
```

### Méthode 4 : Seeder Complet

```bash
php artisan db:seed --class=DatabaseSeeder
```

---

## 🎯 ÉTAPES POUR ACCÉDER

1. **Créer/Mettre à jour le compte :**
   ```bash
   php update-dev-account.php
   ```

2. **Démarrer le serveur :**
   ```bash
   php artisan serve
   ```

3. **Ouvrir dans le navigateur :**
   ```
   http://localhost:8000/admin/login
   ```

4. **Se connecter :**
   - Email : `dev@racine.com`
   - Password : `dev123`

---

## ✅ VÉRIFICATION

Le compte développeur a :
- ✅ `is_admin = true`
- ✅ `role_id = 1` (Super Admin)
- ✅ Accès complet à tous les modules
- ✅ Tous les droits administrateur

---

## 📋 AUTRES COMPTES DISPONIBLES

### Super Admin
- Email : `admin@racine.com`
- Password : `admin123`

### Test Client
- Email : `test@example.com`
- Password : `password`

---

**✅ TOUT EST PRÊT ! Exécutez `php update-dev-account.php` puis accédez à http://localhost:8000/admin/login**

