# 🔑 COMPTES D'ACCÈS - RACINE BACKEND

## 👤 COMPTES DISPONIBLES

### 1. Compte Super Administrateur

**Email :** `admin@racine.com`  
**Password :** `admin123`  
**Accès :** Tous les droits

**URL :** `http://localhost:8000/admin/login`

---

### 2. Compte Développeur (Passe-Partout)

**Email :** `dev@racine.com`  
**Password :** `dev123`  
**Accès :** Tous les droits (Super Admin)

**URL :** `http://localhost:8000/admin/login`

---

### 3. Compte Test (Client)

**Email :** `test@example.com`  
**Password :** `password`  
**Accès :** Client uniquement

**URL :** `http://localhost:8000/login`

---

## 🚀 CRÉER/METTRE À JOUR LES COMPTES

### Option 1 : Seeder Complet

```bash
php artisan db:seed --class=DatabaseSeeder
```

Cela créera/mettra à jour :
- ✅ Super Admin : `admin@racine.com` / `admin123`
- ✅ Développeur : `dev@racine.com` / `dev123`
- ✅ Test Client : `test@example.com` / `password`

### Option 2 : Seeder Développeur Seul

```bash
php artisan db:seed --class=DevAccountSeeder
```

### Option 3 : Commande Artisan

```bash
php artisan dev:account
```

---

## 🔧 MISE À JOUR RAPIDE

Pour mettre à jour le compte développeur :

```bash
php artisan dev:account --email=dev@racine.com --password=dev123
```

---

## ✅ VÉRIFICATION

Pour vérifier que les comptes existent :

```bash
php artisan tinker
```

Puis :
```php
$admin = \App\Models\User::where('email', 'admin@racine.com')->first();
$dev = \App\Models\User::where('email', 'dev@racine.com')->first();

echo "Admin: " . ($admin ? "✅ {$admin->email}" : "❌ Non trouvé") . PHP_EOL;
echo "Dev: " . ($dev ? "✅ {$dev->email}" : "❌ Non trouvé") . PHP_EOL;
```

---

## 📋 ACCÈS PANEL ADMIN

1. **Démarrer le serveur :**
   ```bash
   php artisan serve
   ```

2. **Ouvrir dans le navigateur :**
   ```
   http://localhost:8000/admin/login
   ```

3. **Se connecter avec :**
   - Email : `dev@racine.com`
   - Password : `dev123`

   **OU**

   - Email : `admin@racine.com`
   - Password : `admin123`

---

## 🔐 SÉCURITÉ

⚠️ **IMPORTANT :** Ces comptes sont pour le développement uniquement !

En production :
- Changez tous les mots de passe
- Désactivez ou supprimez les comptes de développement
- Utilisez des mots de passe forts
- Activez la 2FA pour les admins

---

**✅ Comptes prêts à l'emploi !**

*Mis à jour le : 28 novembre 2025*

